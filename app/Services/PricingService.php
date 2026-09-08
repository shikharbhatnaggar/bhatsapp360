<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\WhatsappRate;
use Illuminate\Support\Facades\Log;

/**
 * Per-message pricing built from three figures: what Meta charges us, the
 * markup we add, and what the client pays. Keeping them separate means margin
 * is reportable instead of buried in a single number.
 */
class PricingService
{
    /**
     * @return array{meta: float, markup: float, price: float, currency: string}
     */
    public function rate(Tenant $tenant, string $category, string $countryCode = 'IN'): array
    {
        $rate = WhatsappRate::query()
            ->where('category', $category)
            ->where('country_code', $countryCode)
            ->where('is_active', true)
            // A tenant-specific rate wins over the platform default.
            ->where(fn ($q) => $q->where('tenant_id', $tenant->id)->orWhereNull('tenant_id'))
            ->orderByRaw('tenant_id IS NULL')
            ->first();

        if ($rate) {
            return [
                'meta' => (float) $rate->meta_base_price,
                'markup' => (float) $rate->markup,
                'price' => (float) $rate->client_final_price,
                'currency' => $rate->currency,
                'source' => 'rate_card',
            ];
        }

        // No row matched. Falling back silently would mean billing at cost with
        // no margin, so make it loud: this is a misconfiguration, not a default.
        Log::warning('pricing.rate_missing', [
            'tenant_id' => $tenant->id,
            'category' => $category,
            'country_code' => $countryCode,
            'hint' => 'Add a whatsapp_rates row for this category and ISO country code.',
        ]);

        $fallback = (float) (config('whatsapp.fallback_rates')[$category] ?? 0);

        return [
            'meta' => $fallback,
            'markup' => 0.0,
            'price' => $fallback,
            'currency' => $tenant->currency ?: 'INR',
            'source' => 'fallback',
        ];
    }

    /**
     * Quote a send. $recipients is a collection of Customer models.
     *
     * @return array{unit_price: float, currency: string, total: float, count: int,
     *               meta_total: float, markup_total: float, breakdown: array}
     */
    public function quote(Tenant $tenant, string $category, $recipients): array
    {
        $breakdown = [];
        $total = 0.0;
        $metaTotal = 0.0;
        $markupTotal = 0.0;
        $usesFallback = false;
        $currency = $tenant->currency ?: 'INR';

        foreach ($recipients->groupBy('country_code') as $country => $group) {
            $rate = $this->rate($tenant, $category, $country ?: 'IN');
            $count = $group->count();

            $lineTotal = $rate['price'] * $count;
            $total += $lineTotal;
            $metaTotal += $rate['meta'] * $count;
            $markupTotal += $rate['markup'] * $count;
            $currency = $rate['currency'];
            $usesFallback = $usesFallback || ($rate['source'] ?? '') === 'fallback';

            $breakdown[] = [
                'country_code' => $country ?: 'IN',
                'count' => $count,
                'meta_price' => $rate['meta'],
                'markup' => $rate['markup'],
                'unit_price' => $rate['price'],
                'line_total' => round($lineTotal, 4),
                'currency' => $rate['currency'],
                'source' => $rate['source'] ?? 'rate_card',
            ];
        }

        $count = $recipients->count();

        return [
            'unit_price' => $count ? round($total / $count, 4) : 0.0,
            'currency' => $currency,
            'total' => round($total, 4),
            'meta_total' => round($metaTotal, 4),
            'markup_total' => round($markupTotal, 4),
            'count' => $count,
            'uses_fallback' => $usesFallback,
            'breakdown' => $breakdown,
        ];
    }

    public function format(float $amount, string $currency = 'INR'): string
    {
        $symbol = match ($currency) {
            'INR' => '₹', 'USD' => '$', 'EUR' => '€', 'GBP' => '£', default => $currency.' ',
        };

        return $symbol.number_format($amount, 2);
    }
}

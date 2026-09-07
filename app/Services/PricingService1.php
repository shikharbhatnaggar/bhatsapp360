<?php

namespace App\Services;

use App\Models\PricingRate;
use App\Models\Tenant;

/**
 * Per-message pricing. WhatsApp bills per delivered message by category and
 * recipient country, so an estimate is unit price x selected recipients,
 * grouped by the country codes in the selection.
 */
class PricingService
{
    public function rate(Tenant $tenant, string $category, string $countryCode = 'IN'): array
    {
        $rate = PricingRate::query()
            ->where('category', $category)
            ->where('country_code', $countryCode)
            ->orderByRaw('tenant_id IS NULL')          // tenant override wins
            ->where(fn ($q) => $q->where('tenant_id', $tenant->id)->orWhereNull('tenant_id'))
            ->first();

        if ($rate) {
            return ['price' => (float) $rate->price, 'currency' => $rate->currency];
        }

        return [
            'price' => (float) (config('whatsapp.fallback_rates')[$category] ?? 0),
            'currency' => $tenant->currency ?: 'INR',
        ];
    }

    /**
     * Quote a send. $recipients is a collection of Customer models.
     *
     * @return array{unit_price: float, currency: string, total: float, count: int, breakdown: array}
     */
    public function quote(Tenant $tenant, string $category, $recipients): array
    {
        $breakdown = [];
        $total = 0.0;
        $currency = $tenant->currency ?: 'INR';

        foreach ($recipients->groupBy('country_code') as $country => $group) {
            $rate = $this->rate($tenant, $category, $country ?: 'IN');
            $lineTotal = $rate['price'] * $group->count();
            $currency = $rate['currency'];
            $total += $lineTotal;

            $breakdown[] = [
                'country_code' => $country ?: 'IN',
                'count' => $group->count(),
                'unit_price' => $rate['price'],
                'line_total' => round($lineTotal, 4),
                'currency' => $rate['currency'],
            ];
        }

        $count = $recipients->count();

        return [
            'unit_price' => $count ? round($total / $count, 4) : 0.0,
            'currency' => $currency,
            'total' => round($total, 4),
            'count' => $count,
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

<?php

namespace Database\Seeders;

use App\Models\PricingRate;
use Illuminate\Database\Seeder;

/**
 * Platform default rate card (tenant_id = null). Figures are indicative
 * per-message rates; replace them with your own margin-adjusted card.
 */
class PricingSeeder extends Seeder
{
    public function run(): void
    {
        $card = [
            ['IN', 'MARKETING', 0.7846, 'INR'],
            ['IN', 'UTILITY', 0.1146, 'INR'],
            ['IN', 'AUTHENTICATION', 0.1250, 'INR'],
            ['IN', 'SERVICE', 0.0000, 'INR'],
            ['US', 'MARKETING', 0.0250, 'USD'],
            ['US', 'UTILITY', 0.0040, 'USD'],
            ['US', 'AUTHENTICATION', 0.0135, 'USD'],
            ['US', 'SERVICE', 0.0000, 'USD'],
            ['AE', 'MARKETING', 0.0340, 'USD'],
            ['AE', 'UTILITY', 0.0150, 'USD'],
            ['AE', 'AUTHENTICATION', 0.0289, 'USD'],
            ['AE', 'SERVICE', 0.0000, 'USD'],
            ['GB', 'MARKETING', 0.0705, 'USD'],
            ['GB', 'UTILITY', 0.0157, 'USD'],
            ['GB', 'AUTHENTICATION', 0.0398, 'USD'],
            ['GB', 'SERVICE', 0.0000, 'USD'],
        ];

        foreach ($card as [$country, $category, $price, $currency]) {
            PricingRate::updateOrCreate(
                ['tenant_id' => null, 'country_code' => $country, 'category' => $category],
                ['price' => $price, 'currency' => $currency],
            );
        }
    }
}

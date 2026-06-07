<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['key' => 'app_name', 'value' => 'Laravel-POS'],
            ['key' => 'shop_address', 'value' => 'Main Market, Demo City'],
            ['key' => 'shop_phone', 'value' => '+1 555 0100'],
            ['key' => 'tax_number', 'value' => 'TAX-000123'],
            ['key' => 'receipt_footer', 'value' => 'Thank you for shopping with us.'],
            ['key' => 'currency_symbol', 'value' => '$'],
            ['key' => 'warning_quantity', 'value' => '10'],
        ];

        foreach ($data as $value) {
            Setting::updateOrCreate([
                'key' => $value['key']
            ], [
                'value' => $value['value']
            ]);
        }
    }
}

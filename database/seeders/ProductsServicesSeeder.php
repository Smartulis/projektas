<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductsServicesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Fetch measurement unit IDs by code
        $unitIds = DB::table('measurement_units')->pluck('id', 'code')->toArray();

        // Your product/service definitions
        $items = [
            ['name' => 'Basic Web Hosting',            'description' => 'Shared hosting plan for small websites.',             'price_without_vat' => 5.99,   'unit_code' => 'pkg'],
            ['name' => 'Premium Web Hosting',          'description' => 'Dedicated resources with increased bandwidth.',       'price_without_vat' => 19.99,  'unit_code' => 'pkg'],
            ['name' => 'SSL Certificate',              'description' => 'Secure your site with a standard SSL certificate.',    'price_without_vat' => 49.00,  'unit_code' => 'unit'],
            ['name' => 'Domain Registration',          'description' => 'Register a .com or .net domain name.',               'price_without_vat' => 12.50,  'unit_code' => 'unit'],
            ['name' => 'WordPress Installation',       'description' => 'Professional setup and configuration of WordPress.',   'price_without_vat' => 29.99,  'unit_code' => 'unit'],
            ['name' => 'Custom Logo Design',           'description' => 'Unique logo created by our graphic designers.',       'price_without_vat' => 150.00, 'unit_code' => 'set'],
            ['name' => 'SEO Audit',                    'description' => 'Comprehensive analysis of on-page SEO factors.',       'price_without_vat' => 199.00, 'unit_code' => 'unit'],
            ['name' => 'Monthly SEO Package',          'description' => 'Ongoing optimization and reporting.',                 'price_without_vat' => 299.00, 'unit_code' => 'mo'],
            ['name' => 'Social Media Setup',           'description' => 'Create and configure company profiles on major platforms.', 'price_without_vat' => 75.00,   'unit_code' => 'set'],
            ['name' => 'Social Media Management',      'description' => 'Weekly content creation and posting.',                 'price_without_vat' => 250.00, 'unit_code' => 'mo'],
            ['name' => 'Email Marketing Campaign',     'description' => 'Design and send a targeted email newsletter.',         'price_without_vat' => 120.00, 'unit_code' => 'unit'],
            ['name' => 'E-commerce Store Setup',       'description' => 'Complete setup of online store with payment gateway.', 'price_without_vat' => 499.00, 'unit_code' => 'unit'],
            ['name' => 'Consulting Hour',              'description' => 'One hour of business or technical consulting.',        'price_without_vat' => 85.00,  'unit_code' => 'hr'],
            ['name' => 'API Integration Service',      'description' => 'Connect third-party APIs to your system.',             'price_without_vat' => 350.00, 'unit_code' => 'unit'],
            ['name' => 'Data Backup Service',          'description' => 'Daily automated backups stored offsite.',              'price_without_vat' => 19.99,  'unit_code' => 'day'],
            ['name' => 'Website Maintenance (Monthly)', 'description' => 'Updates, security patches, and minor edits.',         'price_without_vat' => 99.00,  'unit_code' => 'mo'],
            ['name' => 'Landing Page Design',          'description' => 'High-converting single-page website design.',          'price_without_vat' => 250.00, 'unit_code' => 'unit'],
            ['name' => 'Content Writing (per article)', 'description' => 'SEO-friendly blog post of up to 800 words.',          'price_without_vat' => 45.00,  'unit_code' => 'unit'],
            ['name' => 'Graphic Design Pack',          'description' => 'Set of marketing materials: flyers, banners, and brochures.', 'price_without_vat' => 200.00, 'unit_code' => 'pack'],
            ['name' => 'IT Support Subscription',      'description' => '24/7 remote support contract.',                        'price_without_vat' => 150.00, 'unit_code' => 'mo'],
        ];

        $timestamp       = now();
        $defaultVatRate  = 21;
        $defaultCurrency = 'EUR';
        $defaultStatus   = 'Active';
        $defaultStock    = 100; // you can change this

        foreach ($items as $item) {
            $priceWithoutVat = $item['price_without_vat'];
            $priceWithVat    = round($priceWithoutVat * (1 + ($defaultVatRate / 100)), 2);

            DB::table('products_services')->insert([
                'name'               => $item['name'],
                'description'        => $item['description'],
                'price_without_vat'  => $priceWithoutVat,
                'vat_rate'           => $defaultVatRate,
                'price_with_vat'     => $priceWithVat,
                'currency'           => $defaultCurrency,
                'unit_id'            => $unitIds[$item['unit_code']] ?? null,
                'stock_quantity'     => $defaultStock,
                'sku'                => Str::upper(Str::slug($item['name'], '-')),
                'status'             => $defaultStatus,
                'image'              => null,
                'created_at'         => $timestamp,
                'updated_at'         => $timestamp,
            ]);
        }
    }
}

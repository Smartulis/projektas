<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MeasurementUnitsTableSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['code' => 'pcs',      'lt_name' => 'vnt.',               'en_name' => 'pieces'],
            ['code' => 'kg',       'lt_name' => 'kilogramas',         'en_name' => 'kilogram'],
            ['code' => 'g',        'lt_name' => 'gramas',             'en_name' => 'gram'],
            ['code' => 'mg',       'lt_name' => 'miligramas',         'en_name' => 'milligram'],
            ['code' => 't',        'lt_name' => 'tona',               'en_name' => 'ton'],
            ['code' => 'l',        'lt_name' => 'litrai',             'en_name' => 'liter'],
            ['code' => 'ml',       'lt_name' => 'mililitras',         'en_name' => 'milliliter'],
            ['code' => 'm',        'lt_name' => 'metras',             'en_name' => 'meter'],
            ['code' => 'cm',       'lt_name' => 'centimetras',        'en_name' => 'centimeter'],
            ['code' => 'mm',       'lt_name' => 'milimetras',         'en_name' => 'millimeter'],
            ['code' => 'm²',       'lt_name' => 'kvadratinis metras', 'en_name' => 'square meter'],
            ['code' => 'm³',       'lt_name' => 'kubinis metras',     'en_name' => 'cubic meter'],
            ['code' => 'hr',       'lt_name' => 'valanda',            'en_name' => 'hour'],
            ['code' => 'min',      'lt_name' => 'minutė',             'en_name' => 'minute'],
            ['code' => 'sec',      'lt_name' => 'sekundė',            'en_name' => 'second'],
            ['code' => 'day',      'lt_name' => 'diena',              'en_name' => 'day'],
            ['code' => 'mo',       'lt_name' => 'mėnuo',              'en_name' => 'month'],
            ['code' => 'set',      'lt_name' => 'komplektas',         'en_name' => 'set'],
            ['code' => 'pkg',      'lt_name' => 'pakuotė',            'en_name' => 'package'],
            ['code' => 'box',      'lt_name' => 'dėžutė',             'en_name' => 'box'],
            ['code' => 'roll',     'lt_name' => 'ritinėlis',          'en_name' => 'roll'],
            ['code' => 'pair',     'lt_name' => 'pora',               'en_name' => 'pair'],
            ['code' => 'time',     'lt_name' => 'kartas',             'en_name' => 'time'],
            ['code' => 'sheet',    'lt_name' => 'lakštas',            'en_name' => 'sheet'],
            ['code' => 'block',    'lt_name' => 'blokas',             'en_name' => 'block'],
            ['code' => 'bag',      'lt_name' => 'maišas',             'en_name' => 'bag'],
            ['code' => 'bottle',   'lt_name' => 'butelis',            'en_name' => 'bottle'],
            ['code' => 'canister', 'lt_name' => 'kanistras',          'en_name' => 'canister'],
            ['code' => 'pack',     'lt_name' => 'pakelis',            'en_name' => 'pack'],
            ['code' => 'unit',     'lt_name' => 'vienetas',           'en_name' => 'unit'],
        ];

        foreach ($units as $unit) {
            DB::table('measurement_units')->updateOrInsert(
                ['code' => $unit['code']],
                array_merge($unit, [
                    'is_default' => true,
                    'user_id'    => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}

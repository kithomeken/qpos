<?php

namespace Database\Seeders;

use App\Models\PaymentMethods;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $seederDumpArray = array(
            [
                'name'              => 'Cash Payment',
                'code'              => 'cash',
                'surcharge_type'    => 'none', // Matches Migration
                'surcharge_value'   => 0,
                'primary_method'    => true,
            ],
            [
                'name'              => 'Credit/Debit Cards',
                'code'              => 'card',
                'surcharge_type'    => 'percentage', // Changed from 'percent' to 'percentage'
                'surcharge_value'   => 2.5,
                'primary_method'    => false,
            ],
            [
                'name'              => 'Bank Transfers',
                'code'              => 'bank',
                'surcharge_type'    => 'fixed', // Matches Migration
                'surcharge_value'   => 25,
                'primary_method'    => false,
            ],
        );

        foreach ($seederDumpArray as $data) {
            PaymentMethods::updateOrCreate(
                ['code' => $data['code']],
                [
                    'name'            => $data['name'],
                    'surcharge_type'  => $data['surcharge_type'],
                    'surcharge_value' => $data['surcharge_value'],
                    'primary_method'  => $data['primary_method'],
                ]
            );
        }
    }
}

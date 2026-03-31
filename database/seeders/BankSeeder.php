<?php

namespace Database\Seeders;

use App\Models\Bank;
use Illuminate\Database\Seeder;

class BankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $banks = [
            // Banks
            ['name' => 'BCA', 'code' => 'bca', 'type' => 'bank'],
            ['name' => 'Mandiri', 'code' => 'mandiri', 'type' => 'bank'],
            ['name' => 'BNI', 'code' => 'bni', 'type' => 'bank'],
            ['name' => 'BRI', 'code' => 'bri', 'type' => 'bank'],
            ['name' => 'CIMB Niaga', 'code' => 'cimb', 'type' => 'bank'],
            ['name' => 'Permata', 'code' => 'permata', 'type' => 'bank'],
            ['name' => 'Danamon', 'code' => 'danamon', 'type' => 'bank'],
            ['name' => 'BSI', 'code' => 'bsi', 'type' => 'bank'],
            
            // E-Wallets
            ['name' => 'OVO', 'code' => 'ovo', 'type' => 'ewallet'],
            ['name' => 'Dana', 'code' => 'dana', 'type' => 'ewallet'],
            ['name' => 'ShopeePay', 'code' => 'shopeepay', 'type' => 'ewallet'],
            ['name' => 'GoPay', 'code' => 'gopay', 'type' => 'ewallet'],
            ['name' => 'LinkAja', 'code' => 'linkaja', 'type' => 'ewallet'],
        ];

        foreach ($banks as $bank) {
            Bank::updateOrCreate(['code' => $bank['code']], $bank);
        }
    }
}

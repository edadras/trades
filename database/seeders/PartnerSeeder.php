<?php

namespace Database\Seeders;

use App\Domain\Business\Models\Partner;
use Illuminate\Database\Seeder;

class PartnerSeeder extends Seeder
{
    public function run(): void
    {
        $partners = [
            ['CHAMBER1', 'chamber', ['fa' => 'اتاق بازرگانی (نمونه)', 'en' => 'Chamber of Commerce (sample)'], 'Referral link in member newsletter'],
            ['FOODASSN', 'association', ['fa' => 'انجمن صنایع غذایی (نمونه)', 'en' => 'Food Industry Association (sample)'], 'Workshops and referral code'],
            ['INCUB01', 'accelerator', ['fa' => 'مرکز رشد (نمونه)', 'en' => 'Business incubator (sample)'], 'Direct introduction by the programme manager'],
        ];
        foreach ($partners as [$code, $type, $name, $method]) {
            Partner::updateOrCreate(['referral_code' => $code], ['type' => $type, 'name' => $name, 'referral_method' => $method, 'is_active' => true]);
        }
    }
}

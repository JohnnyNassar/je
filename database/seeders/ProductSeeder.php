<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name_en' => 'Leather Wallet',
                'name_ar' => 'محفظة جلدية',
                'description_en' => "Slim leather wallet with 6 card slots.\nGenuine leather, hand-stitched.",
                'description_ar' => "محفظة جلدية رفيعة مع 6 فتحات للبطاقات.\nجلد طبيعي، خياطة يدوية.",
                'price' => 24.99,
                'stock' => 15,
            ],
            [
                'name_en' => 'Bluetooth Earbuds',
                'name_ar' => 'سماعات بلوتوث',
                'description_en' => 'Wireless earbuds with charging case. 6h battery, USB-C.',
                'description_ar' => 'سماعات لاسلكية مع علبة شحن. بطارية 6 ساعات، USB-C.',
                'price' => 39.00,
                'stock' => 30,
            ],
            [
                'name_en' => 'Stainless Steel Water Bottle',
                'name_ar' => 'زجاجة ماء ستانلس ستيل',
                'description_en' => '750ml, keeps cold 24h / hot 12h.',
                'description_ar' => '750 مل، تحافظ على البرودة 24 ساعة / السخونة 12 ساعة.',
                'price' => 18.50,
                'stock' => 25,
            ],
            [
                'name_en' => 'Cotton T-Shirt',
                'name_ar' => 'تيشيرت قطن',
                'description_en' => '100% cotton, available sizes M / L / XL.',
                'description_ar' => 'قطن 100%، المقاسات المتوفرة M / L / XL.',
                'price' => 12.00,
                'stock' => 50,
            ],
            [
                'name_en' => 'LED Desk Lamp',
                'name_ar' => 'مصباح مكتب LED',
                'description_en' => 'Adjustable arm, 3 brightness levels, USB powered.',
                'description_ar' => 'ذراع قابل للتعديل، 3 مستويات إضاءة، يعمل بمنفذ USB.',
                'price' => 22.75,
                'stock' => 12,
            ],
            [
                'name_en' => 'Smartphone Stand',
                'name_ar' => 'حامل هاتف',
                'description_en' => 'Foldable aluminium stand, fits all phones up to 7".',
                'description_ar' => 'حامل ألومنيوم قابل للطي، يناسب جميع الهواتف حتى 7 إنش.',
                'price' => 9.99,
                'stock' => 40,
            ],
        ];

        foreach ($products as $p) {
            Product::create($p + ['is_active' => true]);
        }
    }
}

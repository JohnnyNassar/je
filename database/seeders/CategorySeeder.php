<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Standard 2-level taxonomy. Idempotent: matches on slug, so re-running
     * updates names/positions without creating duplicates. Child slugs are
     * parent-prefixed to stay unique (the categories.slug column is unique).
     */
    public function run(): void
    {
        $taxonomy = [
            ['Electronics', 'إلكترونيات', [
                ['Phones & Accessories', 'هواتف وإكسسوارات'],
                ['Computers & Tablets', 'حواسيب وأجهزة لوحية'],
                ['Audio', 'صوتيات'],
                ['Cameras', 'كاميرات'],
                ['Wearables', 'أجهزة قابلة للارتداء'],
                // Enriched from the eBay category list (gap-filling additions).
                ['TVs', 'التلفزيونات'],
                ['Smart Home & Surveillance', 'المنزل الذكي والمراقبة'],
                ['Gaming Consoles', 'أجهزة الألعاب'],
                ['Drones', 'الطائرات المسيّرة'],
            ]],
            ['Home & Kitchen', 'المنزل والمطبخ', [
                ['Kitchenware', 'أدوات المطبخ'],
                ['Home Décor', 'ديكور المنزل'],
                ['Bedding & Bath', 'مفروشات وحمام'],
                ['Storage', 'تخزين وتنظيم'],
                ['Cleaning', 'تنظيف'],
                // Enriched from the eBay category list (gap-filling additions).
                ['Large Appliances', 'الأجهزة الكبيرة'],
                ['Small Kitchen Appliances', 'أجهزة المطبخ الصغيرة'],
                ['Furniture', 'أثاث'],
                ['Food & Beverages', 'أطعمة ومشروبات'],
            ]],
            ["Men's Fashion", 'أزياء رجالية', [
                ['Clothing', 'ملابس'],
                ['Shoes', 'أحذية'],
                ['Accessories', 'إكسسوارات'],
                ['Watches', 'ساعات'],
            ]],
            ["Women's Fashion", 'أزياء نسائية', [
                ['Clothing', 'ملابس'],
                ['Shoes', 'أحذية'],
                ['Bags', 'حقائب'],
                ['Jewelry', 'مجوهرات'],
                ['Accessories', 'إكسسوارات'],
            ]],
            ['Kids & Baby', 'أطفال ومستلزمات', [
                ['Toys', 'ألعاب'],
                ["Kids' Clothing", 'ملابس أطفال'],
                ['Baby Care', 'عناية بالطفل'],
                ['School Supplies', 'مستلزمات مدرسية'],
                // Enriched from the eBay category list (gap-filling additions).
                ['Strollers & Accessories', 'عربات أطفال وملحقاتها'],
            ]],
            ['Beauty & Personal Care', 'الجمال والعناية', [
                ['Skincare', 'العناية بالبشرة'],
                ['Makeup', 'مكياج'],
                ['Hair Care', 'العناية بالشعر'],
                ['Fragrances', 'عطور'],
                // Enriched from the eBay category list (gap-filling additions).
                ['Nail Care', 'العناية بالأظافر'],
                ['Sun Protection & Tanning', 'الحماية من الشمس والتسمير'],
            ]],
            ['Sports & Outdoors', 'رياضة وهواء طلق', [
                ['Fitness', 'لياقة بدنية'],
                ['Camping', 'تخييم'],
                ['Bikes', 'دراجات'],
                ['Sportswear', 'ملابس رياضية'],
                // Enriched from the eBay category list (gap-filling additions).
                ['Fishing', 'صيد الأسماك'],
            ]],
            ['Health & Wellness', 'الصحة والعافية', [
                ['Supplements', 'مكملات غذائية'],
                ['First Aid', 'إسعافات أولية'],
                ['Personal Care', 'عناية شخصية'],
                // Enriched from the eBay category list (gap-filling addition).
                ['Oral Care', 'العناية بالفم والأسنان'],
            ]],
            ['Automotive', 'سيارات', [
                ['Car Accessories', 'إكسسوارات سيارات'],
                ['Car Care', 'العناية بالسيارة'],
                ['Tools', 'أدوات'],
                // Enriched from the eBay category list (gap-filling addition).
                ['Oils, Fluids & Lubricants', 'الزيوت والسوائل ومواد التشحيم'],
            ]],
            ['Garden & Tools', 'الحديقة والأدوات', [
                ['Garden', 'حديقة'],
                ['Hand Tools', 'أدوات يدوية'],
                ['Power Tools', 'أدوات كهربائية'],
                // Enriched from the eBay category list (gap-filling additions).
                ['Plants, Seeds & Bulbs', 'النباتات والبذور والأبصال'],
                ['Watering Equipment', 'معدات الري'],
                ['Garden Furniture', 'أثاث الحديقة'],
            ]],
        ];

        $position = 1;
        foreach ($taxonomy as [$nameEn, $nameAr, $children]) {
            $parent = Category::updateOrCreate(
                ['slug' => Str::slug($nameEn)],
                ['name_en' => $nameEn, 'name_ar' => $nameAr, 'parent_id' => null, 'position' => $position++, 'is_active' => true],
            );

            $childPosition = 1;
            foreach ($children as [$childEn, $childAr]) {
                Category::updateOrCreate(
                    ['slug' => Str::slug($nameEn . ' ' . $childEn)],
                    ['name_en' => $childEn, 'name_ar' => $childAr, 'parent_id' => $parent->id, 'position' => $childPosition++, 'is_active' => true],
                );
            }
        }

        // Fold the legacy standalone "Men" category into "Men's Fashion".
        $mensFashion = Category::whereNull('parent_id')->where('slug', Str::slug("Men's Fashion"))->first();
        $legacyMen = Category::whereNull('parent_id')
            ->where(fn ($q) => $q->where('slug', 'Men')->orWhere('name_en', 'Men'))
            ->where('id', '!=', $mensFashion?->id)
            ->first();

        if ($mensFashion && $legacyMen) {
            Product::where('category_id', $legacyMen->id)->update(['category_id' => $mensFashion->id]);
            $legacyMen->delete();
        }
    }
}

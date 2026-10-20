<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/** Business details carried over from the WordPress site. */
class SiteSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'company' => [
                'name' => 'Wonder Himalaya',
                'legal_name' => 'Wonder Himalaya',
                'tagline' => 'Wander Always, Wonder Often',
                'phone' => '+977-01-5928066',
                'whatsapp' => '+9779860880827',
                'email' => 'info@wonderhimalaya.com',
                'address' => 'Thamel, Kathmandu, Nepal',
                'hours' => 'Sun–Fri, 9am–6pm (NPT)',
            ],
            'social' => [
                'facebook' => 'https://www.facebook.com/wonderhimalayanepal',
                'instagram' => 'https://www.instagram.com/wonderhimalayanepal/',
                'tripadvisor' => 'https://www.tripadvisor.com/Attraction_Review-g293890-d28897636-Reviews-Wonder_Himalaya-Kathmandu_Kathmandu_Valley_Bagmati_Zone_Central_Region.html',
                'tiktok' => 'https://www.tiktok.com/@wonder_himalaya',
                'twitter' => 'https://twitter.com/WonderH90546',
            ],
            'stats' => [
                ['value' => '2,500+', 'label' => 'Happy travellers'],
                ['value' => '2,075+', 'label' => 'Treks completed'],
                ['value' => '15+', 'label' => 'Peak climbs & expeditions'],
                ['value' => '410+', 'label' => 'Bhutan & Tibet tours'],
            ],
            'reviews' => ['google_rating' => null, 'google_label' => 'Excellent', 'google_count' => 78, 'google_url' => null, 'tripadvisor_award' => "Travellers' Choice"],
            'booking' => ['deposit_percent' => 20, 'balance_due_days' => 30, 'currency' => 'USD'],
            'notifications' => ['sales_email' => 'info@wonderhimalaya.com'],
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}

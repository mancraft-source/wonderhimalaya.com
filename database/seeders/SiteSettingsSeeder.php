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
                'legal_name' => 'Wonder Himalaya Treks & Expedition Pvt. Ltd.',
                'tagline' => 'Wander Always, Wonder Often',
                'phone' => '+977-01-5928066',
                'whatsapp' => '+9779851000000',
                'email' => 'info@wonderhimalaya.com',
                'address' => 'Thamel, Kathmandu, Nepal',
                'hours' => 'Sun–Fri, 9am–6pm (NPT)',
            ],
            'social' => [
                'facebook' => 'https://www.facebook.com/wonderhimalayanepal',
                'instagram' => 'https://www.instagram.com/wonderhimalaya/',
                'tripadvisor' => 'https://www.tripadvisor.com/',
                'tiktok' => 'https://www.tiktok.com/@wonderhimalaya',
                'twitter' => 'https://twitter.com/WonderH90546',
            ],
            'stats' => [
                ['value' => '2,500+', 'label' => 'Happy travellers'],
                ['value' => '1,200+', 'label' => 'Treks completed'],
                ['value' => '150+', 'label' => 'Peaks & expeditions'],
                ['value' => '4.9', 'label' => 'Google rating'],
            ],
            'reviews' => ['google_rating' => 4.9, 'google_count' => 78, 'google_url' => 'https://g.page/r/wonderhimalaya'],
            'booking' => ['deposit_percent' => 20, 'balance_due_days' => 30, 'currency' => 'USD'],
            'notifications' => ['sales_email' => 'info@wonderhimalaya.com'],
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}

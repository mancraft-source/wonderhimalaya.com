<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([RolesAndPermissionsSeeder::class, SiteSettingsSeeder::class]);

        if (! User::where('email', 'admin@wonderhimalaya.com')->exists()) {
            $password = env('ADMIN_PASSWORD') ?: Str::password(16, symbols: false);

            User::create([
                'name' => 'Site Admin',
                'email' => 'admin@wonderhimalaya.com',
                'password' => $password,
                'email_verified_at' => now(),
            ])->assignRole('admin');

            $this->command?->warn("Admin user: admin@wonderhimalaya.com / {$password}  (change after first login)");
        }
    }
}

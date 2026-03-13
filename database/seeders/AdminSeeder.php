<?php

namespace Database\Seeders;

use App\Models\HoSoTho;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->first();

        if (!$admin) {
            $admin = User::create([
                'name' => 'Quan Tri Vien (Admin)',
                'email' => 'admin@gmail.com',
                'password' => Hash::make('123456'),
                'phone' => '0999999999',
                'role' => 'admin',
                'is_active' => true,
            ]);
        }

        HoSoTho::firstOrCreate(
            ['user_id' => $admin->id],
            [
                'cccd' => 'ADMIN_PROFILE_' . $admin->id,
                'trang_thai_duyet' => 'da_duyet',
                'dang_hoat_dong' => true,
                'trang_thai_hoat_dong' => 'dang_hoat_dong',
            ]
        );
    }
}

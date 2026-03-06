<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Kiểm tra xem đã có admin chưa
        if (!User::where('email', 'admin@gmail.com')->exists()) {
            User::create([
                'name' => 'Quản Trị Viên (Admin)',
                'email' => 'admin@gmail.com',
                'password' => Hash::make('123456'), // Mật khẩu mặc định
                'phone' => '0999999999',
                'role' => 'admin',
                'is_active' => true,
            ]);
        }
    }
}

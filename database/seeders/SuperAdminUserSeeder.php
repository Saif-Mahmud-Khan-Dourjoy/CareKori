<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $role = Role::where('name', 'super admin')->first();

        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'phone' => '01712345678',
            'password' => Hash::make('superadmin@123'),
            'unique_user_id' => "123456789",
            'role_id' => $role->id,
        ]);
    }
}

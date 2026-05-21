<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Group;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        $admin = User::firstOrCreate(['email' => 'admin@spliqo.local'], [
            'name'     => 'Admin',
            'password' => Hash::make('password'),
            'is_admin' => true,
            'is_active' => true,
        ]);

        // Demo user
        $demo = User::firstOrCreate(['email' => 'demo@spliqo.local'], [
            'name'     => 'Demo User',
            'password' => Hash::make('password'),
            'is_admin' => false,
            'is_active' => true,
        ]);

        // Sample group
        Group::firstOrCreate(['name' => 'Goa Trip 2025'], [
            'description' => 'Sample group for demonstration',
            'currency'    => 'INR',
            'created_by'  => (string) $admin->_id,
            'members'     => [
                [
                    'user_id'   => (string) $admin->_id,
                    'role'      => 'admin',
                    'name'      => $admin->name,
                    'email'     => $admin->email,
                    'joined_at' => now()->toDateTimeString(),
                ],
                [
                    'user_id'   => (string) $demo->_id,
                    'role'      => 'member',
                    'name'      => $demo->name,
                    'email'     => $demo->email,
                    'joined_at' => now()->toDateTimeString(),
                ],
            ],
        ]);

        $this->command->info('Seeded: admin@spliqo.local / password');
        $this->command->info('Seeded: demo@spliqo.local  / password');
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        // Admin 1 — existing ittipolint@gmail.com. Keep current password.
        User::updateOrCreate(
            ['email' => 'ittipolint@gmail.com'],
            [
                'name' => 'DailyNews Admin',
                'username' => 'ittipolint',
                'role' => 'admin',
                'permissions' => User::MENUS,
            ]
        );

        // Admin 2 — admin / 10203040, all menus.
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator',
                'email' => 'admin@dailynews.local',
                'username' => 'admin',
                'password' => Hash::make('10203040'),
                'role' => 'admin',
                'permissions' => User::MENUS,
            ]
        );

        // user1 — user / 10203040, only Dashboard, ค้นหาข่าว, Chat AI.
        User::updateOrCreate(
            ['username' => 'user1'],
            [
                'name' => 'User 1',
                'email' => 'user1@dailynews.local',
                'username' => 'user1',
                'password' => Hash::make('10203040'),
                'role' => 'user',
                'permissions' => ['dashboard', 'news', 'chat'],
            ]
        );
    }
}

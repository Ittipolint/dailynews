<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'ittipolint@gmail.com');
        $password = env('ADMIN_PASSWORD');

        if (! $password) {
            $password = 'ChangeMe123!';
            $this->command?->warn("ADMIN_PASSWORD not set - using default 'ChangeMe123!'. Change it after first login.");
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NAME', 'DailyNews Admin'),
                'password' => Hash::make($password),
                'role' => 'admin',
            ]
        );
    }
}

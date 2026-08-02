<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class SetupController extends Controller
{
    public function run(Request $request): \Illuminate\Http\JsonResponse
    {
        $token = env('SETUP_TOKEN');

        if (! $token || ! hash_equals((string) $token, (string) $request->input('token', $request->header('X-Setup-Token')))) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $steps = [];

        $migrate = Artisan::call('migrate', ['--force' => true]);
        $steps['migrate'] = ['exit' => $migrate, 'output' => $this->lastOutput()];

        $seed = Artisan::call('db:seed', ['--force' => true]);
        $steps['seed'] = ['exit' => $seed, 'output' => $this->lastOutput()];

        return response()->json(['success' => true, 'steps' => $steps]);
    }

    public function status(): \Illuminate\Http\JsonResponse
    {
        try {
            $count = \App\Models\News::count();
            $users = \App\Models\User::count();

            return response()->json([
                'database' => 'ok',
                'news_count' => $count,
                'users_count' => $users,
                'app_env' => env('APP_ENV'),
                'app_url' => env('APP_URL'),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['database' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    protected function lastOutput(): string
    {
        try {
            return trim((string) Artisan::output());
        } catch (\Throwable) {
            return '';
        }
    }
}

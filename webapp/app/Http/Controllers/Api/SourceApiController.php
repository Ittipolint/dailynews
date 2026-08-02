<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewsSource;
use Illuminate\Http\JsonResponse;

class SourceApiController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => NewsSource::where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}

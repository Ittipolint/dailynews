<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Rag\GraphRagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function index(): View
    {
        return view('chat.index');
    }

    public function ask(Request $request, GraphRagService $rag): JsonResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:2000'],
            'locale' => ['sometimes', 'in:th,en,zh'],
        ]);

        $locale = $data['locale'] ?? 'th';
        $result = $rag->ask($data['question'], $locale);

        return response()->json($result);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeliveryLog;
use App\Models\MemberSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryApiController extends Controller
{
    public function dueSchedules(): JsonResponse
    {
        $schedules = MemberSchedule::with(['member.channels'])
            ->where('is_active', true)
            ->whereHas('member', fn ($q) => $q->where('is_active', true))
            ->get();

        return response()->json(['data' => $schedules]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = DeliveryLog::with(['member', 'schedule']);

        if ($request->filled('from')) {
            $query->where('sent_at', '>=', $request->get('from'));
        }
        if ($request->filled('channel_type')) {
            $query->where('channel_type', $request->get('channel_type'));
        }

        return response()->json([
            'data' => $query->orderByDesc('sent_at')->limit((int) ($request->get('limit', 50)))->get(),
        ]);
    }

    public function record(Request $request): JsonResponse
    {
        $data = $request->validate([
            'member_id' => ['required', 'exists:members,id'],
            'channel_type' => ['required', 'in:line_personal,line_oa,email'],
            'news_ids' => ['nullable', 'array'],
            'status' => ['required', 'in:success,failed,partial'],
            'error_message' => ['nullable', 'string'],
            'sent_at' => ['nullable', 'date'],
        ]);

        $log = DeliveryLog::create([
            ...$data,
            'sent_at' => $data['sent_at'] ?? now(),
        ]);

        return response()->json(['data' => $log], 201);
    }
}

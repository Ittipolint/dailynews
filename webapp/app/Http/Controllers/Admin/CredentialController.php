<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Credential;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CredentialController extends Controller
{
    public function index(): View
    {
        $credentials = Credential::orderBy('code')->get();

        return view('admin.credentials.index', compact('credentials'));
    }

    public function update(Request $request, Credential $credential): RedirectResponse
    {
        $data = $request->validate([
            'config' => ['nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $existing = $credential->config ?? [];
        $incoming = $data['config'] ?? [];

        // Only overwrite provided fields; keep others (masked values come back empty)
        foreach ($incoming as $key => $value) {
            if ($value === '' || $value === null) {
                unset($incoming[$key]);
            }
        }

        $merged = array_merge($existing, $incoming);
        $credential->update([
            'config' => $merged,
            'is_active' => $request->boolean('is_active', $credential->is_active),
            'updated_by' => auth()->user()?->email,
        ]);

        AuditLog::record('credential', 'update', (string) $credential->id);

        return redirect()->route('admin.credentials.index')->with('success', 'แก้ไข Credential เรียบร้อย');
    }
}

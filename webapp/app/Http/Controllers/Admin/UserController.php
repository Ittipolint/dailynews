<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::orderBy('id')->paginate(15);

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('admin.users.form', [
            'user' => new User(),
            'menus' => User::MENUS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, null);

        User::create([
            ...$data,
            'password' => Hash::make($data['password']),
            'permissions' => $this->permissions($request),
        ]);

        AuditLog::record('user', 'create', (string) $data['email']);

        return redirect()->route('admin.users.index')->with('success', 'เพิ่มผู้ใช้เรียบร้อย');
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', [
            'user' => $user,
            'menus' => User::MENUS,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user->id);

        if ($this->isLastAdmin($user) && ($data['role'] ?? $user->role) !== 'admin') {
            return back()->with('error', 'ไม่สามารถลดสิทธิ์ผู้ดูแลระบบคนสุดท้ายได้');
        }

        $update = [
            'name' => $data['name'],
            'email' => $data['email'],
            'username' => $data['username'] ?? null,
            'role' => $data['role'] ?? 'user',
            'permissions' => $this->permissions($request),
        ];

        if (! empty($data['password'])) {
            $update['password'] = Hash::make($data['password']);
        }

        $user->update($update);

        AuditLog::record('user', 'update', (string) $user->id);

        return redirect()->route('admin.users.index')->with('success', 'แก้ไขผู้ใช้เรียบร้อย');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'ไม่สามารถลบบัญชีของตัวเองได้');
        }

        if ($this->isLastAdmin($user)) {
            return back()->with('error', 'ไม่สามารถลบผู้ดูแลระบบคนสุดท้ายได้');
        }

        AuditLog::record('user', 'delete', (string) $user->id);
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'ลบผู้ใช้เรียบร้อย');
    }

    protected function permissions(Request $request): array
    {
        $role = $request->input('role', 'user');
        $selected = $request->input('permissions', []);

        if ($role === 'admin') {
            return User::MENUS;
        }

        $valid = array_values(array_intersect((array) $selected, User::MENUS));

        return $valid;
    }

    protected function validated(Request $request, ?int $ignoreId): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:100', Rule::unique('users', 'username')->ignore($ignoreId)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($ignoreId)],
            'password' => [$ignoreId === null ? 'required' : 'nullable', 'string', 'min:6'],
            'role' => ['required', 'in:admin,staff,user'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['in:'.implode(',', User::MENUS)],
        ]);
    }

    protected function isLastAdmin(User $user): bool
    {
        return $user->isAdmin() && User::where('role', 'admin')->count() <= 1;
    }
}

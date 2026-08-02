<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::orderBy('name')->paginate(15);

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.categories.form', ['category' => new Category()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:100', 'unique:categories,code'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $category = Category::create([...$data, 'is_active' => $request->boolean('is_active', true)]);
        AuditLog::record('category', 'create', (string) $category->id);

        return redirect()->route('admin.categories.index')->with('success', 'เพิ่มหมวดหมู่เรียบร้อย');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.form', compact('category'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:100', 'unique:categories,code,'.$category->id],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $category->update([...$data, 'is_active' => $request->boolean('is_active', $category->is_active)]);
        AuditLog::record('category', 'update', (string) $category->id);

        return redirect()->route('admin.categories.index')->with('success', 'แก้ไขหมวดหมู่เรียบร้อย');
    }

    public function destroy(Category $category): RedirectResponse
    {
        AuditLog::record('category', 'delete', (string) $category->id);
        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'ลบหมวดหมู่เรียบร้อย');
    }
}

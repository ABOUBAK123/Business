<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'parent_id' => 'nullable|exists:categories,id',
            'icon'      => 'nullable|string|max:60',
            'sort_order'=> 'nullable|integer|min:0',
        ]);

        $data['slug']      = Str::slug($data['name']) . '-' . Str::random(4);
        $data['is_active'] = true;

        Category::create($data);

        return redirect()->route('profile.edit', ['tab' => 'categories'])
            ->with('success', 'Catégorie ajoutée.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'parent_id' => 'nullable|exists:categories,id',
            'icon'      => 'nullable|string|max:60',
            'sort_order'=> 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $category->update($data);

        return redirect()->route('profile.edit', ['tab' => 'categories'])
            ->with('success', 'Catégorie modifiée.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        // Reassign articles to no category before deleting
        $category->articles()->update(['category_id' => null]);
        $category->delete();

        return redirect()->route('profile.edit', ['tab' => 'categories'])
            ->with('success', 'Catégorie supprimée.');
    }
}

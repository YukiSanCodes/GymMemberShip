<?php

namespace App\Http\Controllers;

use App\Models\MembershipCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('categories.index', ['categories' => MembershipCategory::withCount('members')->orderBy('name')->paginate(12)]);
    }

    public function create(): View
    {
        return view('categories.create', ['category' => new MembershipCategory]);
    }

    public function store(Request $request): RedirectResponse
    {
        MembershipCategory::create($this->validated($request));

        return redirect()->route('categories.index')->with('success', 'Member category created.');
    }

    public function edit(MembershipCategory $category): View
    {
        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, MembershipCategory $category): RedirectResponse
    {
        $category->update($this->validated($request, $category));

        return redirect()->route('categories.index')->with('success', 'Member category updated.');
    }

    public function destroy(MembershipCategory $category): RedirectResponse
    {
        if ($category->members()->exists()) {
            return back()->with('error', 'This category is assigned to members. Deactivate it instead.');
        } $category->delete();

        return redirect()->route('categories.index')->with('success', 'Category removed.');
    }

    private function validated(Request $request, ?MembershipCategory $category = null): array
    {
        return $request->validate(['name' => ['required', 'string', 'max:80', Rule::unique('membership_categories')->ignore($category?->id)], 'description' => 'nullable|string|max:1000', 'is_active' => 'sometimes|boolean']) + ['is_active' => $request->boolean('is_active')];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\MembershipLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LevelController extends Controller
{
    public function index(): View
    {
        return view('levels.index', ['levels' => MembershipLevel::withCount('members')->orderBy('fee')->paginate(12)]);
    }

    public function create(): View
    {
        return view('levels.create', ['level' => new MembershipLevel]);
    }

    public function store(Request $request): RedirectResponse
    {
        MembershipLevel::create($this->validated($request));

        return redirect()->route('levels.index')->with('success', 'Membership plan created.');
    }

    public function edit(MembershipLevel $level): View
    {
        return view('levels.edit', compact('level'));
    }

    public function update(Request $request, MembershipLevel $level): RedirectResponse
    {
        $level->update($this->validated($request, $level));

        return redirect()->route('levels.index')->with('success', 'Membership plan updated.');
    }

    public function destroy(MembershipLevel $level): RedirectResponse
    {
        if ($level->members()->exists()) {
            return back()->with('error', 'This plan is assigned to members. Deactivate it instead.');
        } $level->delete();

        return redirect()->route('levels.index')->with('success', 'Membership plan removed.');
    }

    private function validated(Request $request, ?MembershipLevel $level = null): array
    {
        return $request->validate(['name' => ['required', 'string', 'max:80', Rule::unique('membership_levels')->ignore($level?->id)], 'fee' => 'required|numeric|min:0|max:99999999.99', 'validity_months' => 'required|integer|min:1|max:60', 'benefits' => 'nullable|string|max:1500', 'is_active' => 'sometimes|boolean']) + ['is_active' => $request->boolean('is_active')];
    }
}

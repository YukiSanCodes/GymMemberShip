<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function index(Request $request): View
    {
        $members = Member::with(['category', 'level'])->when($request->filled('search'), fn ($q) => $q->where(fn ($s) => $s->where('name', 'like', '%'.$request->search.'%')->orWhere('email', 'like', '%'.$request->search.'%')->orWhere('membership_number', 'like', '%'.$request->search.'%')))->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->category_id))->when($request->filled('level_id'), fn ($q) => $q->where('level_id', $request->level_id))->latest()->paginate(12)->withQueryString();

        return view('members.index', ['members' => $members, 'categories' => MembershipCategory::orderBy('name')->get(), 'levels' => MembershipLevel::orderBy('fee')->get()]);
    }

    public function create(): View
    {
        return view('members.create', ['member' => new Member, 'categories' => MembershipCategory::where('is_active', true)->orderBy('name')->get(), 'levels' => MembershipLevel::where('is_active', true)->orderBy('fee')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['membership_number'] = 'GYM-'.strtoupper(Str::random(8));
        $data['status'] = 'pending';
        $member = Member::create($data);

        return redirect()->route('transactions.create', ['member_id' => $member->id])->with('success', 'Member profile created. Create the first membership application to activate a plan.');
    }

    public function show(Member $member): View
    {
        return view('members.show', ['member' => $member->load(['category', 'level', 'transactions.payments'])]);
    }

    public function edit(Member $member): View
    {
        return view('members.edit', ['member' => $member, 'categories' => MembershipCategory::orderBy('name')->get(), 'levels' => MembershipLevel::orderBy('fee')->get()]);
    }

    public function update(Request $request, Member $member): RedirectResponse
    {
        $member->update($this->validatedData($request, $member));

        return redirect()->route('members.show', $member)->with('success', 'Member profile updated.');
    }

    public function destroy(Member $member): RedirectResponse
    {
        if ($member->transactions()->exists()) {
            return back()->with('error', 'This member has transaction history. Deactivate the membership to preserve the audit record.');
        } $member->delete();

        return redirect()->route('members.index')->with('success', 'Member record deleted.');
    }

    public function deactivate(Member $member): RedirectResponse
    {
        $member->update(['status' => 'inactive']);

        return back()->with('success', 'Membership deactivated.');
    }

    public function history(Member $member): View
    {
        return view('members.history', ['member' => $member, 'transactions' => $member->transactions()->with(['level', 'payments'])->latest()->paginate(12)]);
    }

    private function validatedData(Request $request, ?Member $member = null): array
    {
        return $request->validate(['name' => 'required|string|max:255', 'email' => ['required', 'email', 'max:255', Rule::unique('members')->ignore($member?->id)], 'phone' => 'nullable|string|max:30', 'date_of_birth' => 'nullable|date|before:today', 'emergency_contact' => 'nullable|string|max:255', 'category_id' => 'required|exists:membership_categories,id', 'level_id' => 'required|exists:membership_levels,id']);
    }
}

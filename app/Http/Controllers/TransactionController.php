<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MembershipLevel;
use App\Models\MembershipTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $transactions = MembershipTransaction::with(['member', 'level', 'payments'])->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))->when($request->filled('search'), fn ($q) => $q->where(fn ($s) => $s->where('reference_number', 'like', '%'.$request->search.'%')->orWhereHas('member', fn ($m) => $m->where('name', 'like', '%'.$request->search.'%'))))->latest()->paginate(15)->withQueryString();

        return view('transactions.index', compact('transactions'));
    }

    public function create(Request $request): View
    {
        return view('transactions.create', ['members' => Member::orderBy('name')->get(), 'levels' => MembershipLevel::where('is_active', true)->orderBy('fee')->get(), 'selectedMember' => $request->integer('member_id')]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['member_id' => 'required|exists:members,id', 'membership_level_id' => 'required|exists:membership_levels,id', 'type' => 'required|in:application,renewal', 'notes' => 'nullable|string|max:1000']);
        $member = Member::findOrFail($data['member_id']);
        if ($data['type'] === 'application' && $member->transactions()->where('type', 'application')->whereIn('status', ['Pending', 'Paid', 'Approved'])->exists()) {
            return back()->withErrors(['type' => 'This member already has an application. Use renewal for an existing member.'])->withInput();
        } if ($data['type'] === 'renewal' && ! $member->transactions()->where('status', 'Approved')->exists()) {
            return back()->withErrors(['type' => 'Renewal requires a previously approved membership.'])->withInput();
        } $level = MembershipLevel::findOrFail($data['membership_level_id']);
        $startsAt = $member->expires_at?->isFuture() ? $member->expires_at->copy()->addDay() : today();
        $reference = 'GYM-'.now()->format('ymd').'-'.Str::upper(Str::random(7));
        $transaction = MembershipTransaction::create([...$data, 'reference_number' => $reference, 'processed_by' => $request->user()->id, 'status' => (float) $level->fee === 0.0 ? 'Paid' : 'Pending', 'amount' => $level->fee, 'starts_at' => $startsAt, 'expires_at' => Carbon::parse($startsAt)->addMonths($level->validity_months)]);

        return redirect()->route('transactions.show', $transaction)->with('success', 'Membership transaction created. Record payment to continue.');
    }

    public function show(MembershipTransaction $transaction): View
    {
        return view('transactions.show', ['transaction' => $transaction->load(['member.category', 'level', 'payments.recorder', 'processor'])]);
    }

    public function approve(MembershipTransaction $transaction): RedirectResponse
    {
        if ($transaction->status !== 'Paid' || (float) $transaction->payments()->sum('amount') < (float) $transaction->amount) {
            return back()->with('error', 'Only fully paid transactions may be approved.');
        } DB::transaction(function () use ($transaction): void {
            $transaction->update(['status' => 'Approved']);
            $transaction->member->update(['level_id' => $transaction->membership_level_id, 'status' => 'active', 'joined_at' => $transaction->member->joined_at ?? $transaction->starts_at, 'expires_at' => $transaction->expires_at]);
        });

        return back()->with('success', 'Payment verified and gym membership activated.');
    }

    public function cancel(MembershipTransaction $transaction): RedirectResponse
    {
        if (in_array($transaction->status, ['Approved', 'Expired', 'Cancelled'], true)) {
            return back()->with('error', 'This transaction can no longer be cancelled.');
        } $transaction->update(['status' => 'Cancelled']);

        return back()->with('success','Transaction cancelled.');
    }
}

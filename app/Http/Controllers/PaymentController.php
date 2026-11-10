<?php

namespace App\Http\Controllers;

use App\Models\MembershipTransaction;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function create(MembershipTransaction $transaction): View
    {
        abort_if(in_array($transaction->status, ['Approved', 'Expired', 'Cancelled'], true), 404);

        return view('payments.create', ['transaction' => $transaction->load('member')]);
    }

    public function store(Request $request, MembershipTransaction $transaction): RedirectResponse
    {
        if (in_array($transaction->status, ['Approved', 'Expired', 'Cancelled'], true)) {
            return back()->with('error', 'This transaction cannot receive payments.');
        } $data = $request->validate(['amount' => 'required|numeric|gt:0|max:99999999.99', 'method' => 'required|in:Cash,Bank Transfer,Card,E-wallet', 'paid_at' => 'required|date|before_or_equal:now', 'notes' => 'nullable|string|max:1000']);
        DB::transaction(function () use ($data, $transaction, $request): void {
            $locked = MembershipTransaction::query()->lockForUpdate()->findOrFail($transaction->id);
            $paid = (float) $locked->payments()->sum('amount');
            if ((float) $data['amount'] > max((float) $locked->amount - $paid, 0)) {
                throw ValidationException::withMessages(['amount' => 'Payment cannot exceed the outstanding balance.']);
            } Payment::create([...$data, 'membership_transaction_id' => $locked->id, 'payment_reference' => 'PAY-'.now()->format('ymd').'-'.Str::upper(Str::random(8)), 'recorded_by' => $request->user()->id, 'status' => 'recorded']);
            if ($paid + (float) $data['amount'] >= (float) $locked->amount) {
                $locked->update(['status' => 'Paid']);
            }
        });

        return redirect()->route('transactions.show', $transaction)->with('success', 'Payment recorded successfully.');
    }

    public function receipt(Payment $payment): View
    {
        return view('payments.receipt', ['payment' => $payment->load('transaction.member', 'transaction.level', 'recorder')]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MembershipTransaction;
use App\Models\Payment;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('dashboard', ['memberCount' => Member::count(), 'activeMemberCount' => Member::where('status', 'active')->whereDate('expires_at', '>=', today())->count(), 'pendingTransactionCount' => MembershipTransaction::where('status', 'Pending')->count(), 'collectedPayments' => Payment::where('status', 'recorded')->sum('amount'), 'recentTransactions' => MembershipTransaction::with('member')->latest()->take(8)->get(), 'expiringMembers' => Member::with('level')->where('status', 'active')->whereBetween('expires_at', [today(), today()->addDays(14)])->orderBy('expires_at')->take(5)->get()]);
    }
}

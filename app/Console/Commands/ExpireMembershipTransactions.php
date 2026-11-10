<?php

namespace App\Console\Commands;

use App\Models\Member;
use App\Models\MembershipTransaction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('gym:expire-memberships')]
#[Description('Mark expired gym memberships as expired.')]
class ExpireMembershipTransactions extends Command
{
    public function handle(): int
    {
        DB::transaction(function (): void {
            MembershipTransaction::query()->where('status', 'Approved')->whereDate('expires_at', '<', today())->update(['status' => 'Expired']);
            Member::query()->where('status', 'active')->whereDate('expires_at', '<', today())->update(['status' => 'expired']);
        });

        $this->info('Expired memberships have been updated.');

        return self::SUCCESS;
    }
}

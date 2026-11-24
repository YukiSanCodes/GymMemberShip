<?php

namespace App\Notifications;

use App\Models\MembershipTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipApplicationSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public MembershipTransaction $transaction)
    {
        $this->onConnection('database');
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('IronHouse membership application received')
            ->greeting('Hello '.$this->transaction->member->name.',')
            ->line('We received your gym membership application.')
            ->line('Reference: '.$this->transaction->reference_number)
            ->line('Plan: '.$this->transaction->level->name)
            ->line('Payment and staff approval are required to activate your membership.')
            ->salutation('IronHouse Gym');
    }
}

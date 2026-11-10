<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipTransaction extends Model
{
    protected $fillable = ['reference_number', 'member_id', 'membership_level_id', 'processed_by', 'type', 'status', 'amount', 'starts_at', 'expires_at', 'notes'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'starts_at' => 'date', 'expires_at' => 'date'];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(MembershipLevel::class, 'membership_level_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    protected $fillable = ['membership_number', 'name', 'email', 'phone', 'date_of_birth', 'emergency_contact', 'category_id', 'level_id', 'status', 'joined_at', 'expires_at'];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date', 'joined_at' => 'date', 'expires_at' => 'date'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MembershipCategory::class, 'category_id');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(MembershipLevel::class, 'level_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(MembershipTransaction::class);
    }
}

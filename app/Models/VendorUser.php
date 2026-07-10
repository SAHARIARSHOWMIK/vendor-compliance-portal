<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vendor_id',
        'role_within_vendor',
        'invitation_status',
        'invitation_token',
        'invitation_sent_at',
        'invitation_accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'invitation_sent_at' => 'datetime',
            'invitation_accepted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function isPending(): bool
    {
        return $this->invitation_status === 'pending';
    }

    public function isAccepted(): bool
    {
        return $this->invitation_status === 'accepted';
    }
}

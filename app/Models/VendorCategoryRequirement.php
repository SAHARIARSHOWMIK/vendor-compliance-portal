<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorCategoryRequirement extends Model
{
    protected $fillable = [
        'vendor_category',
        'document_type_id',
        'requirement_level',
        'min_risk_level',
        'expiry_required',
    ];

    protected function casts(): array
    {
        return [
            'expiry_required' => 'boolean',
        ];
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function isRequired(): bool
    {
        return $this->requirement_level === 'required';
    }
}

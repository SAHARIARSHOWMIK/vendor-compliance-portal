<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'category',
        'requires_expiry_date',
        'is_mandatory_by_default',
        'allowed_file_types',
        'max_file_size_kb',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'requires_expiry_date' => 'boolean',
            'is_mandatory_by_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function categoryRequirements(): HasMany
    {
        return $this->hasMany(VendorCategoryRequirement::class);
    }

    public function vendorDocuments(): HasMany
    {
        return $this->hasMany(VendorDocument::class);
    }

    /**
     * Returns the allowed extension list as an array, e.g. ['pdf','jpg','png'].
     */
    public function allowedExtensions(): array
    {
        return array_map('trim', explode(',', $this->allowed_file_types));
    }
}

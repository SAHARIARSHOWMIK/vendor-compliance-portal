<?php

namespace App\Http\Requests\Vendor;

use App\Enums\RoleName;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        $vendor = $this->route('vendor');
        return $this->user()->can('update', $vendor);
    }

    public function rules(): array
    {
        return [
            'name'                 => ['required', 'string', 'max:255'],
            'registration_number'  => ['nullable', 'string', 'max:100'],
            'category'             => ['required', 'in:general_supplier,it_vendor,contractor,consultant,high_risk'],
            'risk_level'           => ['required', 'in:low,medium,high'],
            'contact_person'       => ['nullable', 'string', 'max:255'],
            'email'                => ['nullable', 'email', 'max:255'],
            'phone'                => ['nullable', 'string', 'max:50'],
            'address'              => ['nullable', 'string', 'max:500'],
            'country'              => ['nullable', 'string', 'size:2'],
            'internal_notes'       => ['nullable', 'string', 'max:2000'],
            'assigned_reviewer_id' => ['nullable', 'exists:users,id'],
        ];
    }
}

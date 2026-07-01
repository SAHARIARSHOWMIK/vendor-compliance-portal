<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;

class InviteVendorUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $vendor = $this->route('vendor');
        return $this->user()->can('invite', $vendor);
    }

    public function rules(): array
    {
        return [
            'email'        => ['required', 'email', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'        => 'A contact email address is required to send the invitation.',
            'contact_name.required' => 'Please enter the contact person name.',
        ];
    }
}

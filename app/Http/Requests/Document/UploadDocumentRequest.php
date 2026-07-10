<?php

namespace App\Http\Requests\Document;

use App\Models\DocumentType;
use App\Models\Vendor;
use App\Models\VendorDocument;
use Illuminate\Foundation\Http\FormRequest;

class UploadDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $vendor = $this->route('vendor');

        if (! $user || ! $vendor instanceof Vendor) {
            return false;
        }

        $documentTypeId = $this->integer('document_type_id');

        if ($documentTypeId > 0) {
            $existing = VendorDocument::query()
                ->where('vendor_id', $vendor->id)
                ->where('document_type_id', $documentTypeId)
                ->first();

            if ($existing) {
                return $user->can('replace', $existing);
            }
        }

        return $user->can('upload', [VendorDocument::class, $vendor]);
    }

    public function rules(): array
    {
        $documentType = $this->resolveDocumentType();
        $maxKb        = $documentType?->max_file_size_kb ?? (int) env('DOCUMENT_MAX_UPLOAD_SIZE_KB', 10240);
        $allowedExts  = $documentType
            ? $documentType->allowedExtensions()
            : explode(',', env('DOCUMENT_ALLOWED_MIME_TYPES', 'pdf,jpg,jpeg,png'));

        $rules = [
            'document_type_id' => ['required', 'exists:document_types,id'],
            'file'             => [
                'required',
                'file',
                "max:{$maxKb}",
                ...(! empty($allowedExts) ? ['mimes:' . implode(',', $allowedExts)] : []),
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];

        // Expiry date is conditionally required based on the document type
        if ($documentType?->requires_expiry_date) {
            $rules['expiry_date'] = ['required', 'date', 'after:today'];
        } else {
            $rules['expiry_date'] = ['nullable', 'date', 'after:today'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'file.max'          => 'The file exceeds the maximum allowed upload size for this document type.',
            'file.mimes'        => 'This document type only accepts: ' . implode(', ', $this->resolveDocumentType()?->allowedExtensions() ?? []),
            'expiry_date.required' => 'An expiry date is required for this document type.',
            'expiry_date.after'    => 'The expiry date must be in the future.',
        ];
    }

    private function resolveDocumentType(): ?DocumentType
    {
        $id = $this->input('document_type_id');
        return $id ? DocumentType::find($id) : null;
    }
}

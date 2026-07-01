<?php

namespace App\Http\Requests\Document;

use App\Models\DocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $vendor = $this->route('vendor');
        return $this->user()->can('upload', [$vendor]);
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
                Rule::when(! empty($allowedExts), ['mimes:' . implode(',', $allowedExts)]),
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

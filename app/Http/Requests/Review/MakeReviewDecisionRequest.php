<?php

namespace App\Http\Requests\Review;

use Illuminate\Foundation\Http\FormRequest;

class MakeReviewDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $document = $this->route('document');
        return $this->user()->can('decide', $document);
    }

    public function rules(): array
    {
        return [
            'decision' => [
                'required',
                'in:approved,rejected,correction_requested,need_more_info,escalated',
            ],
            'comment' => [
                $this->input('decision') === 'approved' ? 'nullable' : 'required',
                'string',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'decision.required' => 'Please select a review decision.',
            'decision.in'       => 'Invalid review decision.',
            'comment.required'  => 'A comment is required when rejecting or requesting corrections, so the vendor knows what to fix.',
        ];
    }
}

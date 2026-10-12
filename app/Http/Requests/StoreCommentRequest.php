<?php

namespace App\Http\Requests;

use App\Models\Attachment;
use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('ticket');

        if ($this->boolean('is_internal') && ! $this->user()->can('addInternalNote', $ticket)) {
            return false;
        }

        return $this->user()->can('comment', $ticket);
    }

    public function messages(): array
    {
        return [
            'attachments.max' => 'You can attach up to '.Attachment::MAX_FILES.' files.',
            'attachments.*.max' => 'Each file must be 5 MB or smaller.',
            'attachments.*.mimes' => 'Allowed files: images, PDF, text/log files, Word, Excel, CSV and ZIP.',
        ];
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
            'is_internal' => ['sometimes', 'boolean'],
            'attachments' => ['nullable', 'array', 'max:'.Attachment::MAX_FILES],
            'attachments.*' => Attachment::RULES,
        ];
    }
}

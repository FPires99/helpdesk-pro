<?php

namespace App\Http\Requests;

use App\Enums\TicketPriority;
use App\Models\Attachment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
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
            'subject' => ['required', 'string', 'max:150'],
            'category_id' => ['required', 'exists:categories,id'],
            'priority' => ['required', Rule::enum(TicketPriority::class)],
            'description' => ['required', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:'.Attachment::MAX_FILES],
            'attachments.*' => Attachment::RULES,
        ];
    }
}

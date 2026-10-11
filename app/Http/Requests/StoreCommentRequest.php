<?php

namespace App\Http\Requests;

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

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
            'is_internal' => ['sometimes', 'boolean'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Enums\TicketPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:150'],
            'category_id' => ['required', 'exists:categories,id'],
            'priority' => ['required', Rule::enum(TicketPriority::class)],
            'description' => ['required', 'string', 'max:5000'],
        ];
    }
}

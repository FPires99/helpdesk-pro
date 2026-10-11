<?php

namespace App\Http\Requests;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('ticket'));
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::enum(TicketStatus::class)],
            'priority' => ['sometimes', Rule::enum(TicketPriority::class)],
            'category_id' => ['sometimes', 'exists:categories,id'],
            'assignee_id' => [
                'sometimes',
                'nullable',
                Rule::exists('users', 'id')->where(fn ($q) => $q->whereIn('role', ['technician', 'admin'])),
            ],
        ];
    }

    public function messages(): array
    {
        return ['assignee_id.exists' => 'Tickets can only be assigned to technicians or admins.'];
    }
}

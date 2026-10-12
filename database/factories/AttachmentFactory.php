<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Attachment>
 */
class AttachmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'user_id' => User::factory(),
            'original_name' => 'screenshot.png',
            'path' => 'attachments/test/screenshot.png',
            'mime_type' => 'image/png',
            'size' => 20480,
        ];
    }
}

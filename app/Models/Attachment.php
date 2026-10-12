<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    use HasFactory;

    /** Accepted file types and limits, shared by every upload form. */
    public const RULES = ['file', 'max:5120', 'mimes:jpg,jpeg,png,gif,webp,pdf,txt,log,csv,docx,xlsx,zip'];

    public const MAX_FILES = 5;

    /** Files are kept on the private disk and only served through an authorised route. */
    public const DISK = 'local';

    protected $fillable = ['ticket_id', 'comment_id', 'user_id', 'original_name', 'path', 'mime_type', 'size'];

    protected static function booted(): void
    {
        static::deleted(fn (Attachment $attachment) => Storage::disk(self::DISK)->delete($attachment->path));
    }

    public static function store(UploadedFile $file, Ticket $ticket, User $user, ?Comment $comment = null): self
    {
        return self::create([
            'ticket_id' => $ticket->id,
            'comment_id' => $comment?->id,
            'user_id' => $user->id,
            'original_name' => $file->getClientOriginalName(),
            'path' => $file->store("attachments/{$ticket->id}", self::DISK),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => $file->getSize(),
        ]);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    public function isImage(): bool
    {
        return in_array($this->mime_type, ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
    }

    public function humanSize(): string
    {
        return $this->size >= 1048576
            ? round($this->size / 1048576, 1).' MB'
            : max(1, round($this->size / 1024)).' KB';
    }
}

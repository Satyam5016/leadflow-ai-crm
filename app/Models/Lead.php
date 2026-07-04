<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Lead extends Model
{
    /** @use HasFactory<\Database\Factories\LeadFactory> */
    use HasFactory;

    protected $fillable = [
        'workspace_id', 'assigned_to_id', 'name', 'company', 'email', 'phone',
        'status', 'source', 'value', 'notes', 'ai_score', 'ai_reason',
    ];

    protected function casts(): array
    {
        return ['value' => 'decimal:2', 'ai_score' => 'integer'];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable')->latest();
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject')->latest();
    }

    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'taskable')->latest();
    }

    public function files(): MorphMany
    {
        return $this->morphMany(CrmFile::class, 'fileable')->latest();
    }

    public function emails(): MorphMany
    {
        return $this->morphMany(EmailLog::class, 'emailable')->latest();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Deal extends Model
{
    /** @use HasFactory<\Database\Factories\DealFactory> */
    use HasFactory;

    protected $fillable = [
        'workspace_id', 'customer_id', 'lead_id', 'owner_id', 'title', 'stage',
        'value', 'expected_close_date', 'probability', 'description',
    ];

    protected function casts(): array
    {
        return ['expected_close_date' => 'date', 'value' => 'decimal:2', 'probability' => 'integer'];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject')->latest();
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable')->latest();
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

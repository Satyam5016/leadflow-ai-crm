<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EmailLog extends Model
{
    protected $fillable = [
        'workspace_id', 'user_id', 'emailable_type', 'emailable_id',
        'direction', 'subject', 'body', 'sender', 'receiver', 'summary',
    ];

    public function emailable(): MorphTo
    {
        return $this->morphTo();
    }
}

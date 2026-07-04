<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CrmFile extends Model
{
    protected $fillable = ['workspace_id', 'user_id', 'fileable_type', 'fileable_id', 'name', 'mime_type', 'size', 'path'];

    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }
}

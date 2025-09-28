<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateDownload extends Model
{
    protected $fillable = [
        'template_id',
        'ip_address',
        'user_agent',
        'username',
        'country',
        'city',
        'region',
        'timezone',
        'file_name',
        'file_size',
        'downloaded_at'
    ];

    protected $casts = [
        'downloaded_at' => 'datetime',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }
}
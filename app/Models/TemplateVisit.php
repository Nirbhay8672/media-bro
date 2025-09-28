<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateVisit extends Model
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
        'visited_at'
    ];

    protected $casts = [
        'visited_at' => 'datetime',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }
}
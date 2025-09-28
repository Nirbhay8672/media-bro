<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Template extends Model
{
    protected $fillable = [
        'name',
        'description',
        'canvas_data',
        'background_image',
        'width',
        'height',
        'share_token',
        'is_active',
        'user_id'
    ];

    protected $casts = [
        'canvas_data' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the canvas_data attribute
     */
    public function getCanvasDataAttribute($value)
    {
        if (is_string($value)) {
            return json_decode($value, true) ?? [];
        }
        return $value ?? [];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($template) {
            if (empty($template->share_token)) {
                $template->share_token = Str::random(32);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getShareUrlAttribute(): string
    {
        return route('template.share', $this->share_token);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(TemplateVisit::class);
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(TemplateDownload::class);
    }
}

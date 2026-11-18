<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Submitter extends Model
{
    use HasFactory;

    protected $fillable = [
        'submission_id', 'uuid', 'email', 'phone', 'name',
        'slug', 'sent_at', 'opened_at', 'completed_at', 'declined_at',
        'values', 'metadata', 'preferences', 'ip', 'ua', 'external_id',
        'order', 'account_id',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
        'completed_at' => 'datetime',
        'declined_at' => 'datetime',
        'values' => 'array',
        'metadata' => 'array',
        'preferences' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (Submitter $submitter) {
            if (empty($submitter->slug)) {
                $submitter->slug = Str::random(14);
            }
            if (empty($submitter->uuid)) {
                $submitter->uuid = (string) Str::uuid();
            }
        });
    }

    public function submission(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function completedDocuments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CompletedDocument::class);
    }

    public function searchEntry(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(SearchEntry::class, 'record');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->name ?? $this->email ?? $this->phone ?? 'Unknown';
    }

    public function getStatusAttribute(): string
    {
        if ($this->declined_at) {
            return 'declined';
        }
        if ($this->completed_at) {
            return 'completed';
        }
        if ($this->opened_at) {
            return 'opened';
        }
        if ($this->sent_at) {
            return 'sent';
        }

        return 'awaiting';
    }

    public function getStatusEventAtAttribute(): ?\Illuminate\Support\Carbon
    {
        return $this->declined_at ?? $this->completed_at ?? $this->opened_at ?? $this->sent_at ?? $this->created_at;
    }
}

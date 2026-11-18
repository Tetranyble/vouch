<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Submission extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id', 'created_by_user_id', 'archived_at', 'template_fields', 'template_documents',
        'template_schema', 'template_submitters', 'source', 'submitters_order',
        'slug', 'preferences', 'account_id', 'expire_at', 'name',
        'variables_schema', 'variables',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
        'expire_at' => 'datetime',
        'template_fields' => 'array',
        'template_documents' => 'array',
        'template_schema' => 'array',
        'template_submitters' => 'array',
        'preferences' => 'array',
        'variables_schema' => 'array',
        'variables' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (Submission $submission) {
            if (empty($submission->slug)) {
                $submission->slug = Str::random(14);
            }
            if (empty($submission->source)) {
                $submission->source = 'invite';
            }
            if (empty($submission->submitters_order)) {
                $submission->submitters_order = 'random';
            }
        });
    }

    // Relationships
    public function template(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function submitters(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Submitter::class);
    }

    public function account(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function createdByUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function events(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SubmissionEvent::class);
    }

    public function searchEntry(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(SearchEntry::class, 'record');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    public function scopeForAccount(Builder $query, int $accountId): Builder
    {
        return $query->where('account_id', $accountId);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereExists(function ($q) {
            $q->select('id')
                ->from('submitters')
                ->whereColumn('submitters.submission_id', 'submissions.id')
                ->whereNull('completed_at');
        });
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->whereNotExists(function ($q) {
            $q->select('id')
                ->from('submitters')
                ->whereColumn('submitters.submission_id', 'submissions.id')
                ->whereNull('completed_at');
        })->whereHas('submitters');
    }

    public function scopeDeclined(Builder $query): Builder
    {
        return $query->whereExists(function ($q) {
            $q->select('id')
                ->from('submitters')
                ->whereColumn('submitters.submission_id', 'submissions.id')
                ->whereNotNull('declined_at');
        });
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->pending()->where('expire_at', '<=', now());
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', '%'.$term.'%')
                ->orWhereHas('submitters', function ($sq) use ($term) {
                    $sq->where('email', 'like', '%'.$term.'%')
                        ->orWhere('name', 'like', '%'.$term.'%');
                });
        });
    }

    // Accessors
    public function getIsExpiredAttribute(): bool
    {
        return $this->expire_at && $this->expire_at->lte(now());
    }

    public function getStatusAttribute(): string
    {
        if ($this->is_expired) {
            return 'expired';
        }
        $submitters = $this->submitters;
        if ($submitters->isEmpty()) {
            return 'pending';
        }
        if ($submitters->every(fn ($s) => $s->completed_at)) {
            return 'completed';
        }
        if ($submitters->some(fn ($s) => $s->declined_at)) {
            return 'declined';
        }

        return 'pending';
    }
}

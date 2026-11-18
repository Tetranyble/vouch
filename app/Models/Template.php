<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Template extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug', 'name', 'schema', 'fields', 'submitters', 'author_id',
        'account_id', 'archived_at', 'source', 'folder_id', 'external_id',
        'preferences', 'shared_link', 'variables_schema', 'documents',
    ];

    protected $casts = [
        'schema' => 'array',
        'fields' => 'array',
        'submitters' => 'array',
        'preferences' => 'array',
        'variables_schema' => 'array',
        'documents' => 'array',
        'shared_link' => 'boolean',
        'archived_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (Template $template) {
            if (empty($template->slug)) {
                $template->slug = Str::random(14);
            }
            if (empty($template->source)) {
                $template->source = 'native';
            }
            if (empty($template->submitters)) {
                $template->submitters = [['name' => 'First Party', 'uuid' => (string) Str::uuid()]];
            }
        });
    }

    // Relationships
    public function account(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function author(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function submissions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function folder(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(TemplateFolder::class, 'folder_id');
    }

    public function sharings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TemplateSharing::class);
    }

    public function searchEntry(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(SearchEntry::class, 'record');
    }

    public function accesses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TemplateAccess::class);
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

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where('name', 'like', '%' . $term . '%');
    }

    // Accessors
    public function getFolderNameAttribute(): string
    {
        return $this->folder?->name ?? 'Default';
    }

    public function getSubmissionsCountAttribute(): int
    {
        return $this->submissions()->count();
    }

    public function getPendingSubmissionsCountAttribute(): int
    {
        return $this->submissions()->pending()->count();
    }

    public function getCompletedSubmissionsCountAttribute(): int
    {
        return $this->submissions()->completed()->count();
    }
}

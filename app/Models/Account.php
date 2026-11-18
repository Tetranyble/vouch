<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'locale', 'timezone', 'archived_at', 'uuid'];
    protected $casts = ['archived_at' => 'datetime'];


    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (Account $account) {
            if (empty($account->uuid)) {
                $account->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }
    public function users() { return $this->hasMany(User::class); }
    public function templates() { return $this->hasMany(Template::class); }
    public function submissions() { return $this->hasMany(Submission::class); }
    public function templateFolders() { return $this->hasMany(TemplateFolder::class); }
    public function configs() { return $this->hasMany(AccountConfig::class); }
    public function webhookUrls() { return $this->hasMany(WebhookUrl::class); }

    public function getDefaultTemplateFolderAttribute(): TemplateFolder
    {
        return $this->templateFolders()->firstOrCreate(
            ['name' => 'Default'],
            ['author_id' => $this->users()->value('id')]
        );
    }
}

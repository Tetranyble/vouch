<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateFolder extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'account_id', 'author_id', 'parent_folder_id'];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function templates()
    {
        return $this->hasMany(Template::class, 'folder_id');
    }

    public function parent()
    {
        return $this->belongsTo(TemplateFolder::class, 'parent_folder_id');
    }

    public function children()
    {
        return $this->hasMany(TemplateFolder::class, 'parent_folder_id');
    }

    public function getFullNameAttribute(): string
    {
        return $this->parent ? $this->parent->full_name.'/'.$this->name : $this->name;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateSharing extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id',
        'account_id',
        'ability',
    ];

    public function template()
    {
        return $this->belongsTo(Template::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}

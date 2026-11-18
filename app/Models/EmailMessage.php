<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'author_id',
        'account_id',
        'subject',
        'body',
        'sha1',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}

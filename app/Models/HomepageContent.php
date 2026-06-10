<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomepageContent extends Model
{
    public $timestamps = false;

    protected $table = 'homepage_content';

    protected $fillable = [
        'content_text',
    ];
}

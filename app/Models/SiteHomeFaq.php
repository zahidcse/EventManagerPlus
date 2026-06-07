<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteHomeFaq extends Model
{
    protected $table = 'site_home_faqs';

    protected $fillable = [
        'sort_order',
        'question',
        'answer',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }
}

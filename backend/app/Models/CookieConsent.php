<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CookieConsent extends Model
{
    use HasFactory;

    protected $fillable = [
        'consent_token',
        'consent_choices',
        'banner_version',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'consent_choices' => 'array',
        ];
    }
}

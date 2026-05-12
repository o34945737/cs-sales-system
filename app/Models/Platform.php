<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Platform extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_active',
        'tts_days',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'tts_days'  => 'integer',
        ];
    }
}

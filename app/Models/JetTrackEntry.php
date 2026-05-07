<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JetTrackEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'awb',
        'order_id',
        'source_url',
        'kondisi_barang',
        'video_url',
        'warehouse_doc_link',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}

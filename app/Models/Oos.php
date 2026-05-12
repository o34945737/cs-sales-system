<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Oos extends Model
{
    use HasFactory;

    protected $table = 'oos';
    protected $fillable = [
        'complaint_id',
        'tanggal_input', 'brand', 'platform', 'cs_name', 'order_id',
        'product_name', 'sku', 'reason', 'solusi', 'note_detail_varian',
        'update_cs', 'tanggal_blast', 'feedback_customers', 'month',
    ];

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if ($model->tanggal_input) {
                $model->month = Carbon::parse($model->tanggal_input)->format('F Y');
            }

            // Auto-link to complaint via order_id when not explicitly set
            if (filled($model->order_id) && empty($model->complaint_id)) {
                $linked = Complaint::query()
                    ->whereNull('deleted_at')
                    ->whereRaw('TRIM(order_id) = ?', [trim($model->order_id)])
                    ->value('id');
                $model->complaint_id = $linked ?: null;
            }
        });
    }
}

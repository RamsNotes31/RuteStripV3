<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BulkProvenanceAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'action',
        'status',
        'processed_count',
        'failed_count',
        'last_error',
        'started_by',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function starter()
    {
        return $this->belongsTo(User::class, 'started_by');
    }
}

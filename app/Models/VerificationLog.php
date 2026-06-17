<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'gpx_version_id',
        'source',
        'expected_hash',
        'actual_hash',
        'status',
        'message',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function gpxVersion()
    {
        return $this->belongsTo(GpxVersion::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}

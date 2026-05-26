<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceCorrectionRequest extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'attendance_id',
        'request_clock_in',
        'request_clock_out',
        'note',
        'status',
        'approve_by',
        'approve_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function correctionBreaks()
    {
        return $this->hasMany(AttendanceCorrectionBreak::class);
    }

    public function approveBy()
    {
        return $this->belongsTo(User::class, 'approve_by');
    }
}

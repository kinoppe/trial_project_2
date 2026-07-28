<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\AttendanceCorrectionBreak;

class AttendanceCorrectionRequest extends Model
{
    use HasFactory;
    protected $fillable = [
        'attendance_id',
        'request_clock_in',
        'request_clock_out',
        'note',
        'status',
        'approved_by',
        'approved_at',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function breaks()
    {
        return $this->hasMany(AttendanceCorrectionBreak::class,'attendance_correction_request_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}

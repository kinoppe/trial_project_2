<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index()
    {
        $attendance = Attendance::where('user_id',auth()->id())
        ->whereDate('work_date',today())
        ->first();

        if(!$attendance) {
            $status = 'off_work';
            $statusLabel = '勤務外';
        } elseif($attendance->clock_out) {
            $status = 'after_work';
            $statusLabel = '退勤済';
        } elseif($attendance->breaks()->whereNull('break_end')->exists()) {
            $status = 'on_break';
            $statusLabel = '休憩中';
        } else {
            $status = 'working';
            $statusLabel = '出勤中';
        }
        return view('attendance.index',compact('attendance','status','statusLabel'));
    }

    public function store()
    {
        $exists = Attendance::where('user_id',auth()->id())
        ->whereData('work_date',today())
        ->exists();

        if($exists) {
            return redirect('/attendance');
        }

        Attendance::create([
            'user_id' => auth()->id(),
            'work_date' => today(),
            'clock_in' => now(),
        ]);
        return redirect('/attendance');
    }

    public function breakStart()
    {
        $attendance = Attendance::where('user_id',auth()->id())
        ->whereDate('work_date',today())
        ->firstOrFail();

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => now(),
        ]);
        return redirect('/attendance');
    }

    public function breakEnd()
    {
        $attendance = Attendance::where('user_id',auth()->id())
        ->whereDate('work_date',today())
        ->firstOrFail();

        $break = BreakTime::where('attendance_id',$attendance->id)
        ->whereNull('break_end')
        ->latest()
        ->firstOrFail();

        $break->update([
            'break_end' => now(),
        ]);
        return redirect('/attendance');
    }

    public function update()
    {
        $attendance = Attendance::where('user_id',auth()->id())
        ->whereDate('work_date',today())
        ->firstOrFail();

        $attendance->update([
            'clock_out' => now(),
        ]);
        return redirect('/attendance');
    }
}

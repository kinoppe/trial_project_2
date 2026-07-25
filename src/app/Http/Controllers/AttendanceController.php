<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\AttendanceCorrectionRequest;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index()
    {
        $attendance = Attendance::where('user_id',auth()->id())
        ->whereDate('work_date',today())
        ->first();

        if(!$attendance || !$attendance->clock_in) {
            $status = 'off_work';
            $statusLabel = '勤務外';
        } elseif($attendance->clock_out) {
            $status = 'after_work';
            $statusLabel = '退勤済';
        } elseif($attendance->breakTimes()->whereNull('break_end')->exists()) {
            $status = 'on_break';
            $statusLabel = '休憩中';
        } else {
            $status = 'working';
            $statusLabel = '出勤中';
        }
        return view('attendance.index',compact('attendance','status','statusLabel'));
    }

    public function clockIn()
    {
        $exists = Attendance::where('user_id',auth()->id())
        ->whereDate('work_date',today())
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

    public function clockOut()
    {
        $attendance = Attendance::where('user_id',auth()->id())
        ->whereDate('work_date',today())
        ->firstOrFail();

        $attendance->update([
            'clock_out' => now(),
        ]);
        return redirect('/attendance');
    }

    public function list(Request $request)
    {
        $month = $request->input('month')
            ? Carbon::parse($request->input('month'))
            : now();

        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        $attendances = Attendance::where('user_id', auth()->id())
            ->whereBetween('work_date',[$startOfMonth->toDateString(),$endOfMonth->toDateString()])
            ->with('breakTimes')
            ->get()
            ->keyBy(function ($attendance) {
                return Carbon::parse($attendance->work_date)->format('Y-m-d');
            });

        $dates = CarbonPeriod::create($startOfMonth, $endOfMonth);

        $records = [];
        foreach($dates as $date) {
            $attendance = $attendances->get($date->format('Y-m-d'));
            $breakMinutes = $attendance
                ? $attendance->breakTimes->sum(function ($break) {
                    if (!$break->break_start || !$break->break_end) {
                        return 0;
                    }

                    return Carbon::parse($break->break_start)
                        ->diffInMinutes(Carbon::parse($break->break_end));
                })
                : 0;

            $workMinutes = '';
            if($attendance && $attendance->clock_in && $attendance->clock_out) {
                $workMinutes = Carbon::parse($attendance->clock_in)
                    ->diffInMinutes(Carbon::parse($attendance->clock_out))
                    - $breakMinutes;
                $workMinutes = sprintf('%d:%02d',floor($workMinutes / 60),$workMinutes % 60);
            }

            $records[] = [
                'date_key' => $date->format('Y-m-d'),
                'date' => $date->format('m/d'),
                'week' => ['日','月','火','水','木','金','土',][$date->dayOfWeek],
                'clock_in' => $attendance && $attendance->clock_in
                    ? Carbon::parse($attendance->clock_in)->format('H:i')
                    : '',
                'clock_out' => $attendance && $attendance->clock_out
                    ? Carbon::parse($attendance->clock_out)->format('H:i')
                    : '',
                'break_time' => $attendance
                    ? sprintf('%d:%02d',floor($breakMinutes / 60),$breakMinutes % 60)
                    : '',
                'total_time' => $workMinutes,
                'attendance_id' => $attendance?->id,
            ];
        }
        return view('attendance.list',compact('month','records'));
    }

    public function show($date)
    {
        $attendance = Attendance::with(['user','breakTimes'])
            ->where('user_id',auth()->id())
            ->whereDate('work_date',$date)
            ->first();

        $pendingRequest = null;

        if($attendance) {
            $pendingRequest = AttendanceCorrectionRequest::with('breaks')
            ->where('attendance_id',$attendance->id)
            ->where('status','pending')
            ->whereHas('attendance', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->latest()
            ->first();
        }

        $isAdmin = false;
        return view('attendance.show',compact('attendance','pendingRequest','date','isAdmin'));
    }
}

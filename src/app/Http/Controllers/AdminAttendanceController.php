<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AdminAttendanceController extends Controller
{
    public function list(Request $request)
    {
        $date = $request->input('date')
            ? Carbon::parse($request->input('date'))
            : today();

        $attendances = Attendance::with(['user','breakTimes'])
            ->whereDate('work_date',$date)
            ->whereHas('user', function ($query) {
                $query->where('is_admin', false);
            })
            ->get();

        $records = [];
        foreach($attendances as $attendance) {
            $breakMinutes = $attendance->breakTimes->sum(function($break){
                if (!$break->break_start || !$break->break_end) {
                    return 0;
                }
                return \Carbon\Carbon::parse($break->break_start)
                    ->diffInMinutes(\Carbon\Carbon::parse($break->break_end));
            });

            $workMinutes = 0;
            if($attendance->clock_in && $attendance->clock_out) {
                $workMinutes = Carbon::parse($attendance->clock_in)
                    ->diffInMinutes(Carbon::parse($attendance->clock_out))
                    - $breakMinutes;
                $workMinutes = sprintf('%d:%02d',floor($workMinutes / 60),$workMinutes % 60);
            }

            $records[] = [
                'user' => $attendance->user,
                'attendance' => $attendance,
                'clock_in' => $attendance && $attendance->clock_in
                    ? Carbon::parse($attendance->clock_in)->format('H:i')
                    : '',
                'clock_out' => $attendance && $attendance->clock_out
                    ? Carbon::parse($attendance->clock_out)->format('H:i')
                    : '',
                'break_time' => $breakMinutes
                    ? sprintf('%d:%02d',floor($breakMinutes / 60),$breakMinutes % 60)
                    : '',
                'total_time' => $workMinutes,
            ];
        }
        return view('admin.attendance.list',compact('records','date'));
    }
}

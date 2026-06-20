<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use App\Models\AttendanceCorrectionRequest;
use App\Http\Requests\AttendanceCorrectionRequestRequest;

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

    public function show(Request $request,$date)
    {
        $userId = $request->query('user_id');

        $user = User::where('is_admin', false)->findOrFail($userId);

        $attendance = Attendance::with(['user', 'breakTimes'])
            ->where('user_id', $user->id)
            ->whereDate('work_date', $date)
            ->first();

        $pendingRequest = null;

        if ($attendance) {
            $pendingRequest = AttendanceCorrectionRequest::with('breaks')
                ->where('attendance_id', $attendance->id)
                ->where('status', 'pending')
                ->latest()
                ->first();
        }

        $isAdmin = true;

        return view('attendance.show', compact(
            'attendance',
            'pendingRequest',
            'date',
            'isAdmin',
            'user'
        ));
    }

    public function update(AttendanceCorrectionRequestRequest $request, $date)
    {
        $userId = $request->input('user_id');

        $user = User::where('is_admin', false)->findOrFail($userId);

        $attendance = Attendance::with('breakTimes')
            ->where('user_id', $user->id)
            ->whereDate('work_date', $date)
            ->first();

        $workDate = Carbon::parse($date)->toDateString();

        if (!$attendance) {
            $attendance = Attendance::create([
                'user_id' => $user->id,
                'work_date' => $workDate,
                'clock_in' => $workDate . ' ' . $request->clock_in,
                'clock_out' => $workDate . ' ' . $request->clock_out,
                'note' => $request->note,
            ]);
        } else {
            $attendance->update([
                'clock_in' => $workDate . ' ' . $request->clock_in,
                'clock_out' => $workDate . ' ' . $request->clock_out,
                'note' => $request->note,
            ]);
        }

        foreach ($request->breaks ?? [] as $index => $break) {
            if (empty($break['break_start']) && empty($break['break_end'])) {
                continue;
            }

            if (isset($attendance->breakTimes[$index])) {
                $attendance->breakTimes[$index]->update([
                    'break_start' => $workDate . ' ' . $break['break_start'],
                    'break_end' => $workDate . ' ' . $break['break_end'],
                ]);
            } else {
                $attendance->breakTimes()->create([
                    'break_start' => $workDate . ' ' . $break['break_start'],
                    'break_end' => $workDate . ' ' . $break['break_end'],
                ]);
            }
        }

        return redirect('/admin/attendance/list');
    }
}

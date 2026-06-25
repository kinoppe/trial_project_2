<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminStaffController extends Controller
{
    public function index()
    {
        $users = User::where('is_admin', false)->get();

        return view('admin.staff.index', compact('users'));
    }

    public function showAttendance(Request $request,$id)
    {
        $user = User::where('is_admin',false)->findOrFail($id);

        $month = $request->input('month')
            ? Carbon::parse($request->input('month'))
            : now();

        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        $attendances = Attendance::where('user_id', $user->id)
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
        return view('admin.attendance.staff',compact('user','records','month'));
    }

    public function export(Request $request,$id)
    {
        $user = User::where('is_admin',false)->findOrFail($id);

        $month = $request->input('month')
            ? Carbon::parse($request->input('month'))
            : now();

        $response = new StreamedResponse(function()use($user,$month){
            $handle = fopen('php://output','w');
            fputcsv($handle,['日付','出勤','退勤','休憩','合計']);

            $startOfMonth = $month->copy()->startOfMonth();
            $endOfMonth = $month->copy()->endOfMonth();

            $attendances = Attendance::with('breakTimes')
                ->where('user_id',$user->id)
                ->whereBetween('work_date',[
                    $startOfMonth->toDateString(),
                    $endOfMonth->toDateString(),
                ])
                ->get()
                ->keyBy(function($attendance){
                    return Carbon::parse($attendance->work_date)->format('Y-m-d');
                });

            foreach(CarbonPeriod::create($startOfMonth,$endOfMonth) as $date) {
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

                $workMinutes = 0;

                if ($attendance && $attendance->clock_in && $attendance->clock_out) {
                    $workMinutes = Carbon::parse($attendance->clock_in)
                        ->diffInMinutes(Carbon::parse($attendance->clock_out))
                        - $breakMinutes;
                }

                fputcsv($handle, [
                    $date->format('Y-m-d'),
                    $attendance && $attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : '',
                    $attendance && $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : '',
                    $breakMinutes ? sprintf('%d:%02d', floor($breakMinutes / 60), $breakMinutes % 60) : '',
                    $workMinutes ? sprintf('%d:%02d', floor($workMinutes / 60), $workMinutes % 60) : '',
                ]);
            }
            fclose($handle);
        });

        $fileName = $user->name . '_' . $month->format('Ym') . '_attendance.csv';
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', "attachment; filename={$fileName}");

        return $response;
    }
}

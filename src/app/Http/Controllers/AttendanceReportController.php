<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceReportController extends Controller
{
    public function index()
    {
        $startDate = now()->subMonths(5)->startOfMonth();
        $endDate = now()->endOfMonth();

        $attendances = Attendance::with('breakTimes')
            ->where('user_id', auth()->id())
            ->whereBetween('work_date', [$startDate->toDateString(),$endDate->toDateString()])
            ->get();

        $totalWorkMinutes = 0;
        $totalOverMinutes = 0;
        $workDays = 0;

        foreach ($attendances as $attendance) {
            $workMinutes = $this->getWorkMinutes($attendance);
            if ($workMinutes <= 0) {
                continue;
            }
            $totalWorkMinutes += $workMinutes;
            $totalOverMinutes += max($workMinutes - 480, 0);
            $workDays++;
        }

        $monthlyReports = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthAttendances = $attendances->filter(function ($attendance) use ($month) {
                return Carbon::parse($attendance->work_date)->format('Y-m') === $month->format('Y-m');
            });

            $monthWorkMinutes = 0;
            $monthOverMinutes = 0;

            foreach ($monthAttendances as $attendance) {
                $workMinutes = $this->getWorkMinutes($attendance);
                $monthWorkMinutes += $workMinutes;
                $monthOverMinutes += max($workMinutes - 480, 0);
            }

            $monthlyReports[] = [
                'month' => $month->format('Y-m'),
                'work_time' => $this->formatMinutes($monthWorkMinutes),
                'over_time' => $this->formatMinutes($monthOverMinutes),
            ];
        }

        $thisMonthAttendances = $attendances->filter(function ($attendance) {
            return Carbon::parse($attendance->work_date)->format('Y-m') === now()->format('Y-m');
        });

        $lateCount = $thisMonthAttendances->filter(function ($attendance) {
            return $attendance->clock_in
                && Carbon::parse($attendance->clock_in)->format('H:i') > '09:00';
        })->count();

        $earlyCount = $thisMonthAttendances->filter(function ($attendance) {
            return $attendance->clock_out
                && Carbon::parse($attendance->clock_out)->format('H:i') < '18:00';
        })->count();

        $longWorkCount = $thisMonthAttendances->filter(function ($attendance) {
            return $this->getWorkMinutes($attendance) > 600;
        })->count();

        return view('attendance.report', [
            'totalWork' => $this->formatMinutes($totalWorkMinutes),
            'totalOver' => $this->formatMinutes($totalOverMinutes),
            'averageWork' => $this->formatMinutes($workDays ? floor($totalWorkMinutes / $workDays) : 0),
            'monthlyReports' => $monthlyReports,
            'lateCount' => $lateCount,
            'earlyCount' => $earlyCount,
            'longWorkCount' => $longWorkCount,
        ]);
    }

    private function getWorkMinutes($attendance)
    {
        if (!$attendance->clock_in || !$attendance->clock_out) {
            return 0;
        }

        $breakMinutes = $attendance->breakTimes->sum(function ($break) {
            if (!$break->break_start || !$break->break_end) {
                return 0;
            }

            return Carbon::parse($break->break_start)
                ->diffInMinutes($break->break_end);
        });

        return Carbon::parse($attendance->clock_in)
            ->diffInMinutes($attendance->clock_out) - $breakMinutes;
    }

    private function formatMinutes($minutes)
    {
        return sprintf('%dh %dm', floor($minutes / 60), $minutes % 60);
    }
}

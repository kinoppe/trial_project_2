<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceReportController extends Controller
{
    private const STANDARD_WORK_MINUTES = 480;
    private const LONG_WORK_MINUTES = 600;

    public function index()
    {
        $attendances = Attendance::with('breakTimes')
            ->where('user_id', auth()->id())
            ->whereBetween('work_date', [
                now()->startOfMonth()->subMonths(5)->toDateString(),
                now()->endOfMonth()->toDateString(),
            ])
            ->get();

        $summary = $this->createSummary($attendances);
        $monthlyReports = $this->createMonthlyReports($attendances);
        $alerts = $this->createAlerts($attendances);

        return view('attendance.report', [
            'totalWork' => $this->formatMinutes($summary['total_work']),
            'totalOver' => $this->formatMinutes($summary['total_over']),
            'averageWork' => $this->formatMinutes($summary['average_work']),
            'monthlyReports' => $monthlyReports,
            'lateCount' => $alerts['late'],
            'earlyCount' => $alerts['early'],
            'longWorkCount' => $alerts['long_work'],
        ]);
    }

    private function createSummary($attendances)
    {
        $totalWorkMinutes = 0;
        $totalOverMinutes = 0;
        $workDays = 0;

        foreach ($attendances as $attendance) {
            $workMinutes = $this->getWorkMinutes($attendance);

            if ($workMinutes <= 0) {
                continue;
            }

            $totalWorkMinutes += $workMinutes;

            if ($workMinutes > self::STANDARD_WORK_MINUTES) {
                $overMinutes = $workMinutes - self::STANDARD_WORK_MINUTES;
                $totalOverMinutes += $overMinutes;
            }

            $workDays++;
        }

        if ($workDays > 0) {
            $averageWorkMinutes = floor($totalWorkMinutes / $workDays);
        } else {
            $averageWorkMinutes = 0;
        }

        return [
            'total_work' => $totalWorkMinutes,
            'total_over' => $totalOverMinutes,
            'average_work' => $averageWorkMinutes,
        ];
    }

    private function createMonthlyReports($attendances)
    {
        $monthlyReports = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->startOfMonth()->subMonths($i);

            $totalWorkMinutes = 0;
            $totalOverMinutes = 0;

            foreach ($attendances as $attendance) {
                $attendanceMonth = Carbon::parse($attendance->work_date)
                    ->format('Y-m');

                $targetMonth = $month->format('Y-m');

                if ($attendanceMonth !== $targetMonth) {
                    continue;
                }

                $workMinutes = $this->getWorkMinutes($attendance);

                if ($workMinutes <= 0) {
                    continue;
                }

                $totalWorkMinutes += $workMinutes;

                if ($workMinutes > self::STANDARD_WORK_MINUTES) {
                    $overMinutes = $workMinutes - self::STANDARD_WORK_MINUTES;
                    $totalOverMinutes += $overMinutes;
                }
            }

            $monthlyReports[] = [
                'month' => $month->format('Y-m'),
                'work_time' => $this->formatMinutes($totalWorkMinutes),
                'over_time' => $this->formatMinutes($totalOverMinutes),
            ];
        }

        return $monthlyReports;
    }

    private function createAlerts($attendances)
    {
        $thisMonthAttendances = $attendances->filter(function ($attendance) {
            return Carbon::parse($attendance->work_date)->format('Y-m')
                === now()->format('Y-m');
        });

        return [
            'late' => $thisMonthAttendances->filter(function ($attendance) {
                return $attendance->clock_in
                    && Carbon::parse($attendance->clock_in)->format('H:i') > '09:00';
            })->count(),

            'early' => $thisMonthAttendances->filter(function ($attendance) {
                return $attendance->clock_out
                    && Carbon::parse($attendance->clock_out)->format('H:i') < '18:00';
            })->count(),

            'long_work' => $thisMonthAttendances->filter(function ($attendance) {
                return $this->getWorkMinutes($attendance)
                    > self::LONG_WORK_MINUTES;
            })->count(),
        ];
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

        $workMinutes = Carbon::parse($attendance->clock_in)
            ->diffInMinutes($attendance->clock_out)
            - $breakMinutes;

        return max($workMinutes, 0);
    }

    private function formatMinutes($minutes)
    {
        return sprintf(
            '%dh %dm',
            floor($minutes / 60),
            $minutes % 60
        );
    }
}
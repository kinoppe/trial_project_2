<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceCorrectionBreak;
use Carbon\Carbon;
use App\Http\Requests\AttendanceCorrectionRequestRequest;

class AttendanceCorrectionRequestController extends Controller
{
    public function store(AttendanceCorrectionRequestRequest $request,$date)
    {
        $workDate = Carbon::parse($date)->toDateString();

        $attendance = Attendance::firstOrCreate(
            [
                'user_id' => auth()->id(),
                'work_date' => $workDate,
            ],
            [
                'clock_in' => null,
                'clock_out' => null,
            ]
        );

        $AttendanceCorrectionRequest = AttendanceCorrectionRequest::create([
            'user_id' => auth()->id(),
            'attendance_id' => $attendance->id,
            'request_clock_in' => $request->clock_in,
            'request_clock_out' => $request->clock_out,
            'note' => $request->note,
            'status' => 'pending',
        ]);

        foreach ($request->breaks ?? [] as $break) {
            if (empty($break['break_start']) && empty($break['break_end'])) {
                continue;
            }

            AttendanceCorrectionBreak::create([
                'attendance_correction_request_id' => $AttendanceCorrectionRequest->id,
                'break_start' => $break['break_start'],
                'break_end' => $break['break_end'],
            ]);
        }

        return redirect('/attendance/detail/' . $date);
    }
}

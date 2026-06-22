<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AttendanceCorrectionRequest;

class AdminCorrectionRequestController extends Controller
{
    public function show($attendance_correct_request_id)
    {
        $correctionRequest = AttendanceCorrectionRequest::with([
            'attendance.user',
            'breaks'
        ])->findOrFail($attendance_correct_request_id);

        $isAdmin = auth()->user()->is_admin;

        return view('admin.request.approve',compact('correctionRequest','isAdmin'));
    }

    public function approve($id)
    {
        if (!auth()->user()->is_admin) {
            abort(403);
        }

        $correctionRequest = AttendanceCorrectionRequest::with([
            'attendance.breakTimes',
            'breaks'
        ])->findOrFail($id);

        $attendance = $correctionRequest->attendance;

        $attendance->update([
            'clock_in' => $correctionRequest->request_clock_in,
            'clock_out' => $correctionRequest->request_clock_out,
            'note' => $correctionRequest->note,
        ]);

        $attendance->breakTimes()->delete();

        foreach ($correctionRequest->breaks as $break) {
            $attendance->breakTimes()->create([
                'break_start' => $break->break_start,
                'break_end' => $break->break_end,
            ]);
        }

        $correctionRequest->update([
            'status' => 'approved',
        ]);

        return redirect('/stamp_correction_request/list');
    }
}

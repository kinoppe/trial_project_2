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
            'attendance_id' => $attendance->id,
            'request_clock_in' => Carbon::parse($workDate . ' ' . $request->clock_in),
            'request_clock_out' => Carbon::parse($workDate . ' ' . $request->clock_out),
            'note' => $request->note,
            'status' => 'pending',
        ]);

        foreach ($request->breaks ?? [] as $break) {
            if (empty($break['break_start']) && empty($break['break_end'])) {
                continue;
            }

            AttendanceCorrectionBreak::create([
                'attendance_correction_request_id' => $AttendanceCorrectionRequest->id,
                'break_start' => Carbon::parse($workDate . ' ' . $break['break_start']),
                'break_end' => Carbon::parse($workDate . ' ' . $break['break_end']),
            ]);
        }

        return redirect('/attendance/detail/' . $date);
    }

    public function index(Request $request)
    {
        $status = $request->input('status','pending');

        $query = AttendanceCorrectionRequest::with(['attendance.user','breaks'])
            ->where('status',$status);

        if (!auth()->user()->is_admin) {
            $query->whereHas('attendance', function ($q) {
                $q->where('user_id', auth()->id());
            });
        } else {
            $query->whereHas('attendance.user', function ($q) {
                $q->where('is_admin', false);
            });
        }
        $requests = $query->latest()->get();
        return view('request.index',compact('requests','status'));
    }

    public function show($id)
    {
        $correctionRequest = AttendanceCorrectionRequest::with([
            'attendance.user',
            'breaks'
        ])->findOrFail($id);

        if (!auth()->user()->is_admin &&
            $correctionRequest->attendance->user_id !== auth()->id()) {
            abort(403);
        }

        return view('admin.request.approve', compact('correctionRequest'));
    }
}

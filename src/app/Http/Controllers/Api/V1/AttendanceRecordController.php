<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Http\Requests\Api\V1\IndexAttendanceRecordRequest;
use App\Http\Requests\Api\V1\StoreAttendanceRecordRequest;
use App\Http\Requests\Api\V1\UpdateAttendanceRecordRequest;
use App\Http\Resources\Api\V1\AttendanceRecordResource;
use Illuminate\Http\Response;

class AttendanceRecordController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(IndexAttendanceRecordRequest $request)
    {
        $perPage = $request->input('per_page', 20);

        $attendanceRecords = Attendance::with(['user', 'breakTimes', 'correctionRequests'])
            ->when($request->user_id, function ($query, $userId) {
                $query->where('user_id', $userId);
            })
            ->when($request->date, function ($query, $date) {
                $query->whereDate('work_date', $date);
            })
            ->when($request->month, function ($query, $month) {
                $query->whereYear('work_date', substr($month, 0, 4))
                    ->whereMonth('work_date', substr($month, 5, 2));
            })
            ->latest('work_date')
            ->paginate($perPage);

        return AttendanceRecordResource::collection($attendanceRecords);
    }

    public function store(StoreAttendanceRecordRequest $request)
    {
        $validated = $request->validated();
        $attendanceRecord = $request->user()
            ->attendances()
            ->create([
                'work_date' => $validated['date'],
                'clock_in' =>
                    $validated['date']
                    . ' '
                    . $validated['clock_in'],
                'clock_out' =>
                    isset($validated['clock_out'])
                    && $validated['clock_out'] !== null
                        ? $validated['date']
                            . ' '
                            . $validated['clock_out']
                        : null,
                'note' => $validated['comment'] ?? null,
            ]);

        $attendanceRecord->load([
            'user',
            'breakTimes',
        ]);

        return (new AttendanceRecordResource($attendanceRecord))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Attendance $attendanceRecord)
    {
        $attendanceRecord->load([
            'user',
            'breakTimes',
            'correctionRequests',
        ]);

        return new AttendanceRecordResource($attendanceRecord);
    }

    public function update(UpdateAttendanceRecordRequest $request, Attendance $attendanceRecord)
    {
        $this->authorize(
            'update',
            $attendanceRecord
        );

        $validated = $request->validated();

        $workDate = $validated['date']
            ?? $attendanceRecord->work_date->format('Y-m-d');

        $updateData = [];

        if (array_key_exists('date', $validated)) {
            $updateData['work_date'] = $validated['date'];
        }

        if (array_key_exists('clock_in', $validated)) {
            $updateData['clock_in'] =
                $workDate
                . ' '
                . $validated['clock_in'];
        } elseif (array_key_exists('date', $validated)) {
            $updateData['clock_in'] =
                $workDate
                . ' '
                . $attendanceRecord->clock_in->format('H:i:s');
        }

        if (array_key_exists('clock_out', $validated)) {
            $updateData['clock_out'] =
                $validated['clock_out'] !== null
                    ? $workDate
                        . ' '
                        . $validated['clock_out']
                    : null;
        } elseif (
            array_key_exists('date', $validated)
            && $attendanceRecord->clock_out
        ) {
            $updateData['clock_out'] =
                $workDate
                . ' '
                . $attendanceRecord->clock_out->format('H:i:s');
        }

        if (array_key_exists('comment', $validated)) {
            $updateData['note'] = $validated['comment'];
        }

        $attendanceRecord->update($updateData);

        $attendanceRecord->load([
            'user',
            'breakTimes',
        ]);

        return new AttendanceRecordResource(
            $attendanceRecord
        );
    }

    public function destroy(Attendance $attendanceRecord)
    {
        $this->authorize(
            'delete',
            $attendanceRecord
        );

        $attendanceRecord->delete();

        return response()->noContent();
    }
}

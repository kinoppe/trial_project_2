<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class AttendanceRecordResource extends JsonResource
{
    public function toArray($request)
    {
        $breakMinutes = $this->breakTimes->sum(function ($break) {
            if (!$break->break_start || !$break->break_end) {
                return 0;
            }
            return Carbon::parse($break->break_start)
                ->diffInMinutes(Carbon::parse($break->break_end));
        });

        $workMinutes = 0;

        if ($this->clock_in && $this->clock_out) {
            $workMinutes = Carbon::parse($this->clock_in)
                ->diffInMinutes(Carbon::parse($this->clock_out)) - $breakMinutes;
        }

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->user->name,
            'date' => Carbon::parse($this->work_date)->format('Y-m-d'),
            'clock_in' => $this->clock_in ? Carbon::parse($this->clock_in)->format('H:i:s') : null,
            'clock_out' => $this->clock_out ? Carbon::parse($this->clock_out)->format('H:i:s') : null,
            'total_time' => sprintf('%02d:%02d', floor($workMinutes / 60), $workMinutes % 60),
            'total_break_time' => sprintf('%02d:%02d', floor($breakMinutes / 60), $breakMinutes % 60),
            'comment' => $this->note,


            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                ];
            }),

            'breaks' => $this->whenLoaded('breakTimes', function () {
                return $this->breakTimes->map(function ($break) {
                    return [
                        'id' => $break->id,
                        'break_in' => $break->break_start
                            ? Carbon::parse($break->break_start)->format('H:i:s')
                            : null,
                        'break_out' => $break->break_end
                            ? Carbon::parse($break->break_end)->format('H:i:s')
                            : null,
                    ];
                });
            }),

            'applications' => $this->whenLoaded('correctionRequests', function () {
                return $this->correctionRequests->map(function ($request) {
                    return [
                        'id' => $request->id,
                        'status' => $request->status,
                        'comment' => $request->note,
                    ];
                });
            }),
        ];
    }
    }

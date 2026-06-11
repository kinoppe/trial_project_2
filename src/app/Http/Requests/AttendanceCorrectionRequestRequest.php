<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceCorrectionRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'clock_in' => ['required'],
            'clock_out' => ['required'],
            'breaks.*.break_start' => ['nullable'],
            'breaks.*.break_end' => ['nullable'],
            'note' => ['required'],
        ];
    }

    public function messages()
    {
        return [
            'note.required' => '備考を記入してください',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $clockIn = $this->input('clock_in');
            $clockOut = $this->input('clock_out');

            if ($clockIn && $clockOut && $clockIn > $clockOut) {
                $validator->errors()->add(
                    'clock_in',
                    '出勤時間もしくは退勤時間が不適切な値です'
                );
            }

            $breaks = $this->input('breaks', []);

            foreach ($breaks as $index => $break) {
                $breakStart = $break['break_start'] ?? null;
                $breakEnd = $break['break_end'] ?? null;

                if ($breakStart && $clockIn && $breakStart < $clockIn) {
                    $validator->errors()->add(
                        "breaks.$index.break_start",
                        '休憩時間が不適切な値です'
                    );
                }

                if ($breakStart && $clockOut && $breakStart > $clockOut) {
                    $validator->errors()->add(
                        "breaks.$index.break_start",
                        '休憩時間が不適切な値です'
                    );
                }

                if ($breakEnd && $clockOut && $breakEnd > $clockOut) {
                    $validator->errors()->add(
                        "breaks.$index.break_end",
                        '休憩時間もしくは退勤時間が不適切な値です'
                    );
                }
            }
        });
    }
}

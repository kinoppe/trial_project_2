<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
        ]);

        $user = User::create([
            'name' => '木下 悠哉',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
        ]);

        for ($i = 1; $i <= 15; $i++) {
            $date = Carbon::today()->subDays($i);

            $attendance = Attendance::create([
                'user_id' => $user->id,
                'work_date' => $date->toDateString(),
                'clock_in' => $date->copy()->setTime(9, 0, 0),
                'clock_out' => $date->copy()->setTime(18, 0, 0),
            ]);

            BreakTime::create([
                'attendance_id' => $attendance->id,
                'break_start' => $date->copy()->setTime(12, 0, 0),
                'break_end' => $date->copy()->setTime(13, 0, 0),
            ]);
        }

        $users = User::factory()->count(5)->create([
            'password' => Hash::make('password'),
            'is_admin' => false,
        ]);

        foreach ($users as $user) {
            for ($i = 1; $i <= 15; $i++) {
                $date = Carbon::today()->subDays($i);

                $attendance = Attendance::create([
                    'user_id' => $user->id,
                    'work_date' => $date->toDateString(),
                    'clock_in' => $date->copy()->setTime(9, 0, 0),
                    'clock_out' => $date->copy()->setTime(18, 0, 0),
                ]);

                BreakTime::create([
                    'attendance_id' => $attendance->id,
                    'break_start' => $date->copy()->setTime(12, 0, 0),
                    'break_end' => $date->copy()->setTime(13, 0, 0),
                ]);
            }
        }
    }
}

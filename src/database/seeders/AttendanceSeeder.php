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
        $user1 = User::create([
            'name' => 'ユーザー1（一般）',
            'email' => 'user1@example.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
            'email_verified_at' =>now()
        ]);

        $user2 = User::create([
            'name' => 'ユーザー2（一般）',
            'email' => 'user2@example.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
            'email_verified_at' =>now()
        ]);

        $admin = User::create([
            'name' => 'ユーザー3（管理者）',
            'email' => 'user3@example.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
            'email_verified_at' =>now()
        ]);

        $this->createUser1Attendances($user1);
        $this->createAttendances($user2);
        $this->createAttendances($admin);
    }

    private function createUser1Attendances(User $user)
    {
        for ($i = 5; $i >= 1; $i--) {
            $month = Carbon::today()->subMonths($i);
            $dates = $this->weekdayDates($month,15);
            foreach($dates as $date) {
                $this->createAttendance($user,$date,'09:00','18:00');
            }
        }

        $thisMonthDates = $this->weekdayDates(Carbon::today(),17);
        $patterns = array_merge(
            array_fill(0,10,['9:00','18:00']),
            array_fill(0,3,['9:00','20:00']),
            array_fill(0,2,['9:30','18:00']),
            array_fill(0,1,['9:00','17:00']),
            array_fill(0,1,['8:00','21:00']),
        );

        foreach($thisMonthDates as $index => $date){
            [$clockIn,$clockOut] = $patterns[$index];
            $this->createAttendance($user,$date,$clockIn,$clockOut);
        }
    }

    private function createAttendances(User $user)
    {
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::today()->subMonths($i);
            $dates = $this->weekdayDates($month,17);
            foreach($dates as $date) {
                $this->createAttendance($user,$date,'09:00','18:00');
            }
        }
    }

    private function createAttendance(User $user, Carbon $date, string $clockIn, string $clockOut)
    {
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $date->toDateString(),
            'clock_in' => $date->copy()->setTimeFromTimeString($clockIn),
            'clock_out' => $date->copy()->setTimeFromTimeString($clockOut),
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => $date->copy()->setTime(12, 0, 0),
            'break_end' => $date->copy()->setTime(13, 0, 0),
        ]);
    }

    private function weekdayDates(Carbon $month, int $count)
    {
        $dates = [];

        for (
            $date = $month->copy()->startOfMonth();
            $date->lte($month->copy()->endOfMonth());
            $date->addDay()
        ) {
            if ($date->isWeekday()) {
                $dates[] = $date->copy();
            }

            if (count($dates) === $count) {
                break;
            }
        }

        return $dates;
    }
}

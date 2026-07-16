<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DetailTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        return User::factory()->create([
            'name' => '山田 太郎',
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);
    }

    private function createAttendance(User $user): Attendance
    {
        return Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-10',
            'clock_in' => '2026-07-10 09:15:00',
            'clock_out' => '2026-07-10 18:30:00',
            'note' => '通常勤務',
        ]);
    }

    public function test_logged_in_user_name_is_displayed(): void
    {
        $user = $this->createUser();
        $this->createAttendance($user);

        $response = $this->actingAs($user)
            ->get('/attendance/detail/2026-07-10');

        $response->assertStatus(200);
        $response->assertSee('山田 太郎');
    }

    public function test_selected_date_is_displayed(): void
    {
        $user = $this->createUser();
        $this->createAttendance($user);

        $response = $this->actingAs($user)
            ->get('/attendance/detail/2026-07-10');

        $response->assertStatus(200);

        $response->assertSee('2026年');
        $response->assertSee('7月10日');
    }

    public function test_clock_in_and_clock_out_match_user_attendance(): void
    {
        $user = $this->createUser();
        $this->createAttendance($user);

        $response = $this->actingAs($user)
            ->get('/attendance/detail/2026-07-10');

        $response->assertStatus(200);
        $response->assertSee('09:15');
        $response->assertSee('18:30');
    }

    public function test_break_times_match_user_attendance(): void
    {
        $user = $this->createUser();
        $attendance = $this->createAttendance($user);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => '2026-07-10 12:00:00',
            'break_end' => '2026-07-10 13:00:00',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => '2026-07-10 15:00:00',
            'break_end' => '2026-07-10 15:15:00',
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/detail/2026-07-10');

        $response->assertStatus(200);

        $response->assertSee('12:00');
        $response->assertSee('13:00');
        $response->assertSee('15:00');
        $response->assertSee('15:15');
    }
}
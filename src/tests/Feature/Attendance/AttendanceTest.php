<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function createUser(): User
    {
        return User::factory()->create([
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);
    }

    private function createWorkingAttendance(User $user): Attendance
    {
        return Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now()->copy()->setTime(9, 0),
            'clock_out' => null,
        ]);
    }

    public function test_user_can_clock_in(): void
    {
        Carbon::setTestNow('2026-07-16 09:00:00');

        $user = $this->createUser();

        $this->actingAs($user)
            ->get('/attendance')
            ->assertStatus(200)
            ->assertSee('勤務外')
            ->assertSee('出勤');

        $this->actingAs($user)
            ->post('/attendance')
            ->assertStatus(302);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => '2026-07-16',
            'clock_in' => '2026-07-16 09:00:00',
        ]);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertStatus(200)
            ->assertSee('出勤中');
    }

    public function test_clock_in_button_is_not_displayed_after_clock_out(): void
    {
        Carbon::setTestNow('2026-07-16 18:00:00');

        $user = $this->createUser();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => '2026-07-16 09:00:00',
            'clock_out' => '2026-07-16 18:00:00',
        ]);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertStatus(200)
            ->assertSee('退勤済')
            ->assertDontSee('>出勤<', false);
    }

    public function test_clock_in_time_is_displayed_on_attendance_list(): void
    {
        Carbon::setTestNow('2026-07-16 09:15:00');

        $user = $this->createUser();

        $this->actingAs($user)
            ->post('/attendance')
            ->assertStatus(302);

        $this->actingAs($user)
            ->get('/attendance/list?month=2026-07')
            ->assertStatus(200)
            ->assertSee('07/16')
            ->assertSee('09:15');
    }

    public function test_user_can_start_break(): void
    {
        Carbon::setTestNow('2026-07-16 12:00:00');

        $user = $this->createUser();
        $attendance = $this->createWorkingAttendance($user);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertStatus(200)
            ->assertSee('出勤中')
            ->assertSee('休憩入');

        $this->actingAs($user)
            ->post('/attendance/break_start')
            ->assertStatus(302);

        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
            'break_start' => '2026-07-16 12:00:00',
            'break_end' => null,
        ]);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertStatus(200)
            ->assertSee('休憩中')
            ->assertSee('休憩戻');
    }

    public function test_user_can_take_break_multiple_times(): void
    {
        Carbon::setTestNow('2026-07-16 14:00:00');

        $user = $this->createUser();
        $attendance = $this->createWorkingAttendance($user);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => '2026-07-16 12:00:00',
            'break_end' => '2026-07-16 13:00:00',
        ]);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertStatus(200)
            ->assertSee('出勤中')
            ->assertSee('休憩入');

        $this->actingAs($user)
            ->post('/attendance/break_start')
            ->assertStatus(302);

        $this->assertDatabaseCount('break_times', 2);

        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
            'break_start' => '2026-07-16 14:00:00',
            'break_end' => null,
        ]);
    }

    public function test_user_can_end_break(): void
    {
        Carbon::setTestNow('2026-07-16 13:00:00');

        $user = $this->createUser();
        $attendance = $this->createWorkingAttendance($user);

        $breakTime = BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => '2026-07-16 12:00:00',
            'break_end' => null,
        ]);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertStatus(200)
            ->assertSee('休憩中')
            ->assertSee('休憩戻');

        $this->actingAs($user)
            ->post('/attendance/break_end')
            ->assertStatus(302);

        $this->assertDatabaseHas('break_times', [
            'id' => $breakTime->id,
            'break_end' => '2026-07-16 13:00:00',
        ]);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertStatus(200)
            ->assertSee('出勤中')
            ->assertSee('休憩入');
    }

    public function test_user_can_end_break_multiple_times(): void
    {
        Carbon::setTestNow('2026-07-16 15:00:00');

        $user = $this->createUser();
        $attendance = $this->createWorkingAttendance($user);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => '2026-07-16 12:00:00',
            'break_end' => '2026-07-16 13:00:00',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => '2026-07-16 14:30:00',
            'break_end' => null,
        ]);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertStatus(200)
            ->assertSee('休憩中')
            ->assertSee('休憩戻');

        $this->actingAs($user)
            ->post('/attendance/break_end')
            ->assertStatus(302);

        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
            'break_start' => '2026-07-16 14:30:00',
            'break_end' => '2026-07-16 15:00:00',
        ]);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertSee('出勤中')
            ->assertSee('休憩入');
    }

    public function test_break_time_is_displayed_on_attendance_list(): void
    {
        Carbon::setTestNow('2026-07-16 13:00:00');

        $user = $this->createUser();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-16',
            'clock_in' => '2026-07-16 09:00:00',
            'clock_out' => '2026-07-16 18:00:00',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => '2026-07-16 12:00:00',
            'break_end' => '2026-07-16 13:00:00',
        ]);

        $this->actingAs($user)
            ->get('/attendance/list?month=2026-07')
            ->assertStatus(200)
            ->assertSee('07/16')
            ->assertSee('1:00');
    }

    public function test_user_can_clock_out(): void
    {
        Carbon::setTestNow('2026-07-16 18:00:00');

        $user = $this->createUser();
        $attendance = $this->createWorkingAttendance($user);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertStatus(200)
            ->assertSee('出勤中')
            ->assertSee('退勤');

        $this->actingAs($user)
            ->post('/attendance/clock_out')
            ->assertStatus(302);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_out' => '2026-07-16 18:00:00',
        ]);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertStatus(200)
            ->assertSee('退勤済')
            ->assertDontSee('>出勤<', false);
    }

    public function test_clock_out_time_is_displayed_on_attendance_list(): void
    {
        $user = $this->createUser();

        Carbon::setTestNow('2026-07-16 09:00:00');

        $this->actingAs($user)
            ->post('/attendance')
            ->assertStatus(302);

        Carbon::setTestNow('2026-07-16 18:10:00');

        $this->actingAs($user)
            ->post('/attendance/clock_out')
            ->assertStatus(302);

        $this->actingAs($user)
            ->get('/attendance/list?month=2026-07')
            ->assertStatus(200)
            ->assertSee('07/16')
            ->assertSee('09:00')
            ->assertSee('18:10');
    }
}

<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceStatusTest extends TestCase
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

    public function test_current_date_and_time(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 7, 15, 14, 30, 0)
        );

        $user = $this->createUser();

        $response = $this
            ->actingAs($user)
            ->get('/attendance');

        $response->assertStatus(200);

        $response->assertSee('2026年7月15日');

        $response->assertSee('14:30');
    }

    public function test_status_is_off_work(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 7, 15, 9, 0, 0)
        );

        $user = $this->createUser();

        $response = $this
            ->actingAs($user)
            ->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('勤務外');
    }

    public function test_status_is_working(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 7, 15, 10, 0, 0)
        );

        $user = $this->createUser();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now()->copy()->setTime(9, 0),
            'clock_out' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('出勤中');
    }

    public function test_status_is_on_break(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 7, 15, 12, 30, 0)
        );

        $user = $this->createUser();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now()->copy()->setTime(9, 0),
            'clock_out' => null,
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => now()->copy()->setTime(12, 0),
            'break_end' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('休憩中');
    }

    public function test_status_is_after_work(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 7, 15, 18, 30, 0)
        );

        $user = $this->createUser();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now()->copy()->setTime(9, 0),
            'clock_out' => now()->copy()->setTime(18, 0),
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('退勤済');
    }


}

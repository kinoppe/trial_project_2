<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function createUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'is_admin' => false,
            'email_verified_at' => now(),
        ], $attributes));
    }

    private function createAttendance(
        User $user,
        string $date,
        string $clockIn,
        string $clockOut
    ): Attendance {
        return Attendance::create([
            'user_id' => $user->id,
            'work_date' => $date,
            'clock_in' => "{$date} {$clockIn}:00",
            'clock_out' => "{$date} {$clockOut}:00",
        ]);
    }

    public function test_all_own_attendance_records_are_displayed(): void
    {
        Carbon::setTestNow('2026-07-16 10:00:00');

        $user = $this->createUser();
        $otherUser = $this->createUser();

        $attendance1 = $this->createAttendance(
            $user,
            '2026-07-01',
            '09:00',
            '18:00'
        );

        $attendance2 = $this->createAttendance(
            $user,
            '2026-07-02',
            '09:15',
            '18:30'
        );

        $this->createAttendance(
            $otherUser,
            '2026-07-03',
            '08:30',
            '17:30'
        );

        BreakTime::create([
            'attendance_id' => $attendance1->id,
            'break_start' => '2026-07-01 12:00:00',
            'break_end' => '2026-07-01 13:00:00',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance2->id,
            'break_start' => '2026-07-02 12:15:00',
            'break_end' => '2026-07-02 13:00:00',
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/list?month=2026-07');

        $response->assertStatus(200);

        $response->assertSee('07/01');
        $response->assertSee('09:00');
        $response->assertSee('18:00');

        $response->assertSee('07/02');
        $response->assertSee('09:15');
        $response->assertSee('18:30');

        $response->assertDontSee('08:30');
        $response->assertDontSee('17:30');
    }

    public function test_current_month_is_displayed_on_attendance_list(): void
    {
        Carbon::setTestNow('2026-07-16 10:00:00');

        $user = $this->createUser();

        $response = $this->actingAs($user)
            ->get('/attendance/list');

        $response->assertStatus(200);

        $response->assertSee('2026/07');
    }

    public function test_previous_month_attendance_is_displayed(): void
    {
        Carbon::setTestNow('2026-07-16 10:00:00');

        $user = $this->createUser();

        $this->createAttendance(
            $user,
            '2026-06-10',
            '09:10',
            '18:10'
        );

        $this->createAttendance(
            $user,
            '2026-07-10',
            '10:20',
            '19:20'
        );

        $response = $this->actingAs($user)
            ->get('/attendance/list?month=2026-06');

        $response->assertStatus(200);
        $response->assertSee('2026/06');
        $response->assertSee('06/10');
        $response->assertSee('09:10');
        $response->assertSee('18:10');

        $response->assertDontSee('10:20');
        $response->assertDontSee('19:20');
    }

    public function test_next_month_attendance_is_displayed(): void
    {
        Carbon::setTestNow('2026-07-16 10:00:00');

        $user = $this->createUser();

        $this->createAttendance(
            $user,
            '2026-07-10',
            '09:00',
            '18:00'
        );

        $this->createAttendance(
            $user,
            '2026-08-10',
            '09:30',
            '18:30'
        );

        $response = $this->actingAs($user)
            ->get('/attendance/list?month=2026-08');

        $response->assertStatus(200);
        $response->assertSee('2026/08');
        $response->assertSee('08/10');
        $response->assertSee('09:30');
        $response->assertSee('18:30');

        $response->assertDontSee('09:00');
        $response->assertDontSee('18:00');
    }

    public function test_detail_link_navigates_to_attendance_detail_page(): void
    {
        Carbon::setTestNow('2026-07-16 10:00:00');

        $user = $this->createUser();

        $this->createAttendance(
            $user,
            '2026-07-10',
            '09:00',
            '18:00'
        );

        $listResponse = $this->actingAs($user)
            ->get('/attendance/list?month=2026-07');

        $listResponse->assertStatus(200);
        $listResponse->assertSee('詳細');
        $listResponse->assertSee(
            '/attendance/detail/2026-07-10',
            false
        );

        $detailResponse = $this->actingAs($user)
            ->get('/attendance/detail/2026-07-10');

        $detailResponse->assertStatus(200);
    }
}
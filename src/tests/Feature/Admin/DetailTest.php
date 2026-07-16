<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DetailTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $user;
    private Attendance $attendance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();

        $this->user = User::factory()->general()->create([
            'name' => '山田太郎',
        ]);

        $this->attendance = Attendance::create([
            'user_id' => $this->user->id,
            'work_date' => '2026-07-16',
            'clock_in' => '2026-07-16 09:00:00',
            'clock_out' => '2026-07-16 18:00:00',
            'note' => '通常勤務',
        ]);

        BreakTime::create([
            'attendance_id' => $this->attendance->id,
            'break_start' => '2026-07-16 12:00:00',
            'break_end' => '2026-07-16 13:00:00',
        ]);
    }

    public function test_selected_attendance_information_is_displayed(): void
    {
        $response = $this
            ->actingAs($this->admin)
            ->get('/admin/attendance/2026-07-16?user_id='. $this->user->id);

        $response->assertStatus(200);

        $response->assertSee('山田太郎');
        $response->assertSee('2026年');
        $response->assertSee('7月16日');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('12:00');
        $response->assertSee('13:00');
        $response->assertSee('通常勤務');
    }

    public function test_error_is_displayed_when_clock_in_is_after_clock_out(): void
    {
        $response = $this
            ->actingAs($this->admin)
            ->from('/admin/attendance/2026-07-16')
            ->post('/admin/attendance/' . $this->attendance->id, [
                'clock_in' => '19:00',
                'clock_out' => '18:00',
                'breaks' => [
                    [
                        'break_start' => '12:00',
                        'break_end' => '13:00',
                    ],
                ],
                'note' => '修正',
            ]);

        $response->assertRedirect('/admin/attendance/2026-07-16');

        $response->assertSessionHasErrors([
            'clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function test_error_is_displayed_when_break_start_is_after_clock_out(): void
    {
        $response = $this
            ->actingAs($this->admin)
            ->from('/admin/attendance/2026-07-16')
            ->post('/admin/attendance/' . $this->attendance->id, [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'breaks' => [
                    [
                        'break_start' => '19:00',
                        'break_end' => '20:00',
                    ],
                ],
                'note' => '修正',
            ]);

        $response->assertSessionHasErrors([
            'breaks.0.break_start' => '休憩時間が不適切な値です',
        ]);
    }

    public function test_error_is_displayed_when_break_end_is_after_clock_out(): void
    {
        $response = $this
            ->actingAs($this->admin)
            ->from('/admin/attendance/2026-07-16')
            ->post('/admin/attendance/' . $this->attendance->id, [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'breaks' => [
                    [
                        'break_start' => '17:00',
                        'break_end' => '19:00',
                    ],
                ],
                'note' => '修正',
            ]);

        $response->assertSessionHasErrors([
            'breaks.0.break_end'
                => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function test_error_is_displayed_when_note_is_empty(): void
    {
        $response = $this
            ->actingAs($this->admin)
            ->from('/admin/attendance/2026-07-16')
            ->post('/admin/attendance/' . $this->attendance->id, [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'breaks' => [
                    [
                        'break_start' => '12:00',
                        'break_end' => '13:00',
                    ],
                ],
                'note' => '',
            ]);

        $response->assertSessionHasErrors([
            'note' => '備考を記入してください',
        ]);
    }
}
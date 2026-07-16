<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(
            Carbon::create(2026, 7, 16, 10, 0, 0)
        );
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_all_users_attendance_information_is_displayed(): void
    {
        $admin = User::factory()->admin()->create();

        $user1 = User::factory()->general()->create([
            'name' => '山田太郎',
        ]);

        $user2 = User::factory()->general()->create([
            'name' => '佐藤花子',
        ]);

        $attendance1 = Attendance::create([
            'user_id' => $user1->id,
            'work_date' => '2026-07-16',
            'clock_in' => '2026-07-16 09:00:00',
            'clock_out' => '2026-07-16 18:00:00',
            'note' => '通常勤務',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance1->id,
            'break_start' => '2026-07-16 12:00:00',
            'break_end' => '2026-07-16 13:00:00',
        ]);

        Attendance::create([
            'user_id' => $user2->id,
            'work_date' => '2026-07-16',
            'clock_in' => '2026-07-16 10:00:00',
            'clock_out' => '2026-07-16 19:00:00',
            'note' => '時差勤務',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get('/admin/attendance/list?date=2026-07-16');

        $response->assertStatus(200);

        $response->assertSee('山田太郎');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');
        $response->assertSee('8:00');

        $response->assertSee('佐藤花子');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
    }

    public function test_current_date_is_displayed(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this
            ->actingAs($admin)
            ->get('/admin/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('2026/07/16');
    }

    public function test_previous_day_link_is_displayed(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this
            ->actingAs($admin)
            ->get('/admin/attendance/list?date=2026-07-16');

        $response->assertStatus(200);

        $response->assertSee(
            '/admin/attendance/list?date=2026-07-15',
            false
        );
    }

    public function test_next_day_link_is_displayed(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this
            ->actingAs($admin)
            ->get('/admin/attendance/list?date=2026-07-16');

        $response->assertStatus(200);

        $response->assertSee(
            '/admin/attendance/list?date=2026-07-17',
            false
        );
    }
}
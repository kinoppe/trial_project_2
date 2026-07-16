<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffTest extends TestCase
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

    public function test_all_general_users_are_displayed(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => '管理者',
            'email' => 'admin@example.com',
        ]);

        $user1 = User::factory()->general()->create([
            'name' => '山田太郎',
            'email' => 'yamada@example.com',
        ]);

        $user2 = User::factory()->general()->create([
            'name' => '佐藤花子',
            'email' => 'sato@example.com',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get('/admin/staff/list');

        $response->assertStatus(200);

        $response->assertSee($user1->name);
        $response->assertSee($user1->email);
        $response->assertSee($user2->name);
        $response->assertSee($user2->email);

        $response->assertDontSee('admin@example.com');
    }

    public function test_selected_users_attendance_is_displayed(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->general()->create([
            'name' => '山田太郎',
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-10',
            'clock_in' => '2026-07-10 09:00:00',
            'clock_out' => '2026-07-10 18:00:00',
            'note' => '通常勤務',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(
                "/admin/attendance/staff/{$user->id}?month=2026-07"
            );

        $response->assertStatus(200);
        $response->assertSee('山田太郎');
        $response->assertSee('07/10');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    public function test_previous_month_link_is_displayed(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->general()->create();

        $response = $this
            ->actingAs($admin)
            ->get(
                "/admin/attendance/staff/{$user->id}?month=2026-07"
            );

        $response->assertSee(
            "/admin/attendance/staff/{$user->id}?month=2026-06",
            false
        );
    }

    public function test_next_month_link_is_displayed(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->general()->create();

        $response = $this
            ->actingAs($admin)
            ->get(
                "/admin/attendance/staff/{$user->id}?month=2026-07"
            );

        $response->assertSee(
            "/admin/attendance/staff/{$user->id}?month=2026-08",
            false
        );
    }

    public function test_detail_link_points_to_selected_date(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->general()->create();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-10',
            'clock_in' => '2026-07-10 09:00:00',
            'clock_out' => '2026-07-10 18:00:00',
            'note' => '通常勤務',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(
                "/admin/attendance/staff/{$user->id}?month=2026-07"
            );

        $response->assertSee(
            '/admin/attendance/2026-07-10',
            false
        );
    }
}
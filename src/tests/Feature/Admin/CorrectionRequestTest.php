<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CorrectionRequestTest extends TestCase
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
            'note' => '修正前',
        ]);
    }

    /**
     * 全ユーザーの承認待ち申請が表示される
     */
    public function test_all_pending_requests_are_displayed(): void
    {
        $pending = AttendanceCorrectionRequest::create([
            'attendance_id' => $this->attendance->id,
            'request_clock_in' => '2026-07-16 10:00:00',
            'request_clock_out' => '2026-07-16 19:00:00',
            'note' => '電車遅延',
            'status' => 'pending',
        ]);

        $approved = AttendanceCorrectionRequest::create([
            'attendance_id' => $this->attendance->id,
            'request_clock_in' => '2026-07-15 09:00:00',
            'request_clock_out' => '2026-07-15 18:00:00',
            'note' => '承認済み申請',
            'status' => 'approved',
        ]);

        $response = $this
            ->actingAs($this->admin)
            ->get('/stamp_correction_request/list?status=pending');

        $response->assertStatus(200);
        $response->assertSee('電車遅延');
        $response->assertDontSee('承認済み申請');
    }

    /**
     * 全ユーザーの承認済み申請が表示される
     */
    public function test_all_approved_requests_are_displayed(): void
    {
        AttendanceCorrectionRequest::create([
            'attendance_id' => $this->attendance->id,
            'request_clock_in' => '2026-07-16 10:00:00',
            'request_clock_out' => '2026-07-16 19:00:00',
            'note' => '未承認申請',
            'status' => 'pending',
        ]);

        AttendanceCorrectionRequest::create([
            'attendance_id' => $this->attendance->id,
            'request_clock_in' => '2026-07-16 08:30:00',
            'request_clock_out' => '2026-07-16 17:30:00',
            'note' => '承認済み申請',
            'status' => 'approved',
        ]);

        $response = $this
            ->actingAs($this->admin)
            ->get('/stamp_correction_request/list?status=approved');

        $response->assertStatus(200);
        $response->assertSee('承認済み申請');
        $response->assertDontSee('未承認申請');
    }

    /**
     * 修正申請の詳細が正しく表示される
     */
    public function test_request_detail_is_displayed_correctly(): void
    {
        $request = AttendanceCorrectionRequest::create([
            'attendance_id' => $this->attendance->id,
            'request_clock_in' => '2026-07-16 10:00:00',
            'request_clock_out' => '2026-07-16 19:00:00',
            'note' => '電車遅延のため',
            'status' => 'pending',
        ]);

        $response = $this
            ->actingAs($this->admin)
            ->get(
                '/stamp_correction_request/approve/' . $request->id
            );

        $response->assertStatus(200);
        $response->assertSee('山田太郎');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
        $response->assertSee('電車遅延のため');
    }

    /**
     * 承認後に申請状態と勤怠情報が更新される
     */
    public function test_request_is_approved_and_attendance_is_updated(): void
    {
        $request = AttendanceCorrectionRequest::create([
            'attendance_id' => $this->attendance->id,
            'request_clock_in' => '2026-07-16 10:00:00',
            'request_clock_out' => '2026-07-16 19:00:00',
            'note' => '電車遅延のため',
            'status' => 'pending',
        ]);

        $response = $this
            ->actingAs($this->admin)
            ->post(
                '/stamp_correction_request/approve/' . $request->id
            );

        $response->assertStatus(302);

        $this->assertDatabaseHas(
            'attendance_correction_requests',
            [
                'id' => $request->id,
                'status' => 'approved',
            ]
        );

        $this->assertDatabaseHas('attendances', [
            'id' => $this->attendance->id,
            'clock_in' => '2026-07-16 10:00:00',
            'clock_out' => '2026-07-16 19:00:00',
            'note' => '電車遅延のため',
        ]);
    }
}
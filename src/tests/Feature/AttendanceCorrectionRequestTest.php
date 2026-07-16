<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCorrectionRequestTest extends TestCase
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

    private function createAdmin(): User
    {
        return User::factory()->create([
            'name' => '管理者',
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);
    }

    private function createAttendance(User $user): Attendance
    {
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-10',
            'clock_in' => '2026-07-10 09:00:00',
            'clock_out' => '2026-07-10 18:00:00',
            'note' => '通常勤務',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => '2026-07-10 12:00:00',
            'break_end' => '2026-07-10 13:00:00',
        ]);

        return $attendance;
    }

    private function validRequestData(): array
    {
        return [
            'clock_in' => '09:30',
            'clock_out' => '18:30',
            'breaks' => [
                [
                    'break_start' => '12:15',
                    'break_end' => '13:15',
                ],
            ],
            'note' => '電車遅延のため修正',
        ];
    }

    public function test_error_is_displayed_when_clock_in_is_after_clock_out(): void
    {
        $user = $this->createUser();
        $this->createAttendance($user);

        $response = $this->actingAs($user)
            ->from('/attendance/detail/2026-07-10')
            ->post('/attendance/detail/2026-07-10', [
                'clock_in' => '19:00',
                'clock_out' => '18:00',
                'breaks' => [
                    [
                        'break_start' => '12:00',
                        'break_end' => '13:00',
                    ],
                ],
                'note' => '修正理由',
            ]);

        $response->assertRedirect(
            '/attendance/detail/2026-07-10'
        );

        $response->assertSessionHasErrors('clock_in');

        $this->followRedirects($response)
            ->assertSee('出勤時間もしくは退勤時間が不適切な値です');
    }

    public function test_error_is_displayed_when_break_start_is_after_clock_out(): void
    {
        $user = $this->createUser();
        $this->createAttendance($user);

        $response = $this->actingAs($user)
            ->from('/attendance/detail/2026-07-10')
            ->post('/attendance/detail/2026-07-10', [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'breaks' => [
                    [
                        'break_start' => '19:00',
                        'break_end' => '19:30',
                    ],
                ],
                'note' => '修正理由',
            ]);

        $response->assertRedirect(
            '/attendance/detail/2026-07-10'
        );

        $response->assertSessionHasErrors(
            'breaks.0.break_start'
        );

        $this->followRedirects($response)
            ->assertSee('休憩時間が不適切な値です');
    }

    public function test_error_is_displayed_when_break_end_is_after_clock_out(): void
    {
        $user = $this->createUser();
        $this->createAttendance($user);

        $response = $this->actingAs($user)
            ->from('/attendance/detail/2026-07-10')
            ->post('/attendance/detail/2026-07-10', [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'breaks' => [
                    [
                        'break_start' => '17:30',
                        'break_end' => '18:30',
                    ],
                ],
                'note' => '修正理由',
            ]);

        $response->assertRedirect(
            '/attendance/detail/2026-07-10'
        );

        $response->assertSessionHasErrors(
            'breaks.0.break_end'
        );

        $this->followRedirects($response)
            ->assertSee(
                '休憩時間もしくは退勤時間が不適切な値です'
            );
    }

    public function test_error_is_displayed_when_note_is_empty(): void
    {
        $user = $this->createUser();
        $this->createAttendance($user);

        $response = $this->actingAs($user)
            ->from('/attendance/detail/2026-07-10')
            ->post('/attendance/detail/2026-07-10', [
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

        $response->assertRedirect(
            '/attendance/detail/2026-07-10'
        );

        $response->assertSessionHasErrors('note');

        $this->followRedirects($response)
            ->assertSee('備考を記入してください');
    }

    public function test_attendance_correction_request_is_created(): void
    {
        Carbon::setTestNow('2026-07-16 10:00:00');

        $user = $this->createUser();
        $attendance = $this->createAttendance($user);

        $response = $this->actingAs($user)
            ->post(
                '/attendance/detail/2026-07-10',
                $this->validRequestData()
            );

        $response->assertStatus(302);

        $this->assertDatabaseHas(
            'attendance_correction_requests',
            [
                'attendance_id' => $attendance->id,
                'request_clock_in' => '2026-07-10 09:30:00',
                'request_clock_out' => '2026-07-10 18:30:00',
                'note' => '電車遅延のため修正',
                'status' => 'pending',
            ]
        );

        $request = AttendanceCorrectionRequest::where(
            'attendance_id',
            $attendance->id
        )->firstOrFail();

        $this->assertDatabaseHas(
            'attendance_correction_breaks',
            [
                'attendance_correction_request_id' => $request->id,
                'break_start' => '2026-07-10 12:15:00',
                'break_end' => '2026-07-10 13:15:00',
            ]
        );
    }

    public function test_request_is_displayed_on_admin_request_list(): void
    {
        $user = $this->createUser([
            'name' => '一般ユーザーA',
        ]);

        $admin = $this->createAdmin();
        $attendance = $this->createAttendance($user);

        $request = AttendanceCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'request_clock_in' => '2026-07-10 09:30:00',
            'request_clock_out' => '2026-07-10 18:30:00',
            'note' => '電車遅延のため修正',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)
            ->get('/stamp_correction_request/list?status=pending');

        $response->assertStatus(200);
        $response->assertSee('一般ユーザーA');
        $response->assertSee('電車遅延のため修正');
        $response->assertSee('承認待ち');

        $response->assertSee(
            "/stamp_correction_request/approve/{$request->id}",
            false
        );
    }

    public function test_all_own_pending_requests_are_displayed(): void
    {
        $user = $this->createUser([
            'name' => 'テストユーザー',
        ]);

        $otherUser = $this->createUser();

        $attendance1 = $this->createAttendance($user);

        $attendance2 = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-11',
            'clock_in' => '2026-07-11 09:00:00',
            'clock_out' => '2026-07-11 18:00:00',
            'note' => '通常勤務',
        ]);

        $otherAttendance = Attendance::create([
            'user_id' => $otherUser->id,
            'work_date' => '2026-07-12',
            'clock_in' => '2026-07-12 08:00:00',
            'clock_out' => '2026-07-12 17:00:00',
            'note' => '別ユーザー',
        ]);

        AttendanceCorrectionRequest::create([
            'attendance_id' => $attendance1->id,
            'request_clock_in' => '2026-07-10 09:10:00',
            'request_clock_out' => '2026-07-10 18:10:00',
            'note' => '自分の申請1',
            'status' => 'pending',
        ]);

        AttendanceCorrectionRequest::create([
            'attendance_id' => $attendance2->id,
            'request_clock_in' => '2026-07-11 09:20:00',
            'request_clock_out' => '2026-07-11 18:20:00',
            'note' => '自分の申請2',
            'status' => 'pending',
        ]);

        AttendanceCorrectionRequest::create([
            'attendance_id' => $otherAttendance->id,
            'request_clock_in' => '2026-07-12 08:30:00',
            'request_clock_out' => '2026-07-12 17:30:00',
            'note' => '他人の申請',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)
            ->get('/stamp_correction_request/list?status=pending');

        $response->assertStatus(200);

        $response->assertSee('自分の申請1');
        $response->assertSee('自分の申請2');

        $response->assertDontSee('他人の申請');
    }

    public function test_approved_requests_are_displayed(): void
    {
        $user = $this->createUser();
        $attendance = $this->createAttendance($user);

        AttendanceCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'request_clock_in' => '2026-07-10 09:30:00',
            'request_clock_out' => '2026-07-10 18:30:00',
            'note' => '承認された修正申請',
            'status' => 'approved',
        ]);

        AttendanceCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'request_clock_in' => '2026-07-10 09:15:00',
            'request_clock_out' => '2026-07-10 18:15:00',
            'note' => 'まだ承認されていない申請',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)
            ->get('/stamp_correction_request/list?status=approved');

        $response->assertStatus(200);
        $response->assertSee('承認された修正申請');

        $response->assertDontSee('まだ承認されていない申請');
    }

    public function test_request_detail_link_navigates_to_attendance_detail(): void
    {
        $user = $this->createUser();
        $attendance = $this->createAttendance($user);

        AttendanceCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'request_clock_in' => '2026-07-10 09:30:00',
            'request_clock_out' => '2026-07-10 18:30:00',
            'note' => '詳細画面確認用',
            'status' => 'pending',
        ]);

        $listResponse = $this->actingAs($user)
            ->get('/stamp_correction_request/list?status=pending');

        $listResponse->assertStatus(200);
        $listResponse->assertSee('詳細');
        $listResponse->assertSee(
            '/attendance/detail/2026-07-10',
            false
        );

        $detailResponse = $this->actingAs($user)
            ->get('/attendance/detail/2026-07-10');

        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('詳細画面確認用');
    }
}
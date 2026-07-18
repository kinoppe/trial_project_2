<?php

namespace Tests\Feature\Api;

use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\BreakTime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceApiTest extends TestCase
{
    use RefreshDatabase;

    private function createAttendance(
        User $user,
        string $date = '2026-07-01'
    ): Attendance {
        return Attendance::create([
            'user_id' => $user->id,
            'work_date' => $date,
            'clock_in' => $date . ' 09:00:00',
            'clock_out' => $date . ' 18:00:00',
            'note' => '通常勤務',
        ]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'date' => '2026-07-02',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '通常勤務',
        ], $overrides);
    }

    public function test_attendance_records_can_be_retrieved(): void
    {
        $user = User::factory()->create();

        $this->createAttendance($user, '2026-07-01');
        $this->createAttendance($user, '2026-07-02');

        $response = $this->getJson('/api/v1/attendance-records');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'user_name',
                        'date',
                        'clock_in',
                        'clock_out',
                        'total_time',
                        'total_break_time',
                        'comment',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);

        $response->assertJsonPath('meta.total', 2);
    }

    public function test_attendance_record_detail_can_be_retrieved(): void
    {
        $user = User::factory()->create([
            'name' => 'テストユーザー',
        ]);

        $attendance = $this->createAttendance($user);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => '2026-07-01 12:00:00',
            'break_end' => '2026-07-01 13:00:00',
        ]);

        AttendanceCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'request_clock_in' => '2026-07-01 09:30:00',
            'request_clock_out' => '2026-07-01 18:30:00',
            'note' => '打刻時間を修正します',
            'status' => 'pending',
        ]);

        $response = $this->getJson(
            "/api/v1/attendance-records/{$attendance->id}"
        );

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'user_name',
                    'date',
                    'clock_in',
                    'clock_out',
                    'total_time',
                    'total_break_time',
                    'comment',
                    'user' => [
                        'id',
                        'name',
                    ],
                    'breaks' => [
                        '*' => [
                            'id',
                            'break_in',
                            'break_out',
                        ],
                    ],
                    'applications' => [
                        '*' => [
                            'id',
                            'status',
                            'comment',
                        ],
                    ],
                ],
            ])
            ->assertJsonPath('data.id', $attendance->id)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.name', 'テストユーザー')
            ->assertJsonPath('data.breaks.0.break_in', '12:00:00')
            ->assertJsonPath('data.breaks.0.break_out', '13:00:00');
    }

    public function test_nonexistent_attendance_record_returns_404(): void
    {
        $response = $this->getJson(
            '/api/v1/attendance-records/99999'
        );

        $response
            ->assertNotFound()
            ->assertExactJson([
                'error' => '勤怠情報が見つかりませんでした。',
            ]);
    }

    public function test_authenticated_user_can_create_attendance_record(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson(
            '/api/v1/attendance-records',
            $this->validPayload()
        );

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'date',
                    'clock_in',
                    'clock_out',
                    'comment',
                ],
            ]);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => '2026-07-02',
            'note' => '通常勤務',
        ]);

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', '2026-07-02')
            ->firstOrFail();
    }

    public function test_create_attendance_returns_japanese_validation_errors(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson(
            '/api/v1/attendance-records',
            []
        );

        $response
            ->assertUnprocessable()
            ->assertJsonStructure([
                'message',
                'errors',
            ])
            ->assertJsonValidationErrors([
                'date',
                'clock_in',
            ]);

        $errors = $response->json('errors');

        $this->assertNotEmpty($errors['date']);
        $this->assertNotEmpty($errors['clock_in']);

        $this->assertSame(
            '勤怠日は必須です。',
            $errors['date'][0]
        );

        $this->assertSame(
            '出勤時刻は必須です。',
            $errors['clock_in'][0]
        );
    }

    public function test_authenticated_user_can_update_own_attendance_record(): void
    {
        $user = User::factory()->create();
        $attendance = $this->createAttendance($user);

        Sanctum::actingAs($user);

        $response = $this->putJson(
            "/api/v1/attendance-records/{$attendance->id}",
            $this->validPayload([
                'date' => '2026-07-01',
                'clock_in' => '08:30:00',
                'clock_out' => '17:30:00',
                'comment' => '更新後の備考',
            ])
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $attendance->id)
            ->assertJsonPath('data.clock_in', '08:30:00')
            ->assertJsonPath('data.clock_out', '17:30:00')
            ->assertJsonPath('data.comment', '更新後の備考');

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'user_id' => $user->id,
            'note' => '更新後の備考',
        ]);
    }

    public function test_updating_nonexistent_attendance_record_returns_404(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->putJson(
            '/api/v1/attendance-records/99999',
            $this->validPayload()
        );

        $response
            ->assertNotFound()
            ->assertExactJson([
                'error' => '勤怠情報が見つかりませんでした。',
            ]);
    }

    public function test_authenticated_user_can_delete_own_attendance_record(): void
    {
        $user = User::factory()->create();
        $attendance = $this->createAttendance($user);

        Sanctum::actingAs($user);

        $response = $this->deleteJson(
            "/api/v1/attendance-records/{$attendance->id}"
        );

        $response->assertNoContent();

        $this->assertDatabaseMissing('attendances', [
            'id' => $attendance->id,
        ]);
    }

    public function test_deleting_nonexistent_attendance_record_returns_404(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->deleteJson(
            '/api/v1/attendance-records/99999'
        );

        $response
            ->assertNotFound()
            ->assertExactJson([
                'error' => '勤怠情報が見つかりませんでした。',
            ]);
    }

    public function test_unauthenticated_user_cannot_create_attendance_record(): void
    {
        $response = $this->postJson(
            '/api/v1/attendance-records',
            $this->validPayload()
        );

        $response
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_unauthenticated_user_cannot_update_attendance_record(): void
    {
        $user = User::factory()->create();
        $attendance = $this->createAttendance($user);

        $response = $this->putJson(
            "/api/v1/attendance-records/{$attendance->id}",
            $this->validPayload()
        );

        $response
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_unauthenticated_user_cannot_delete_attendance_record(): void
    {
        $user = User::factory()->create();
        $attendance = $this->createAttendance($user);

        $response = $this->deleteJson(
            "/api/v1/attendance-records/{$attendance->id}"
        );

        $response
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_user_cannot_update_another_users_attendance_record(): void
    {
        $loginUser = User::factory()->create();
        $otherUser = User::factory()->create();

        $attendance = $this->createAttendance($otherUser);

        Sanctum::actingAs($loginUser);

        $response = $this->putJson(
            "/api/v1/attendance-records/{$attendance->id}",
            $this->validPayload([
                'date' => '2026-07-01',
            ])
        );

        $response
            ->assertForbidden()
            ->assertExactJson([
                'error' => 'この操作を実行する権限がありません。',
            ]);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'note' => '通常勤務',
        ]);
    }

    public function test_user_cannot_delete_another_users_attendance_record(): void
    {
        $loginUser = User::factory()->create();
        $otherUser = User::factory()->create();

        $attendance = $this->createAttendance($otherUser);

        Sanctum::actingAs($loginUser);

        $response = $this->deleteJson(
            "/api/v1/attendance-records/{$attendance->id}"
        );

        $response
            ->assertForbidden()
            ->assertExactJson([
                'error' => 'この操作を実行する権限がありません。',
            ]);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
        ]);
    }
}
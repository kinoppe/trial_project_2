<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_attendance_report()
    {
        $response = $this->get('/attendance/report');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_statistics_are_calculated_correctly()
    {
        Carbon::setTestNow(Carbon::create(2026, 7, 17, 10, 0, 0));

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance1 = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-01',
            'clock_in' => '2026-07-01 09:00:00',
            'clock_out' => '2026-07-01 18:00:00',
            'note' => '通常勤務',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance1->id,
            'break_start' => '2026-07-01 12:00:00',
            'break_end' => '2026-07-01 13:00:00',
        ]);

        $attendance2 = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-02',
            'clock_in' => '2026-07-02 09:00:00',
            'clock_out' => '2026-07-02 20:00:00',
            'note' => '残業あり',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance2->id,
            'break_start' => '2026-07-02 12:00:00',
            'break_end' => '2026-07-02 13:00:00',
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/attendance/report');

        $response->assertStatus(200);

        $response->assertViewHas('totalWork', '18h 0m');
        $response->assertViewHas('totalOver', '2h 0m');
        $response->assertViewHas('averageWork', '9h 0m');

        $response->assertViewHas('monthlyReports', function ($monthlyReports) {
            $julyReport = collect($monthlyReports)->firstWhere(
                'month',
                '2026-07'
            );

            return $julyReport !== null
                && $julyReport['work_time'] === '18h 0m'
                && $julyReport['over_time'] === '2h 0m';
        });

        $response->assertViewHas('lateCount', 0);
        $response->assertViewHas('earlyCount', 0);
        $response->assertViewHas('longWorkCount', 0);

        Carbon::setTestNow();
    }

    public function test_user_without_attendance_records_is_processed_safely()
    {
        Carbon::setTestNow(Carbon::create(2026, 7, 17, 10, 0, 0));

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/attendance/report');

        $response->assertStatus(200);

        $response->assertViewHas('totalWork', '0h 0m');
        $response->assertViewHas('totalOver', '0h 0m');
        $response->assertViewHas('averageWork', '0h 0m');

        $response->assertViewHas('monthlyReports', function ($monthlyReports) {
            return collect($monthlyReports)->count() === 6
                && collect($monthlyReports)->every(function ($report) {
                    return $report['work_time'] === '0h 0m'
                        && $report['over_time'] === '0h 0m';
                });
        });

        $response->assertViewHas('lateCount', 0);
        $response->assertViewHas('earlyCount', 0);
        $response->assertViewHas('longWorkCount', 0);

        Carbon::setTestNow();
    }
}
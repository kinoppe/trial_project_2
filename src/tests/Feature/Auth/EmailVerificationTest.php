<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_verification_email_is_sent_after_registration()
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'test@example.com')->first();

        $this->assertNotNull($user);

        Notification::assertSentTo(
            $user,
            VerifyEmail::class
        );
    }

    public function test_verification_button_links_to_mail_site()
    {
        $user = User::factory()->unverified()->create();

        $response = $this
            ->actingAs($user)
            ->get('/email/verify');

        $response->assertStatus(200);

        $response->assertSee('認証はこちらから');

        $response->assertSee(
            'http://localhost:8025',
            false
        );
    }

    public function test_user_is_redirected_to_attendance_page_after_verification()
    {
        Event::fake();

        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        $response = $this
            ->actingAs($user)
            ->get($verificationUrl);

        $response->assertRedirect('/attendance?verified=1');

        $this->assertTrue(
            $user->fresh()->hasVerifiedEmail()
        );

        Event::assertDispatched(Verified::class);
    }
}
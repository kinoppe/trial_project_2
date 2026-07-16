<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ], $overrides);
    }

    public function test_name_is_required(): void
    {
        $response = $this->post('/register', $this->validData([
            'name' => '',
        ]));

        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください',
        ]);
    }

    public function test_email_is_required(): void
    {
        $response = $this->post('/register', $this->validData([
            'email' => '',
        ]));

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    public function test_password_must_be_at_least_8_characters(): void
    {
        $response = $this->post('/register', $this->validData([
            'password' => 'pass123',
            'password_confirmation' => 'pass123',
        ]));

        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください',
        ]);
    }

    public function test_password_confirmation_does_not_match(): void
    {
        $response = $this->post('/register', $this->validData([
            'password_confirmation' => 'different-password',
        ]));

        $response->assertSessionHasErrors([
            'password' => 'パスワードと一致しません',
        ]);
    }

    public function test_password_is_required(): void
    {
        $response = $this->post('/register', $this->validData([
            'password' => '',
            'password_confirmation' => '',
        ]));

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    public function test_user_can_register(): void
    {
        $response = $this->post('/register', $this->validData());

        $this->assertDatabaseHas('users', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'is_admin' => false,
        ]);

        $user = \App\Models\User::where('email', 'test@example.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue(
            Hash::check('password', $user->password)
        );

        $response->assertSessionHasNoErrors();
    }
}

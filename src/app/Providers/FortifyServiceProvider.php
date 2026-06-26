<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\RegisterRequest;
use Laravel\Fortify\Http\Requests\RegisterRequest as FortifyRegisterRequest;
use App\Http\Requests\LoginRequest;
use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Fortify\Contracts\LoginResponse;


class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', function ($request) {
            return Limit::perMinute(1000)->by($request->email.$request->ip());
        });

        $this->app->bind(
            FortifyLoginRequest::class,
            LoginRequest::class
        );

        $this->app->bind(
            FortifyRegisterRequest::class,
            RegisterRequest::class
        );

        Fortify::verifyEmailView(function () {
            return view('auth.verify-email');
        });

        $this->app->singleton(LoginResponse::class, function () {
            return new class implements LoginResponse {
                public function toResponse($request)
                {
                    if (auth()->user()->is_admin) {
                        return redirect('/admin/attendance/list');
                    }

                    return redirect('/attendance');
                }
            };
        });

        Fortify::createUsersUsing(CreateNewUser::class);

        Fortify::registerView(function(){
            return view('auth.register');
        });

        Fortify::loginView(function(){
            return view('auth.login');
        });

        Fortify::authenticateUsing(function ($request) {
            $user = User::where('email', $request->email)->first();

            if (! $user || ! Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages([
                    'email' => ['ログイン情報が登録されていません'],
                ]);
            }

            if ($request->is('admin/login') && ! $user->is_admin) {
                throw ValidationException::withMessages([
                    'email' => ['管理者アカウントではありません'],
                ]);
            }

            if ($request->is('login') && $user->is_admin) {
                throw ValidationException::withMessages([
                    'email' => ['一般ユーザーとしてログインできません'],
                ]);
            }

            return $user;
        });
    }
}

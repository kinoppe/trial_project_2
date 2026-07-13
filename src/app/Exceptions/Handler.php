<?php

namespace App\Exceptions;

use App\Models\Attendance;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception)
    {
        if ($request->is('api/*')) {
            if (
                $exception instanceof ModelNotFoundException
                && $exception->getModel() === Attendance::class
            ) {
                return response()->json([
                    'error' => '勤怠情報が見つかりませんでした。',
                ], 404);
            }

            if ($exception instanceof AuthorizationException) {
                return response()->json([
                    'error' => 'この操作を実行する権限がありません。',
                ], 403);
            }
        }

        return parent::render($request, $exception);
    }

    protected function unauthenticated(
        $request,
        AuthenticationException $exception
    ) {
        if ($request->is('api/*')) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        return parent::unauthenticated($request, $exception);
    }
}

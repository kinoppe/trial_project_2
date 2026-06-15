<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceCorrectionRequestController;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/admin/login', function () {
    return view('admin.login');
})->middleware('guest');

Route::post('/admin/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware('guest');

Route::middleware('auth')->group(function () {
    Route::get('/attendance', [AttendanceController::class,'index']);
    Route::post('/attendance', [AttendanceController::class,'clockIn']);
    Route::post('/attendance/clock_out', [AttendanceController::class,'clockOut']);
    Route::post('/attendance/break_start', [AttendanceController::class,'breakStart']);
    Route::post('/attendance/break_end', [AttendanceController::class,'breakEnd']);
    Route::get('/attendance/list', [AttendanceController::class,'list']);
    Route::get('/attendance/detail/{date}', [AttendanceController::class,'show']);
    Route::post('/attendance/detail/{date}', [AttendanceCorrectionRequestController::class,'store']);
    Route::get('/stamp_correction_request/list', [AttendanceCorrectionRequestController::class,'index']);
});
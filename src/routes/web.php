<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceCorrectionRequestController;
use App\Http\Controllers\AdminAttendanceController;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\AdminStaffController;


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

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'list']);
    Route::get('/admin/attendance/{id}', [AdminAttendanceController::class, 'show']);
    Route::post('/admin/attendance/{id}', [AdminAttendanceController::class, 'update']);
    Route::get('/admin/staff/list', [AdminStaffController::class, 'index']);
    Route::get('/admin/attendance/staff/{id}', [AdminStaffController::class, 'showAttendance']);
});

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
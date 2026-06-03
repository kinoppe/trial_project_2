<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;

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
Route::middleware('auth')->group(function () {
    Route::get('/attendance', [AttendanceController::class,'index']);
    Route::post('/attendance', [AttendanceController::class,'store']);
    Route::post('/attendance/update', [AttendanceController::class,'update']);
    Route::post('/attendance/break_start', [AttendanceController::class,'breakStart']);
    Route::post('/attendance/break_end', [AttendanceController::class,'breakEnd']);
    Route::get('/attendance/list', [AttendanceController::class,'list']);
    Route::get('/attendance/detail/{id}', [AttendanceController::class,'show']);
});
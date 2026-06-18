<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class AdminStaffController extends Controller
{
    public function index()
    {
        $users = User::where('is_admin', false)->get();

        return view('admin.staff.index', compact('users'));
    }

    public function show($id)
    {
        return redirect('/admin/attendance/staff/' . $id);
    }
}

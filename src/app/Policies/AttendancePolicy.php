<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy
{
    public function before(User $user, $ability)
    {
        if ($user->is_admin) {
            return true;
        }
        return null;
    }

    public function update(User $user, Attendance $attendance)
    {
        return $user->id === $attendance->user_id;
    }

    public function delete(User $user, Attendance $attendance)
    {
        return $user->id === $attendance->user_id;
    }
}

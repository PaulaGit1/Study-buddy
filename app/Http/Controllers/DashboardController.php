<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function showDashboard()
    {
        $user = Auth::user();
        switch ($user->role) {
            case 'Student':
                return redirect('/student-dashboard');
            case 'Tutor':
                return redirect('/tutor-dashboard');
            case 'Admin':
                return redirect('/admin-dashboard');
            default:
                return redirect('/');
        }
    }

    public function studentDashboard()
    {
        if (!Auth::check() || Auth::user()->role != 'Student') {
            return redirect('/');
        }
        return view('student-dashboard');
    }

    public function tutorDashboard()
    {
        if (!Auth::check() || Auth::user()->role != 'Tutor') {
            return redirect('/');
        }
        return view('tutor-dashboard');
    }

    public function adminDashboard()
    {
        if (!Auth::check() || Auth::user()->role != 'Admin') {
            return redirect('/');
        }
        return view('admin-dashboard');
    }
}

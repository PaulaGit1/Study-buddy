<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\TutorSubject;
use Illuminate\Http\Request;

class TutorPageController extends Controller
{
    public function index()
    {
        $tutors = User::where('role', 'Tutor')
            ->where('status', 'Verified')
            ->with('subjects')
            ->paginate(9); // Adjust the number of items per page as needed

        return view('tutor', compact('tutors'));
    }
}

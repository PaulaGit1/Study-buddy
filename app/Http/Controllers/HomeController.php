<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\User;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index()
    {
        $courses = Subject::with(['tutors' => function ($query) {
            $query->where('status', 'Verified');
        }])->paginate(6);

        $tutors = User::where('role', 'Tutor')->where('status', 'Verified')->get();

        foreach ($courses as $course) {
            foreach ($course->tutors as $tutor) {
                $tutor->rating = Feedback::whereHas('session', function ($query) use ($tutor, $course) {
                    $query->where('tutor_id', $tutor->id)->where('subject_id', $course->id);
                })->avg('rating');
            }
        }

        $testimonials = Feedback::with(['session.student', 'session.tutor'])
            ->latest()
            ->limit(6)
            ->get();

        return view('index', compact('courses', 'tutors', 'testimonials'));
    }
}

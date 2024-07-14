<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TutorSession;
use App\Models\Feedback;
use App\Models\Payment;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Auth;

class StudentDashboardController extends Controller
{
    public function index()
    {
        $student = Auth::user();

        $totalSessions = TutorSession::where('student_id', $student->id)->count();
        $completedSessionsCount = TutorSession::where('student_id', $student->id)->where('status', 'completed')->count();
        $upcomingSessions = TutorSession::where('student_id', $student->id)->where('status', 'booked')->where('session_time', '>', now())->get();

        // Updated query to check for feedback
        $completedSessions = TutorSession::leftJoin('feedback', 'tutor_sessions.id', '=', 'feedback.session_id')
            ->where('tutor_sessions.student_id', $student->id)
            ->where('tutor_sessions.status', 'completed')
            ->select('tutor_sessions.*', 'feedback.id as feedback_id')
            ->get();

        $payments = Payment::where('student_id', $student->id)->get();

        return view('student-dashboard', compact('student', 'totalSessions', 'completedSessionsCount', 'upcomingSessions', 'completedSessions', 'payments'));
    }

    public function rateSession(Request $request, $sessionId)
    {
        $session = TutorSession::findOrFail($sessionId);

        $request->validate([
            'rating' => 'required|integer|between:1,5',
            'comments' => 'nullable|string',
        ]);

        Feedback::create([
            'session_id' => $sessionId,
            'rating' => $request->rating,
            'comments' => $request->comments,
        ]);

        $tutor = $session->tutor;
        $student = $session->student;
        UserNotification::create([
            'user_id' => $tutor->id,
            'message' => $student->name . ' has rated your session.',
            'is_read' => false,
        ]);

        return redirect()->route('student-dashboard')->with('status', 'Feedback submitted successfully.');
    }

    public function showRatePage($sessionId)
    {
        $session = TutorSession::findOrFail($sessionId);
        return view('rate-session', compact('session'));
    }
}

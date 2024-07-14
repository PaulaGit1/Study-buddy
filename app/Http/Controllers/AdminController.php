<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\TutorSession;
use App\Models\Feedback;
use App\Models\Subject;
use App\Models\Payment;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function dashboard()
    {   
        $admin = auth()->user();
        $totalTutors = User::where('role', 'Tutor')->count();
        $totalStudents = User::where('role', 'Student')->count();
        $totalSessions = TutorSession::count();
        $pendingApprovals = User::where('role', 'Tutor')->where('status', 'Unverified')->count();

        $tutors = User::where('role', 'Tutor')->get()->map(function ($tutor) {
            $rating = Feedback::whereHas('session', function ($query) use ($tutor) {
                $query->where('tutor_id', $tutor->id);
            })->join('tutor_sessions', 'feedback.session_id', '=', 'tutor_sessions.id')
                ->where('tutor_sessions.tutor_id', $tutor->id)
                ->avg('feedback.rating');
            $tutor->rating = $rating;
            return $tutor;
        });

        $students = User::where('role', 'Student')->get();
        $recentSessions = TutorSession::with(['student', 'tutor', 'subject'])
            ->orderBy('session_time', 'desc')
            ->limit(10)
            ->get();

        $subjects = Subject::all();
        $payments = Payment::with(['student', 'tutor'])->get();

        return view('admin-dashboard', compact('admin', 'totalTutors', 'totalStudents', 'totalSessions', 'pendingApprovals', 'tutors', 'students', 'recentSessions', 'subjects', 'payments'));
    }

    public function approveTutor(Request $request, $id)
    {
        $tutor = User::findOrFail($id);
        $tutor->status = 'Verified';
        $tutor->save();

        UserNotification::create([
            'user_id' => $tutor->id,
            'message' => 'Your account has been approved by the admin. Please set your subject prices, durations, and availabilities.',
            'is_read' => false,
        ]);

        return redirect()->route('admin.dashboard', ['section' => 'tutorsList'])
                         ->with('status', 'Tutor approved successfully!');
    }

    public function addImage(Request $request, $id)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $subject = Subject::findOrFail($id);

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('subject_images', 'public');
            $subject->image = $imagePath;
            $subject->save();

            return redirect()->route('admin.dashboard', ['section' => 'subjectsList'])
                             ->with('status', 'Subject image has been added.');
        }

        return redirect()->route('admin.dashboard', ['section' => 'subjectsList'])
                         ->with('error', 'Failed to upload image.');
    }

    public function addDescription(Request $request, $id)
    {
        $request->validate([
            'description' => 'required|string|max:255',
        ]);

        $subject = Subject::findOrFail($id);
        $subject->description = $request->description;
        $subject->save();

        return redirect()->route('admin.dashboard', ['section' => 'subjectsList'])
                         ->with('status', 'Description has been added.');
    }
}

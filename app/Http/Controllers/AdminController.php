<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\TutorSession;
use App\Models\Feedback;
use App\Models\Subject;
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

        return view('admin-dashboard', compact('admin','totalTutors', 'totalStudents', 'totalSessions', 'pendingApprovals', 'tutors', 'students', 'recentSessions', 'subjects'));
    }

    public function approveTutor(Request $request, $id)
    {
        $tutor = User::findOrFail($id);
        $tutor->status = 'Verified';
        $tutor->save();

        // Send notification to the tutor
        UserNotification::create([
            'user_id' => $tutor->id,
            'message' => 'Your account has been approved by the admin. Please set your subject prices, durations, and availabilities.',
            'is_read' => false,
        ]);

        return response()->json(['status' => 'success', 'message' => 'Tutor approved successfully!']);
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

            return response()->json(['status' => 'success', 'message' => 'Subject image has been added.']);
        }

        return response()->json(['status' => 'error', 'message' => 'Failed to upload image.'], 400);
    }

    public function addDescription(Request $request, $id)
    {
        $request->validate([
            'description' => 'required|string|max:255',
        ]);

        $subject = Subject::findOrFail($id);
        $subject->description = $request->description;
        $subject->save();

        return response()->json(['status' => 'success', 'message' => 'Description has been added.']);
    }
}

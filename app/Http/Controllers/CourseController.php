<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\User;
use App\Models\TutorSession;
use App\Models\Payment;
use App\Models\TutorAvailability;
use App\Models\TutorSubject;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CourseController extends Controller
{
    public function showSubjectDetail($subjectId, $tutorId)
    {
        $subject = Subject::findOrFail($subjectId);
        $tutor = User::findOrFail($tutorId);
        $tutorSubject = TutorSubject::where('subject_id', $subjectId)
                                    ->where('tutor_id', $tutorId)
                                    ->firstOrFail();
        $averageRating = DB::table('feedback')
                           ->join('tutor_sessions', 'feedback.session_id', '=', 'tutor_sessions.id')
                           ->where('tutor_sessions.tutor_id', $tutorId)
                           ->where('tutor_sessions.subject_id', $subjectId)
                           ->avg('feedback.rating');
        
        return view('subject-detail', compact('subject', 'tutor', 'tutorSubject', 'averageRating'));
    }

    public function bookLesson($subjectId, $tutorId)
    {
        $subject = Subject::findOrFail($subjectId);
        $tutor = User::findOrFail($tutorId);
        $tutorSubject = TutorSubject::where('subject_id', $subjectId)
                                    ->where('tutor_id', $tutorId)
                                    ->firstOrFail();
        $availabilities = TutorAvailability::where('tutor_id', $tutorId)->get();
        
        return view('book-lesson', compact('subject', 'tutor', 'tutorSubject', 'availabilities'));
    }

    public function bookSession(Request $request)
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'tutor_id' => 'required|exists:users,id',
            'session_time' => 'required|date_format:Y-m-d\TH:i',
            'duration' => 'required|integer'
        ]);

        $tutorId = $request->tutor_id;
        $subjectId = $request->subject_id;
        $studentId = Auth::id();
        $sessionTime = $request->session_time;
        $duration = $request->duration;

        $TutorAvailabilityCheck = TutorAvailability::where('tutor_id', $tutorId)
                                         ->where('start_time', '<=', $sessionTime)
                                         ->where('end_time', '>=', DB::raw("DATE_ADD('$sessionTime', INTERVAL $duration MINUTE)"))
                                         ->exists();

        if (!$TutorAvailabilityCheck) {
            return response()->json(['message' => 'The selected time is not within the tutor\'s availability.'], 422);
        }

        DB::transaction(function () use ($tutorId, $studentId, $subjectId, $sessionTime, $duration) {
            TutorSession::create([
                'tutor_id' => $tutorId,
                'student_id' => $studentId,
                'subject_id' => $subjectId,
                'session_time' => $sessionTime,
                'status' => 'booked'
            ]);

            $price = TutorSubject::where('tutor_id', $tutorId)
                                       ->where('subject_id', $subjectId)
                                       ->value('price');
            Payment::create([
                'student_id' => $studentId,
                'tutor_id' => $tutorId,
                'amount' => $price,
                'status' => 'completed'
            ]);

            $student = Auth::user();
            $tutor = User::find($tutorId);

            // Send notification to the tutor
            UserNotification::create([
                'user_id' => $tutorId,
                'message' => 'A new session has been booked by ' . $student->name,
                'is_read' => false,
            ]);

            UserNotification::create([
                'user_id' => $studentId,
                'message' => 'You have booked a session with ' . $tutor->name . ' for Ksh ' . number_format($price, 2),
                'is_read' => false,
            ]);
        

            // Update tutor TutorAvailability
            $TutorAvailability = TutorAvailability::where('tutor_id', $tutorId)
                                        ->where('start_time', '<=', $sessionTime)
                                        ->where('end_time', '>=', DB::raw("DATE_ADD('$sessionTime', INTERVAL $duration MINUTE)"))
                                        ->first();
            if ($TutorAvailability) {
                $TutorAvailability->start_time = DB::raw("DATE_ADD('$sessionTime', INTERVAL $duration MINUTE)");
                $TutorAvailability->save();
            }
        });

        return response()->json(['message' => 'Session booked successfully!'], 200);
    }
}

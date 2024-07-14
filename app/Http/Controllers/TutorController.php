<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\TutorSession;
use App\Models\Feedback;
use App\Models\Subject;
use App\Models\TutorSubject;
use App\Models\TutorAvailability;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TutorController extends Controller
{
    public function tutorDashboard()
    {
        $tutor = auth()->user();

        if ($tutor->status !== 'Verified') {
            return view('tutor-unverified');
        }

        $totalSessions = TutorSession::where('tutor_id', $tutor->id)->count();
        $averageRating = Feedback::whereHas('session', function ($query) use ($tutor) {
            $query->where('tutor_id', $tutor->id);
        })->avg('rating');
        $upcomingSessions = TutorSession::where('tutor_id', $tutor->id)
            ->where('status', 'booked')
            ->where('session_time', '>', now())
            ->get();
        $completedSessions = TutorSession::where('tutor_id', $tutor->id)
            ->where('status', 'completed')
            ->get();
        $subjects = $tutor->subjects()->withPivot('price', 'duration')->get();
        $availabilities = TutorAvailability::where('tutor_id', $tutor->id)->get();

        return view('tutor-dashboard', compact('tutor', 'totalSessions', 'averageRating', 'upcomingSessions', 'completedSessions', 'subjects', 'availabilities'));
    }

    public function setPrice(Request $request, $subjectId)
    {
        $request->validate([
            'price' => 'required|numeric|min:0',
        ]);

        $tutor = auth()->user();

        TutorSubject::updateOrCreate(
            ['tutor_id' => $tutor->id, 'subject_id' => $subjectId],
            ['price' => $request->price]
        );

        return response()->json(['status' => 'success']);
    }

    public function setDuration(Request $request, $subjectId)
    {
        $request->validate([
            'duration' => 'required|integer|min:1',
        ]);

        $tutor = auth()->user();

        TutorSubject::updateOrCreate(
            ['tutor_id' => $tutor->id, 'subject_id' => $subjectId],
            ['duration' => $request->duration]
        );

        return response()->json(['status' => 'success']);
    }

    public function completeSession($sessionId)
    {
        $session = TutorSession::findOrFail($sessionId);
        $session->status = 'completed';
        $session->save();

        return response()->json(['status' => 'success']);
    }

    public function cancelSession($sessionId)
    {
        $session = TutorSession::findOrFail($sessionId);
        $session->status = 'cancelled';
        $session->save();

        return response()->json(['status' => 'success']);
    }

    public function setAvailability(Request $request)
    {
        $request->validate([
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
        ]);

        try {
            $tutor = auth()->user();

            // Check for overlapping availability
            $overlap = TutorAvailability::where('tutor_id', $tutor->id)
                ->where(function ($query) use ($request) {
                    $query->whereBetween('start_time', [$request->start_time, $request->end_time])
                          ->orWhereBetween('end_time', [$request->start_time, $request->end_time])
                          ->orWhere(function ($query) use ($request) {
                              $query->where('start_time', '<=', $request->start_time)
                                    ->where('end_time', '>=', $request->end_time);
                          });
                })->exists();

            if ($overlap) {
                return response()->json(['status' => 'error', 'message' => 'The availability range overlaps with an existing range.'], 400);
            }

            TutorAvailability::create([
                'tutor_id' => $tutor->id,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
            ]);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error setting availability: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'There was an error setting the availability.'], 500);
        }
    }
}


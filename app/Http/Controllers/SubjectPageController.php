<?php


namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\User;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubjectPageController extends Controller
{
    public function index(Request $request)
    {
        $query = Subject::query();

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $subjects = $query->with([
            'tutors' => function ($query) {
                $query->where('status', 'Verified');
            }
        ])->paginate(9);

        // Calculate the rating for each subject-tutor combination
        foreach ($subjects as $subject) {
            foreach ($subject->tutors as $tutor) {
                $rating = Feedback::whereHas('session', function ($query) use ($tutor, $subject) {
                    $query->where('tutor_id', $tutor->id)->where('subject_id', $subject->id);
                })->avg('rating');
                $tutor->rating = $rating;
            }
        }

        return view('subject', compact('subjects'));
    }

    public function show($subjectId, $tutorId)
    {
        $subject = Subject::findOrFail($subjectId);
        $tutor = User::findOrFail($tutorId);

        $averageRating = DB::table('feedback')
            ->join('tutor_sessions', 'feedback.session_id', '=', 'tutor_sessions.id')
            ->where('tutor_sessions.tutor_id', $tutor->id)
            ->where('tutor_sessions.subject_id', $subject->id)
            ->avg('feedback.rating');

        $tutorSubject = DB::table('tutor_subjects')
            ->where('tutor_id', $tutor->id)
            ->where('subject_id', $subject->id)
            ->first();

        return view('subject-detail', compact('subject', 'tutor', 'averageRating', 'tutorSubject'));
    }
}

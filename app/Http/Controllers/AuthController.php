<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Subject;
use App\Models\TutorSubject;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function registerStudent(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'password_confirmation' => 'required|string|min:8',
                'phone' => 'required|string|max:15',
                'profile_photo' => 'nullable|image|mimes:jpg,png,jpeg,gif,svg|max:2048',
            ]);

            $profilePhotoPath = $request->file('profile_photo') ? $request->file('profile_photo')->store('profile_photos', 'public') : null;

            $student = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'contact_info' => $request->phone,
                'role' => 'Student',
                'profile_photo' => $profilePhotoPath,
            ]);

            UserNotification::create([
                'user_id' => $student->id,
                'message' => 'Welcome to Study Buddy! We are glad to have you here.',
                'is_read' => false,
            ]);

            return redirect('/login')->with('status', 'Registration successful! Please login.');
        } catch (\Exception $e) {
            return back()->with('error', 'Registration failed: ' . $e->getMessage())->withInput();
        }
    }

    public function registerTutor(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'password_confirmation' => 'required|string|min:8',
                'phone' => 'required|string|max:15',
                'profile_photo' => 'nullable|image|mimes:jpg,png,jpeg,gif,svg|max:2048',
                'subjects' => 'required|array|min:1',
                'subjects.*' => 'required|string|max:255',
            ]);

            if ($request->password !== $request->password_confirmation) {
                return back()->with('error', 'Passwords do not match.')->withInput();
            }

            $profilePhotoPath = $request->file('profile_photo') ? $request->file('profile_photo')->store('profile_photos', 'public') : null;

            $tutor = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'contact_info' => $request->phone,
                'role' => 'Tutor',
                'status' => 'Unverified',
                'profile_photo' => $profilePhotoPath,
            ]);

            foreach ($request->subjects as $subjectName) {
                $subject = Subject::firstOrCreate(['name' => $subjectName]);
                TutorSubject::create([
                    'tutor_id' => $tutor->id,
                    'subject_id' => $subject->id,
                ]);
            }

            UserNotification::create([
                'user_id' => $tutor->id,
                'message' => 'Welcome to Study Buddy! Your account is pending approval.',
                'is_read' => false,
            ]);

            return redirect('/login')->with('status', 'Registration successful! Please login.');
        } catch (\Exception $e) {
            return back()->with('error', 'Registration failed: ' . $e->getMessage())->withInput();
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->with('error', 'The provided email does not match our records.')->withInput();
        }

        if (!Hash::check($request->password, $user->password)) {
            return back()->with('error', 'The provided password is incorrect.')->withInput();
        }

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $request->session()->regenerate();

            $role = $user->role;
            $message = 'Welcome ' . ucfirst($role) . ', you have logged in successfully.';

            // Redirect based on role
            switch ($role) {
                case 'Admin':
                    return redirect()->route('admin.dashboard')->with('status', $message);
                case 'Tutor':
                    return redirect()->route('tutor.dashboard')->with('status', $message);
                case 'Student':
                    return redirect('/')->with('status', $message);
                default:
                    Auth::logout();
                    return redirect('/login')->with('error', 'Unauthorized access.');
            }
        }

        return back()->with('error', 'The provided credentials do not match our records.')->withInput();
    }


    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('status', 'Logged out successfully.');
    }
}

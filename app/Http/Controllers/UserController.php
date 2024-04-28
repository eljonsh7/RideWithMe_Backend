<?php

namespace App\Http\Controllers;

use App\Models\Ban;
use App\Models\User;
use App\Models\Report;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

class UserController extends Controller
{
    public function login(Request $request)
    {

        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        try {
            $user = User::where('email', $request->email)->first();
            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json(['message' => 'Invalid credentials!'], 401);
            }

        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }

        $bannedUser = Ban::where('user_id', $user->id)->first();
        if ($bannedUser) {
            $dateUntil = Carbon::parse($bannedUser->date_until);
            $currentTime = now();

            $remainingHours = round($dateUntil->diffInMinutes($currentTime) / 60, 2);
            $remainingDays = ceil($remainingHours / 24);

            $response = ($remainingDays <= 1)
                ? ['message' => 'User is banned!', 'remaining_hours' => $remainingHours]
                : ['message' => 'User is banned!', 'remaining_days' => $remainingDays];

            return response()->json($response, 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json(['token' => $token, 'user' => $user]);
    }

    public function signup(Request $request)
    {
        $request->validate([
            'firstName' => 'required|string',
            'lastName' => 'required|string',
            'role' => 'required|string',
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        try {
            $user = User::where('email', $request->email)->first();
            if ($user) {
                return response()->json(['message' => 'A user with this email already exists.'], 409);
            } else {
                $user = new User();

                $user->id = Str::uuid();
                $user->first_name = $request->firstName;
                $user->last_name = $request->lastName;
                $user->role = $request->role;
                $user->email = $request->email;
                $user->password = Hash::make($request->password);

                $user->save();

                $token = $user->createToken('auth_token')->plainTextToken;
                return response()->json(['message' => 'User created successfully', 'user' => $user, 'token' => $token], 201);
            }
        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }


    public function getAllUsers()
    {
        try {
            $users = User::get();

            foreach ($users as $user) {
                $isBanned = false;
                $ban = Ban::where('user_id', $user->id)->first();
                if ($ban) {
                    $now = now();
                    if ($ban->date_until > $now) {
                        $isBanned = true;
                    }
                }
                $user->is_banned = $isBanned;

                $reportsCount = Report::where('reported_user_id', $user->id)->count();
                $user->reports_number = $reportsCount;
            }

            return response()->json(['users' => $users], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $userId)
    {
        try {
            $request->validate([
                'first_name' => 'nullable|string',
                'last_name' => 'nullable|string',
                'role' => 'nullable|string',
                'email' => 'nullable|string'
            ]);

            $user = User::findOrFail($userId);

            $fillableFields = ['first_name', 'last_name','role','email'];
            foreach ($fillableFields as $field) {
                if ($request->filled($field)) {
                    $user->$field = $request->$field;
                }
            }
            $user->save();

            return response()->json(['message' => 'User updated successfully', 'user' => $user], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function delete($userId) {
        $user = User::findOrFail($userId);
        if($user){
            $user->delete();
            return response()->json(['message'=>'User deleted successfully.'],200);
        }
        return response()->json(['message'=>'User not found.'],404);
    }

    public function ban(Request $request, $userId) {
        $user = User::findOrFail($userId);
        if($user){
            $request->validate([
                'date_until' => 'nullable|string',
            ]);

            $ban = new Ban();
            $ban->id = Str::uuid();
            $ban->user_id = $request->userId;
            $ban->date_until = $request->date_until;

            $ban->save();

            return response()->json(['message'=>'User banned successfully.'],200);
        }
        return response()->json(['message'=>'User not found.'],404);
    }

    public function removeBan($userId) {
        $ban = Ban::where('user_id', $userId);
        if($ban){
            $ban->delete();

            return response()->json(['message'=>'User unbanned successfully.'],200);
        }
        return response()->json(['message'=>'Ban not found.'],404);
    }

    public function getUser($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $user
        ], 200);
    }
}

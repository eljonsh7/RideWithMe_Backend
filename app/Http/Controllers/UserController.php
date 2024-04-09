<?php

namespace App\Http\Controllers;

use App\Models\Ban;
use App\Models\User;
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
                return response()->json(['message' => 'A user with this email already exists.'], 401);
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
            return response()->json(['users' => $users], 200);
        }catch (Exception $e){
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateUser(Request $request,$id)
    {
        try {
            $request->validate([
                'first_name' => 'nullable|string',
                'last_name' => 'nullable|string',
                'role' => 'nullable|string',
                'email' => 'nullable|string'
            ]);

            $user = User::findOrFail($id);

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
}

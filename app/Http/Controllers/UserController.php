<?php

namespace App\Http\Controllers;

use App\Models\Ban;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
            return response()->json(['message' => 'User not found!'], 401);
        }

        $bannedUser = Ban::where('user_id', $user->id)->first();
        if($bannedUser){
            $dateUntil = Carbon::parse($bannedUser->date_until);
            $remainingDays = now()->diffInDays($dateUntil);

            return response()->json(['message' => 'User is banned!', 'remaining_days' => $remainingDays], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json(['token' => $token, 'user'=>$user]);
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

                $user->firstName = $request->firstName;
                $user->lastName = $request->lastName;
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
}

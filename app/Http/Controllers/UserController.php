<?php

namespace App\Http\Controllers;

use App\Models\Ban;
use App\Models\User;
use Carbon\Carbon;
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

        } catch (\Exception $e) {
            return response()->json(['message' => 'User not found!'], 401);
        }

        $bannedUser = Ban::where('user_id', $user->id)->first();
        if($bannedUser){
            $dateUntil = Carbon::parse($bannedUser->date_until);
            $remainingDays = now()->diffInDays($dateUntil);

            return response()->json(['message' => 'User is banned!', 'remaining_days' => $remainingDays], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json(['token' => $token, 'user'=>$user], 200);
    }

}

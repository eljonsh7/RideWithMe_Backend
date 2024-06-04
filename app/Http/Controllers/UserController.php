<?php

namespace App\Http\Controllers;

use App\Models\Ban;
use App\Models\Car;
use App\Models\User;
use App\Models\Report;
use App\Models\FriendRequest;
use App\Models\UserCar;
use App\Models\Rating;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Ramsey\Uuid\Uuid;


/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     title="User",
 *     description="User model",
 *     required={"id", "first_name", "last_name", "email", "password", "role"},
 *     @OA\Property(property="id", type="string", format="uuid", description="User ID"),
 *     @OA\Property(property="first_name", type="string", description="First name of the user"),
 *     @OA\Property(property="last_name", type="string", description="Last name of the user"),
 *     @OA\Property(property="profile_picture", type="string", nullable=true, description="URL of the user's profile picture"),
 *     @OA\Property(property="email", type="string", format="email", description="Email address of the user"),
 *     @OA\Property(property="password", type="string", description="Password of the user"),
 *     @OA\Property(property="role", type="string", description="Role of the user")
 * )
 */
class UserController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/login",
     *     tags={"User"},
     *     summary="Login user",
     *     description="Login a user with email and password",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="password", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful login",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="user", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="User is banned"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="An error occurred"
     *     )
     * )
     */
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
        return response()->json(['message' => 'Login was successful.', 'token' => $token, 'user' => $user]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/signup",
     *     tags={"User"},
     *     summary="Signup user",
     *     description="Signup a new user",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="firstName", type="string"),
     *             @OA\Property(property="lastName", type="string"),
     *             @OA\Property(property="role", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="password", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="user", ref="#/components/schemas/User"),
     *             @OA\Property(property="token", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="A user with this email already exists"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="An error occurred"
     *     )
     * )
     */
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


    /**
     * @OA\Get(
     *     path="/api/v1/users/get",
     *     tags={"User"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get all users",
     *     description="Get a list of all users",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="users", type="array", @OA\Items(ref="#/components/schemas/User"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="An error occurred"
     *     )
     * )
     */
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

    /**
     * @OA\Put(
     *     path="/api/v1/users/update/{userId}",
     *     tags={"User"},
     *     security={{"bearerAuth": {}}},
     *     summary="Update user",
     *     description="Update user details",
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="first_name", type="string"),
     *             @OA\Property(property="last_name", type="string"),
     *             @OA\Property(property="role", type="string"),
     *             @OA\Property(property="email", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="user", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="An error occurred"
     *     )
     * )
     */
    public function update(Request $request, $userId)
    {
        try {
            $request->validate([
                'first_name' => 'nullable|string',
                'last_name' => 'nullable|string',
                'role' => 'nullable|string',
                'profile_picture' => 'nullable|string'
            ]);

            $user = User::findOrFail($userId);

            $fillableFields = ['first_name', 'last_name', 'role', 'profile_picture'];
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

    /**
     * @OA\Delete(
     *     path="/api/v1/users/delete/{userId}",
     *     tags={"User"},
     *     security={{"bearerAuth": {}}},
     *     summary="Delete user",
     *     description="Delete a user by ID",
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */

    public function delete($userId)
    {
        $user = User::findOrFail($userId);
        if ($user) {
            $user->delete();
            return response()->json(['message' => 'User deleted successfully.'], 200);
        }
        return response()->json(['message' => 'User not found.'], 404);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/users/ban/{userId}",
     *     tags={"User"},
     *     security={{"bearerAuth": {}}},
     *     summary="Ban user",
     *     description="Ban a user by ID",
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="date_until", type="string", description="Date until the user is banned")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User banned successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */

    public function ban(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        if ($user) {
            $request->validate([
                'date_until' => 'nullable|string',
            ]);

            $dateUntil = $request->has('date_until') ? $request->date_until : Carbon::now()->addDays(30);

            $ban = new Ban();
            $ban->id = Str::uuid();
            $ban->user_id = $request->userId;
            $ban->date_until = $dateUntil;

            $ban->save();

            return response()->json(['message' => 'User banned successfully.'], 200);
        }
        return response()->json(['message' => 'User not found.'], 404);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/users/ban/remove/{userId}",
     *     tags={"User"},
     *     security={{"bearerAuth": {}}},
     *     summary="Remove ban from user",
     *     description="Remove ban from a user by ID",
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User unbanned successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Ban not found"
     *     )
     * )
     */

    public function removeBan($userId)
    {
        $ban = Ban::where('user_id', $userId);
        if ($ban) {
            $ban->delete();

            return response()->json(['message' => 'User unbanned successfully.'], 200);
        }
        return response()->json(['message' => 'Ban not found.'], 404);
    }


    /**
     * @OA\Get(
     *     path="/api/v1/users/{id}",
     *     tags={"User"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get user by ID",
     *     description="Get user information by user ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     )
     * )
     */
    public function getUser($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $authUser = auth()->user();
        $friendRequest = FriendRequest::where('sender_id', $id)->where('receiver_id', $authUser->id)
            ->orWhere('sender_id', $authUser->id)
            ->where('receiver_id', $id)->first();

        $averageRating = Rating::where('rated_user_id', $id)
        ->avg('stars_number');

        $rating = Rating::where('rated_user_id',$id)->where('rater_id',$authUser->id)->first();

        if ($friendRequest) {
            $user->isFriend = ['status' => $friendRequest->status, 'sending' => $authUser->id == $friendRequest->sender_id];
        }
        if($averageRating){
            $user->averageRating = $averageRating;
        }
        if($rating){
            $user->alreadyRating = $rating;
        }
        unset($user->password);
        return response()->json([
            'message' => 'User fetched successfully.',
            'success' => true,
            'data' => $user
        ], 200);
    }

    public function searchUsers($name)
    {
        $user = auth()->user();

        $users = User::where(function ($query) use ($name) {
            $query->where('first_name', 'like', '%' . $name . '%')
                ->orWhere('last_name', 'like', '%' . $name . '%');
        })
            ->where('id', '<>', $user->id)
            ->get();

        return response()->json(['message' => 'Users fetched successfully', 'users' => $users], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/users/getByToken",
     *     tags={"User"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get current user",
     *     description="Get information of the current authenticated user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/User")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
     */

    public function getUserByToken()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $userCar = UserCar::where('user_id', $user->id)->first();
        if($userCar) {
            $car = Car::where('id', $userCar->car_id)->first();
            $user->car = $car;
        }

        $user->userCar = $userCar;
        return response()->json(['message' => 'Token is valid', 'user' => $user]);
    }

    public function attachCar(Request $request)
    {
        $request->validate([
            'car_id' => 'required|string',
            'color' => 'required|string',
            'year' => 'required|numeric',
            'thumbnail' => 'required|string',
        ]);

        $userCar = new UserCar();
        $userCar->id = Str::uuid();
        $userCar->user_id = auth()->user()->id;
        $userCar->car_id = $request->car_id;
        $userCar->color = $request->color;
        $userCar->year = $request->year;
        $userCar->thumbnail = $request->thumbnail;

        $userCar->save();

        return response()->json(['message' => 'Car attached successfully.'], 200);
    }

    public function UpdateAttachedCar(Request $request) {
        $request->validate([
            'car_id' => 'nullable|string',
            'color' => 'nullable|string',
            'year' => 'nullable|numeric',
            'thumbnail' => 'nullable|string',
        ]);

        $userCar = UserCar::where('user_id', Auth::user()->id)->firstOrFail();

        if ($request->has('car_id')) {
            $userCar->car_id = $request->car_id;
        }
        if ($request->has('color')) {
            $userCar->color = $request->color;
        }
        if ($request->has('year')) {
            $userCar->year = $request->year;
        }
        if ($request->has('thumbnail')) {
            $userCar->thumbnail = $request->thumbnail;
        }

        $userCar->save();

        return response()->json(['message' => 'Car updated successfully.'], 200);
    }

}

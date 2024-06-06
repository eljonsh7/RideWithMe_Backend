<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Requests\API\V1\User\Ban\BanRequest;
use App\Http\Requests\API\V1\User\Ban\RemoveBanRequest;
use App\Http\Requests\API\V1\User\Car\AttachCarRequest;
use App\Http\Requests\API\V1\User\Car\UpdateAttachedCarRequest;
use App\Http\Requests\API\V1\User\DeleteUserRequest;
use App\Http\Requests\API\V1\User\GetAllUsersRequest;
use App\Http\Requests\API\V1\User\GetUserByTokenRequest;
use App\Http\Requests\API\V1\User\GetUserRequest;
use App\Http\Requests\API\V1\User\LoginRequest;
use App\Http\Requests\API\V1\User\SearchUsersRequest;
use App\Http\Requests\API\V1\User\SignupRequest;
use App\Http\Requests\API\V1\User\UpdateUserRequest;
use App\Models\Ban;
use App\Models\Car;
use App\Models\FriendRequest;
use App\Models\Rating;
use App\Models\Report;
use App\Models\User;
use App\Models\UserCar;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;


/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     title="User",
 *     description="User model",
 *     required={"id", "first_name", "last_name", "email", "password", "role"},
 *     @OA\Property(property="id", type="string", format="uuid", description="Primary key of the report user"),
 *     @OA\Property(property="first_name", type="string", description="First name of the user"),
 *     @OA\Property(property="last_name", type="string", description="Last name of the user"),
 *     @OA\Property(property="profile_picture", type="string", nullable=true, description="URL of the user's profile picture"),
 *     @OA\Property(property="email", type="string", format="email", description="Email address of the user"),
 *     @OA\Property(property="password", type="string", description="Password of the user"),
 *     @OA\Property(property="role", type="string", description="Role of the user"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Timestamp when the user was created"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Timestamp when the user was updated"),
 * )
 *
 * @OA\Schema(
 *      schema="UserCar",
 *      type="object",
 *      title="UserCar",
 *      description="UserCar model",
 *      required={"id", "user_id", "car_id", "year", "thumbnail", "color"},
 *      @OA\Property(property="id", format="uuid", type="string", description="Primary key of the userCar "),
 *      @OA\Property(property="user_id", format="uuid", type="string", description="User ID"),
 *      @OA\Property(property="car_id", format="uuid", type="string", description="Car ID"),
 *      @OA\Property(property="year", type="string", description="Car manufacturing year"),
 *      @OA\Property(property="thumbnail", type="string", description="Thumbnail URL of the car"),
 *      @OA\Property(property="color", type="string", description="Car color"),
 *      @OA\Property(property="created_at", type="string", format="date-time", description="Timestamp when the user car was created"),
 *      @OA\Property(property="updated_at", type="string", format="date-time", description="Timestamp when the user car was updated")
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
    public function login(LoginRequest $request)
    {
        $data = $request->validated();

        try {
            $user = User::where('email', $data['email'])->first();
            if (!$user || !Hash::check($data['password'], $user->password)) {
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
    public function signup(SignupRequest $request)
    {
        $data = $request->validated();

        try {
            $user = User::where('email', $data['email'])->first();
            if ($user) {
                return response()->json(['message' => 'A user with this email already exists.'], 409);
            } else {
                $user = new User();

                $user->id = Str::uuid();
                $user->first_name = $data['firstName'];
                $user->last_name = $data['lastName'];
                $user->role = $data['role'];
                $user->email = $data['email'];
                $user->password = Hash::make($data['password']);

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
    public function getAllUsers(GetAllUsersRequest $request)
    {
        try {
            $request->validated();
            $users = User::where('is_admin', false)->get();

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
     *         @OA\Schema(type="string", format="uuid"),
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
    public function update(UpdateUserRequest $request, $userId)
    {
        try {
            $data = $request->validated();

            $user = User::findOrFail($userId);

            $fillableFields = ['first_name', 'last_name', 'role', 'profile_picture'];
            foreach ($fillableFields as $field) {
                if (isset($data[$field])) {
                    $user->$field = $data[$field];
                }
            }
            $user->save();

            if($user->car) {
                $car = Car::where('id', $user->car->car_id)->with('car')->first();
                $user->car = $car;
            }

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
     *         @OA\Schema(type="string", format="uuid")
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

    public function delete(DeleteUserRequest $request, $userId)
    {
        $request->validated();
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
     *         @OA\Schema(type="string", format="uuid")
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

    public function ban(BanRequest $request, $userId)
    {
        $data = $request->validated();
        $user = User::where('id', $userId);
        if ($user) {
            $request->validate([
                'date_until' => 'nullable|string',
            ]);

            $dateUntil = $data['date_until'] ?? Carbon::now()->addDays(30);

            $ban = new Ban();
            $ban->id = Str::uuid();
            $ban->user_id = $userId;
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
     *         @OA\Schema(type="string", format="uuid")
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

    public function removeBan(RemoveBanRequest $request, $userId)
    {
        $request->validated();
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
     *         @OA\Schema(type="string", format="uuid")
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
    public function getUser(GetUserRequest $request, $id)
    {
        $request->validated();
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

    public function searchUsers(SearchUsersRequest $request, $name)
    {
        $request->validated();
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

    public function getUserByToken(GetUserByTokenRequest $request)
    {
        $request->validated();
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

    /**
 * @OA\Post(
 *     path="/api/v1/users/car/attach",
 *     tags={"User"},
 *     security={{"bearerAuth": {}}},
 *     summary="Attach a car to the user",
 *     description="Attach a car to the authenticated user",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="car_id", format="uuid", type="string", description="ID of the car to attach"),
 *             @OA\Property(property="year", type="string", description="Car manufacturing year"),
 *             @OA\Property(property="thumbnail", type="string", description="Thumbnail URL of the car"),
 *             @OA\Property(property="color", type="string", description="Car color")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Car attached successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Car attached successfully.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Bad request",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Bad request")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="An error occurred",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="An error occurred.")
 *         )
 *     )
 * )
 */

    public function attachCar(AttachCarRequest $request)
    {
        $data = $request->validated();

        $userCar = new UserCar();
        $userCar->id = Str::uuid();
        $userCar->user_id = auth()->user()->id;
        $userCar->car_id = $data['car_id'];
        $userCar->color = $data['color'];
        $userCar->year = $data['year'];
        $userCar->thumbnail = $data['thumbnail'];

        $userCar->save();

        return response()->json(['message' => 'Car attached successfully.'], 200);
    }

    /**
 * @OA\Put(
 *     path="/api/v1/users/car/update",
 *     tags={"User"},
 *     security={{"bearerAuth": {}}},
 *     summary="Update attached car details",
 *     description="Update the details of the car attached to the authenticated user",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="year", type="string", description="Car manufacturing year", nullable=true),
 *             @OA\Property(property="thumbnail", type="string", description="Thumbnail URL of the car", nullable=true),
 *             @OA\Property(property="color", type="string", description="Car color", nullable=true)
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Car updated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Car updated successfully.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Car not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Car not found.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="An error occurred",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="An error occurred.")
 *         )
 *     )
 * )
 */

    public function updateAttachedCar(UpdateAttachedCarRequest $request) {
        $data = $request->validated();

        $userCar = UserCar::where('user_id', Auth::user()->id)->firstOrFail();

        if (isset($data['car_id'])) {
            $userCar->car_id = $data['car_id'];
        }
        if (isset($data['color'])) {
            $userCar->color = $data['color'];
        }
        if (isset($data['year'])) {
            $userCar->year = $data['year'];
        }
        if (isset($data['thumbnail'])) {
            $userCar->thumbnail = $data['thumbnail'];
        }

        $userCar->save();

        return response()->json(['message' => 'Car updated successfully.'], 200);
    }

}

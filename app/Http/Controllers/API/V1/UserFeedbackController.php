<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Requests\API\V1\Rating\AddRatingRequest;
use App\Http\Requests\API\V1\Rating\DeleteRatingRequest;
use App\Http\Requests\API\V1\Rating\GetRatingsRequest;
use App\Http\Requests\API\V1\Rating\UpdateRatingRequest;
use App\Http\Requests\API\V1\Report\AddReportRequest;
use App\Http\Requests\API\V1\Report\DeleteReportRequest;
use App\Http\Requests\API\V1\Suggestion\AddSuggestionRequest;
use App\Http\Requests\API\V1\Suggestion\DeleteSuggestionRequest;
use App\Http\Requests\API\V1\Suggestion\GetSuggestionsRequest;
use App\Models\Rating;
use App\Models\Report;
use App\Models\Suggestion;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *     schema="Report",
 *     type="object",
 *     title="Report",
 *     description="Report model",
 *     required={"id", "reporter_id", "reported_user_id", "reason"},
 *     @OA\Property(property="id", type="string", format="uuid", description="Primary key of the report"),
 *     @OA\Property(property="reporter_id", type="string", format="uuid", description="ID of the reporter"),
 *     @OA\Property(property="reported_user_id", type="string", format="uuid", description="ID of the reported user"),
 *     @OA\Property(property="reason", type="integer", description="Reason for the report"),
 *     @OA\Property(property="description", type="string", description="Description of the report"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Timestamp when the report was created"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Timestamp when the report was last updated")
 * )
 *
 * @OA\Schema(
 *     schema="Rating",
 *     type="object",
 *     title="Rating",
 *     description="Rating model",
 *     required={"id", "rated_user_id", "rater_id", "stars_number"},
 *     @OA\Property(property="id", type="string", format="uuid", description="Primary key of the rating"),
 *     @OA\Property(property="rated_user_id", type="string", format="uuid", description="ID of the rated user"),
 *     @OA\Property(property="rater_id", type="string", format="uuid", description="ID of the rater"),
 *     @OA\Property(property="stars_number", type="integer", description="Number of stars"),
 *     @OA\Property(property="description", type="string", description="Description of the rating"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Timestamp when the rating was created"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Timestamp when the rating was last updated")
 * )
 *
 * @OA\Schema(
 *     schema="Suggestion",
 *     type="object",
 *     title="Suggestion",
 *     description="Suggestion model",
 *     required={"uuid", "user_id", "type", "content"},
 *     @OA\Property(property="uuid", type="string", format="uuid", description="Primary key of the suggestion"),
 *     @OA\Property(property="user_id", type="string", format="uuid", description="ID of the user who made the suggestion"),
 *     @OA\Property(property="type", type="string", description="Type of the suggestion"),
 *     @OA\Property(property="content", type="string", description="Content of the suggestion"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Timestamp when the suggestion was created"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Timestamp when the suggestion was last updated")
 * )
 */


class UserFeedbackController extends Controller
{

    /**
     * @OA\Post(
     *     path="/api/v1/reports/add/{user}",
     *     summary="Add a report",
     *     description="Create a new report for a user.",
     *     operationId="addReport",
     *     tags={"Reports"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid"),
     *         description="ID of the user to report"
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="reason", type="integer", description="Reason for the report"),
     *             @OA\Property(property="description", type="string", description="Description of the report")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Report added successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function addReport(AddReportRequest $request, $user)
    {
        $data = $request->validated();

        $authUser = auth()->user();

        Report::create([
            'reported_user_id' => $user,
            'reporter_id' => $authUser->id,
            'reason' => $data['reason'],
            'description' => $data['description']
        ]);

        return response()->json(['message' => 'Report added successfully!'], 201);
    }

     /**
     * @OA\Delete(
     *     path="/api/v1/reports/delete/{user}",
     *     summary="Delete a report",
     *     description="Delete a report made by the authenticated user.",
     *     operationId="deleteReport",
     *     tags={"Reports"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid"),
     *         description="ID of the reported user"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Report deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Report not found"
     *     )
     * )
     */

    public function deleteReport(DeleteReportRequest $request, $user)
    {
        $request->validated();
        $authUser = auth()->user();

        $rating = Report::where('reported_user_id',$user)->where('reporter_id',$authUser->id)->first();
        if(!$rating){
            return response()->json(['message' => 'Report not found.'], 404);
        }

        $rating->delete();

        return response()->json(['message' => 'Report deleted successfully!'], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/ratings/add/{user}",
     *     summary="Add a rating",
     *     description="Create a new rating for a user.",
     *     operationId="addRating",
     *     tags={"Ratings"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid"),
     *         description="ID of the user to rate"
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="stars_number", type="integer", description="Number of stars"),
     *             @OA\Property(property="description", type="string", description="Description of the rating")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Rating added successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */

    public function addRating(AddRatingRequest $request, $user)
    {
        $data = $request->validated();

        $authUser = auth()->user();

        Rating::create([
            'rated_user_id' => $user,
            'rater_id' => $authUser->id,
            'stars_number' => $data['stars_number'],
            'description' => $data['description']
        ]);

        return response()->json(['message' => 'Rating added successfully!'], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/ratings/update/{user}",
     *     summary="Update a rating",
     *     description="Update an existing rating for a user.",
     *     operationId="updateRating",
     *     tags={"Ratings"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid"),
     *         description="ID of the user to rate"
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="stars_number", type="integer", description="Number of stars"),
     *             @OA\Property(property="description", type="string", description="Description of the rating")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Rating updated successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Rating not found"
     *     )
     * )
     */
    public function updateRating(UpdateRatingRequest $request, $user)
    {
        $data = $request->validated();
        $authUser = auth()->user();

        $rating = Rating::where('rated_user_id',$user)->where('rater_id',$authUser->id)->first();
        if(!$rating){
            return response()->json(['message' => 'Rating not found.'], 404);
        }

        $rating->stars_number = $data['stars_number'];
        $rating->description = $data['description'];

        $rating->save();

        return response()->json(['message' => 'Rating updated successfully!'], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/ratings/delete/{user}",
     *     summary="Delete a rating",
     *     description="Delete an existing rating for a user.",
     *     operationId="deleteRating",
     *     tags={"Ratings"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid"),
     *         description="ID of the rated user"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Rating deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Rating not found"
     *     )
     * )
     */

    public function deleteRating(DeleteRatingRequest $request, $user)
    {
        $request->validated();
        $authUser = auth()->user();
        $rating = Rating::where('rated_user_id',$user)->where('rater_id',$authUser->id)->first();
        if(!$rating){
            return response()->json(['message' => 'Rating not found.'], 404);
        }

        $rating->delete();

        return response()->json(['message' => 'Rating deleted successfully!'], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/ratings/get/{user}",
     *     summary="Get all ratings for a user",
     *     description="Retrieve all ratings for a user.",
     *     operationId="getRatings",
     *     tags={"Ratings"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid"),
     *         description="ID of the rated user"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ratings fetched successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Rating")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Rating not found"
     *     )
     * )
     */
    public function getRatings(GetRatingsRequest $request, $user)
    {
        $request->validated();
        $ratings = Rating::where('rated_user_id',$user)->get();
        $ratings->load("ratedUser","rater");
        foreach ($ratings as $rating) {
            if ($rating->ratedUser) {
                unset($rating->ratedUser->password);
                unset($rating->ratedUser->email);
                unset($rating->ratedUser->role);
                unset($rating->ratedUser->is_admin);
            }
            if ($rating->rater) {
                unset($rating->rater->password);
                unset($rating->rater->email);
                unset($rating->rater->role);
                unset($rating->rater->is_admin);
            }
        }

        if(!$ratings){
            return response()->json(['message' => 'Rating not found.'], 404);
        }
        return response()->json(['ratings' => $ratings], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/suggestions/add",
     *     summary="Add a suggestion",
     *     description="Create a new suggestion.",
     *     operationId="addSuggestion",
     *     tags={"Suggestions"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="suggestion_type", type="string", description="Type of the suggestion"),
     *             @OA\Property(property="suggestion_content", type="string", description="Content of the suggestion")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Suggestion added successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function addSuggestion(AddSuggestionRequest $request)
    {
        $data = $request->validated();

        $authUser = auth()->user();

        Suggestion::create([
            'user_id' => $authUser->id,
            'type' =>  $data['suggestion_type'],
            'content' => $data['suggestion_content']
        ]);

        return response()->json(['message' => 'Suggestion added successfully!'], 201);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/suggestions/delete/{suggestion}",
     *     summary="Delete a suggestion",
     *     description="Delete an existing suggestion.",
     *     operationId="deleteSuggestion",
     *     tags={"Suggestions"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="suggestion",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid"),
     *         description="ID of the suggestion"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Suggestion deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Suggestion not found"
     *     )
     * )
     */

    public function deleteSuggestion(DeleteSuggestionRequest $request, $suggestion)
    {
        $request->validated();
        $suggestion = Suggestion::where('id',$suggestion)->first();
        if(!$suggestion){
            return response()->json(['message' => 'Suggestion not found.'], 404);
        }

        $suggestion->delete();

        return response()->json(['message' => 'Suggestion deleted successfully!'], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/suggestions/get",
     *     summary="Get all suggestions",
     *     description="Retrieve all suggestions.",
     *     operationId="getSuggestions",
     *     tags={"Suggestions"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Suggestions fetched successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Suggestion")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No suggestions found"
     *     )
     * )
     */
    public function getSuggestions(GetSuggestionsRequest $request)
    {
        $request->validated();
        $suggestions = Suggestion::with(['user' => function($query) {
            $query->select('id', 'first_name', 'last_name','profile_picture');
        }])->get();

        if ($suggestions->isEmpty()) {
            return response()->json(['message' => 'No suggestions.', 'suggestions' => []], 200);
        }

        return response()->json(['message' => 'Suggestions fetched successfully!', 'suggestions' => $suggestions], 200);
    }
}

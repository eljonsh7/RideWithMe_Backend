<?php

namespace App\Http\Controllers;

use App\Models\Rating;
use App\Models\Report;
use Illuminate\Http\Request;

class UserFeedbackController extends Controller
{
    public function addReport(Request $request, $user)
    {
        $request->validate([
            'reason' => 'required|string'
        ]);

        $authUser = auth()->user();

        Report::create([
            'reported_user_id' => $user,
            'reporter_id' => $authUser->id,
            'reason' => $request->reason
        ]);

        return response()->json(['message' => 'Report added successfully!'], 201);
    }

    public function deleteReport($user)
    {
        $authUser = auth()->user();

        $rating = Report::where('reported_user_id',$user)->where('reporter_id',$authUser->id)->first();
        if(!$rating){
            return response()->json(['message' => 'Report not found.'], 404);
        }

        $rating->delete();

        return response()->json(['message' => 'Report deleted successfully!'], 200);
    }

    public function addRating(Request $request, $user)
    {
        $request->validate([
            'stars_number' => 'required|integer'
        ]);

        $authUser = auth()->user();

        Rating::create([
            'rated_user_id' => $user,
            'rater_id' => $authUser->id,
            'stars_number' => $request->stars_number
        ]);

        return response()->json(['message' => 'Rating added successfully!'], 201);
    }

    public function updateRating(Request $request, $user)
    {
        $request->validate([
            'stars_number' => 'required|integer'
        ]);
        $authUser = auth()->user();

        $rating = Rating::where('rated_user_id',$user)->where('rater_id',$authUser->id)->first();
        if(!$rating){
            return response()->json(['message' => 'Rating not found.'], 404);
        }

        $rating->stars_number = $request->stars_number;

        $rating->save();

        return response()->json(['message' => 'Rating updated successfully!'], 200);
    }

    public function deleteRating($user)
    {

        $authUser = auth()->user();
        $rating = Rating::where('rated_user_id',$user)->where('rater_id',$authUser->id)->first();
        if(!$rating){
            return response()->json(['message' => 'Rating not found.'], 404);
        }

        $rating->delete();

        return response()->json(['message' => 'Rating deleted successfully!'], 200);
    }
}

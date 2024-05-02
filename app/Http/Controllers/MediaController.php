<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function store(Request $request)
    {
        $filePath = $request->file('media')->store('cars', 'public');
        return response()->json(['message' => 'File stored successfully', 'file_path' => $filePath], 201);
    }
}

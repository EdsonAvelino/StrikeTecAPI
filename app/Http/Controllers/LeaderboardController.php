<?php

namespace App\Http\Controllers;

use App\Leaderboard;

class LeaderboardController extends Controller
{
    public function getList()
    {
        $leadersList = Leaderboard::with(['user' => function ($query) {
                $query->select(['first_name', 'last_name']);
            }])->orderBy('punches_count', 'desc')->get();

        return response()->json(['error' => 'false', 'message' => '', 'data' => $leadersList->toArray()]);
    }
}

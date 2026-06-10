<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Event;

class AdminController extends Controller
{
    public function index()
    {
        // Statistik global untuk super admin
        $totalUsers = User::count();
        $totalEvents = Event::count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_users' => $totalUsers,
                'total_events' => $totalEvents,
            ]
        ]);
    }
}

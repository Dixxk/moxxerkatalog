<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class OwnerDashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Owner/Dashboard', [
            'owner' => Auth::guard('owner')->user(),
        ]);
    }
}
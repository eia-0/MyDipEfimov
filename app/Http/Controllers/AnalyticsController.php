<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function index()
    {
        $portfolios = Portfolio::where('user_id', auth()->id())->get();
        return view('analytics', compact('portfolios'));
    }
}
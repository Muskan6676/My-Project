<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class VisitController extends Controller
{
    public function index()
    {
        $visits = Visit::all();
        return view('visits.index', compact('visits'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'visitor_name' => 'required|string|max:255',
        ]);

        Visit::create($request->all());

        return redirect('/visits')->with('success', 'Visit scheduled successfully.');
    }
}


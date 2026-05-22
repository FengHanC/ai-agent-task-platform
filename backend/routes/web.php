<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\Agent;

Route::get('/', function () {
    return Inertia::render('Welcome');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->name('dashboard');

// Agent 页面路由
Route::get('/agents', function () {
    return Inertia::render('Agents/Index', [
        'agents' => Agent::paginate(15),
    ]);
})->name('agents.index');

Route::get('/agents/create', function () {
    return Inertia::render('Agents/Create');
})->name('agents.create');

Route::get('/agents/{agent}', function (Agent $agent) {
    return Inertia::render('Agents/Show', [
        'agent' => $agent->load('tasks'),
    ]);
})->name('agents.show');

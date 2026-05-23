<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Private channel for task-specific broadcasting
// Development mode: allow all authenticated users
Broadcast::channel('tasks.{taskId}', function ($user) {
    return true;
});

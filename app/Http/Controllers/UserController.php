<?php

namespace App\Http\Controllers;

use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        return User::all();
    }

    public function get(User $user)
    {
        return $user;
    }

    public function me()
    {
        return auth('sanctum')->user();
    }
}

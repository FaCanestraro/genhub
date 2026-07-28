<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class MenuController extends Controller
{
    public function index()
    {
        return response()->json(config('menus'));
    }
}

<?php

namespace App\Http\Controllers;

class ManagementController extends Controller
{
    public function index()
    {
        return view('management.index');
    }
}
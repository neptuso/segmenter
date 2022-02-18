<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EdicionController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $user = Auth::user();
        return view('edicion')->with(['user'=>$user]);
    }

    /**
     * Edicion DELETE application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function delete()
    {
        return view('edicion.delete');
    }

}

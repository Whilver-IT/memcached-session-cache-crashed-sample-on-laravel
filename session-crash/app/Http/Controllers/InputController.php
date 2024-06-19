<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\InputRequest;

class InputController extends Controller {

    public function input(Request $request)
    {
        return view('input');
    }

    public function inputPost(InputRequest $request)
    {
        return view('inputPost');
    }
}
<?php

namespace App\Http\Controllers\Ahhob\Blog\Web;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AboutController extends Controller
{
    public function index(): View
    {
        return view('web.home.contract');
    }
}

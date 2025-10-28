<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TestArweaveController extends Controller
{
    /**
     * Show the Arweave test page
     */
    public function showTestPage()
    {
        return view('test-arweave');
    }
}

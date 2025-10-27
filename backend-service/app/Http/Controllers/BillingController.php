<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function subscriptions()
    {
        return view('billing.subscriptions');
    }
}

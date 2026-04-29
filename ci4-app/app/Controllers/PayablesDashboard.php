<?php

namespace App\Controllers;

class PayablesDashboard extends BaseController
{
    public function index(): string
    {
        return view('payables_dashboard');
    }
}

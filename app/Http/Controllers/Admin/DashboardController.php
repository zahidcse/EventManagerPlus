<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\EventRepositoryInterface;

class DashboardController extends Controller
{
    public function __construct(
        private readonly EventRepositoryInterface $events,
    ) {}

    public function index()
    {
        return view('admin.dashboard', [
            'activeNav' => 'dashboard',
            'overview' => $this->events->dashboardOverview(),
        ]);
    }
}

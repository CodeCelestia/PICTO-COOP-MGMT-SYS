<?php

namespace App\Http\Controllers;

use App\Services\HomepageDisplayService;
use Inertia\Inertia;
use Inertia\Response;

class HomepageController extends Controller
{
    public function __construct(
        protected HomepageDisplayService $homepageDisplayService,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('Homepage', $this->homepageDisplayService->payload());
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class LearnedPatternController extends Controller
{
    /**
     * Display the learned patterns management page.
     */
    public function index(): Response
    {
        return Inertia::render('LearnedPatterns/Index');
    }
}

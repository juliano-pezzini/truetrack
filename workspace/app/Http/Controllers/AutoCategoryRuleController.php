<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class AutoCategoryRuleController extends Controller
{
    /**
     * Display the auto-category rules management page.
     */
    public function index(): Response
    {
        return Inertia::render('AutoCategoryRules/Index');
    }
}

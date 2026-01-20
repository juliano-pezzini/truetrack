<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\OfxImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class OfxImportController extends Controller
{
    /**
     * Display OFX import page.
     */
    public function index(Request $request)
    {
        // Get user's accounts
        $accounts = Account::where('user_id', Auth::id())
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        // Get recent imports for the user
        $imports = OfxImport::with(['account', 'reconciliation'])
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return Inertia::render('OfxImports/Index', [
            'accounts' => $accounts,
            'imports' => $imports,
        ]);
    }
}


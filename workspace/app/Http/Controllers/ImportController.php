<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\OfxImport;
use App\Models\Reconciliation;
use App\Models\XlsxImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ImportController extends Controller
{
    /**
     * Display unified import page (OFX + XLSX/CSV).
     */
    public function index(Request $request)
    {
        // Get user's accounts
        $accounts = Account::where('user_id', Auth::id())
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        // Get recent OFX imports
        $ofxImports = OfxImport::with(['account', 'reconciliation'])
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function (OfxImport $import): array {
                $matchedCount = 0;
                if ($import->reconciliation instanceof Reconciliation) {
                    $matchedCount = $import->reconciliation->transactions()->count();
                }

                return [
                    'id' => $import->id,
                    'type' => 'ofx',
                    'filename' => $import->filename,
                    'account' => $import->account,
                    'status' => $import->status,
                    'processed_count' => $import->processed_count,
                    'matched_count' => $matchedCount,
                    'progress' => $import->getProgressPercentage(),
                    'error_message' => $import->error_message,
                    'reconciliation_id' => $import->reconciliation_id,
                    'created_at' => $import->created_at,
                ];
            });

        // Get recent XLSX imports (if table exists)
        $xlsxImports = collect([]);
        if (class_exists(\App\Models\XlsxImport::class)) {
            try {
                $xlsxImports = XlsxImport::with(['account', 'reconciliation'])
                    ->where('user_id', Auth::id())
                    ->orderBy('created_at', 'desc')
                    ->limit(20)
                    ->get()
                    ->map(function (XlsxImport $import): array {
                        return [
                            'id' => $import->id,
                            'type' => 'xlsx',
                            'filename' => $import->filename,
                            'account' => $import->account,
                            'status' => $import->status,
                            'processed_count' => $import->processed_count,
                            'skipped_count' => $import->skipped_count ?? 0,
                            'duplicate_count' => $import->duplicate_count ?? 0,
                            'progress' => $import->getProgressPercentage(),
                            'error_message' => $import->error_message,
                            'has_errors' => $import->hasErrors(),
                            'reconciliation_id' => $import->reconciliation_id,
                            'created_at' => $import->created_at,
                        ];
                    });
            } catch (\Exception $e) {
                // XLSX imports table doesn't exist yet
            }
        }

        // Combine and sort by created_at
        $allImports = $ofxImports->concat($xlsxImports)
            ->sortByDesc('created_at')
            ->values()
            ->take(20);

        return Inertia::render('Imports/Index', [
            'accounts' => $accounts,
            'imports' => $allImports,
        ]);
    }
}

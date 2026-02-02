<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\ImportRulesRequest;
use App\Http\Requests\StoreAutoRuleRequest;
use App\Http\Requests\UpdateAutoRuleRequest;
use App\Http\Resources\AutoCategoryRuleResource;
use App\Models\AutoCategoryRule;
use App\Services\AutoCategorizationService;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AutoCategoryRuleController extends \App\Http\Controllers\Controller
{
    use AuthorizesRequests;

    public function __construct(
        private AutoCategorizationService $categorizationService,
    ) {}

    /**
     * Get all auto-category rules for the authenticated user.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = AutoCategoryRule::where('user_id', auth()->id())
            ->whereNull('deleted_at');

        // Filter by active status
        if ($request->has('filter.active')) {
            $isActive = $request->boolean('filter.active');
            if ($isActive) {
                $query->active();
            } else {
                $query->archived();
            }
        }

        // Filter by category
        if ($request->has('filter.category_id')) {
            $query->where('category_id', $request->integer('filter.category_id'));
        }

        // Sort by priority or other fields
        $sort = $request->input('sort', '-created_at');
        if ($sort === 'priority' || $sort === '-priority') {
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $query->orderBy('priority', $direction);
        } else {
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $field = ltrim($sort, '-');
            $query->orderBy($field, $direction);
        }

        $rules = $query->paginate($request->integer('per_page', 15));

        return AutoCategoryRuleResource::collection($rules);
    }

    /**
     * Create a new auto-category rule.
     */
    public function store(StoreAutoRuleRequest $request): JsonResponse
    {
        $rule = AutoCategoryRule::create([
            'user_id' => auth()->id(),
            'pattern' => $request->input('pattern'),
            'category_id' => $request->input('category_id'),
            'priority' => $request->input('priority'),
            'is_active' => true,
        ]);

        return response()->json([
            'data' => new AutoCategoryRuleResource($rule),
            'message' => 'Rule created successfully',
        ], 201);
    }

    /**
     * Get a specific auto-category rule.
     */
    public function show(Request $request, AutoCategoryRule $auto_category_rule): AutoCategoryRuleResource
    {

        $this->authorize('view', $auto_category_rule);

        return new AutoCategoryRuleResource($auto_category_rule);
    }

    /**
     * Update an auto-category rule.
     */
    public function update(UpdateAutoRuleRequest $request, AutoCategoryRule $auto_category_rule): JsonResponse
    {
        $this->authorize('update', $auto_category_rule);

        $auto_category_rule->update([
            'pattern' => $request->input('pattern'),
            'category_id' => $request->input('category_id'),
            'priority' => $request->input('priority'),
        ]);

        return response()->json([
            'data' => new AutoCategoryRuleResource($auto_category_rule),
            'message' => 'Rule updated successfully',
        ]);
    }

    /**
     * Delete an auto-category rule.
     */
    public function destroy(AutoCategoryRule $auto_category_rule): JsonResponse
    {
        $this->authorize('delete', $auto_category_rule);

        $auto_category_rule->delete();

        return response()->json([
            'message' => 'Rule deleted successfully',
        ], 204);
    }

    /**
     * Archive a rule.
     */
    public function archive(AutoCategoryRule $auto_category_rule): JsonResponse
    {
        $this->authorize('archive', $auto_category_rule);

        $auto_category_rule->archive();

        return response()->json([
            'data' => new AutoCategoryRuleResource($auto_category_rule),
            'message' => 'Rule archived successfully',
        ]);
    }

    /**
     * Restore a rule from archive.
     */
    public function restore(AutoCategoryRule $auto_category_rule): JsonResponse
    {
        $this->authorize('archive', $auto_category_rule);

        $auto_category_rule->restore();

        return response()->json([
            'data' => new AutoCategoryRuleResource($auto_category_rule),
            'message' => 'Rule restored successfully',
        ]);
    }

    /**
     * Reorder rules by updating priorities.
     * Uses two-pass update to avoid unique constraint violations.
     */
    public function reorder(Request $request): JsonResponse
    {
        $this->authorize('reorder', AutoCategoryRule::class);

        $request->validate([
            'rules' => 'required|array',
            'rules.*.id' => 'required|integer|exists:auto_category_rules,id',
            'rules.*.priority' => 'required|integer|min:1|max:1000',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($request) {
            // Verify ownership for all rules first
            foreach ($request->input('rules') as $ruleData) {
                $rule = AutoCategoryRule::find($ruleData['id']);
                if ($rule->user_id !== auth()->id()) {
                    throw new \Illuminate\Auth\Access\AuthorizationException('Unauthorized');
                }
            }

            // Pass 1: Move all rules to temporary negative priorities
            foreach ($request->input('rules') as $index => $ruleData) {
                AutoCategoryRule::where('id', $ruleData['id'])
                    ->update(['priority' => -1 * ($index + 1)]);
            }

            // Pass 2: Set final priorities
            foreach ($request->input('rules') as $ruleData) {
                AutoCategoryRule::where('id', $ruleData['id'])
                    ->update(['priority' => $ruleData['priority']]);
            }
        });

        return response()->json([
            'message' => 'Rules reordered successfully',
        ]);
    }

    /**
     * Test rules coverage against uncategorized transactions.
     */
    public function testCoverage(Request $request): JsonResponse
    {
        $this->authorize('testRules', AutoCategoryRule::class);

        $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
        ]);

        $coverage = $this->categorizationService->testRulesCoverage(
            auth()->id(),
            Carbon::parse($request->input('from_date')),
            Carbon::parse($request->input('to_date'))
        );

        return response()->json([
            'data' => $coverage,
        ]);
    }

    /**
     * Export rules as JSON or CSV.
     */
    public function export(Request $request): JsonResponse
    {
        $this->authorize('export', AutoCategoryRule::class);

        $request->validate([
            'format' => 'in:json,csv',
        ]);

        $rules = AutoCategoryRule::where('user_id', auth()->id())
            ->active()
            ->orderBy('priority')
            ->get();

        $format = $request->input('format', 'json');

        if ($format === 'csv') {
            return response()->json([
                'data' => $this->formatRulesAsCSV($rules),
                'filename' => 'auto-rules-'.now()->format('Y-m-d').'.csv',
            ]);
        }

        return response()->json([
            'data' => $rules,
            'filename' => 'auto-rules-'.now()->format('Y-m-d').'.json',
        ]);
    }

    /**
     * Import rules from file.
     */
    public function import(ImportRulesRequest $request): JsonResponse
    {
        $this->authorize('import', AutoCategoryRule::class);

        $file = $request->file('import_file');
        $strategy = $request->input('merge_strategy', 'skip_duplicates');
        $imported = 0;
        $skipped = 0;
        $errors = [];

        try {
            $content = file_get_contents($file->getRealPath());

            if ($file->getClientOriginalExtension() === 'json') {
                $data = json_decode($content, true);
                if (! is_array($data)) {
                    $data = [$data];
                }
            } else {
                // CSV parsing
                $rows = array_map('str_getcsv', explode("\n", $content));
                $headers = array_shift($rows);
                $data = [];

                foreach ($rows as $row) {
                    if (count($row) >= 3) {
                        $data[] = [
                            'pattern' => $row[0] ?? null,
                            'category_id' => $row[1] ?? null,
                            'priority' => $row[2] ?? null,
                        ];
                    }
                }
            }

            foreach ($data as $index => $ruleData) {
                try {
                    // Check for duplicates
                    $existing = AutoCategoryRule::where('user_id', auth()->id())
                        ->where('pattern', strtolower($ruleData['pattern'] ?? ''))
                        ->where('category_id', $ruleData['category_id'] ?? null)
                        ->first();

                    if ($existing && $strategy === 'skip_duplicates') {
                        $skipped++;

                        continue;
                    }

                    if ($existing && $strategy === 'merge') {
                        // Update existing rule
                        $existing->update([
                            'priority' => $ruleData['priority'] ?? $existing->priority,
                        ]);
                        $imported++;
                    } else {
                        // Create new rule
                        AutoCategoryRule::create([
                            'user_id' => auth()->id(),
                            'pattern' => strtolower($ruleData['pattern'] ?? ''),
                            'category_id' => $ruleData['category_id'],
                            'priority' => $ruleData['priority'],
                            'is_active' => true,
                        ]);
                        $imported++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Row $index: ".$e->getMessage();
                }
            }

            return response()->json([
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Import failed: '.$e->getMessage(),
            ], 400);
        }
    }

    /**
     * Format rules as CSV string.
     */
    private function formatRulesAsCSV($rules): string
    {
        $csv = "Pattern,Category ID,Priority\n";

        foreach ($rules as $rule) {
            $csv .= "\"{$rule->pattern}\",{$rule->category_id},{$rule->priority}\n";
        }

        return $csv;
    }
}

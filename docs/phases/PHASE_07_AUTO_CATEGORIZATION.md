# Phase 7: Transaction Auto-Categorization with Learning

**Goal**: Intelligent category suggestion using rule-based patterns and machine learning from user behavior

**Status**: Planned

**Estimated Effort**: 2-3 weeks

---

## Overview

Implement intelligent transaction categorization using **Auto-Category Rules** (explicit pattern matching) and **Learned Patterns** (frequency-based machine learning from user corrections). Uses Laravel Model Observer pattern to centralize categorization logic in a single hook that automatically fires for ALL transaction creation methods (manual, imports, reconciliation, tests, future features) without requiring changes to 20+ existing creation points.

**Key Innovation**: Single `TransactionObserver::creating()` event eliminates maintenance nightmare of modifying multiple controllers, jobs, and services.

---

## Deliverables

### Phase 7.1: Architecture Refactoring (Week 1)
**PREREQUISITE**: Separate PR required before Phase 07 begins

- **Bug Fix**: Refactor [ProcessXlsxImport:209](../../workspace/app/Jobs/ProcessXlsxImport.php) to use `AccountingService::recordTransaction()`
- **Bug Fix**: Refactor [ReconciliationService](../../workspace/app/Services/ReconciliationService.php) credit card closures
- **Issue**: Both currently bypass `AccountingService`, causing stale monthly balance snapshots

### Phase 7.2: Observer Pattern Infrastructure (Week 1)

**Models**
- `TransactionObserver` - Intercepts transaction creation events

**Service Provider**
- Update `AppServiceProvider::boot()` - Register observer

### Phase 7.3: Core Auto-Categorization (Week 1-2)

**Models**
- `AutoCategoryRule` - Explicit pattern-to-category rules with priority
- `AutoCategoryCorrection` - User correction tracking for learning
- `LearnedCategoryPattern` - Keyword-to-category associations with confidence scores
- `AutoCategorySuggestionLog` - Suggestion history and acceptance tracking

**Migrations**
- `create_auto_category_rules_table` - Rules with unique priority constraint
- `create_auto_category_corrections_table` - Correction audit trail
- `create_learned_category_patterns_table` - ML pattern storage
- `create_auto_category_suggestions_log_table` - Analytics logging

**Services**
- `AutoCategorizationService` - Pattern matching, overlap detection, coverage testing
- `AutoCategoryLearningService` - Keyword extraction, confidence scoring, pattern updates

### Phase 7.4: API & Controllers (Week 2)

**Controllers**
- `Api\V1\AutoCategoryRuleController` - CRUD + reorder + test + import/export
- `Api\V1\LearnedPatternController` - View, disable, delete, convert to rule
- Update `TransactionController::update()` - Trigger learning on category changes

**Form Requests**
- `StoreAutoCategoryRuleRequest` - Validation with priority uniqueness
- `UpdateAutoCategoryRuleRequest` - Validation
- `ImportAutoCategoryRulesRequest` - JSON validation with auto-category creation
- `ConvertLearnedPatternRequest` - Convert pattern to rule

**Policies**
- `AutoCategoryRulePolicy` - Ownership checks
- `LearnedPatternPolicy` - Ownership checks

### Phase 7.5: React UI (Week 2-3)

**Pages**
- `AutoCategoryRules/Index.jsx` - Draggable priority list with import/export
- `LearnedPatterns/Index.jsx` - Pattern management with conversion

**Components**
- `AutoCategoryRules/RuleForm.jsx` - Create/edit rules with overlap warnings
- `AutoCategoryRules/ConvertPatternModal.jsx` - Convert learned pattern to rule
- `AutoCategoryRules/TestCoverageModal.jsx` - Bulk rule testing results
- `CategorySuggestion.jsx` - Suggestion panel with match highlighting
- `MatchHighlight.jsx` - Reusable text highlighter component

### Phase 7.6: Testing (Week 3)

**PHPUnit Tests** (50+ tests)
- Observer behavior tests
- Pattern matching accuracy tests
- Keyword extraction tests
- Confidence calculation tests
- Learning trigger tests
- Archive/restore tests
- Import/export tests
- Coverage testing tests

**Jest Tests** (20+ tests)
- Drag-drop reordering tests
- Archive/restore UI tests
- Import/export workflow tests
- Overlap warning display tests
- Coverage modal tests
- Match highlighting tests

---

## Models

### AutoCategoryRule Model

```php
class AutoCategoryRule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'pattern',
        'category_id',
        'priority',
        'is_active',
        'archived_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'archived_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo { }
    public function category(): BelongsTo { }

    // Scopes
    public function scopeActive(Builder $query): Builder { }
    public function scopeArchived(Builder $query): Builder { }
    public function scopeOrderedByPriority(Builder $query): Builder { }
}
```

**Attributes:**
- `id` - Primary key
- `user_id` - Foreign key to users
- `pattern` - Substring pattern (case-insensitive, e.g., "walmart")
- `category_id` - Foreign key to categories
- `priority` - Integer (unique per user, gap spacing 10, 20, 30...)
- `is_active` - Boolean (false when archived)
- `archived_at` - Timestamp (nullable, set when archived)
- `created_at`, `updated_at`, `deleted_at`

### LearnedCategoryPattern Model

```php
class LearnedCategoryPattern extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'keyword',
        'occurrence_count',
        'confidence_score',
        'first_learned_at',
        'last_matched_at',
        'is_active',
    ];

    protected $casts = [
        'first_learned_at' => 'datetime',
        'last_matched_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function user(): BelongsTo { }
    public function category(): BelongsTo { }

    // Scopes
    public function scopeActive(Builder $query): Builder { }
    public function scopeForUser(Builder $query, int $userId): Builder { }
    public function scopeAboveThreshold(Builder $query): Builder { }
}
```

**Attributes:**
- `id` - Primary key
- `user_id` - Foreign key to users
- `category_id` - Foreign key to categories
- `keyword` - Extracted keyword (normalized lowercase)
- `occurrence_count` - Number of times user chose this category for this keyword
- `confidence_score` - Integer 0-100 (formula: min(95, 50 + (count * 5) + recency_bonus))
- `first_learned_at` - Timestamp when first learned
- `last_matched_at` - Timestamp when last matched (nullable)
- `is_active` - Boolean (user can disable patterns)
- `created_at`, `updated_at`

### AutoCategoryCorrection Model

```php
class AutoCategoryCorrection extends Model
{
    protected $fillable = [
        'user_id',
        'transaction_id',
        'original_category_id',
        'corrected_category_id',
        'description_text',
        'correction_type',
        'confidence_at_correction',
    ];

    protected $casts = [
        'confidence_at_correction' => 'integer',
    ];

    // Relationships
    public function user(): BelongsTo { }
    public function transaction(): BelongsTo { }
    public function originalCategory(): BelongsTo { }
    public function correctedCategory(): BelongsTo { }
}
```

**Attributes:**
- `id` - Primary key
- `user_id` - Foreign key to users
- `transaction_id` - Foreign key to transactions
- `original_category_id` - Foreign key to categories (nullable, NULL if was uncategorized)
- `corrected_category_id` - Foreign key to categories
- `description_text` - Transaction description at time of correction
- `correction_type` - Enum: 'override', 'manual_assign', 'reject_suggestion'
- `confidence_at_correction` - Integer 0-100 (nullable, confidence if from suggestion)
- `created_at`

### AutoCategorySuggestionLog Model

```php
class AutoCategorySuggestionLog extends Model
{
    protected $fillable = [
        'user_id',
        'transaction_id',
        'suggested_category_id',
        'confidence_score',
        'matched_keywords',
        'suggestion_source',
        'user_action',
        'suggested_at',
        'responded_at',
    ];

    protected $casts = [
        'confidence_score' => 'integer',
        'matched_keywords' => 'array',
        'suggested_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo { }
    public function transaction(): BelongsTo { }
    public function suggestedCategory(): BelongsTo { }
}
```

**Attributes:**
- `id` - Primary key
- `user_id` - Foreign key to users
- `transaction_id` - Foreign key to transactions
- `suggested_category_id` - Foreign key to categories
- `confidence_score` - Integer 0-100
- `matched_keywords` - JSON array of matched keywords
- `suggestion_source` - Enum: 'explicit_rule', 'learned_pattern', 'hybrid'
- `user_action` - Enum: 'accepted', 'rejected', 'ignored', 'pending'
- `suggested_at` - Timestamp when suggestion made
- `responded_at` - Timestamp when user responded (nullable)

---

## Database Schema

### auto_category_rules Table
```sql
CREATE TABLE auto_category_rules (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    pattern VARCHAR(255) NOT NULL,
    category_id BIGINT NOT NULL,
    priority INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    archived_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    UNIQUE INDEX idx_user_priority (user_id, priority),
    INDEX idx_user_active (user_id, is_active),
    INDEX idx_user_archived (user_id, archived_at)
);
```

### learned_category_patterns Table
```sql
CREATE TABLE learned_category_patterns (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    category_id BIGINT NOT NULL,
    keyword VARCHAR(255) NOT NULL,
    occurrence_count INT DEFAULT 1,
    confidence_score TINYINT DEFAULT 50,
    first_learned_at TIMESTAMP,
    last_matched_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    UNIQUE INDEX idx_user_keyword_category (user_id, keyword, category_id),
    INDEX idx_active_patterns (user_id, is_active, confidence_score),
    INDEX idx_category_keywords (category_id)
);
```

### auto_category_corrections Table
```sql
CREATE TABLE auto_category_corrections (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    transaction_id BIGINT NOT NULL,
    original_category_id BIGINT NULL,
    corrected_category_id BIGINT NOT NULL,
    description_text TEXT NOT NULL,
    correction_type ENUM('override', 'manual_assign', 'reject_suggestion') NOT NULL,
    confidence_at_correction TINYINT NULL,
    created_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (original_category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (corrected_category_id) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idx_user_corrections (user_id, created_at),
    INDEX idx_transaction_lookup (transaction_id)
);
```

### auto_category_suggestions_log Table
```sql
CREATE TABLE auto_category_suggestions_log (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    transaction_id BIGINT NOT NULL,
    suggested_category_id BIGINT NOT NULL,
    confidence_score TINYINT NOT NULL,
    matched_keywords JSON,
    suggestion_source ENUM('explicit_rule', 'learned_pattern', 'hybrid') NOT NULL,
    user_action ENUM('accepted', 'rejected', 'ignored', 'pending') DEFAULT 'pending',
    suggested_at TIMESTAMP,
    responded_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (suggested_category_id) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idx_pending_suggestions (user_id, user_action, suggested_at),
    INDEX idx_transaction_suggestions (transaction_id)
);
```

---

## Observer Pattern Architecture

### TransactionObserver

```php
<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Transaction;
use App\Services\AutoCategorizationService;

class TransactionObserver
{
    public function __construct(
        private readonly AutoCategorizationService $autoCategorizationService
    ) {}

    /**
     * Handle the Transaction "creating" event.
     * 
     * Auto-categorize transactions when category is not provided.
     * Fires BEFORE transaction is inserted into database.
     */
    public function creating(Transaction $transaction): void
    {
        // Skip if category already set
        if ($transaction->category_id !== null) {
            return;
        }
        
        // Skip if no description to work with
        if (empty($transaction->description)) {
            return;
        }
        
        // Attempt auto-categorization
        $suggestion = $this->autoCategorizationService->suggestCategory(
            userId: $transaction->user_id,
            description: $transaction->description,
            amount: (float) $transaction->amount
        );
        
        // Apply if confidence meets threshold
        if ($suggestion && $this->autoCategorizationService->shouldAutoApply($suggestion['confidence'])) {
            $transaction->category_id = $suggestion['category_id'];
        }
    }
}
```

**Registration** (in `AppServiceProvider::boot()`):
```php
use App\Models\Transaction;
use App\Observers\TransactionObserver;

public function boot(): void
{
    Vite::prefetch(concurrency: 3);
    
    // Register Transaction observer for auto-categorization
    Transaction::observe(TransactionObserver::class);
}
```

**Benefits:**
1. ✅ **Single Integration Point** - One observer handles ALL transaction creation
2. ✅ **Transparent** - Existing controllers/jobs unchanged
3. ✅ **Future-Proof** - New transaction creation automatically categorizes
4. ✅ **Testable** - Can disable with `Transaction::unsetEventDispatcher()`
5. ✅ **Laravel Convention** - Follows framework best practices

---

## API Endpoints

### Auto-Category Rules

**GET /api/v1/auto-category-rules**
```
Query Parameters:
  - filter[archived] - Boolean (default: false)
  - sort - Sort by field (priority, created_at)
  - per_page - Items per page (default: 15)

Response:
{
  "data": [
    {
      "id": 1,
      "pattern": "walmart",
      "category": {
        "id": 5,
        "name": "Groceries"
      },
      "priority": 10,
      "is_active": true,
      "archived_at": null,
      "created_at": "2026-01-15T10:30:00Z"
    }
  ],
  "meta": { ... }
}
```

**POST /api/v1/auto-category-rules**
```
Request:
{
  "pattern": "walmart",
  "category_id": 5,
  "priority": null,  // Auto-assigned if null
  "is_active": true
}

Response (201 Created):
{
  "data": {
    "id": 1,
    "pattern": "walmart",
    "category_id": 5,
    "priority": 10,  // Auto-assigned
    "overlap_warnings": [
      {
        "rule_id": 8,
        "pattern": "wal",
        "priority": 20,
        "message": "Pattern overlaps with existing rule"
      }
    ]
  }
}
```

**PUT /api/v1/auto-category-rules/{id}**
```
Request: Update pattern, category_id, is_active
Response (200 OK): Updated rule
```

**DELETE /api/v1/auto-category-rules/{id}**
```
Response (204 No Content)
```

**POST /api/v1/auto-category-rules/{id}/archive**
```
Response (200 OK):
{
  "data": {
    "id": 1,
    "is_active": false,
    "archived_at": "2026-01-30T15:45:00Z"
  }
}
```

**POST /api/v1/auto-category-rules/{id}/restore**
```
Response (200 OK):
{
  "data": {
    "id": 1,
    "is_active": true,
    "archived_at": null
  }
}
```

**POST /api/v1/auto-category-rules/reorder**
```
Request:
{
  "rules": [
    {"id": 1, "priority": 10},
    {"id": 2, "priority": 20},
    {"id": 3, "priority": 30}
  ]
}

Response (200 OK): Updated rules
```

**POST /api/v1/auto-category-rules/{id}/test**
```
Request:
{
  "description": "WALMART GROCERY #1234"
}

Response:
{
  "data": {
    "matched": true,
    "match_positions": {
      "start": 0,
      "length": 7
    },
    "category": {
      "id": 5,
      "name": "Groceries"
    }
  }
}
```

**GET /api/v1/auto-category-rules/export**
```
Query Parameters:
  - include_archived - Boolean (default: false)

Response:
{
  "data": [
    {
      "pattern": "walmart",
      "category_name": "Groceries",
      "priority": 10,
      "is_active": true
    }
  ]
}
```

**POST /api/v1/auto-category-rules/import**
```
Request:
{
  "rules": [
    {
      "pattern": "walmart",
      "category_name": "Groceries",
      "priority": 10,
      "is_active": true
    }
  ]
}

Response (201 Created):
{
  "data": {
    "imported": 15,
    "skipped": 2,
    "categories_created": 3,
    "errors": []
  }
}
```

**POST /api/v1/auto-category-rules/test-coverage**
```
Request:
{
  "transaction_limit": 50,
  "include_archived": false
}

Response:
{
  "data": {
    "total": 50,
    "categorized": 42,
    "coverage_percentage": 84,
    "matches": [
      {
        "transaction_id": 123,
        "description": "Walmart Grocery",
        "matched_rule_id": 1,
        "suggested_category": "Groceries"
      }
    ],
    "unmatched": [...]
  }
}
```

### Learned Patterns

**GET /api/v1/learned-patterns**
```
Query Parameters:
  - filter[category_id] - Filter by category
  - filter[is_active] - Filter by active status
  - sort - Sort by confidence_score, occurrence_count
  - per_page - Items per page (default: 15)

Response:
{
  "data": [
    {
      "id": 1,
      "keyword": "walmart",
      "category": {
        "id": 5,
        "name": "Groceries"
      },
      "occurrence_count": 12,
      "confidence_score": 85,
      "first_learned_at": "2026-01-01T00:00:00Z",
      "last_matched_at": "2026-01-28T14:30:00Z",
      "is_active": true
    }
  ]
}
```

**POST /api/v1/learned-patterns/{id}/convert-to-rule**
```
Request:
{
  "pattern": "walmart",  // Pre-filled with keyword
  "category_id": 5,      // Pre-filled from pattern
  "priority": null       // Auto-assigned
}

Response (201 Created): New AutoCategoryRule
```

**PUT /api/v1/learned-patterns/{id}**
```
Request:
{
  "is_active": false
}

Response (200 OK): Updated pattern
```

**DELETE /api/v1/learned-patterns/{id}**
```
Response (204 No Content)
```

**DELETE /api/v1/learned-patterns/clear**
```
Response (200 OK):
{
  "data": {
    "deleted_count": 45
  }
}
```

---

## React Components

### AutoCategoryRules/Index.jsx

**Features:**
- Draggable priority list (react-beautiful-dnd)
- "Show Archived" checkbox toggle
- Archived rules with gray background and "ARCHIVED" badge
- "Archive" button (icon: archive box) for active rules
- "Restore" button (icon: restore arrow) for archived rules
- "Export Rules" button (downloads JSON)
- "Import Rules" button (file upload with progress)
- "Test All Rules" button (opens TestCoverageModal)
- "Create Rule" button

**Example Structure:**
```jsx
export default function Index({ rules, archivedRules }) {
    const [showArchived, setShowArchived] = useState(false);
    const [importing, setImporting] = useState(false);
    
    const handleDragEnd = (result) => {
        // Reorder rules and update priorities
    };
    
    const handleExport = () => {
        // Download rules as JSON
    };
    
    const handleImport = (file) => {
        // Upload and import rules
    };
    
    return (
        <AuthenticatedLayout>
            <Head title="Auto-Category Rules" />
            
            <div className="py-12">
                <div className="flex justify-between items-center mb-6">
                    <h2>Auto-Category Rules</h2>
                    <div className="flex gap-2">
                        <Checkbox 
                            checked={showArchived}
                            onChange={setShowArchived}
                            label="Show Archived"
                        />
                        <button onClick={handleExport}>Export Rules</button>
                        <button onClick={() => fileInput.click()}>Import Rules</button>
                        <button onClick={() => setShowTestModal(true)}>Test All Rules</button>
                        <Link href={route('auto-category-rules.create')}>Create Rule</Link>
                    </div>
                </div>
                
                <DragDropContext onDragEnd={handleDragEnd}>
                    <Droppable droppableId="rules">
                        {(provided) => (
                            <div {...provided.droppableProps} ref={provided.innerRef}>
                                {rules.data.map((rule, index) => (
                                    <Draggable key={rule.id} draggableId={String(rule.id)} index={index}>
                                        {(provided) => (
                                            <RuleCard 
                                                rule={rule}
                                                provided={provided}
                                                onArchive={handleArchive}
                                            />
                                        )}
                                    </Draggable>
                                ))}
                            </div>
                        )}
                    </Droppable>
                </DragDropContext>
                
                {showArchived && <ArchivedRulesSection rules={archivedRules} />}
            </div>
        </AuthenticatedLayout>
    );
}
```

### AutoCategoryRules/RuleForm.jsx

**Features:**
- Pattern input with validation
- Category dropdown (autocomplete)
- Priority input (optional, auto-assigned if empty)
- Active status toggle
- Overlap warnings (yellow alert)
- Test pattern button (inline test)
- Form validation

**Overlap Warning Display:**
```jsx
{overlapWarnings.length > 0 && (
    <div className="bg-yellow-50 border border-yellow-200 rounded p-4 mb-4">
        <h4 className="font-medium text-yellow-900">Pattern Overlap Detected</h4>
        <p className="text-sm text-yellow-700 mt-1">
            Your pattern overlaps with {overlapWarnings.length} existing rule(s):
        </p>
        <ul className="mt-2 text-sm text-yellow-700">
            {overlapWarnings.map(warning => (
                <li key={warning.rule_id}>
                    • Priority {warning.priority}: "{warning.pattern}"
                </li>
            ))}
        </ul>
        <p className="text-xs text-yellow-600 mt-2">
            The rule with highest priority will be used first.
        </p>
    </div>
)}
```

### LearnedPatterns/Index.jsx

**Features:**
- Table with columns: Keyword, Category, Confidence %, Occurrences, Last Matched, Actions
- Progress bars for confidence scores
- "Convert to Rule" button per row
- Enable/Disable toggle per row
- Delete button with confirmation
- "Clear All Patterns" button (with confirmation)
- Search/filter by keyword or category
- Pagination

**Example Row:**
```jsx
<tr>
    <td>{pattern.keyword}</td>
    <td>{pattern.category.name}</td>
    <td>
        <div className="flex items-center gap-2">
            <ProgressBar 
                value={pattern.confidence_score} 
                max={100}
                className="flex-1"
            />
            <span className="text-sm">{pattern.confidence_score}%</span>
        </div>
    </td>
    <td>{pattern.occurrence_count}</td>
    <td>{formatDate(pattern.last_matched_at)}</td>
    <td>
        <div className="flex gap-2">
            <button 
                onClick={() => convertToRule(pattern)}
                className="text-blue-600 hover:text-blue-800"
            >
                Convert to Rule
            </button>
            <ToggleSwitch 
                checked={pattern.is_active}
                onChange={() => toggleActive(pattern.id)}
            />
            <DeleteButton onClick={() => deletePattern(pattern.id)} />
        </div>
    </td>
</tr>
```

### CategorySuggestion.jsx

**Features:**
- Blue alert panel with suggestion details
- Confidence score badge
- Matched text highlighting in description
- Accept/Reject buttons
- Collapsible "Other suggestions" section (alternatives)
- Source indicator (explicit rule vs learned pattern)

**Example:**
```jsx
export default function CategorySuggestion({ suggestion, description, onAccept, onReject }) {
    const [showAlternatives, setShowAlternatives] = useState(false);
    
    const highlightedDescription = useMemo(() => {
        if (!suggestion?.match_positions) return description;
        
        const { start, length } = suggestion.match_positions;
        return (
            <>
                {description.slice(0, start)}
                <mark className="bg-yellow-200">{description.slice(start, start + length)}</mark>
                {description.slice(start + length)}
            </>
        );
    }, [description, suggestion]);
    
    if (!suggestion) return null;
    
    return (
        <div className="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-md">
            <div className="flex items-center justify-between">
                <div>
                    <p className="text-sm font-medium text-blue-900">
                        Suggested Category 
                        <span className="ml-2 px-2 py-1 bg-blue-100 rounded text-xs">
                            {suggestion.confidence}% confident
                        </span>
                    </p>
                    <p className="text-sm text-blue-800 mt-1">
                        {suggestion.category.name}
                    </p>
                    {suggestion.source === 'learned_pattern' && suggestion.matched_keywords && (
                        <p className="text-xs text-blue-600 mt-1">
                            Based on: "{suggestion.matched_keywords.join('", "')}"
                        </p>
                    )}
                    {suggestion.source === 'explicit_rule' && (
                        <p className="text-xs text-blue-600 mt-1">
                            Matched pattern: "{suggestion.matched_pattern}"
                        </p>
                    )}
                </div>
                <div className="flex gap-2">
                    <button
                        onClick={() => onAccept(suggestion)}
                        className="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700"
                    >
                        Accept
                    </button>
                    <button
                        onClick={() => onReject(suggestion)}
                        className="px-3 py-1 bg-gray-300 text-gray-700 rounded text-sm hover:bg-gray-400"
                    >
                        Reject
                    </button>
                </div>
            </div>
            
            <div className="mt-2 text-sm text-blue-700">
                <strong>Description:</strong> {highlightedDescription}
            </div>
            
            {suggestion.alternatives && suggestion.alternatives.length > 0 && (
                <details className="mt-2">
                    <summary className="text-xs text-blue-600 cursor-pointer hover:underline">
                        Other suggestions ({suggestion.alternatives.length})
                    </summary>
                    <ul className="mt-1 text-xs text-blue-700 space-y-1">
                        {suggestion.alternatives.map((alt, idx) => (
                            <li 
                                key={idx} 
                                className="cursor-pointer hover:underline"
                                onClick={() => onAccept(alt)}
                            >
                                • {alt.category.name} ({alt.confidence}%)
                            </li>
                        ))}
                    </ul>
                </details>
            )}
        </div>
    );
}
```

### TestCoverageModal.jsx

**Features:**
- Display coverage percentage with visual progress bar
- Show "X/Y transactions would be categorized"
- List of matched transactions with rule details
- List of unmatched transactions
- Tabs: All / Matched / Unmatched
- "Create Rule" quick action for unmatched transactions

**Example:**
```jsx
export default function TestCoverageModal({ isOpen, onClose, results }) {
    const [activeTab, setActiveTab] = useState('all');
    
    return (
        <Modal isOpen={isOpen} onClose={onClose} size="lg">
            <div className="p-6">
                <h3 className="text-lg font-medium mb-4">Rule Coverage Test Results</h3>
                
                <div className="mb-6">
                    <div className="flex items-center justify-between mb-2">
                        <span className="text-sm text-gray-700">
                            {results.categorized} of {results.total} transactions categorized
                        </span>
                        <span className="text-sm font-medium text-blue-600">
                            {results.coverage_percentage}% coverage
                        </span>
                    </div>
                    <ProgressBar value={results.coverage_percentage} max={100} />
                </div>
                
                <Tabs value={activeTab} onChange={setActiveTab}>
                    <Tab value="all">All ({results.total})</Tab>
                    <Tab value="matched">Matched ({results.categorized})</Tab>
                    <Tab value="unmatched">Unmatched ({results.total - results.categorized})</Tab>
                </Tabs>
                
                <div className="mt-4 max-h-96 overflow-y-auto">
                    {activeTab === 'matched' && (
                        <MatchedTransactionsList matches={results.matches} />
                    )}
                    {activeTab === 'unmatched' && (
                        <UnmatchedTransactionsList unmatched={results.unmatched} />
                    )}
                    {activeTab === 'all' && (
                        <AllTransactionsList results={results} />
                    )}
                </div>
            </div>
        </Modal>
    );
}
```

---

## Services

### AutoCategorizationService

**Core Methods:**

```php
/**
 * Suggest a category for transaction description.
 * 
 * @return array|null ['category_id', 'confidence', 'source', 'matched_pattern', 'match_positions']
 */
public function suggestCategory(int $userId, string $description, float $amount): ?array;

/**
 * Check if suggestion should be auto-applied based on confidence threshold.
 */
public function shouldAutoApply(int $confidence): bool;

/**
 * Detect patterns that overlap with given pattern.
 * 
 * @return array List of overlapping rules
 */
public function detectOverlappingPatterns(int $userId, string $pattern, bool $excludeArchived = true): array;

/**
 * Test rule coverage against recent transactions.
 * 
 * @return array ['total', 'categorized', 'coverage_percentage', 'matches', 'unmatched']
 */
public function testRulesCoverage(int $userId, int $transactionLimit = 50, bool $includeArchived = false): array;
```

**Algorithm:**

1. **Check Explicit Rules First** (100% confidence)
   - Query active rules ordered by priority ascending
   - Case-insensitive substring match using `stripos()`
   - First match wins
   - Return with source: 'explicit_rule'

2. **Check Learned Patterns** (60-95% confidence)
   - Query active patterns with confidence >= 60
   - Match keywords in description
   - Aggregate by category
   - Calculate weighted confidence
   - Return top 3 suggestions

3. **Return NULL** if no matches

### AutoCategoryLearningService

**Core Methods:**

```php
/**
 * Learn from user correction.
 * 
 * Triggered when user:
 * 1. Overrides auto-suggestion
 * 2. Manually assigns category to uncategorized transaction
 * 3. Changes category on existing transaction
 */
public function learnFromCorrection(
    int $userId,
    int $transactionId,
    ?int $originalCategoryId,
    int $correctedCategoryId,
    string $description,
    string $correctionType,
    ?int $confidenceAtCorrection = null
): void;

/**
 * Extract meaningful keywords from description.
 * 
 * Returns 3-5 keywords, filtered and normalized.
 */
protected function extractKeywords(string $description): array;

/**
 * Update or create learned pattern with frequency-based scoring.
 */
protected function updateLearnedPattern(int $userId, int $categoryId, string $keyword): void;

/**
 * Penalize patterns that led to incorrect suggestions.
 */
protected function penalizeIncorrectPattern(int $userId, int $incorrectCategoryId, array $keywords): void;
```

**Learning Algorithm:**

1. **Record Correction** in `auto_category_corrections`
2. **Extract Keywords** (3-5 words, 3+ chars, no stopwords)
3. **Update Patterns**:
   - New pattern: confidence = 50%, occurrence = 1
   - Existing pattern: increment occurrence, recalculate confidence
   - Formula: `confidence = min(95, 50 + (occurrence_count * 5) + recency_bonus)`
   - Recency bonus: `max(0, 10 - (days_since_first / 30))`
4. **Penalize Incorrect** (if override): reduce confidence by 10%

**Hardcoded Constants:**
- `MIN_OCCURRENCES_FOR_SUGGESTION = 3`
- `LEARNING_RATE = 5` (percent per occurrence)
- `MAX_CONFIDENCE = 95`
- `RECENCY_DECAY_DAYS = 30`
- `PENALTY_AMOUNT = 10`
- `MIN_CONFIDENCE = 20`

---

## Form Requests

### StoreAutoCategoryRuleRequest

```php
'pattern' => 'required|string|max:255',
'category_id' => [
    'required',
    'integer',
    Rule::exists('categories', 'id')->where('user_id', $this->user()->id)
],
'priority' => [
    'nullable',
    'integer',
    'min:1',
    Rule::unique('auto_category_rules')->where(function ($query) {
        return $query->where('user_id', $this->user()->id);
    })->ignore($this->route('auto_category_rule'))
],
'is_active' => 'boolean',
```

**Custom Validation:**
- Auto-assign priority if null: `(max_priority ?? 0) + 10`
- Check overlap and return warnings (non-blocking)

### ImportAutoCategoryRulesRequest

```php
'rules' => 'required|array|min:1',
'rules.*.pattern' => 'required|string|max:255',
'rules.*.category_name' => 'required|string|max:100',
'rules.*.priority' => 'nullable|integer|min:1',
'rules.*.is_active' => 'boolean',
```

**Custom Logic:**
- Auto-create missing categories: `Category::firstOrCreate(['user_id' => $userId, 'name' => $categoryName], ['type' => CategoryType::EXPENSE, 'is_active' => true])`
- Auto-assign priorities if null
- Skip duplicates (same pattern + category)

---

## Factories & Seeders

### AutoCategoryRuleFactory

```php
return [
    'user_id' => User::factory(),
    'pattern' => fake()->randomElement(['walmart', 'amazon', 'netflix', 'uber', 'starbucks']),
    'category_id' => Category::factory(),
    'priority' => fake()->unique()->numberBetween(10, 1000),
    'is_active' => true,
    'archived_at' => null,
];
```

### LearnedCategoryPatternFactory

```php
return [
    'user_id' => User::factory(),
    'category_id' => Category::factory(),
    'keyword' => fake()->word(),
    'occurrence_count' => fake()->numberBetween(3, 20),
    'confidence_score' => fake()->numberBetween(60, 95),
    'first_learned_at' => fake()->dateTimeBetween('-6 months'),
    'last_matched_at' => fake()->dateTimeBetween('-1 month'),
    'is_active' => true,
];
```

### AutoCategoryRuleSeeder

```php
// Seed common patterns for test user
$user = User::first();
$groceriesCategory = Category::where('name', 'Groceries')->first();
$transportCategory = Category::where('name', 'Transportation')->first();

AutoCategoryRule::create([
    'user_id' => $user->id,
    'pattern' => 'walmart',
    'category_id' => $groceriesCategory->id,
    'priority' => 10,
]);

AutoCategoryRule::create([
    'user_id' => $user->id,
    'pattern' => 'uber',
    'category_id' => $transportCategory->id,
    'priority' => 20,
]);
```

---

## Testing Strategy

### PHPUnit Tests (50+ tests)

**Observer Tests** (`tests/Unit/Observers/TransactionObserverTest.php`):
```php
test_observer_categorizes_when_category_null()
test_observer_respects_existing_category()
test_observer_skips_when_no_description()
test_observer_skips_archived_rules()
test_observer_applies_highest_priority_rule()
test_observer_uses_learned_patterns_as_fallback()
test_observer_respects_auto_apply_threshold()
```

**Service Tests** (`tests/Unit/Services/AutoCategorizationServiceTest.php`):
```php
test_substring_matching_case_insensitive()
test_first_matching_rule_wins()
test_detect_overlapping_patterns()
test_detect_overlapping_excludes_archived()
test_test_coverage_returns_accurate_percentage()
test_test_coverage_excludes_archived_by_default()
test_should_auto_apply_respects_threshold()
test_suggest_category_returns_null_when_no_match()
```

**Learning Service Tests** (`tests/Unit/Services/AutoCategoryLearningServiceTest.php`):
```php
test_extract_keywords_from_simple_description()
test_extract_keywords_filters_stopwords()
test_extract_keywords_normalizes_case()
test_learned_pattern_requires_minimum_3_occurrences()
test_confidence_increases_with_occurrences()
test_confidence_capped_at_95()
test_recency_bonus_applied()
test_penalize_incorrect_pattern_reduces_confidence()
test_penalize_respects_minimum_20()
test_learn_from_correction_creates_correction_record()
test_learn_from_correction_updates_patterns()
```

**Feature Tests** (`tests/Feature/Api/AutoCategoryRules/`):
```php
test_user_can_list_rules()
test_user_can_create_rule_with_auto_priority()
test_priority_must_be_unique_per_user()
test_user_can_archive_rule()
test_user_can_restore_rule()
test_archived_rules_excluded_from_suggestions()
test_user_can_reorder_rules()
test_user_can_test_rule()
test_user_can_export_rules()
test_user_can_import_rules_with_auto_category_creation()
test_import_skips_duplicates()
test_user_can_test_coverage()
```

**Feature Tests** (`tests/Feature/Api/LearnedPatterns/`):
```php
test_user_can_list_learned_patterns()
test_user_can_disable_pattern()
test_user_can_delete_pattern()
test_user_can_convert_pattern_to_rule()
test_conversion_auto_assigns_priority()
test_user_cannot_view_other_users_patterns()
```

**Integration Tests** (`tests/Integration/`):
```php
test_transaction_creation_triggers_observer()
test_transaction_update_triggers_learning()
test_learning_from_manual_categorization()
test_learning_from_override()
test_full_auto_categorization_workflow()
```

### Jest Tests (20+ tests)

**Component Tests** (`resources/js/__tests__/`):
```javascript
// AutoCategoryRules/Index.test.jsx
test('renders rules list with drag handles')
test('drag and drop reorders rules')
test('archive button sets archived_at')
test('restore button clears archived_at')
test('show archived toggle displays archived rules')
test('archived rules have gray background')
test('export downloads JSON file')
test('import uploads and processes file')
test('test coverage button opens modal')

// CategorySuggestion.test.jsx
test('renders suggestion with confidence badge')
test('highlights matched text in description')
test('accept button calls onAccept with suggestion')
test('reject button calls onReject')
test('shows alternatives in collapsible section')
test('displays source indicator')

// LearnedPatterns/Index.test.jsx
test('renders patterns table')
test('confidence progress bars display correctly')
test('convert to rule button opens modal')
test('toggle active updates pattern')
test('delete button shows confirmation')

// TestCoverageModal.test.jsx
test('displays coverage percentage')
test('shows matched transactions list')
test('shows unmatched transactions list')
test('tabs switch between all/matched/unmatched')
```

### Coverage Targets
- **Services**: 90%+ (critical business logic)
- **Controllers**: 85%+
- **Models**: 80%+
- **React Components**: 70%+ (focus on logic)
- **Overall**: 85%+

---

## User Capabilities

After Phase 07 completion, users will be able to:

✅ **Auto-Categorization**
- Automatically categorize transactions during creation (manual, import, reconciliation)
- Define explicit pattern-based rules (e.g., "walmart" → Groceries)
- Set priority order for rules (first match wins)
- Archive/restore rules without deleting

✅ **Intelligent Learning**
- System learns from manual category assignments
- Frequency-based confidence scoring (more corrections = higher confidence)
- Temporal decay (recent patterns weighted higher)
- View all learned patterns with confidence scores
- Disable learned patterns individually

✅ **Rule Management**
- Create, edit, delete auto-category rules
- Drag-and-drop priority reordering
- Test individual rules against sample descriptions
- Export rules as JSON (backup/sharing)
- Import rules with auto-category creation
- Detect and warn about overlapping patterns

✅ **Analytics & Testing**
- Test all rules against recent 50 transactions
- View coverage percentage (how many would be categorized)
- See which transactions matched which rules
- Identify unmatched transactions for new rule creation

✅ **Pattern Conversion**
- Convert high-confidence learned patterns to explicit rules with one click
- Pre-filled form with keyword and category
- Auto-assigned priority

✅ **Match Highlighting**
- See highlighted matched text in transaction descriptions
- Understand why suggestion was made
- View matched keywords for learned patterns

---

## Quality Gates

All PRs must pass:
- ✅ PHPUnit tests (50+ tests, all passing)
- ✅ Jest tests (20+ tests, all passing)
- ✅ Laravel Pint (no style violations)
- ✅ PHPStan Level 5+ (no errors)
- ✅ Coverage 85%+ for services, 80%+ overall

---

## Notes

### Observer Pattern Benefits
- **Centralized** - Single point of integration
- **Transparent** - No changes to existing code
- **Future-proof** - New creation methods automatically categorize
- **Testable** - Can disable with `Transaction::unsetEventDispatcher()`

### Pattern Matching Strategy
- **Simple substring** - No regex complexity
- **Case-insensitive** - User-friendly
- **First match wins** - Deterministic behavior
- **Priority-based** - User controls order

### Learning Strategy
- **Frequency-based** - Simple and transparent
- **Temporal decay** - Recent > old
- **User control** - Can disable/delete patterns
- **Explicit rules take priority** - Deterministic over probabilistic

### Archive vs Delete
- **Archive** - Soft deactivation, preserves history
- **Restore** - Re-activate archived rule
- **Delete** - Hard delete (soft delete via Eloquent)

---

## Future Enhancements (Post-Phase 07)

Document for potential Phase 08 or future iterations:

1. **Multi-Pattern Support** - Single rule with multiple patterns (comma-separated)
2. **Confidence Visualization** - Chart showing confidence evolution over time
3. **Smart Pattern Suggestions** - Auto-generate rules from frequently uncategorized descriptions
4. **Regex Pattern Support** - Advanced mode for power users
5. **Category Suggestions from AI** - OpenAI/Claude API integration for natural language understanding
6. **Batch Rule Application** - Apply rules to historical transactions
7. **Rule Analytics** - Usage statistics, accuracy metrics, A/B testing

---

## Previous Phase

[Phase 6: Statement Import](PHASE_06_STATEMENT_IMPORT.md)

---

**Created**: January 30, 2026
**Last Updated**: January 30, 2026

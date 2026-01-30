# Phase 3: Categories and Tags

**Goal**: Provide financial taxonomy for organizing transactions

**Status**: Planned

**Estimated Effort**: 1-2 weeks

---

## Overview

Implement hierarchical categories for transaction classification (revenue/expense) and flexible tagging system for advanced transaction organization.

---

## Deliverables

### Models
- `Category` model with hierarchical support
- `Tag` model with color support
- Migrations with proper relationships

### API
- RESTful API Controller (`Api\V1\CategoryController`)
- RESTful API Controller (`Api\V1\TagController`)
- Pagination and filtering

### Web
- Web Controllers for Inertia
- React components for management

### Database
- `create_categories_table` migration
- `create_tags_table` migration
- `create_tag_transaction_table` pivot migration
- Indexes on user_id, parent_id

### Testing
- Factories: `CategoryFactory`, `TagFactory`
- Seeders: `CategorySeeder`, `TagSeeder`
- Comprehensive tests

---

## Models

### Category Model

```php
class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'parent_id',
        'name',
        'description',
        'type',
    ];

    protected $casts = [
        'type' => CategoryType::class,
    ];

    // Relationships
    public function user(): BelongsTo { }
    public function parent(): BelongsTo { }
    public function children(): HasMany { }
    public function transactions(): HasMany { }

    // Scopes
    public function scopeByType(Builder $query, CategoryType $type): Builder { }
    public function scopeRootOnly(Builder $query): Builder { }
}
```

**Attributes:**
- `id` - Primary key
- `user_id` - Foreign key to users
- `parent_id` - Foreign key to categories (nullable, for hierarchy)
- `name` - Category name (e.g., "Groceries", "Salary")
- `description` - Optional description
- `type` - CategoryType enum: `revenue`, `expense`
- `created_at`, `updated_at`, `deleted_at`

### Tag Model

```php
class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'color',
    ];

    // Relationships
    public function user(): BelongsTo { }
    public function transactions(): BelongsToMany { }

    // Scopes
    public function scopeForUser(Builder $query, int $userId): Builder { }
}
```

**Attributes:**
- `id` - Primary key
- `user_id` - Foreign key to users
- `name` - Tag name (e.g., "Vacation", "Work")
- `color` - Hex color code (e.g., "#FF5733")
- `created_at`, `updated_at`

---

## Database Schema

### categories Table
```sql
CREATE TABLE categories (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    parent_id BIGINT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('revenue', 'expense') NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    UNIQUE INDEX idx_user_name_type (user_id, name, type),
    INDEX idx_parent_id (parent_id)
);
```

### tags Table
```sql
CREATE TABLE tags (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    color VARCHAR(7) DEFAULT '#808080',
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE INDEX idx_user_name (user_id, name)
);
```

### tag_transaction Pivot Table
```sql
CREATE TABLE tag_transaction (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    tag_id BIGINT NOT NULL,
    transaction_id BIGINT NOT NULL,
    created_at TIMESTAMP,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    UNIQUE INDEX idx_tag_transaction (tag_id, transaction_id)
);
```

---

## API Endpoints

### Categories Endpoints

**GET /api/v1/categories**
```
Query Parameters:
  - filter[type] - Filter by type (revenue, expense)
  - sort - Sort by field
  - per_page - Items per page

Response:
{
  "data": [
    {
      "id": 1,
      "name": "Groceries",
      "description": "Food and household items",
      "type": "expense",
      "parent_id": null,
      "children_count": 0,
      "created_at": "2026-01-15T10:30:00Z"
    }
  ],
  "meta": { ... }
}
```

**POST /api/v1/categories**
```
Request:
{
  "name": "Groceries",
  "description": "Food and household items",
  "type": "expense",
  "parent_id": null
}

Response (201 Created): Category resource
```

**GET /api/v1/categories/{id}**
```
Response: Category with children and transaction count
```

**PUT /api/v1/categories/{id}**
```
Request: Update name, description, parent_id
Response (200 OK): Updated category
```

**DELETE /api/v1/categories/{id}**
```
Response (204 No Content)
Note: Deleting parent category cascades to children
```

### Tags Endpoints

**GET /api/v1/tags**
```
Response:
{
  "data": [
    {
      "id": 1,
      "name": "Vacation",
      "color": "#FF5733",
      "usage_count": 12,
      "created_at": "2026-01-15T10:30:00Z"
    }
  ],
  "meta": { ... }
}
```

**POST /api/v1/tags**
```
Request:
{
  "name": "Vacation",
  "color": "#FF5733"
}

Response (201 Created): Tag resource
```

**PUT /api/v1/tags/{id}**
```
Request: Update name, color
Response (200 OK): Updated tag
```

**DELETE /api/v1/tags/{id}**
```
Response (204 No Content)
```

---

## React Components

### CategoryList Component
- Display categories in tree/hierarchy view
- Show category type (revenue/expense) with badge
- Show transaction count per category
- Actions: Create, Edit, Delete
- Collapsible subcategories

### CategoryForm Component
- Name input (required, max 100)
- Description textarea (optional)
- Type dropdown (revenue, expense)
- Parent category dropdown (optional)
- Form validation
- Submit and cancel buttons

### TagList Component
- Display tags with color indicators
- Show tag usage count
- Filter by usage
- Actions: Edit, Delete
- Create button

### TagForm Component
- Name input (required, max 100)
- Color picker
- Form validation
- Submit and cancel buttons

---

## Factories & Seeders

### CategoryFactory
```php
Seeds with both revenue and expense categories
Includes hierarchy (parent-child relationships)
```

### TagFactory
```php
Seeds with random colors
```

### Seeders
```php
CategorySeeder: Creates standard expense/revenue categories
TagSeeder: Creates common tags (Vacation, Business, Personal, etc.)
```

---

## Testing Strategy

### PHPUnit Tests

**Feature Tests** (`tests/Feature/Api/Categories/`):
```
- test_user_can_list_categories()
- test_user_can_filter_by_type()
- test_user_can_create_category()
- test_user_can_create_subcategory()
- test_user_can_view_category_details()
- test_user_can_update_category()
- test_user_can_delete_category()
- test_deleting_parent_cascades_to_children()
- test_user_cannot_view_other_users_categories()
```

**Feature Tests** (`tests/Feature/Api/Tags/`):
```
- test_user_can_list_tags()
- test_user_can_create_tag()
- test_user_can_update_tag()
- test_user_can_delete_tag()
- test_cannot_delete_tag_used_by_transactions()
```

**Unit Tests** (`tests/Unit/Models/`):
```
- test_category_hierarchy_relationships()
- test_tag_transaction_many_to_many()
```

### Jest Tests
```
- test_category_list_renders_hierarchy()
- test_category_form_validates_input()
- test_tag_list_renders_with_colors()
- test_tag_form_color_picker_works()
```

### Coverage Targets
- Overall: 75%+
- Controllers: 85%+
- Models: 80%+

---

## User Capabilities

After Phase 3 completion, users will be able to:

✅ **Categories**
- Create revenue categories (Income, Salary, Bonuses)
- Create expense categories (Groceries, Entertainment, Utilities)
- Organize categories hierarchically (Subcategories)
- Edit and delete categories
- Apply categories to transactions

✅ **Tags**
- Create custom tags with colors
- Manage tag names and colors
- Apply multiple tags to transactions
- Filter transactions by tags

---

## Quality Gates

All PRs must pass:
- ✅ PHPUnit tests (all tests pass)
- ✅ Jest tests (all tests pass)
- ✅ Laravel Pint (no style violations)
- ✅ PHPStan Level 5+ (no errors)
- ✅ Coverage 75%+

---

## Previous Phase

[Phase 2: Accounts Module](PHASE_02_ACCOUNTS_MODULE.md)

## Next Phase

[Phase 4: Transactions with Double-Entry Accounting](PHASE_04_TRANSACTIONS.md)

---

**Created**: January 30, 2026
**Last Updated**: January 30, 2026

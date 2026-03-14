# Snapshot Pattern

Detailed guide for implementing snapshot pattern to preserve historical data integrity in foreign key relationships.

---

## Overview

**Problem:** When storing only `foreign_key` references, historical data can be corrupted when referenced data changes or is deleted.

**Example:**
```php
// Day 1: Transaction created
receipt_items: {
    category_id: 'uuid-makanan',
    item_name: 'Indomie'
}

// Day 2: Category renamed
categories: { id: 'uuid-makanan', name: 'Minuman' } // Changed from "Makanan"

// Problem: Historical data corrupted!
// Transaction now shows: "Indomie - Minuman" ❌ WRONG!
```

**Solution:** Store snapshot of referenced data at transaction time.

---

## When to Use Snapshot Pattern

### ✅ USE Snapshot For:

| Scenario | Example |
|----------|---------|
| **Financial transactions** | Receipts, orders, invoices |
| **Audit records** | Logs, history, tracking |
| **User-generated content** | Comments, posts with user reference |
| **Billing data** | Subscriptions, payments |
| **Any historical reporting** | Stats, reports, analytics |

### ❌ DON'T Use Snapshot For:

| Scenario | Reason |
|----------|--------|
| **Temporary relations** | Cache, sessions |
| **Real-time references** | Current user, active profile |
| **Configurations** | Settings, preferences |
| **Master data** - rarely changes | System constants, lookups |

---

## Implementation Pattern

### 1. Migration Pattern

```php
Schema::create('receipt_items', function (Blueprint $table) {
    $table->uuid('id')->primary();

    // Foreign key (for querying/filtering)
    $table->foreignUuid('category_id')
        ->nullable()
        ->constrained('categories')
        ->nullOnDelete();

    // Snapshot fields (for historical accuracy)
    $table->string('category_name')->nullable();
    $table->string('category_slug')->nullable();

    // Other fields...
    $table->string('item_name');
    $table->integer('quantity');
    $table->decimal('price', 10, 2);

    $table->auditFields();
    $table->timestamps(6);
    $table->softDeletes();
});
```

### 2. Model Pattern

```php
class ReceiptItem extends Model
{
    use HasUuids, AppAuditable, SoftDeletes;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get category display name with fallback
     * Uses snapshot first, then current relation
     */
    public function getCategoryDisplayNameAttribute(): string
    {
        // Priority 1: Use snapshot (historical accuracy)
        if (!empty($this->category_name)) {
            return $this->category_name;
        }

        // Priority 2: Use current relation (if exists)
        if ($this->category && !$this->category->trashed()) {
            return $this->category->name;
        }

        // Priority 3: Fallback
        return 'Uncategorized';
    }
}
```

### 3. Service Pattern

```php
class ReceiptService implements ReceiptInterface
{
    use AppTransactional;

    public function create(
        string $userId,
        string $profileId,
        ?string $merchantId,
        string $receiptNumber,
        // ... other parameters
        array $items // Array of receipt items
    ): Receipt {
        $this->requireTransaction();

        // Get referenced models for snapshot
        $user = User::findOrFail($userId);
        $profile = Profile::findOrFail($profileId);
        $merchant = $merchantId ? Merchant::find($merchantId) : null;

        // Create receipt with snapshot data
        $receipt = Receipt::create([
            'user_id' => $user->id,
            'user_name' => $user->name,        // SNAPSHOT
            'user_email' => $user->email,      // SNAPSHOT
            'profile_id' => $profile->id,
            'profile_name' => $profile->name,  // SNAPSHOT
            'merchant_id' => $merchant?->id,
            'merchant_name' => $merchant?->name,  // SNAPSHOT
            'merchant_address' => $merchant?->address,  // SNAPSHOT
            'receipt_number' => $receiptNumber,
            // ...
        ]);

        // Create receipt items with category snapshot
        foreach ($items as $item) {
            $category = Category::find($item['category_id']);

            ReceiptItem::create([
                'receipt_id' => $receipt->id,
                'category_id' => $category?->id,
                'category_name' => $category?->name,  // SNAPSHOT
                'category_slug' => $category?->slug,  // SNAPSHOT
                'item_name' => $item['item_name'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'subtotal' => $item['quantity'] * $item['price'],
            ]);
        }

        return $receipt->fresh();
    }
}
```

### 4. Resource Pattern

```php
class ReceiptItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item_name' => $this->item_name,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'subtotal' => $this->subtotal,

            // Use snapshot for display, but include relation ID
            'category' => [
                'id' => $this->category_id,
                'name' => $this->category_name,  // Snapshot value
                'slug' => $this->category_slug,  // Snapshot value
            ],
        ];
    }
}
```

---

## Snapshot Field Naming Convention

**Pattern:** `{relation}_{field}`

| Reference | Snapshot Field | Example Value |
|-----------|----------------|---------------|
| `users.name` | `user_name` | "Ahmad" |
| `users.email` | `user_email` | "ahmad@example.com" |
| `profiles.name` | `profile_name` | "Keluarga" |
| `merchants.name` | `merchant_name` | "Indomaret" |
| `merchants.address` | `merchant_address` | "Jl. Sudirman No. 1" |
| `categories.name` | `category_name` | "Makanan" |
| `categories.slug` | `category_slug` | "makanan" |

---

## Complete Example: E-commerce Order

### Scenario:
Order with customer, products, and shipping address.

### Schema:
```php
Schema::create('orders', function (Blueprint $table) {
    $table->uuid('id')->primary();

    // Foreign keys
    $table->foreignUuid('user_id')->constrained();
    $table->foreignUuid('shipping_address_id')->nullable()->constrained('addresses');

    // Snapshot fields
    $table->string('user_name');          // Customer name at order time
    $table->string('user_email');         // Customer email at order time
    $table->string('shipping_full_name'); // Recipient name
    $table->text('shipping_address');     // Full address snapshot
    $table->string('shipping_city');      // City snapshot
    $table->string('shipping_phone');     // Phone snapshot

    // Order fields
    $table->string('order_number')->unique();
    $table->decimal('total_amount', 10, 2);

    $table->auditFields();
    $table->timestamps(6);
    $table->softDeletes();
});

Schema::create('order_items', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('order_id')->constrained();
    $table->foreignUuid('product_id')->nullable()->constrained();

    // Snapshot fields
    $table->string('product_name');      // Product name at order time
    $table->text('product_description'); // Description at order time
    $table->decimal('product_price', 10, 2); // Price at order time

    // Order item fields
    $table->integer('quantity');
    $table->decimal('subtotal', 10, 2);

    $table->auditFields();
    $table->timestamps(6);
    $table->softDeletes();
});
```

### Benefits:

| Scenario | Without Snapshot | With Snapshot |
|----------|------------------|--------------|
| **Customer changes name** | Old orders show new name ❌ | Old orders show original name ✅ |
| **Product renamed** | Order history shows new name ❌ | Order history shows original name ✅ |
| **Product price changed** | Can't see historical price ❌ | Original price preserved ✅ |
| **Address deleted** | Shipping info lost ❌ | Snapshot preserved ✅ |

---

## Handling Updates and Deletes

### When Reference is Updated:

```php
// Category renamed from "Makanan" to "Minuman"
$category->update(['name' => 'Minuman']);

// Historical data UNAFFECTED:
receipt_items: {
    category_id: 'uuid-makanan',
    category_name: 'Makanan',  // Still shows original value
    category_slug: 'makanan',
}

// New transactions will use new name:
ReceiptItem::create([
    'category_id' => $category->id,
    'category_name' => 'Minuman',  // New snapshot
    'category_slug' => 'minuman',
]);
```

### When Reference is Soft Deleted:

```php
// Category soft deleted
$category->delete();

// Historical data UNAFFECTED:
receipt_items: {
    category_id: 'uuid-makanan',
    category_name: 'Makanan',  // Still shows original value
}

// In UI/Resource:
public function getCategoryDisplayNameAttribute(): string
{
    // Use snapshot (still available even after delete)
    return $this->category_name ?? 'Uncategorized';
}
```

---

## Migration for Existing Data

### Adding Snapshot to Existing Table:

```php
// Step 1: Add snapshot columns
Schema::table('receipt_items', function (Blueprint $table) {
    $table->string('category_name')->nullable()->after('category_id');
    $table->string('category_slug')->nullable()->after('category_name');
});

// Step 2: Backfill existing data (in seeder or command)
DB::statement('
    UPDATE receipt_items ri
    LEFT JOIN categories c ON ri.category_id = c.id
    SET ri.category_name = c.name,
        ri.category_slug = c.slug
    WHERE ri.category_name IS NULL
');

// Step 3: Make column non-nullable if desired (after backfill)
// Schema::table('receipt_items', function (Blueprint $table) {
//     $table->string('category_name')->nullable(false)->change();
// });
```

---

## Best Practices

### ✅ DO:

1. **Snapshot critical fields** - name, email, address, etc.
2. **Use consistent naming** - `{relation}_{field}`
3. **Make nullable** - Allow NULL for edge cases
4. **Backfill existing data** - When adding to existing tables
5. **Document snapshot fields** - Comment in migration
6. **Use in Resource/Model** - Display snapshot data
7. **Consider storage** - Snapshot increases storage, balance with needs

### ❌ DON'T:

1. **Snapshot everything** - Only critical historical data
2. **Snapshot static data** - Don't snapshot data that rarely changes
3. **Make non-nullable without backfill** - Will break existing records
4. **Forget to update service** - Ensure snapshot is populated on create
5. **Duplicate data unnecessarily** - Balance between storage and accuracy

---

## Performance Considerations

| Aspect | Impact | Mitigation |
|--------|--------|------------|
| **Storage** | Increases row size | Only snapshot critical fields |
| **Insert speed** | Slightly slower (more fields) | Negligible impact |
| **Query speed** | No impact (not queried) | Joins only when needed |
| **Data accuracy** | **HUGE positive impact** | Historical integrity maintained |

---

## Quick Reference

| Need to... | Pattern |
|------------|----------|
| **Add foreign key with snapshot** | Add `{relation}_{field}` column alongside `{relation}_id` |
| **Populate snapshot on create** | `$model->{relation}_{field} = $related->{field}` |
| **Display snapshot data** | Use in Resource/Model directly |
| **Handle deleted reference** | Use snapshot value as fallback |
| **Backfill existing data** | Update statement with JOIN to populate |

---

## Checklist

Before implementing snapshot pattern:

- [ ] Identify all foreign keys to mutable data
- [ ] Determine which fields need snapshot
- [ ] Add snapshot columns to migration
- [ ] Update Service to populate snapshot on create
- [ ] Update Model/Resource to use snapshot
- [ ] Backfill existing data (if applicable)
- [ ] Test with deleted/updated references

---

**Related Patterns:**
- [Migration Pattern](../quick-reference.md#8-migration-rules)
- [Model Retrieval Pattern](./model-retrieval.md)
- [Service Layer Pattern](./service-layer.md)

---

**Last Updated:** 2026-02-27
# Bug Fix: Automatic Resource Generation

## Problem
The automatic resource generation system was not working after fresh-start, causing resources to remain static instead of increasing based on production buildings. The test-events.sh script was failing with:

```
❌ Resources did not increase (stayed at 5000)
```

## Root Cause
The UpdateResources database event had SQL syntax issues causing MySQL errors:
- `Unknown column 'bc.productionRate' in 'field list'` errors every second
- Complex nested subqueries with conflicting table aliases were causing parsing failures
- The event was technically enabled but failing to execute properly

## Solution
Rewrote the UpdateResources event using a cursor-based approach to:

1. **Eliminate SQL syntax errors**: Separated complex queries into individual steps
2. **Properly handle table aliases**: Removed conflicting nested subquery aliases
3. **Maintain correct production rates**: Ensured /3600 division for per-hour to per-second conversion
4. **Improve reliability**: Used cursor iteration to process each settlement individually

## Files Changed
- `sql/database.sql`: Updated UpdateResources event definition
- `sql/fix-resource-generation.sql`: Updated standalone fix script

## Technical Details

### Before (Broken)
```sql
UPDATE Settlement s SET wood = LEAST(
    wood + (SELECT COALESCE(SUM(bc.productionRate), 0)...),
    (SELECT COALESCE(bc.productionRate, 10000)...)  -- Alias conflict
) WHERE...
```

### After (Fixed)
```sql
-- Calculate production rates separately
SELECT COALESCE(SUM(bc.productionRate), 0) / 3600 INTO wood_production...
-- Apply updates with clear variable usage
UPDATE Settlement SET wood = LEAST(wood + wood_production, storage_limit)...
```

## Verification
- All resources now increase at 1 unit/second per level-1 building (3600/hour ÷ 3600 = 1/second)
- No database errors in logs
- test-events.sh passes all checks
- Fresh-start cycle works correctly with new event definition

## Testing Results
```
✓ Resources increased by 10-11 in 10 seconds (expected ~10-20)
✓ Automatic resource generation is working
✅ Database events are working correctly after fresh-start
```
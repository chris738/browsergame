#!/bin/bash

# Test script to validate that database events are working after fresh-start
# This tests the fix for: die events scheinen beim start von fresh-start nicht automatisch zu starten

set -e

echo "=== Testing Database Events After Fresh-Start ==="

cd "$(dirname "$0")"

# Check if containers are running
if ! docker compose ps | grep -q "Up"; then
    echo "❌ Containers are not running. Please run fresh-start first."
    exit 1
fi

echo "✓ Containers are running"

# Check that event scheduler is enabled
SCHEDULER_STATUS=$(docker compose exec -T db mysql -u browsergame -psicheresPasswort browsergame -se "SHOW VARIABLES LIKE 'event_scheduler';" 2>/dev/null | awk '{print $2}')

if [ "$SCHEDULER_STATUS" = "ON" ]; then
    echo "✓ Event Scheduler is enabled"
else
    echo "❌ Event Scheduler is not enabled"
    exit 1
fi

# Check that all required events exist and are enabled
EVENTS=$(docker compose exec -T db mysql -u browsergame -psicheresPasswort browsergame -se "SELECT COUNT(*) FROM information_schema.EVENTS WHERE EVENT_SCHEMA = 'browsergame' AND STATUS = 'ENABLED';" 2>/dev/null)

if [ "$EVENTS" -eq 4 ]; then
    echo "✓ All 4 events are enabled"
else
    echo "❌ Expected 4 enabled events, found $EVENTS"
    exit 1
fi

# Check for errors in event execution
ERROR_COUNT=$(docker compose logs db --tail=100 2>/dev/null | grep -i "Event Scheduler.*ERROR" | wc -l)

if [ "$ERROR_COUNT" -eq 0 ]; then
    echo "✓ No event execution errors found"
else
    echo "❌ Found $ERROR_COUNT event execution errors"
    echo "Recent errors:"
    docker compose logs db --tail=20 | grep -i "Event Scheduler.*ERROR" | tail -3
    exit 1
fi

# Test resource generation by reducing resources and waiting for them to increase
echo "Testing automatic resource generation..."

# Get initial resources for settlement 1
INITIAL_WOOD=$(docker compose exec -T db mysql -u browsergame -psicheresPasswort browsergame -se "SELECT wood FROM Settlement WHERE settlementId = 1;" 2>/dev/null)

# Reduce resources to test generation
docker compose exec -T db mysql -u browsergame -psicheresPasswort browsergame -e "UPDATE Settlement SET wood = 5000 WHERE settlementId = 1;" 2>/dev/null

echo "Set wood to 5000, waiting 10 seconds for resource generation..."
sleep 10

# Check if resources have increased
FINAL_WOOD=$(docker compose exec -T db mysql -u browsergame -psicheresPasswort browsergame -se "SELECT wood FROM Settlement WHERE settlementId = 1;" 2>/dev/null)

if [ "$FINAL_WOOD" -gt 5000 ]; then
    INCREASE=$((FINAL_WOOD - 5000))
    echo "✓ Resources increased by $INCREASE in 10 seconds (expected ~10-20)"
    echo "✓ Automatic resource generation is working"
else
    echo "❌ Resources did not increase (stayed at $FINAL_WOOD)"
    exit 1
fi

# Reset resources to original value
docker compose exec -T db mysql -u browsergame -psicheresPasswort browsergame -e "UPDATE Settlement SET wood = $INITIAL_WOOD WHERE settlementId = 1;" 2>/dev/null

echo ""
echo "=== All Tests Passed! ==="
echo "✅ Database events are working correctly after fresh-start"
echo "✅ Automatic resource generation is functioning"
echo "✅ No event execution errors detected"
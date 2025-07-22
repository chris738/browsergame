# Travel Time System

This document describes the travel time system implementation for the browsergame. The system adds realistic travel times for military attacks and resource trades based on distance between settlements.

## Overview

### Key Features

- **Travel Times for Attacks**: Military units now take time to travel between settlements (2-10 seconds per block distance)
- **Travel Times for Trades**: Resource trades require travel time (configurable, default 5 seconds per block)
- **Configurable Speed**: Different military units have different travel speeds
- **Configurable Loot**: Military units have configurable resource theft amounts
- **Admin Panel**: Complete administrative interface for managing all settings
- **Real-time Tracking**: Players can see their armies and trades in transit with countdown timers

## System Components

### Database Tables

- **`TravelConfig`**: Stores base travel speeds for different travel types
- **`TravelingArmies`**: Tracks military units in transit for attacks
- **`TravelingTrades`**: Tracks resource shipments in transit
- **`TravelHistory`**: Archives completed travels for statistics
- **`MilitaryUnitConfig`**: Extended with `speed` and `lootAmount` fields

### PHP Components

- **`TravelRepository`**: Handles all travel-related database operations
- **`process-arrivals.php`**: Cron script for processing arrivals
- **`admin-travel.php`**: Admin interface for managing travel settings

### Frontend Updates

- **Battle Interface**: Shows traveling armies with real-time countdowns
- **Market Interface**: Shows traveling trades with progress tracking
- **Admin Panel**: Complete configuration interface

## Installation

### 1. Database Setup

Run the database migration script:

```bash
chmod +x setup-travel-system.sh
./setup-travel-system.sh
```

Or manually run the SQL files:

```bash
mysql -u browsergame -p browsergame < sql/tables/travel_tables.sql
mysql -u browsergame -p browsergame < sql/data/military_travel_data.sql
```

### 2. Cron Job Setup

Set up automatic processing of arrivals by adding a cron job:

```bash
# Edit your crontab
crontab -e

# Add this line (replace /path/to/browsergame with actual path):
*/1 * * * * cd /path/to/browsergame && php php/process-arrivals.php >> logs/travel-processor.log 2>&1
```

Alternatively, use the provided cron configuration:
```bash
# Copy the appropriate line from travel-cron.txt to your crontab
cat travel-cron.txt
```

### 3. Test the Installation

Run the test script to verify everything is working:

```bash
php test-travel-system.php
```

## Configuration

### Admin Panel

Access the admin panel at `admin-travel.php` to configure:

1. **Travel Speeds**
   - Trade travel speed (seconds per block)
   - Military base speed (seconds per block)

2. **Military Unit Configuration**
   - Individual unit speeds (2-10 seconds per block)
   - Loot amounts (resources units can carry)
   - Attack and defense powers

3. **System Monitoring**
   - View all traveling armies
   - View all traveling trades
   - Manual arrival processing

### Default Configuration

**Military Unit Speeds** (seconds per block):
- Guards: 8 seconds (slowest, most defensive)
- Soldiers: 6 seconds (balanced)
- Archers: 4 seconds (fast)
- Cavalry: 2 seconds (fastest)

**Loot Amounts** (resources per unit):
- Guards: 5.0
- Soldiers: 10.0
- Archers: 8.0
- Cavalry: 20.0

**Trade Speed**: 5 seconds per block

## How It Works

### Attack Process

1. Player selects target and units for attack
2. System calculates distance between settlements
3. Determines travel time based on slowest unit's speed
4. Units are removed from attacking settlement immediately
5. Travel record is created with arrival time
6. When cron job processes arrival, battle is executed
7. Surviving units are returned to attacking settlement
8. Resources are plundered based on unit loot capacity

### Trade Process

1. Player accepts a trade offer
2. Resources are removed from both players immediately
3. Two travel records are created (one for each direction)
4. When cron job processes arrivals, resources are delivered
5. Trade is marked as completed

### Distance Calculation

Distance is calculated using Euclidean distance between settlement coordinates:
```
distance = √((x₁ - x₂)² + (y₁ - y₂)²)
```

Minimum distance is 1 block (for same settlement trades/attacks).

## API Endpoints

### Battle Endpoints

- `GET php/battle-backend.php?action=getTravelingArmies&settlementId=X`
- `POST php/battle-backend.php` with `action=attack` (now starts travel)

### Market Endpoints

- `GET php/market-backend.php?getTravelingTrades=true&settlementId=X`
- `POST php/market-backend.php` with `action=acceptOffer` (now starts travel)

### Admin Endpoints

- `GET php/admin-backend.php?action=getTravelConfig`
- `GET php/admin-backend.php?action=getMilitaryUnitConfig`
- `GET php/admin-backend.php?action=getAllTravelingArmies`
- `GET php/admin-backend.php?action=getAllTravelingTrades`
- `POST php/admin-backend.php` with `action=updateTravelConfig`
- `POST php/admin-backend.php` with `action=updateMilitaryUnitConfig`
- `POST php/admin-backend.php` with `action=processArrivals`

## User Interface

### Battle Page

- **Military Power Overview**: Shows available units
- **Armies in Transit**: Real-time view of traveling armies
  - Outgoing attacks (red border)
  - Incoming attacks (orange border)
  - Countdown timers
  - Unit composition
  - Distance information

### Market Page

- **Trades in Transit**: Real-time view of traveling trades
  - Outgoing trades (green border)
  - Incoming trades (blue border)
  - Resource amounts
  - Countdown timers
  - Distance information

### Admin Panel

- **Travel Configuration**: Speed settings for different travel types
- **Military Unit Configuration**: Complete unit stats management
- **Live Monitoring**: Real-time view of all system activity
- **Manual Processing**: Emergency arrival processing

## Performance Considerations

### Cron Job Frequency

- **Recommended**: Every 30 seconds (runs twice per minute)
- **Alternative**: Every minute (simpler but less responsive)
- **High Load**: Every 2 minutes (for servers with many travels)

### Database Optimization

The system automatically cleans up completed travels to prevent database bloat. Consider adding these optimizations:

1. **Index Optimization**: Ensure proper indexes on arrival times
2. **Archive Old Data**: Move old travel history to archive tables
3. **Monitor Performance**: Watch query execution times during peak usage

## Troubleshooting

### Common Issues

1. **Cron Job Not Running**
   - Check cron service: `systemctl status cron`
   - Verify crontab: `crontab -l`
   - Check logs: `tail -f logs/travel-processor.log`

2. **Arrivals Not Processing**
   - Run manual processing: `php php/process-arrivals.php`
   - Check database connectivity
   - Verify arrival times in database

3. **Frontend Not Updating**
   - Check browser console for JavaScript errors
   - Verify API endpoints are accessible
   - Clear browser cache

### Logs

- **Travel Processor**: `logs/travel-processor.log`
- **PHP Errors**: Check server error logs
- **Database Errors**: Check MySQL error logs

## Security Considerations

1. **Validation**: All travel requests are validated for unit availability
2. **Authentication**: Admin endpoints require proper authentication
3. **Rate Limiting**: Consider implementing rate limits for frequent API calls
4. **Resource Limits**: Travel capacity is limited by unit availability

## Future Enhancements

Potential improvements for the travel system:

1. **Unit Groups**: Allow sending multiple unit types with different speeds
2. **Waypoints**: Enable multi-stop journeys
3. **Terrain Effects**: Different terrain types affect travel speed
4. **Weather System**: Weather conditions impact travel times
5. **Supply Lines**: Require supply management for long journeys
6. **Battle Formations**: Unit formation affects travel and combat
7. **Notifications**: Email/SMS alerts for important arrivals
8. **Mobile App**: Dedicated mobile interface for travel management
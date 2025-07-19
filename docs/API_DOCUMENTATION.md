# API Documentation

This document describes the REST API endpoints available in the Browser Settlement Game.

## Base URLs

- **Main Game API**: `php/backend.php`
- **Admin API**: `php/admin-backend.php`
- **Market API**: `php/market-backend.php`

## Authentication

Currently, the game uses simple settlement-based access control. Most endpoints require a `settlementId` parameter to identify the settlement being managed.

## Game API Endpoints (`php/backend.php`)

### GET Endpoints

#### Get Settlement Resources
```
GET php/backend.php?settlementId={id}
```
**Description**: Retrieves current resources for a settlement

**Parameters**:
- `settlementId` (required): Settlement ID

**Response**:
```json
{
    "resources": {
        "wood": 1000,
        "stone": 800,
        "ore": 500,
        "storageCapacity": 5000,
        "maxSettlers": 50,
        "freeSettlers": 25
    }
}
```

#### Get Player Information
```
GET php/backend.php?getPlayerInfo=true&settlementId={id}
```
**Description**: Retrieves player information including name and gold

**Response**:
```json
{
    "playerName": "TestPlayer",
    "playerGold": 1000,
    "playerId": 1
}
```

#### Get Settlement Name
```
GET php/backend.php?getSettlementName=true&settlementId={id}
```
**Description**: Gets the name of a settlement

**Response**:
```json
{
    "settlementName": "My Settlement"
}
```

#### Get Building Information
```
GET php/backend.php?settlementId={id}&buildingType={type}
```
**Description**: Gets details about a specific building type in the settlement

**Parameters**:
- `settlementId` (required): Settlement ID
- `buildingType` (required): Building type (e.g., "Holzfäller", "Rathaus")

**Response**:
```json
{
    "building": {
        "buildingType": "Holzfäller",
        "level": 5,
        "translatedName": "Lumberjack",
        "upgradeCosts": {
            "wood": 250,
            "stone": 180,
            "ore": 120
        }
    }
}
```

#### Get Building Queue
```
GET php/backend.php?getBuildingQueue=true&settlementId={id}
```
**Description**: Retrieves active building upgrade queue

**Response**:
```json
{
    "queue": [
        {
            "buildingType": "Holzfäller",
            "translatedName": "Lumberjack",
            "queueId": 1,
            "endTime": "2024-01-15 14:30:00"
        }
    ]
}
```

#### Get Resource Regeneration
```
GET php/backend.php?getRegen=true&settlementId={id}
```
**Description**: Gets resource production rates

**Response**:
```json
{
    "regen": {
        "wood": 15.5,
        "stone": 12.0,
        "ore": 8.0
    }
}
```

#### Get Building Types
```
GET php/backend.php?getBuildingTypes=true
```
**Description**: Lists all available building types

**Response**:
```json
{
    "buildingTypes": [
        {
            "buildingType": "Holzfäller",
            "translatedName": "Lumberjack"
        },
        {
            "buildingType": "Rathaus", 
            "translatedName": "Town Hall"
        }
    ]
}
```

#### Get Map Data
```
GET php/backend.php?getMap=true
```
**Description**: Retrieves map with all settlements

**Response**:
```json
{
    "map": [
        {
            "settlementId": 1,
            "playerName": "TestPlayer",
            "coordinateX": 100,
            "coordinateY": 150,
            "points": 1500
        }
    ]
}
```

#### Get All Players
```
GET php/backend.php?getAllPlayers=true
```
**Description**: Lists all players with their settlements

### Military API Endpoints

#### Get Military Units
```
GET php/backend.php?getMilitaryUnits=true&settlementId={id}
```
**Description**: Gets military unit counts for settlement

**Response**:
```json
{
    "units": {
        "guards": 10,
        "soldiers": 5,
        "archers": 3,
        "cavalry": 2
    }
}
```

#### Get Military Training Queue
```
GET php/backend.php?getMilitaryQueue=true&settlementId={id}
```
**Description**: Gets active unit training queue

**Response**:
```json
{
    "queue": [
        {
            "queueId": 1,
            "unitType": "soldiers",
            "count": 3,
            "endTime": "2024-01-15 15:00:00"
        }
    ]
}
```

#### Get Military Stats
```
GET php/backend.php?getMilitaryStats=true&settlementId={id}
```
**Description**: Gets military statistics and capabilities

#### Get Unit Research Status
```
GET php/backend.php?getUnitResearch=true&settlementId={id}
```
**Description**: Gets research status for all unit types

#### Get Research Queue
```
GET php/backend.php?getResearchQueue=true&settlementId={id}
```
**Description**: Gets active research queue

#### Get Research Configuration
```
GET php/backend.php?getResearchConfig=true
```
**Description**: Gets research costs and times for all units

### POST Endpoints

#### Upgrade Building
```
POST php/backend.php
Content-Type: application/json

{
    "action": "upgrade",
    "settlementId": 1,
    "buildingType": "Holzfäller"
}
```
**Description**: Initiates a building upgrade

**Response**:
```json
{
    "success": true,
    "message": "Building upgrade started",
    "queueId": 123
}
```

#### Train Military Units
```
POST php/backend.php
Content-Type: application/json

{
    "action": "trainUnit",
    "settlementId": 1,
    "unitType": "soldiers",
    "count": 5
}
```
**Description**: Starts training military units

#### Start Research
```
POST php/backend.php
Content-Type: application/json

{
    "action": "startResearch",
    "settlementId": 1,
    "unitType": "cavalry"
}
```
**Description**: Begins researching a unit type

### DELETE Endpoints

#### Cancel Building Upgrade
```
DELETE php/backend.php
Content-Type: application/json

{
    "action": "cancelBuilding",
    "queueId": 123
}
```
**Description**: Cancels a building upgrade (resources may be refunded)

#### Cancel Unit Training
```
DELETE php/backend.php
Content-Type: application/json

{
    "action": "cancelTraining",
    "queueId": 456
}
```
**Description**: Cancels unit training (resources may be refunded)

## Admin API Endpoints (`php/admin-backend.php`)

### Authentication
Admin endpoints require session-based authentication. Login via `admin.php` first.

### GET Endpoints

#### Get System Statistics
```
GET php/admin-backend.php?action=stats
```
**Description**: Retrieves system overview statistics

#### Get All Players
```
GET php/admin-backend.php?action=players
```
**Description**: Lists all players with detailed information

#### Get All Settlements
```
GET php/admin-backend.php?action=settlements
```
**Description**: Lists all settlements with resources and coordinates

#### Get Building Queues
```
GET php/admin-backend.php?action=queues
```
**Description**: Shows all active building queues across settlements

### POST Endpoints

#### Create Player
```
POST php/admin-backend.php
Content-Type: application/json

{
    "action": "createPlayer",
    "playerName": "NewPlayer",
    "gold": 1000
}
```

#### Update Player Stats
```
POST php/admin-backend.php
Content-Type: application/json

{
    "action": "updatePlayerStats",
    "playerId": 1,
    "points": 2000,
    "gold": 1500
}
```

#### Update Settlement Resources
```
POST php/admin-backend.php
Content-Type: application/json

{
    "action": "updateSettlementResources",
    "settlementId": 1,
    "wood": 5000,
    "stone": 4000,
    "ore": 3000
}
```

### DELETE Endpoints

#### Delete Player
```
DELETE php/admin-backend.php
Content-Type: application/json

{
    "action": "deletePlayer",
    "playerId": 1
}
```

#### Clear All Queues
```
DELETE php/admin-backend.php
Content-Type: application/json

{
    "action": "clearAllQueues"
}
```

## Market API Endpoints (`php/market-backend.php`)

### GET Endpoints

#### Get Market Status
```
GET php/market-backend.php?action=status&settlementId={id}
```
**Description**: Checks if settlement has market access

#### Get Trade Offers
```
GET php/market-backend.php?action=offers&settlementId={id}
```
**Description**: Lists available trade offers

### POST Endpoints

#### Create Trade Offer
```
POST php/market-backend.php
Content-Type: application/json

{
    "action": "createOffer",
    "settlementId": 1,
    "offerResource": "wood",
    "offerAmount": 1000,
    "requestResource": "stone",
    "requestAmount": 800
}
```

#### Accept Trade Offer
```
POST php/market-backend.php
Content-Type: application/json

{
    "action": "acceptTrade",
    "settlementId": 1,
    "offerId": 123
}
```

## Error Handling

All endpoints return errors in the following format:

```json
{
    "error": "Error description",
    "code": "ERROR_CODE" // (optional)
}
```

Common error scenarios:
- Missing or invalid `settlementId`
- Insufficient resources for operations
- Building already at maximum level
- Invalid building or unit types
- Database connection failures

## Rate Limiting

Currently, no rate limiting is implemented. For production use, consider implementing rate limiting on critical endpoints.

## Data Types

### Building Types (German Internal Names)
- `Rathaus` - Town Hall
- `Holzfäller` - Lumberjack  
- `Steinbruch` - Quarry
- `Erzbergwerk` - Mine
- `Lager` - Storage
- `Farm` - Farm
- `Markt` - Market
- `Kaserne` - Barracks

### Unit Types
- `guards` - Guards (defensive)
- `soldiers` - Soldiers (melee)
- `archers` - Archers (ranged)
- `cavalry` - Cavalry (fast, powerful)

### Resource Types
- `wood` - Wood resource
- `stone` - Stone resource  
- `ore` - Ore resource

## Development Notes

- The API uses German internal names for buildings but returns translated names for display
- Settlement ownership is validated for most operations
- The Event Scheduler must be enabled for resource regeneration
- Database transactions are used for resource-consuming operations
- All timestamps are in MySQL DATETIME format
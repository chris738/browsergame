# Game Mechanics Guide

This document provides detailed information about the game mechanics in the Browser Settlement Game.

## 🏗️ Building System

### Resource Buildings

#### Lumberjack (Holzfäller)
- **Purpose**: Produces wood resources over time
- **Base Production**: 10 wood per hour (level 1)
- **Upgrade Requirements**: Wood, Stone, Ore (increases with level)
- **Max Level**: No explicit limit

#### Quarry (Steinbruch) 
- **Purpose**: Produces stone resources over time
- **Base Production**: 8 stone per hour (level 1)
- **Upgrade Requirements**: Wood, Stone, Ore (increases with level)
- **Max Level**: No explicit limit

#### Mine (Erzbergwerk)
- **Purpose**: Produces ore resources over time
- **Base Production**: 5 ore per hour (level 1)
- **Upgrade Requirements**: Wood, Stone, Ore (increases with level)
- **Max Level**: No explicit limit

### Infrastructure Buildings

#### Town Hall (Rathaus)
- **Purpose**: Central building that determines overall settlement level
- **Special**: Usually required for other building upgrades
- **Upgrade Requirements**: Significant resources (scales with level)

#### Storage (Lager)
- **Purpose**: Increases resource storage capacity
- **Base Capacity**: 1000 units per resource type (level 1)
- **Capacity Increase**: ~500-1000 units per level
- **Importance**: Essential for storing produced resources

#### Farm
- **Purpose**: Provides settlers/population for other buildings
- **Base Production**: Provides workforce for building operations
- **Special**: Required for manning other buildings effectively

### Military Buildings

#### Barracks (Kaserne)
- **Purpose**: Train military units and manage army
- **Requirements**: Requires settlers from farms
- **Units**: Can train Guards, Soldiers, Archers, Cavalry
- **Research**: Higher-tier units require research first

### Economic Buildings

#### Market (Markt)
- **Purpose**: Enables trading with other players
- **Requirements**: Must be built before trading is available
- **Function**: Resource exchange between settlements

## ⚔️ Military System

### Unit Types

#### Guards
- **Cost**: 50 Wood, 30 Stone, 20 Ore
- **Training Time**: 30 seconds
- **Stats**: 0 Attack, 2 Defense, 0 Ranged, Speed 1
- **Purpose**: Basic defensive units
- **Research**: No prerequisite

#### Soldiers
- **Cost**: 80 Wood, 60 Stone, 40 Ore
- **Training Time**: 60 seconds
- **Stats**: 3 Attack, 1 Defense, 0 Ranged, Speed 1
- **Purpose**: Primary melee combat units
- **Research**: No prerequisite

#### Archers
- **Cost**: 100 Wood, 40 Stone, 60 Ore
- **Training Time**: 90 seconds
- **Stats**: 0 Attack, 1 Defense, 4 Ranged, Speed 1
- **Purpose**: Ranged combat specialists
- **Research**: No prerequisite

#### Cavalry
- **Cost**: 150 Wood, 100 Stone, 120 Ore
- **Training Time**: 180 seconds (3 minutes)
- **Stats**: 5 Attack, 2 Defense, 0 Ranged, Speed 2
- **Purpose**: Fast, powerful melee units
- **Research**: No prerequisite

### Research System

#### Research Requirements
- **Guards**: 200 Wood, 150 Stone, 100 Ore (5 minutes)
- **Soldiers**: 400 Wood, 300 Stone, 200 Ore (10 minutes)
- **Archers**: 600 Wood, 400 Stone, 400 Ore (15 minutes)
- **Cavalry**: 1000 Wood, 800 Stone, 600 Ore (20 minutes)

#### Research Process
1. Units must be researched before they can be trained
2. Research is performed at the Barracks
3. Only one research can be active at a time per settlement
4. Some units may have prerequisites (higher tier units)

## 💰 Resource System

### Resource Types
- **Wood**: Primary building material, produced by Lumberjacks
- **Stone**: Secondary building material, produced by Quarries
- **Ore**: Advanced material, produced by Mines

### Resource Generation
- **Automatic**: Resources are generated automatically over time
- **Event Scheduler**: Uses MySQL Event Scheduler for real-time updates
- **Production Rate**: Scales with building level
- **Storage Limits**: Limited by Storage building capacity

### Resource Management
- Resources are consumed for:
  - Building upgrades
  - Unit training
  - Unit research
  - Trading (market transactions)

## 🏪 Trading System

### Market Building
- **Requirement**: Must build and upgrade Market building
- **Access**: Market level determines available trading features
- **Interface**: Accessible via market.php

### Trading Mechanics
- **Player-to-Player**: Trade resources with other settlements
- **Exchange Rates**: Market-determined or player-negotiated
- **Limits**: May be limited by market level and available resources

## 🏰 Settlement Management

### Settlement Coordinates
- Each settlement has X,Y coordinates on the game map
- Visible on map.php interface
- Used for distance calculations and potential future features

### Building Queue System
- **Queue Management**: Multiple buildings can be upgraded simultaneously
- **Time-based**: Each upgrade has a completion time
- **Resource Lock**: Resources are consumed when upgrade starts
- **Cancellation**: Upgrades can be cancelled (resources may be refunded)

### Settlement Info
- **Overview**: Available via settlement-info.php
- **Stats**: Shows all building levels, resources, and military units
- **Progress**: Displays active building and training queues

## 🎮 Game Progression

### Early Game (Levels 1-5)
1. **Resource Focus**: Upgrade Lumberjack, Quarry, Mine
2. **Storage**: Expand Storage to handle increased production
3. **Population**: Build and upgrade Farm for settlers
4. **Defense**: Start basic military unit production

### Mid Game (Levels 6-15)
1. **Military Development**: Research and train diverse unit types
2. **Economic Growth**: Build Market for trading
3. **Optimization**: Balance resource production and consumption
4. **Expansion**: Consider multiple building upgrade paths

### Late Game (Levels 15+)
1. **Military Supremacy**: Advanced unit types and large armies
2. **Economic Domination**: High-level trading and resource control
3. **Settlement Optimization**: Maximum efficiency in all systems

## 🔧 Technical Details

### Event Scheduler
- **Purpose**: Handles automatic resource generation
- **Frequency**: Updates resources periodically
- **Requirements**: MySQL Event Scheduler must be enabled
- **Verification**: Check with `SHOW VARIABLES LIKE 'event_scheduler';`

### Database Storage
- **Settlements**: Settlement table stores basic info and coordinates
- **Buildings**: Buildings table stores level and type per settlement
- **Resources**: Stored in Settlement table (wood, stone, ore)
- **Military**: MilitaryUnits table tracks unit counts
- **Queues**: Separate tables for building and training queues

### Performance Considerations
- **Caching**: Game data is cached in browser for responsiveness
- **AJAX Updates**: Real-time updates without page refresh
- **Queue Processing**: Background processing of time-based actions
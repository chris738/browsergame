# Battle System Documentation

## Overview
The Battle System is a comprehensive addition to the browsergame that allows players to engage in combat with other settlements using their trained military units. The system integrates seamlessly with the existing military unit training system.

## Key Features

### 🎯 Attack Interface
- **Target Selection**: Choose from all other settlements on the map
- **Unit Selection**: Select which military units to send into battle
- **Attack Power Preview**: Real-time calculation of total attack power
- **Confirmation System**: Prevent accidental attacks with confirmation dialog

### ⚔️ Battle Mechanics
- **Power Calculation**: Based on unit attack, defense, and ranged values
- **Randomness Factor**: ±20% variation to keep battles interesting
- **Loss Calculation**: Both sides suffer casualties based on battle outcome
- **Victory Conditions**: Superior attack power vs. defense determines winner

### 💰 Resource Plundering
- **Victory Rewards**: Winners plunder 5-15% of defender's resources
- **Plunder Rate**: Based on victory margin (decisive wins = more plunder)
- **Resource Transfer**: Automatic transfer from defender to attacker

### 📜 Battle History
- **Battle Records**: Complete log of all battles (as attacker or defender)
- **Battle Details**: Winner, losses, resources plundered
- **Battle Logs**: Detailed event logging for each battle phase

## Technical Implementation

### Database Tables
- **Battles**: Main battle records with participants and outcomes
- **BattleParticipants**: Detailed unit participation data
- **BattleLogs**: Event logging for battle phases

### Backend Components
- **BattleRepository**: Core battle logic and database operations
- **Database Class**: Battle methods integrated into main database interface
- **Battle Backend API**: RESTful endpoints for AJAX operations

### Frontend Components
- **battle.php**: Main battle interface with attack form
- **Navigation Integration**: Battle tab added to main navigation
- **Real-time Updates**: AJAX-powered interface with live data

### Battle Calculation Algorithm
```
1. Calculate total attack power (attack + ranged)
2. Calculate total defense power (defense + ranged * 0.5)
3. Apply randomness factor (±20%)
4. Determine winner based on effective power comparison
5. Calculate casualty rates:
   - Winner: 10-50% losses based on victory margin
   - Loser: 30-80% losses based on defeat margin
6. Apply unit losses to both sides
7. Calculate resource plunder (5-15% of defender resources)
8. Record battle results and logs
```

## User Interface

### Battle Command Center
The battle interface (`battle.php`) provides:
- **Military Power Overview**: Current attack/defense statistics
- **Target Selection**: Dropdown of attackable settlements
- **Unit Selection Table**: Choose units for attack with availability limits
- **Attack Summary**: Real-time power calculation
- **Battle History**: Recent battle results

### Navigation Integration
- Battle tab added to main navigation menu
- Consistent styling with existing game interface
- Proper active state highlighting

## Unit Types and Combat Stats
- **Guards** 🛡️: 0 Attack, 2 Defense, 0 Ranged (Defensive specialists)
- **Soldiers** ⚔️: 3 Attack, 1 Defense, 0 Ranged (Melee combat)
- **Archers** 🏹: 0 Attack, 1 Defense, 4 Ranged (Ranged combat)
- **Cavalry** 🐎: 5 Attack, 2 Defense, 0 Ranged (Fast heavy units)

## Game Balance

### Attack vs Defense
- Attackers must overcome defensive bonuses
- Ranged units less effective on defense (50% power)
- Random factor prevents predictable outcomes

### Casualty System
- Both sides always suffer losses
- Winners suffer fewer casualties
- Decisive victories reduce winner losses
- Close battles result in heavy losses for both sides

### Resource Economics
- Plunder rates encourage strategic attacks
- Defenders don't lose everything (max 15% per battle)
- Resource transfer maintains game economy balance

## Security and Validation

### Input Validation
- Settlement ID validation
- Unit count validation against available units
- Target validation (cannot attack self)
- Attack confirmation system

### Database Security
- Parameterized queries prevent SQL injection
- Transaction handling for atomic operations
- Error logging for debugging and monitoring

## Future Enhancement Opportunities

### Advanced Features (Not Implemented)
- **Battle Formations**: Unit positioning and tactics
- **Terrain Bonuses**: Map-based combat modifiers
- **Alliance System**: Team battles and diplomacy
- **Siege Mechanics**: Special rules for fortified settlements
- **Battle Animations**: Visual combat representation

### Performance Optimizations
- Battle result caching
- Batch battle processing
- Real-time battle notifications

## Testing Coverage

The battle system includes comprehensive testing:
- **Unit Tests**: Battle calculation algorithms
- **Integration Tests**: Full system component testing
- **Syntax Validation**: PHP code quality checks
- **Class Loading Tests**: Dependency verification

## Files Added/Modified

### New Files
- `battle.php` - Main battle interface
- `php/battle-backend.php` - AJAX API endpoints
- `php/database/repositories/BattleRepository.php` - Core battle logic
- `sql/tables/battle_tables.sql` - Database schema
- `tests/test-battle-system.php` - System tests
- `tests/test-battle-calculations.php` - Unit tests
- `tests/test-battle-integration.php` - Integration tests

### Modified Files
- `php/database.php` - Added battle methods
- `php/navigation.php` - Added battle tab
- `php/emoji-config.php` - Added unit emojis

## Usage Instructions

1. **Train Units**: Use the Military tab to train military units
2. **Access Battle**: Click the Battle tab in navigation
3. **Select Target**: Choose a settlement to attack from dropdown
4. **Choose Units**: Select how many units to send to battle
5. **Launch Attack**: Confirm and execute the attack
6. **View Results**: See battle outcome and resource gains/losses
7. **Check History**: Review past battles in the history section

The battle system is fully functional and ready for player engagement, providing an exciting new dimension to the browsergame experience.
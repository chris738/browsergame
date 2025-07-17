# Admin Panel Documentation

## Overview
The admin panel provides administrative control over the browser game, allowing administrators to manage players, settlements, and building queues.

## Features

### 1. Authentication
- Simple username/password authentication
- Session-based login system
- Default credentials: `admin` / `admin123`

### 2. System Overview
- Real-time statistics dashboard
- Player count monitoring
- Settlement count tracking
- Active building queues overview

### 3. Player Management
- View all players with their stats
- Create new players with custom gold amounts
- Edit player points and gold
- Delete players (removes all associated settlements)

### 4. Settlement Management
- View all settlements with resources and locations
- Edit settlement resources (wood, stone, ore)
- View settlement details and coordinates
- Direct links to game view for each settlement

### 5. Building Queue Management
- Monitor active building queues
- View progress and completion times
- Cancel individual queue entries
- Clear all building queues

## File Structure

### Core Files
- `admin.php` - Main admin interface
- `admin-backend.php` - Admin API endpoints
- `admin.css` - Admin-specific styling
- `admin.js` - Admin panel JavaScript functionality

### Database Extensions
- Extended `database.php` with admin-specific methods
- Graceful handling of database connection failures

## Usage

### Accessing the Admin Panel
1. Navigate to `admin.php` in your browser
2. Login with admin credentials
3. Use the dashboard to manage game data

### Player Management
- **Create Player**: Click "Create New Player" button, fill form
- **Edit Player**: Click "Edit" button next to player, modify values in prompts
- **Delete Player**: Click "Delete" button (requires confirmation)

### Settlement Management
- **Edit Resources**: Click "Edit Resources" button, modify values in modal
- **View Game**: Click "View Game" to see settlement in game interface

### Queue Management
- **Cancel Queue**: Click "Cancel" button next to specific queue entry
- **Clear All**: Click "Clear All Queues" button (requires confirmation)

## Security Notes

### Production Considerations
1. **Change Default Credentials**: Update admin username/password in `admin.php`
2. **Use Password Hashing**: Implement proper password hashing (bcrypt/Argon2)
3. **Add HTTPS**: Ensure admin panel is served over HTTPS
4. **Session Security**: Configure secure session settings
5. **Input Validation**: Enhanced server-side validation for all inputs
6. **Rate Limiting**: Implement rate limiting for login attempts

### Database Security
- Admin panel uses existing database connection class
- Prepared statements prevent SQL injection
- Graceful error handling prevents information disclosure

## API Endpoints

### GET Requests
- `?action=players` - Get all players
- `?action=settlements` - Get all settlements  
- `?action=queues` - Get all active building queues
- `?action=stats` - Get system statistics

### POST Requests
- `action=createPlayer` - Create new player
- `action=updatePlayerStats` - Update player points/gold
- `action=updateSettlementResources` - Update settlement resources
- `action=clearAllQueues` - Clear all building queues

### DELETE Requests  
- `action=deletePlayer` - Delete player and settlements
- `action=deleteQueue` - Delete specific queue entry

## Error Handling
- Database connection failures show demo data
- API errors display user-friendly messages
- Form validation prevents invalid submissions
- Confirmation dialogs for destructive actions

## Responsive Design
- Mobile-friendly responsive layout
- Scalable table interfaces
- Touch-friendly buttons and controls
- Optimized for various screen sizes
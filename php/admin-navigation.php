<?php
// Admin navigation component
// This provides a consistent header with navigation links for the admin panel

require_once __DIR__ . '/emoji-config.php';

$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>

<nav class="navigation">
    <!-- Top row: Admin title, tabs, and user controls -->
    <div class="nav-top-row">
        <div class="settlement-name-compact">
            <span>Admin Panel</span>
        </div>
        
        <!-- Tab navigation in top row -->
        <div class="nav-tabs-inline">
            <a href="admin-overview.php" class="nav-link <?= ($current_page == 'admin-overview') ? 'active' : '' ?>">
                Overview
            </a>
            <a href="admin-buildings.php" class="nav-link <?= ($current_page == 'admin-buildings') ? 'active' : '' ?>">
                Buildings
            </a>
            <a href="admin-market.php" class="nav-link <?= ($current_page == 'admin-market') ? 'active' : '' ?>">
                Trade
            </a>
            <a href="admin-map.php" class="nav-link <?= ($current_page == 'admin-map') ? 'active' : '' ?>">
                Map
            </a>
        </div>
        
        <div class="nav-user-compact">
            <button id="theme-toggle" class="theme-toggle" aria-label="Switch to dark mode"><?= EmojiConfig::getUIEmoji('moon') ?> Dark</button>
            <span class="user-display-compact"><?= EmojiConfig::getUIEmoji('player') ?> admin</span>
            <a href="../admin.php?logout" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <!-- Fallback second row for small screens -->
    <div class="nav-tabs-row nav-tabs-fallback">
        <a href="admin-overview.php" class="nav-link <?= ($current_page == 'admin-overview') ? 'active' : '' ?>">
            Overview
        </a>
        <a href="admin-buildings.php" class="nav-link <?= ($current_page == 'admin-buildings') ? 'active' : '' ?>">
            Buildings
        </a>
        <a href="admin-market.php" class="nav-link <?= ($current_page == 'admin-market') ? 'active' : '' ?>">
            Trade
        </a>
        <a href="admin-map.php" class="nav-link <?= ($current_page == 'admin-map') ? 'active' : '' ?>">
            Map
        </a>
    </div>
</nav>

<script>
    // Make admin navigation more interactive if needed
    document.addEventListener('DOMContentLoaded', function() {
        // Add any admin navigation specific JavaScript here
    });
</script>
<?php
// Admin navigation component
// This provides a consistent header with navigation links for the admin panel

require_once __DIR__ . '/emoji-config.php';

$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>

<nav class="navigation">
    <div class="nav-links">
        <div class="settlement-name">
            <span>Admin Panel</span>
        </div>
        <a href="admin-overview.php" class="nav-link <?= ($current_page == 'admin-overview') ? 'active' : '' ?>">
            Overview
        </a>
        <a href="admin-buildings.php" class="nav-link <?= ($current_page == 'admin-buildings') ? 'active' : '' ?>">
            Building Configuration
        </a>
        <a href="admin-market.php" class="nav-link <?= ($current_page == 'admin-market') ? 'active' : '' ?>">
            Market Trades
        </a>
        <a href="admin-map.php" class="nav-link <?= ($current_page == 'admin-map') ? 'active' : '' ?>">
            Map
        </a>
    </div>
    <div class="nav-user">
        <button id="theme-toggle" class="theme-toggle" aria-label="Switch to dark mode"><?= EmojiConfig::getUIEmoji('moon') ?> Dark</button>
        <span class="user-display"><?= EmojiConfig::getUIEmoji('player') ?> admin</span>
        <a href="../admin.php?logout" class="logout-btn">Logout</a>
    </div>
</nav>

<script>
    // Make admin navigation more interactive if needed
    document.addEventListener('DOMContentLoaded', function() {
        // Add any admin navigation specific JavaScript here
    });
</script>
<?php
// Admin navigation component
// This provides a consistent header with navigation links for the admin panel

$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>

<nav class="admin-navigation">
    <div class="admin-nav-links">
        <div class="admin-title">
            <h1>Admin Panel - Browsergame</h1>
        </div>
        <a href="admin-overview.php" class="admin-nav-link <?= ($current_page == 'admin-overview') ? 'active' : '' ?>">
            Übersicht
        </a>
        <a href="admin-buildings.php" class="admin-nav-link <?= ($current_page == 'admin-buildings') ? 'active' : '' ?>">
            Gebäude-Konfiguration
        </a>
    </div>
    <div class="admin-nav-actions">
        <a href="../admin.php?logout" class="logout-btn">Logout</a>
    </div>
</nav>

<script>
    // Make admin navigation more interactive if needed
    document.addEventListener('DOMContentLoaded', function() {
        // Add any admin navigation specific JavaScript here
    });
</script>
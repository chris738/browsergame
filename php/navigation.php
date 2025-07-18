<?php
// Navigation component for the browser game
// This provides a consistent header with settlement name, resources and navigation links

$settlementId = $_GET['settlementId'] ?? 1; // Default to settlement 1 if not provided
?>

<nav class="navigation">
    <div class="nav-links">
        <div class="settlement-name">
            <span id="Siedlungsname">Settlement Name</span>
        </div>
        <a href="index.php?settlementId=<?= $settlementId ?>" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : '' ?>">
            Settlement
        </a>
        <a href="market.php?settlementId=<?= $settlementId ?>" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'market.php') ? 'active' : '' ?>">
            Market
        </a>
        <a href="map.php?settlementId=<?= $settlementId ?>" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'map.php') ? 'active' : '' ?>">
            Map
        </a>
    </div>
    <div class="nav-user">
        <button id="theme-toggle" class="theme-toggle" aria-label="Switch to dark mode">🌙 Dark</button>
        <span class="user-display">👤 <span id="currentPlayer">Player</span></span>
    </div>
</nav>

<section class="resources">
    <div class="resource">
        <p><span class="resource-emoji" title="Wood - Used for construction and upgrades">🪵</span> <span id="holz">0</span>
        <span class="regen">(+<span id="holzRegen">0</span>/h)</span></p>
    </div>
    <div class="resource">
        <p><span class="resource-emoji" title="Stone - Used for advanced buildings">🧱</span> <span id="stein">0</span>
        <span class="regen">(+<span id="steinRegen">0</span>/h)</span></p>
    </div>
    <div class="resource">
        <p><span class="resource-emoji" title="Ore - Used for high-level buildings">🪨</span> <span id="erz">0</span>
        <span class="regen">(+<span id="erzRegen">0</span>/h)</span></p>
    </div>
    <div class="resource">
        <p><span class="resource-emoji" title="Storage Capacity - Maximum resources you can store">🏪</span> <span id="lagerKapazität">0</span></p>
    </div>
    <div class="resource">
        <p><span class="resource-emoji" title="Settlers - Population available for construction">👥</span> <span id="settlers">0</span> / <span id="maxSettlers">0</span></p>
    </div>
</section>

<script>
    // Make settlement ID available globally for JavaScript
    const settlementId = <?= $settlementId ?>;
</script>
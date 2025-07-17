<?php
// Navigation component for the browser game
// This provides a consistent header with settlement name, resources and navigation links

$settlementId = $_GET['settlementId'] ?? 1; // Default to settlement 1 if not provided
?>

<nav class="navigation">
    <div class="nav-links">
        <div class="settlement-name">
            <span id="Siedlungsname">Siedlungsname</span>
        </div>
        <a href="index.php?settlementId=<?= $settlementId ?>" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : '' ?>">
            Siedlung
        </a>
        <a href="map.php?settlementId=<?= $settlementId ?>" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'map.php') ? 'active' : '' ?>">
            Karte
        </a>
    </div>
</nav>

<section class="resources">
    <div class="resource">
        <p>Holz: <span id="holz">0</span>
        <span class="regen">(+<span id="holzRegen">0</span>/h)</span></p>
    </div>
    <div class="resource">
        <p>Stein: <span id="stein">0</span>
        <span class="regen">(+<span id="steinRegen">0</span>/h)</span></p>
    </div>
    <div class="resource">
        <p>Erz: <span id="erz">0</span>
        <span class="regen">(+<span id="erzRegen">0</span>/h)</span></p>
    </div>
    <div class="resource">
        <p>Lager: <span id="lagerKapazität">0</span></p>
    </div>
    <div class="resource">
        <p>Siedler: <span id="settlers">0</span> / <span id="maxSettlers">0</span></p>
    </div>
</section>

<script>
    // Make settlement ID available globally for JavaScript
    const settlementId = <?= $settlementId ?>;
</script>
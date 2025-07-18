<?php
// Navigation component for the browser game
// This provides a consistent header with settlement name, resources and navigation links

require_once __DIR__ . '/emoji-config.php';

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
        <button id="theme-toggle" class="theme-toggle" aria-label="Switch to dark mode"><?= EmojiConfig::getUIEmoji('moon') ?> Dark</button>
        <span class="user-display"><?= EmojiConfig::getUIEmoji('player') ?> <span id="currentPlayer">Player</span></span>
    </div>
</nav>

<section class="resources">
    <div class="resource">
        <p><?= EmojiConfig::formatResourceWithEmoji('wood', '<span id="holz">0</span>', true, '<span id="holzRegen">0</span>') ?></p>
    </div>
    <div class="resource">
        <p><?= EmojiConfig::formatResourceWithEmoji('stone', '<span id="stein">0</span>', true, '<span id="steinRegen">0</span>') ?></p>
    </div>
    <div class="resource">
        <p><?= EmojiConfig::formatResourceWithEmoji('ore', '<span id="erz">0</span>', true, '<span id="erzRegen">0</span>') ?></p>
    </div>
    <div class="resource">
        <p><?= EmojiConfig::formatResourceWithEmoji('storage', '<span id="lagerKapazität">0</span>') ?></p>
    </div>
    <div class="resource">
        <p><?= EmojiConfig::formatResourceWithEmoji('settlers', '<span id="settlers">0</span> / <span id="maxSettlers">0</span>') ?></p>
    </div>
</section>

<script>
    // Make settlement ID available globally for JavaScript
    const settlementId = <?= $settlementId ?>;
</script>
<?php
// Navigation component for the browser game
// This provides a consistent header with settlement name, resources and navigation links

require_once __DIR__ . '/emoji-config.php';

$settlementId = $_GET['settlementId'] ?? 1; // Default to settlement 1 if not provided
?>

<nav class="navigation">
    <!-- Top row: Settlement name, tabs, and user controls -->
    <div class="nav-top-row">
        <div class="settlement-name-compact">
            <span id="Siedlungsname">Settlement Name</span>
        </div>
        
        <!-- Tab navigation in top row -->
        <div class="nav-tabs-inline">
            <a href="index.php?settlementId=<?= $settlementId ?>" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : '' ?>">
                Settlement
            </a>
            <a href="market.php?settlementId=<?= $settlementId ?>" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'market.php') ? 'active' : '' ?>">
                Trade
            </a>
            <a href="map.php?settlementId=<?= $settlementId ?>" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'map.php') ? 'active' : '' ?>">
                Map
            </a>
            <a href="kaserne.php?settlementId=<?= $settlementId ?>" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'kaserne.php') ? 'active' : '' ?>">
                Military
            </a>
            <a href="battle.php?settlementId=<?= $settlementId ?>" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'battle.php') ? 'active' : '' ?>">
                Battle
            </a>
        </div>
        
        <div class="nav-user-compact">
            <button id="theme-toggle" class="theme-toggle" aria-label="Switch to dark mode"><?= EmojiConfig::getUIEmoji('moon') ?> Dark</button>
            <div class="player-controls-compact">
                <select id="playerSwitcher" class="player-switcher">
                    <option value="">Loading players...</option>
                </select>
                <span class="user-display-compact">
                    <?= EmojiConfig::getUIEmoji('player') ?> <span id="currentPlayer">Player</span>
                    | <?= EmojiConfig::getResourceEmoji('gold') ?> <span id="playerGold">0</span>
                </span>
            </div>
        </div>
    </div>
    
    <!-- Fallback second row for small screens -->
    <div class="nav-tabs-row nav-tabs-fallback">
        <a href="index.php?settlementId=<?= $settlementId ?>" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : '' ?>">
            Settlement
        </a>
        <a href="market.php?settlementId=<?= $settlementId ?>" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'market.php') ? 'active' : '' ?>">
            Trade
        </a>
        <a href="map.php?settlementId=<?= $settlementId ?>" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'map.php') ? 'active' : '' ?>">
            Map
        </a>
        <a href="kaserne.php?settlementId=<?= $settlementId ?>" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'kaserne.php') ? 'active' : '' ?>">
            Military
        </a>
        <a href="battle.php?settlementId=<?= $settlementId ?>" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'battle.php') ? 'active' : '' ?>">
            Battle
        </a>
    </div>
    
    <!-- Resources row (responsive) -->
    <div class="nav-resources-row">
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
    </div>
</nav>

<script>
    // Make settlement ID available globally for JavaScript
    const settlementId = <?= $settlementId ?>;
</script>
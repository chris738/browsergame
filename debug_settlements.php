<?php
require_once 'php/database.php';

$database = new Database();
echo "Database connected: " . ($database->isConnected() ? "YES" : "NO") . "\n";

$settlements = $database->getAllSettlements();
echo "Number of settlements: " . count($settlements) . "\n";

foreach ($settlements as $settlement) {
    echo "Settlement ID: {$settlement['settlementId']}, Name: {$settlement['name']}, Player: {$settlement['playerName']}, Coords: ({$settlement['xCoordinate']}, {$settlement['yCoordinate']})\n";
}
?>
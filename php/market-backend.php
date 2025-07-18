<?php
require_once 'database.php';
header('Content-Type: application/json; charset=utf-8');

// Helper function to get player name from settlement
function getPlayerNameFromSettlement($database, $settlementId) {
    return $database->getPlayerNameFromSettlement($settlementId);
}

// Create a new trade offer
function createTradeOffer($database, $settlementId, $offerData) {
    try {
        // Validate that player has required resources
        $resources = $database->getResources($settlementId);
        if (!$resources) {
            return ['success' => false, 'message' => 'Could not check your resources.'];
        }

        // Check if player has enough resources to offer
        if ($offerData['offerWood'] > $resources['wood'] ||
            $offerData['offerStone'] > $resources['stone'] ||
            $offerData['offerOre'] > $resources['ore']) {
            return ['success' => false, 'message' => 'You do not have enough resources to make this offer.'];
        }

        // For gold offers, check player's gold (we'll need to add gold to Settlement table or get it from Spieler)
        $playerGold = $database->getPlayerGold($settlementId);
        if ($offerData['offerGold'] > $playerGold) {
            return ['success' => false, 'message' => 'You do not have enough gold to make this offer.'];
        }

        // Insert trade offer
        $sql = "INSERT INTO TradeOffers (fromSettlementId, offerType, offerWood, offerStone, offerOre, offerGold, 
                requestWood, requestStone, requestOre, requestGold, maxTrades) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $database->getConnection()->prepare($sql);
        $success = $stmt->execute([
            $settlementId,
            $offerData['offerType'],
            $offerData['offerWood'],
            $offerData['offerStone'],
            $offerData['offerOre'],
            $offerData['offerGold'],
            $offerData['requestWood'],
            $offerData['requestStone'],
            $offerData['requestOre'],
            $offerData['requestGold'],
            $offerData['maxTrades']
        ]);

        if ($success) {
            return ['success' => true, 'message' => 'Trade offer created successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to create trade offer.'];
        }
    } catch (Exception $e) {
        error_log("Error creating trade offer: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred.'];
    }
}

// Get player's gold amount
function getPlayerGold($database, $settlementId) {
    return $database->getPlayerGold($settlementId);
}

// Get available trade offers (excluding own offers)
function getTradeOffers($database, $settlementId, $filter = 'all') {
    // Check if database is connected, return failure if not
    if (!$database->isConnected()) {
        return ['success' => false, 'offers' => []];
    }
    
    try {
        $whereClause = "WHERE t.fromSettlementId != ? AND t.isActive = 1 AND t.currentTrades < t.maxTrades";
        
        if ($filter !== 'all') {
            $whereClause .= " AND t.offerType = ?";
        }

        $sql = "SELECT t.*, p.name as playerName, s.name as settlementName 
                FROM TradeOffers t
                INNER JOIN Settlement s ON t.fromSettlementId = s.settlementId
                INNER JOIN Spieler p ON s.playerId = p.playerId
                $whereClause
                ORDER BY t.createdAt DESC";
        
        $stmt = $database->getConnection()->prepare($sql);
        if ($filter !== 'all') {
            $stmt->execute([$settlementId, $filter]);
        } else {
            $stmt->execute([$settlementId]);
        }
        
        return ['success' => true, 'offers' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    } catch (Exception $e) {
        error_log("Error fetching trade offers: " . $e->getMessage());
        return ['success' => false, 'offers' => []];
    }
}

// Get player's own offers
function getMyTradeOffers($database, $settlementId) {
    // Check if database is connected, return failure if not
    if (!$database->isConnected()) {
        return ['success' => false, 'offers' => []];
    }
    
    try {
        $sql = "SELECT * FROM TradeOffers 
                WHERE fromSettlementId = ? AND isActive = 1 
                ORDER BY createdAt DESC";
        
        $stmt = $database->getConnection()->prepare($sql);
        $stmt->execute([$settlementId]);
        
        return ['success' => true, 'offers' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    } catch (Exception $e) {
        error_log("Error fetching my trade offers: " . $e->getMessage());
        return ['success' => false, 'offers' => []];
    }
}

// Accept a trade offer
function acceptTradeOffer($database, $settlementId, $offerId) {
    try {
        $database->getConnection()->beginTransaction();

        // Get the offer details
        $sql = "SELECT * FROM TradeOffers WHERE offerId = ? AND isActive = 1 AND currentTrades < maxTrades";
        $stmt = $database->getConnection()->prepare($sql);
        $stmt->execute([$offerId]);
        $offer = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$offer) {
            $database->getConnection()->rollback();
            return ['success' => false, 'message' => 'Trade offer not found or no longer available.'];
        }

        // Can't trade with yourself
        if ($offer['fromSettlementId'] == $settlementId) {
            $database->getConnection()->rollback();
            return ['success' => false, 'message' => 'You cannot accept your own trade offer.'];
        }

        // Check if accepting player has required resources
        $accepterResources = $database->getResources($settlementId);
        $accepterGold = getPlayerGold($database, $settlementId);

        if ($offer['requestWood'] > $accepterResources['wood'] ||
            $offer['requestStone'] > $accepterResources['stone'] ||
            $offer['requestOre'] > $accepterResources['ore'] ||
            $offer['requestGold'] > $accepterGold) {
            $database->getConnection()->rollback();
            return ['success' => false, 'message' => 'You do not have the required resources for this trade.'];
        }

        // Check if offering player still has required resources
        $offerrerResources = $database->getResources($offer['fromSettlementId']);
        $offerrerGold = getPlayerGold($database, $offer['fromSettlementId']);

        if ($offer['offerWood'] > $offerrerResources['wood'] ||
            $offer['offerStone'] > $offerrerResources['stone'] ||
            $offer['offerOre'] > $offerrerResources['ore'] ||
            $offer['offerGold'] > $offerrerGold) {
            $database->getConnection()->rollback();
            return ['success' => false, 'message' => 'The offering player no longer has the required resources.'];
        }

        // Execute the trade
        // Update offering player's resources
        $sql = "UPDATE Settlement SET 
                wood = wood - ? + ?,
                stone = stone - ? + ?,
                ore = ore - ? + ?
                WHERE settlementId = ?";
        $stmt = $database->getConnection()->prepare($sql);
        $stmt->execute([
            $offer['offerWood'], $offer['requestWood'],
            $offer['offerStone'], $offer['requestStone'],
            $offer['offerOre'], $offer['requestOre'],
            $offer['fromSettlementId']
        ]);

        // Update accepting player's resources
        $sql = "UPDATE Settlement SET 
                wood = wood + ? - ?,
                stone = stone + ? - ?,
                ore = ore + ? - ?
                WHERE settlementId = ?";
        $stmt = $database->getConnection()->prepare($sql);
        $stmt->execute([
            $offer['offerWood'], $offer['requestWood'],
            $offer['offerStone'], $offer['requestStone'],
            $offer['offerOre'], $offer['requestOre'],
            $settlementId
        ]);

        // Handle gold transactions
        if ($offer['offerGold'] > 0 || $offer['requestGold'] > 0) {
            // Update offering player's gold
            $sql = "UPDATE Spieler p 
                    INNER JOIN Settlement s ON p.playerId = s.playerId 
                    SET p.gold = p.gold - ? + ?
                    WHERE s.settlementId = ?";
            $stmt = $database->getConnection()->prepare($sql);
            $stmt->execute([$offer['offerGold'], $offer['requestGold'], $offer['fromSettlementId']]);

            // Update accepting player's gold
            $sql = "UPDATE Spieler p 
                    INNER JOIN Settlement s ON p.playerId = s.playerId 
                    SET p.gold = p.gold + ? - ?
                    WHERE s.settlementId = ?";
            $stmt = $database->getConnection()->prepare($sql);
            $stmt->execute([$offer['offerGold'], $offer['requestGold'], $settlementId]);
        }

        // Record the transaction
        $sql = "INSERT INTO TradeTransactions (offerId, fromSettlementId, toSettlementId, 
                tradedWood, tradedStone, tradedOre, tradedGold,
                receivedWood, receivedStone, receivedOre, receivedGold) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $database->getConnection()->prepare($sql);
        $stmt->execute([
            $offerId,
            $offer['fromSettlementId'],
            $settlementId,
            $offer['offerWood'], $offer['offerStone'], $offer['offerOre'], $offer['offerGold'],
            $offer['requestWood'], $offer['requestStone'], $offer['requestOre'], $offer['requestGold']
        ]);

        // Update the offer's trade count
        $sql = "UPDATE TradeOffers SET currentTrades = currentTrades + 1 WHERE offerId = ?";
        $stmt = $database->getConnection()->prepare($sql);
        $stmt->execute([$offerId]);

        // If max trades reached, deactivate the offer
        if ($offer['currentTrades'] + 1 >= $offer['maxTrades']) {
            $sql = "UPDATE TradeOffers SET isActive = 0 WHERE offerId = ?";
            $stmt = $database->getConnection()->prepare($sql);
            $stmt->execute([$offerId]);
        }

        $database->getConnection()->commit();
        return ['success' => true, 'message' => 'Trade completed successfully!'];

    } catch (Exception $e) {
        $database->getConnection()->rollback();
        error_log("Error accepting trade offer: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred during trade.'];
    }
}

// Cancel a trade offer
function cancelTradeOffer($database, $settlementId, $offerId) {
    try {
        $sql = "UPDATE TradeOffers SET isActive = 0 
                WHERE offerId = ? AND fromSettlementId = ? AND isActive = 1";
        $stmt = $database->getConnection()->prepare($sql);
        $success = $stmt->execute([$offerId, $settlementId]);
        
        if ($success && $stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Trade offer cancelled successfully.'];
        } else {
            return ['success' => false, 'message' => 'Could not cancel trade offer.'];
        }
    } catch (Exception $e) {
        error_log("Error cancelling trade offer: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred.'];
    }
}

// Get trade history
function getTradeHistory($database, $settlementId, $limit = 10) {
    // Check if database is connected, return failure if not
    if (!$database->isConnected()) {
        return ['success' => false, 'history' => []];
    }
    
    try {
        $sql = "SELECT t.*, 
                p1.name as offerrerName, s1.name as offerrerSettlement,
                p2.name as accepterName, s2.name as accepterSettlement
                FROM TradeTransactions t
                INNER JOIN Settlement s1 ON t.fromSettlementId = s1.settlementId
                INNER JOIN Spieler p1 ON s1.playerId = p1.playerId
                INNER JOIN Settlement s2 ON t.toSettlementId = s2.settlementId
                INNER JOIN Spieler p2 ON s2.playerId = p2.playerId
                WHERE t.fromSettlementId = ? OR t.toSettlementId = ?
                ORDER BY t.completedAt DESC
                LIMIT ?";
        
        $stmt = $database->getConnection()->prepare($sql);
        $stmt->execute([$settlementId, $settlementId, $limit]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format the data for frontend
        $history = [];
        foreach ($transactions as $tx) {
            $isOfferer = ($tx['fromSettlementId'] == $settlementId);
            
            $history[] = [
                'completedAt' => $tx['completedAt'],
                'otherPlayerName' => $isOfferer ? $tx['accepterName'] : $tx['offerrerName'],
                'yourGave' => [
                    'wood' => $isOfferer ? $tx['tradedWood'] : $tx['receivedWood'],
                    'stone' => $isOfferer ? $tx['tradedStone'] : $tx['receivedStone'],
                    'ore' => $isOfferer ? $tx['tradedOre'] : $tx['receivedOre'],
                    'gold' => $isOfferer ? $tx['tradedGold'] : $tx['receivedGold']
                ],
                'youGot' => [
                    'wood' => $isOfferer ? $tx['receivedWood'] : $tx['tradedWood'],
                    'stone' => $isOfferer ? $tx['receivedStone'] : $tx['tradedStone'],
                    'ore' => $isOfferer ? $tx['receivedOre'] : $tx['tradedOre'],
                    'gold' => $isOfferer ? $tx['receivedGold'] : $tx['tradedGold']
                ]
            ];
        }
        
        return ['success' => true, 'history' => $history];
    } catch (Exception $e) {
        error_log("Error fetching trade history: " . $e->getMessage());
        return ['success' => false, 'history' => []];
    }
}

// Main request handling
$database = new Database();
$method = $_SERVER['REQUEST_METHOD'];
$settlementId = $_GET['settlementId'] ?? 1; // Default to 1 for demo purposes

// For demo purposes, we'll allow the calls even if database is not available
$databaseConnected = $database->isConnected();

try {
    if ($method === 'GET') {
        if (isset($_GET['getOffers'])) {
            $filter = $_GET['filter'] ?? 'all';
            $result = getTradeOffers($database, $settlementId, $filter);
            
            // Provide fallback data if database failed
            if (!$result['success']) {
                $result = [
                    'success' => true,
                    'offers' => [
                        [
                            'offerId' => 1,
                            'playerName' => 'Demo Player 1',
                            'offerType' => 'resource_trade',
                            'offerWood' => 100,
                            'offerStone' => 0,
                            'offerOre' => 0,
                            'offerGold' => 0,
                            'requestWood' => 0,
                            'requestStone' => 50,
                            'requestOre' => 0,
                            'requestGold' => 0,
                            'currentTrades' => 0,
                            'maxTrades' => 3,
                            'createdAt' => date('Y-m-d H:i:s')
                        ],
                        [
                            'offerId' => 2,
                            'playerName' => 'Demo Player 2',
                            'offerType' => 'resource_sell',
                            'offerWood' => 0,
                            'offerStone' => 75,
                            'offerOre' => 25,
                            'offerGold' => 0,
                            'requestWood' => 0,
                            'requestStone' => 0,
                            'requestOre' => 0,
                            'requestGold' => 50,
                            'currentTrades' => 1,
                            'maxTrades' => 2,
                            'createdAt' => date('Y-m-d H:i:s', strtotime('-1 hour'))
                        ],
                        [
                            'offerId' => 3,
                            'playerName' => 'Demo Player 3',
                            'offerType' => 'resource_trade',
                            'offerWood' => 200,
                            'offerStone' => 0,
                            'offerOre' => 0,
                            'offerGold' => 0,
                            'requestWood' => 0,
                            'requestStone' => 0,
                            'requestOre' => 100,
                            'requestGold' => 0,
                            'currentTrades' => 0,
                            'maxTrades' => 5,
                            'createdAt' => date('Y-m-d H:i:s', strtotime('-30 minutes'))
                        ],
                        [
                            'offerId' => 4,
                            'playerName' => 'Demo Player 4',
                            'offerType' => 'resource_buy',
                            'offerWood' => 0,
                            'offerStone' => 0,
                            'offerOre' => 0,
                            'offerGold' => 100,
                            'requestWood' => 150,
                            'requestStone' => 0,
                            'requestOre' => 0,
                            'requestGold' => 0,
                            'currentTrades' => 2,
                            'maxTrades' => 4,
                            'createdAt' => date('Y-m-d H:i:s', strtotime('-2 hours'))
                        ]
                    ]
                ];
            }
            
            echo json_encode($result);
        } elseif (isset($_GET['getMyOffers'])) {
            $result = getMyTradeOffers($database, $settlementId);
            
            // Provide fallback data if database failed
            if (!$result['success']) {
                $result = [
                    'success' => true,
                    'offers' => []  // No active offers for demo
                ];
            }
            
            echo json_encode($result);
        } elseif (isset($_GET['getHistory'])) {
            $result = getTradeHistory($database, $settlementId);
            
            // Provide fallback data if database failed
            if (!$result['success']) {
                $result = [
                    'success' => true,
                    'history' => [
                        [
                            'otherPlayerName' => 'Demo Trader 1',
                            'yourGave' => ['wood' => 50, 'stone' => 0, 'ore' => 0, 'gold' => 0],
                            'youGot' => ['wood' => 0, 'stone' => 25, 'ore' => 0, 'gold' => 0],
                            'completedAt' => date('Y-m-d H:i:s', strtotime('-2 hours'))
                        ],
                        [
                            'otherPlayerName' => 'Demo Trader 2',
                            'yourGave' => ['wood' => 0, 'stone' => 30, 'ore' => 0, 'gold' => 0],
                            'youGot' => ['wood' => 0, 'stone' => 0, 'ore' => 15, 'gold' => 0],
                            'completedAt' => date('Y-m-d H:i:s', strtotime('-4 hours'))
                        ],
                        [
                            'otherPlayerName' => 'Demo Trader 3',
                            'yourGave' => ['wood' => 100, 'stone' => 0, 'ore' => 0, 'gold' => 0],
                            'youGot' => ['wood' => 0, 'stone' => 0, 'ore' => 0, 'gold' => 25],
                            'completedAt' => date('Y-m-d H:i:s', strtotime('-6 hours'))
                        ],
                        [
                            'otherPlayerName' => 'Demo Trader 4',
                            'yourGave' => ['wood' => 0, 'stone' => 0, 'ore' => 20, 'gold' => 0],
                            'youGot' => ['wood' => 40, 'stone' => 0, 'ore' => 0, 'gold' => 0],
                            'completedAt' => date('Y-m-d H:i:s', strtotime('-8 hours'))
                        ],
                        [
                            'otherPlayerName' => 'Demo Trader 5',
                            'yourGave' => ['wood' => 0, 'stone' => 0, 'ore' => 0, 'gold' => 15],
                            'youGot' => ['wood' => 75, 'stone' => 0, 'ore' => 0, 'gold' => 0],
                            'completedAt' => date('Y-m-d H:i:s', strtotime('-10 hours'))
                        ],
                        [
                            'otherPlayerName' => 'Demo Trader 6',
                            'yourGave' => ['wood' => 25, 'stone' => 25, 'ore' => 0, 'gold' => 0],
                            'youGot' => ['wood' => 0, 'stone' => 0, 'ore' => 30, 'gold' => 0],
                            'completedAt' => date('Y-m-d H:i:s', strtotime('-12 hours'))
                        ],
                        [
                            'otherPlayerName' => 'Demo Trader 7',
                            'yourGave' => ['wood' => 0, 'stone' => 0, 'ore' => 10, 'gold' => 0],
                            'youGot' => ['wood' => 0, 'stone' => 0, 'ore' => 0, 'gold' => 20],
                            'completedAt' => date('Y-m-d H:i:s', strtotime('-14 hours'))
                        ],
                        [
                            'otherPlayerName' => 'Demo Trader 8',
                            'yourGave' => ['wood' => 80, 'stone' => 0, 'ore' => 0, 'gold' => 0],
                            'youGot' => ['wood' => 0, 'stone' => 40, 'ore' => 0, 'gold' => 0],
                            'completedAt' => date('Y-m-d H:i:s', strtotime('-16 hours'))
                        ],
                        [
                            'otherPlayerName' => 'Demo Trader 9',
                            'yourGave' => ['wood' => 0, 'stone' => 15, 'ore' => 15, 'gold' => 0],
                            'youGot' => ['wood' => 60, 'stone' => 0, 'ore' => 0, 'gold' => 0],
                            'completedAt' => date('Y-m-d H:i:s', strtotime('-18 hours'))
                        ],
                        [
                            'otherPlayerName' => 'Demo Trader 10',
                            'yourGave' => ['wood' => 0, 'stone' => 0, 'ore' => 0, 'gold' => 30],
                            'youGot' => ['wood' => 120, 'stone' => 0, 'ore' => 0, 'gold' => 0],
                            'completedAt' => date('Y-m-d H:i:s', strtotime('-20 hours'))
                        ]
                    ]
                ];
            }
            
            echo json_encode($result);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid request.']);
        }
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? null;

        switch ($action) {
            case 'createOffer':
                $result = createTradeOffer($database, $settlementId, $input);
                break;
            case 'acceptOffer':
                $offerId = $input['offerId'] ?? null;
                if (!$offerId) {
                    $result = ['success' => false, 'message' => 'Offer ID required.'];
                } else {
                    $result = acceptTradeOffer($database, $settlementId, $offerId);
                }
                break;
            case 'cancelOffer':
                $offerId = $input['offerId'] ?? null;
                if (!$offerId) {
                    $result = ['success' => false, 'message' => 'Offer ID required.'];
                } else {
                    $result = cancelTradeOffer($database, $settlementId, $offerId);
                }
                break;
            default:
                $result = ['success' => false, 'message' => 'Invalid action.'];
                break;
        }
        
        echo json_encode($result);
    } else {
        echo json_encode(['success' => false, 'message' => 'Method not supported.']);
    }
} catch (Exception $e) {
    error_log("Market backend error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred.']);
}
?>
<?php

class TradingRepository {
    private $conn;
    private $connectionFailed;

    public function __construct($connection, $connectionFailed = false) {
        $this->conn = $connection;
        $this->connectionFailed = $connectionFailed;
    }

    // Market/Trade management methods
    public function getTotalTradeOffers() {
        try {
            $sql = "SELECT COUNT(*) as count FROM TradeOffers";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'];
        } catch (Exception $e) {
            return 0;
        }
    }

    public function getActiveTradeOffers() {
        try {
            $sql = "SELECT COUNT(*) as count FROM TradeOffers WHERE currentTrades < maxTrades";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'];
        } catch (Exception $e) {
            return 0;
        }
    }

    public function getCompletedTradesCount() {
        try {
            $sql = "SELECT COUNT(*) as count FROM TradeHistory";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'];
        } catch (Exception $e) {
            return 0;
        }
    }

    public function cancelTradeOffer($tradeId) {
        try {
            $sql = "DELETE FROM TradeOffers WHERE offerId = ?";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$tradeId]);
        } catch (Exception $e) {
            return false;
        }
    }

    public function clearAllTradeOffers() {
        try {
            $sql = "DELETE FROM TradeOffers";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }

    public function getActiveTradesForAdmin() {
        try {
            $sql = "SELECT 
                        to.offerId as id,
                        p.name as fromPlayer,
                        to.offerType as type,
                        to.offerWood,
                        to.offerStone, 
                        to.offerOre,
                        to.offerGold,
                        to.requestWood,
                        to.requestStone,
                        to.requestOre,
                        to.requestGold,
                        to.currentTrades,
                        to.maxTrades,
                        to.createdAt
                    FROM TradeOffers to
                    INNER JOIN Settlement s ON to.fromSettlementId = s.settlementId
                    INNER JOIN Spieler p ON s.playerId = p.playerId
                    WHERE to.currentTrades < to.maxTrades
                    ORDER BY to.createdAt DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getTradeHistoryForAdmin($limit = 20) {
        try {
            $sql = "SELECT 
                        th.id,
                        p1.name as fromPlayer,
                        p2.name as toPlayer,
                        th.wood,
                        th.stone,
                        th.ore,
                        th.gold,
                        th.completedAt
                    FROM TradeHistory th
                    INNER JOIN Settlement s1 ON th.fromSettlementId = s1.settlementId
                    INNER JOIN Settlement s2 ON th.toSettlementId = s2.settlementId
                    INNER JOIN Spieler p1 ON s1.playerId = p1.playerId
                    INNER JOIN Spieler p2 ON s2.playerId = p2.playerId
                    ORDER BY th.completedAt DESC
                    LIMIT ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getTradeAnalytics() {
        try {
            // Most traded resource
            $sql1 = "SELECT 'wood' as resource, SUM(wood) as total FROM TradeHistory WHERE wood > 0
                     UNION ALL
                     SELECT 'stone' as resource, SUM(stone) as total FROM TradeHistory WHERE stone > 0
                     UNION ALL
                     SELECT 'ore' as resource, SUM(ore) as total FROM TradeHistory WHERE ore > 0
                     UNION ALL
                     SELECT 'gold' as resource, SUM(gold) as total FROM TradeHistory WHERE gold > 0
                     ORDER BY total DESC LIMIT 1";
            $stmt1 = $this->conn->prepare($sql1);
            $stmt1->execute();
            $mostTraded = $stmt1->fetch(PDO::FETCH_ASSOC);

            // Average trade value (sum of all resources)
            $sql2 = "SELECT AVG(wood + stone + ore + gold) as avgValue FROM TradeHistory";
            $stmt2 = $this->conn->prepare($sql2);
            $stmt2->execute();
            $avgValue = $stmt2->fetch(PDO::FETCH_ASSOC);

            // Top trader
            $sql3 = "SELECT p.name, COUNT(*) as tradeCount
                     FROM TradeHistory th
                     INNER JOIN Settlement s ON th.fromSettlementId = s.settlementId OR th.toSettlementId = s.settlementId
                     INNER JOIN Spieler p ON s.playerId = p.playerId
                     GROUP BY p.playerId
                     ORDER BY tradeCount DESC
                     LIMIT 1";
            $stmt3 = $this->conn->prepare($sql3);
            $stmt3->execute();
            $topTrader = $stmt3->fetch(PDO::FETCH_ASSOC);

            return [
                'mostTradedResource' => $mostTraded ? ucfirst($mostTraded['resource']) : 'N/A',
                'avgTradeValue' => $avgValue ? round($avgValue['avgValue'], 2) : '0',
                'topTrader' => $topTrader ? $topTrader['name'] : 'N/A'
            ];
        } catch (Exception $e) {
            return [
                'mostTradedResource' => 'Error',
                'avgTradeValue' => 'Error',
                'topTrader' => 'Error'
            ];
        }
    }

    public function getTradeHistoryBetweenPlayers($playerId1, $playerId2, $limit = 10) {
        if ($this->connectionFailed) {
            return [];
        }

        try {
            $sql = "SELECT 
                        tt.transactionId,
                        tt.tradedWood as wood,
                        tt.tradedStone as stone,
                        tt.tradedOre as ore,
                        tt.tradedGold as gold,
                        tt.completedAt,
                        p1.name as fromPlayer,
                        p2.name as toPlayer
                    FROM TradeTransactions tt
                    INNER JOIN Settlement s1 ON tt.fromSettlementId = s1.settlementId
                    INNER JOIN Settlement s2 ON tt.toSettlementId = s2.settlementId
                    INNER JOIN Spieler p1 ON s1.playerId = p1.playerId
                    INNER JOIN Spieler p2 ON s2.playerId = p2.playerId
                    WHERE (s1.playerId = ? AND s2.playerId = ?) OR (s1.playerId = ? AND s2.playerId = ?)
                    ORDER BY tt.completedAt DESC
                    LIMIT ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$playerId1, $playerId2, $playerId2, $playerId1, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting trade history between players: " . $e->getMessage());
            return [];
        }
    }
}

?>
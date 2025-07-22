-- Travel System Procedures
-- These procedures handle the travel processing logic for the event system

DELIMITER //

-- Procedure: Process Arrived Armies
DROP PROCEDURE IF EXISTS ProcessArrivedArmies//
CREATE PROCEDURE ProcessArrivedArmies()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE army_travel_id INT;
    DECLARE army_attacker_id INT;
    DECLARE army_defender_id INT;
    DECLARE army_guards INT;
    DECLARE army_soldiers INT;
    DECLARE army_archers INT;
    DECLARE army_cavalry INT;
    
    -- Cursor for arrived armies
    DECLARE army_cursor CURSOR FOR 
        SELECT travelId, attackerSettlementId, defenderSettlementId, 
               guardsCount, soldiersCount, archersCount, cavalryCount
        FROM TravelingArmies 
        WHERE arrivalTime <= NOW() AND status = 'traveling';
        
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN army_cursor;
    
    read_loop: LOOP
        FETCH army_cursor INTO army_travel_id, army_attacker_id, army_defender_id,
                               army_guards, army_soldiers, army_archers, army_cavalry;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Execute battle processing
        CALL ExecuteBattleOnArrival(army_attacker_id, army_defender_id, 
                                  army_guards, army_soldiers, army_archers, army_cavalry);
        
        -- Mark army as arrived
        UPDATE TravelingArmies SET status = 'arrived' WHERE travelId = army_travel_id;
        
    END LOOP;
    
    CLOSE army_cursor;
END//

-- Procedure: Process Arrived Trades
DROP PROCEDURE IF EXISTS ProcessArrivedTrades//
CREATE PROCEDURE ProcessArrivedTrades()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE trade_travel_id INT;
    DECLARE trade_to_settlement INT;
    DECLARE trade_wood FLOAT;
    DECLARE trade_stone FLOAT;
    DECLARE trade_ore FLOAT;
    DECLARE trade_gold INT;
    
    -- Cursor for arrived trades
    DECLARE trade_cursor CURSOR FOR 
        SELECT travelId, toSettlementId, woodAmount, stoneAmount, oreAmount, goldAmount
        FROM TravelingTrades 
        WHERE arrivalTime <= NOW() AND status = 'traveling';
        
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN trade_cursor;
    
    read_loop: LOOP
        FETCH trade_cursor INTO trade_travel_id, trade_to_settlement,
                               trade_wood, trade_stone, trade_ore, trade_gold;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Execute trade delivery
        CALL ExecuteTradeOnArrival(trade_to_settlement, trade_wood, trade_stone, trade_ore, trade_gold);
        
        -- Mark trade as completed
        UPDATE TravelingTrades SET status = 'completed' WHERE travelId = trade_travel_id;
        
    END LOOP;
    
    CLOSE trade_cursor;
END//

-- Procedure: Execute Battle On Arrival (Simplified)
DROP PROCEDURE IF EXISTS ExecuteBattleOnArrival//
CREATE PROCEDURE ExecuteBattleOnArrival(
    IN p_attacker_settlement_id INT,
    IN p_defender_settlement_id INT,
    IN p_guards INT,
    IN p_soldiers INT,
    IN p_archers INT,
    IN p_cavalry INT
)
BEGIN
    DECLARE attacker_power INT DEFAULT 0;
    DECLARE defender_power INT DEFAULT 0;
    DECLARE battle_result VARCHAR(20) DEFAULT 'defeat';
    DECLARE plunder_wood FLOAT DEFAULT 0;
    DECLARE plunder_stone FLOAT DEFAULT 0;
    DECLARE plunder_ore FLOAT DEFAULT 0;
    
    -- Add traveling units back to attacker temporarily for battle calculation
    IF p_guards > 0 THEN
        INSERT INTO MilitaryUnits (settlementId, unitType, count) 
        VALUES (p_attacker_settlement_id, 'guards', p_guards)
        ON DUPLICATE KEY UPDATE count = count + p_guards;
    END IF;
    
    IF p_soldiers > 0 THEN
        INSERT INTO MilitaryUnits (settlementId, unitType, count) 
        VALUES (p_attacker_settlement_id, 'soldiers', p_soldiers)
        ON DUPLICATE KEY UPDATE count = count + p_soldiers;
    END IF;
    
    IF p_archers > 0 THEN
        INSERT INTO MilitaryUnits (settlementId, unitType, count) 
        VALUES (p_attacker_settlement_id, 'archers', p_archers)
        ON DUPLICATE KEY UPDATE count = count + p_archers;
    END IF;
    
    IF p_cavalry > 0 THEN
        INSERT INTO MilitaryUnits (settlementId, unitType, count) 
        VALUES (p_attacker_settlement_id, 'cavalry', p_cavalry)
        ON DUPLICATE KEY UPDATE count = count + p_cavalry;
    END IF;
    
    -- Calculate military power
    SELECT COALESCE(SUM(mu.count * muc.attackPower), 0)
    INTO attacker_power
    FROM MilitaryUnits mu
    INNER JOIN MilitaryUnitConfig muc ON mu.unitType = muc.unitType
    WHERE mu.settlementId = p_attacker_settlement_id;
    
    SELECT COALESCE(SUM(mu.count * muc.defensePower), 0)
    INTO defender_power
    FROM MilitaryUnits mu
    INNER JOIN MilitaryUnitConfig muc ON mu.unitType = muc.unitType
    WHERE mu.settlementId = p_defender_settlement_id;
    
    -- Simple battle calculation (attacker needs higher power to win)
    IF attacker_power > defender_power THEN
        SET battle_result = 'victory';
        
        -- Calculate plunder (simplified: 10% of resources, max based on units)
        SELECT 
            LEAST(s.wood * 0.1, (p_guards * 5 + p_soldiers * 10 + p_archers * 8 + p_cavalry * 20)),
            LEAST(s.stone * 0.1, (p_guards * 5 + p_soldiers * 10 + p_archers * 8 + p_cavalry * 20)),
            LEAST(s.ore * 0.1, (p_guards * 5 + p_soldiers * 10 + p_archers * 8 + p_cavalry * 20))
        INTO plunder_wood, plunder_stone, plunder_ore
        FROM Settlement s WHERE s.settlementId = p_defender_settlement_id;
        
        -- Transfer plundered resources
        UPDATE Settlement SET 
            wood = GREATEST(0, wood - plunder_wood),
            stone = GREATEST(0, stone - plunder_stone),
            ore = GREATEST(0, ore - plunder_ore)
        WHERE settlementId = p_defender_settlement_id;
        
        UPDATE Settlement SET 
            wood = wood + plunder_wood,
            stone = stone + plunder_stone,
            ore = ore + plunder_ore
        WHERE settlementId = p_attacker_settlement_id;
    END IF;
    
    -- Apply battle losses (simplified: 20% unit loss for attacker, 30% for defender)
    IF battle_result = 'victory' THEN
        -- Attacker losses (20%)
        UPDATE MilitaryUnits SET count = GREATEST(0, FLOOR(count * 0.8))
        WHERE settlementId = p_attacker_settlement_id;
        
        -- Defender losses (30%)
        UPDATE MilitaryUnits SET count = GREATEST(0, FLOOR(count * 0.7))
        WHERE settlementId = p_defender_settlement_id;
    ELSE
        -- Attacker losses (50% for defeat)
        UPDATE MilitaryUnits SET count = GREATEST(0, FLOOR(count * 0.5))
        WHERE settlementId = p_attacker_settlement_id;
        
        -- Defender losses (10% for victory)
        UPDATE MilitaryUnits SET count = GREATEST(0, FLOOR(count * 0.9))
        WHERE settlementId = p_defender_settlement_id;
    END IF;
    
    -- Record battle in history table
    INSERT INTO BattleHistory (attackerSettlementId, defenderSettlementId, result, battleTime)
    VALUES (p_attacker_settlement_id, p_defender_settlement_id, battle_result, NOW());
    
END//

-- Procedure: Execute Trade On Arrival
DROP PROCEDURE IF EXISTS ExecuteTradeOnArrival//
CREATE PROCEDURE ExecuteTradeOnArrival(
    IN p_to_settlement_id INT,
    IN p_wood_amount FLOAT,
    IN p_stone_amount FLOAT,
    IN p_ore_amount FLOAT,
    IN p_gold_amount INT
)
BEGIN
    -- Add resources to destination settlement
    UPDATE Settlement SET 
        wood = wood + p_wood_amount,
        stone = stone + p_stone_amount,
        ore = ore + p_ore_amount
    WHERE settlementId = p_to_settlement_id;
    
    -- Add gold to player if applicable
    IF p_gold_amount > 0 THEN
        UPDATE Spieler p 
        INNER JOIN Settlement s ON p.playerId = s.playerId 
        SET p.gold = p.gold + p_gold_amount
        WHERE s.settlementId = p_to_settlement_id;
    END IF;
END//

-- Main procedure that processes all arrivals
DROP PROCEDURE IF EXISTS ProcessAllArrivals//
CREATE PROCEDURE ProcessAllArrivals()
BEGIN
    -- Process armies
    CALL ProcessArrivedArmies();
    
    -- Process trades  
    CALL ProcessArrivedTrades();
END//

DELIMITER ;
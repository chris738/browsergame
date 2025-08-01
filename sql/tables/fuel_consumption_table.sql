-- Fuel consumption tracking table
-- This table stores fuel consumption records with German formatting support

CREATE TABLE IF NOT EXISTS FuelConsumption (
    id INT AUTO_INCREMENT PRIMARY KEY,
    settlementId INT NOT NULL,
    date DATE NOT NULL,
    fuelType ENUM('Super', 'Super E10', 'Diesel', 'Super Premium') NOT NULL,
    pricePerLiter DECIMAL(5,3) NOT NULL COMMENT 'Price per liter in Euro',
    liters DECIMAL(8,3) NOT NULL COMMENT 'Amount of fuel in liters',
    totalCost DECIMAL(10,2) AS (pricePerLiter * liters) STORED COMMENT 'Calculated total cost',
    displayedConsumption DECIMAL(5,2) NOT NULL COMMENT 'Displayed fuel consumption in L/100km',
    engineRuntime INT NOT NULL COMMENT 'Engine runtime in minutes',
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (settlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE,
    INDEX idx_settlement_date (settlementId, date),
    INDEX idx_fuel_type (fuelType),
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
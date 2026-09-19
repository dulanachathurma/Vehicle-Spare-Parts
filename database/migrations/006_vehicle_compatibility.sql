-- =====================================================================
-- database/migrations/006_vehicle_compatibility.sql
--
-- Vehicle Compatibility / Model Match feature:
-- Adds vehicle_model table and part_compatibility junction table,
-- populated with common Sri Lankan passenger and commercial vehicles
-- (Toyota, Suzuki, Honda, Nissan, Mitsubishi) and mapped to existing
-- spare parts in the catalogue.
-- =====================================================================

USE vspms_db;

CREATE TABLE IF NOT EXISTS vehicle_model (
    vehicleID    INT AUTO_INCREMENT PRIMARY KEY,
    make         VARCHAR(50)  NOT NULL,
    model        VARCHAR(100) NOT NULL,
    chassisCode  VARCHAR(50)  NOT NULL,
    yearRange    VARCHAR(50)  NULL,
    INDEX idx_vehicle_make (make),
    INDEX idx_vehicle_model (model),
    INDEX idx_vehicle_chassis (chassisCode),
    UNIQUE KEY uk_vehicle (make, model, chassisCode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS part_compatibility (
    compatibilityID INT AUTO_INCREMENT PRIMARY KEY,
    partID          INT NOT NULL,
    vehicleID       INT NOT NULL,
    notes           VARCHAR(255) NULL,
    CONSTRAINT fk_compat_part    FOREIGN KEY (partID)    REFERENCES spare_part(partID)    ON DELETE CASCADE,
    CONSTRAINT fk_compat_vehicle FOREIGN KEY (vehicleID) REFERENCES vehicle_model(vehicleID) ON DELETE CASCADE,
    UNIQUE KEY uk_part_vehicle (partID, vehicleID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Seed Popular Sri Lankan Vehicle Models
-- ---------------------------------------------------------------------
INSERT IGNORE INTO vehicle_model (make, model, chassisCode, yearRange) VALUES
-- Toyota
('Toyota', 'Aqua', 'NHP10', '2011–2021'),
('Toyota', 'Axio', 'NKE165 (Hybrid)', '2012–2020'),
('Toyota', 'Axio', 'NZE161 (Petrol)', '2012–2020'),
('Toyota', 'Axio', 'NZE141', '2006–2012'),
('Toyota', 'Vitz', 'KSP130 (1.0L)', '2010–2020'),
('Toyota', 'Vitz', 'NSP130 (1.3L)', '2010–2020'),
('Toyota', 'Vitz', 'SCP90', '2005–2010'),
('Toyota', 'Prius', 'ZVW30', '2009–2015'),
('Toyota', 'Prius', 'ZVW50', '2015–2022'),
('Toyota', 'Premio', 'NZT260 / ZRT260', '2007–2021'),
('Toyota', 'Allion', 'NZT260 / ZRT260', '2007–2021'),
('Toyota', 'Hiace', 'KDH200 / KDH201', '2004–2020'),
('Toyota', 'Corolla', 'NZE121', '2001–2007'),

-- Suzuki
('Suzuki', 'Wagon R', 'MH34S / MH44S', '2012–2017'),
('Suzuki', 'Wagon R', 'MH55S (FZ/FX)', '2017–2023'),
('Suzuki', 'Alto', 'HA36S (Japanese 660cc)', '2014–2021'),
('Suzuki', 'Alto', '800cc (Indian)', '2005–2020'),
('Suzuki', 'Swift', 'ZC83S / ZD83S', '2017–2023'),
('Suzuki', 'Swift', 'ZC72S', '2010–2017'),
('Suzuki', 'Every', 'DA64V / DA64W', '2005–2015'),
('Suzuki', 'Every', 'DA17V / DA17W', '2015–present'),
('Suzuki', 'Spacia', 'MK42S / MK53S', '2013–2022'),
('Suzuki', 'Hustler', 'MR31S / MR41S', '2014–2019'),

-- Honda
('Honda', 'Fit', 'GP5 (Hybrid)', '2013–2020'),
('Honda', 'Fit', 'GK3 (Petrol)', '2013–2020'),
('Honda', 'Fit', 'GP1 (Hybrid)', '2010–2013'),
('Honda', 'Vezel', 'RU3 (Hybrid)', '2013–2021'),
('Honda', 'Grace', 'GM4 (Hybrid)', '2014–2020'),
('Honda', 'Civic', 'FD1 / FD2', '2006–2011'),

-- Nissan
('Nissan', 'Leaf', 'AZE0 (24/30kWh)', '2012–2017'),
('Nissan', 'Leaf', 'ZE1 (40/62kWh)', '2017–present'),
('Nissan', 'Dayz', 'B21W', '2013–2019'),
('Nissan', 'March', 'K13', '2010–2020'),
('Nissan', 'X-Trail', 'T31 / NT31', '2007–2013'),
('Nissan', 'X-Trail', 'T32 / HT32 (Hybrid)', '2013–2020'),

-- Mitsubishi
('Mitsubishi', 'Outlander', 'GG2W (PHEV)', '2013–2021'),
('Mitsubishi', 'Montero / Pajero', 'V98W / V88W', '2006–2020'),
('Mitsubishi', 'L200 / Sportero', 'KB4T', '2006–2015');

-- ---------------------------------------------------------------------
-- Seed Part Compatibility Mappings
-- Maps existing spare parts (partID 1 to 47) to compatible vehicle models
-- ---------------------------------------------------------------------

-- Helper temporary table to seed compatibilities easily
INSERT IGNORE INTO part_compatibility (partID, vehicleID, notes)
SELECT sp.partID, vm.vehicleID, 'Direct OEM fit'
FROM spare_part sp
CROSS JOIN vehicle_model vm
WHERE
    -- Piston Ring Set (Petrol) -> Petrol sedans
    (sp.partID = 1 AND vm.make = 'Toyota' AND vm.model IN ('Axio', 'Premio', 'Allion', 'Corolla'))
    -- Piston Kit (Diesel) -> Hiace, L200
    OR (sp.partID = 2 AND ((vm.make = 'Toyota' AND vm.model = 'Hiace') OR (vm.make = 'Mitsubishi' AND vm.model = 'L200 / Sportero')))
    -- Timing Belt Kit -> Corolla NZE121, Hiace
    OR (sp.partID = 3 AND vm.make = 'Toyota' AND vm.model IN ('Corolla', 'Hiace'))
    -- Timing Chain Set -> Axio, Aqua, Vitz, Premio, Wagon R
    OR (sp.partID = 4 AND ((vm.make = 'Toyota' AND vm.model IN ('Aqua', 'Axio', 'Vitz', 'Premio')) OR (vm.make = 'Suzuki' AND vm.model = 'Wagon R')))
    -- Front Brake Pad Set - Sedan -> Axio, Premio, Allion, Civic
    OR (sp.partID = 5 AND ((vm.make = 'Toyota' AND vm.model IN ('Axio', 'Premio', 'Allion', 'Corolla')) OR (vm.make = 'Honda' AND vm.model IN ('Grace', 'Civic'))))
    -- Rear Brake Pad Set - SUV -> Vezel, X-Trail, Outlander, Montero
    OR (sp.partID = 6 AND ((vm.make = 'Honda' AND vm.model = 'Vezel') OR (vm.make = 'Nissan' AND vm.model = 'X-Trail') OR (vm.make = 'Mitsubishi' AND vm.model IN ('Outlander', 'Montero / Pajero'))))
    -- Front Brake Pad Set - Hatchback -> Aqua, Vitz, Wagon R, Alto, Swift, Fit, Dayz, March
    OR (sp.partID = 7 AND ((vm.make = 'Toyota' AND vm.model IN ('Aqua', 'Vitz')) OR (vm.make = 'Suzuki' AND vm.model IN ('Wagon R', 'Alto', 'Swift', 'Every')) OR (vm.make = 'Honda' AND vm.model = 'Fit') OR (vm.make = 'Nissan' AND vm.model IN ('Dayz', 'March'))))
    -- Ventilated Front Brake Disc Pair -> Axio, Premio, Fit, Vezel, Swift
    OR (sp.partID = 8 AND ((vm.make = 'Toyota' AND vm.model IN ('Axio', 'Premio', 'Allion')) OR (vm.make = 'Honda' AND vm.model IN ('Fit', 'Vezel')) OR (vm.make = 'Suzuki' AND vm.model = 'Swift')))
    -- Rear Brake Drum Pair -> Aqua, Vitz, Wagon R, Alto
    OR (sp.partID = 9 AND ((vm.make = 'Toyota' AND vm.model IN ('Aqua', 'Vitz', 'Corolla')) OR (vm.make = 'Suzuki' AND vm.model IN ('Wagon R', 'Alto', 'Every'))))
    -- Front Shock Absorber Pair -> Axio, Premio, Allion, Civic, Grace
    OR (sp.partID = 10 AND ((vm.make = 'Toyota' AND vm.model IN ('Axio', 'Premio', 'Allion')) OR (vm.make = 'Honda' AND vm.model IN ('Grace', 'Civic'))))
    -- Rear Shock Absorber Pair - SUV -> Vezel, X-Trail, Outlander
    OR (sp.partID = 11 AND ((vm.make = 'Honda' AND vm.model = 'Vezel') OR (vm.make = 'Nissan' AND vm.model = 'X-Trail') OR (vm.make = 'Mitsubishi' AND vm.model = 'Outlander')))
    -- Front Lower Control Arm -> Axio, Aqua, Vitz, Wagon R, Fit
    OR (sp.partID = 12 AND ((vm.make = 'Toyota' AND vm.model IN ('Aqua', 'Axio', 'Vitz')) OR (vm.make = 'Suzuki' AND vm.model IN ('Wagon R', 'Swift')) OR (vm.make = 'Honda' AND vm.model = 'Fit')))
    -- Rear Control Arm Bushing Kit -> Axio, Premio, Fit, Civic
    OR (sp.partID = 13 AND ((vm.make = 'Toyota' AND vm.model IN ('Axio', 'Premio', 'Corolla')) OR (vm.make = 'Honda' AND vm.model IN ('Fit', 'Civic'))))
    -- Battery 12V 45Ah -> Aqua, Axio, Vitz, Wagon R, Alto, Swift, Fit
    OR (sp.partID = 14 AND ((vm.make = 'Toyota' AND vm.model IN ('Aqua', 'Axio', 'Vitz')) OR (vm.make = 'Suzuki' AND vm.model IN ('Wagon R', 'Alto', 'Swift', 'Every', 'Spacia', 'Hustler')) OR (vm.make = 'Honda' AND vm.model IN ('Fit', 'Grace')) OR (vm.make = 'Nissan' AND vm.model IN ('Dayz', 'March'))))
    -- Battery 12V 65Ah -> Premio, Hiace, X-Trail, Outlander, Montero, L200
    OR (sp.partID = 15 AND ((vm.make = 'Toyota' AND vm.model IN ('Premio', 'Allion', 'Hiace')) OR (vm.make = 'Nissan' AND vm.model = 'X-Trail') OR (vm.make = 'Mitsubishi' AND vm.model IN ('Outlander', 'Montero / Pajero', 'L200 / Sportero'))))
    -- Halogen Headlight Bulb H4 -> Allion, Vitz, Wagon R, Alto, Every, March
    OR (sp.partID = 16 AND ((vm.make = 'Toyota' AND vm.model IN ('Vitz', 'Corolla')) OR (vm.make = 'Suzuki' AND vm.model IN ('Wagon R', 'Alto', 'Every')) OR (vm.make = 'Nissan' AND vm.model = 'March')))
    -- Headlight Assembly - Left -> Aqua NHP10, Axio NKE165, Wagon R MH34S
    OR (sp.partID = 17 AND ((vm.make = 'Toyota' AND vm.model IN ('Aqua', 'Axio')) OR (vm.make = 'Suzuki' AND vm.model = 'Wagon R')))
    -- Side Mirror Assembly - Right -> Aqua, Axio, Vitz, Wagon R, Fit
    OR (sp.partID = 18 AND ((vm.make = 'Toyota' AND vm.model IN ('Aqua', 'Axio', 'Vitz')) OR (vm.make = 'Suzuki' AND vm.model = 'Wagon R') OR (vm.make = 'Honda' AND vm.model = 'Fit')))
    -- Mirror Glass Replacement - Left -> Universal fit across hatchbacks & sedans
    OR (sp.partID = 19 AND vm.make IN ('Toyota', 'Suzuki', 'Honda', 'Nissan'))
    -- Front Bumper Cover -> Aqua, Axio, Wagon R, Fit
    OR (sp.partID = 20 AND ((vm.make = 'Toyota' AND vm.model IN ('Aqua', 'Axio')) OR (vm.make = 'Suzuki' AND vm.model = 'Wagon R') OR (vm.make = 'Honda' AND vm.model = 'Fit')))
    -- Rear Bumper Cover -> Aqua, Vitz, Wagon R, Alto
    OR (sp.partID = 21 AND ((vm.make = 'Toyota' AND vm.model IN ('Aqua', 'Vitz')) OR (vm.make = 'Suzuki' AND vm.model IN ('Wagon R', 'Alto'))))
    -- Clutch Kit - 1.6L Manual -> Corolla, Swift, Alto, Every, March
    OR (sp.partID = 22 AND ((vm.make = 'Toyota' AND vm.model = 'Corolla') OR (vm.make = 'Suzuki' AND vm.model IN ('Swift', 'Alto', 'Every')) OR (vm.make = 'Nissan' AND vm.model = 'March')))
    -- Clutch Kit - 2.0L Diesel -> Hiace, L200
    OR (sp.partID = 23 AND ((vm.make = 'Toyota' AND vm.model = 'Hiace') OR (vm.make = 'Mitsubishi' AND vm.model = 'L200 / Sportero')))
    -- Oil Filter - Spin-On -> Axio, Corolla, Wagon R, Alto, Swift, Fit, March
    OR (sp.partID = 24 AND ((vm.make = 'Toyota' AND vm.model IN ('Axio', 'Corolla')) OR (vm.make = 'Suzuki' AND vm.model IN ('Wagon R', 'Alto', 'Swift', 'Every')) OR (vm.make = 'Honda' AND vm.model IN ('Fit', 'Civic')) OR (vm.make = 'Nissan' AND vm.model = 'March')))
    -- Oil Filter - OEM Cartridge -> Aqua, Prius, Premio, Vezel, Outlander
    OR (sp.partID = 25 AND ((vm.make = 'Toyota' AND vm.model IN ('Aqua', 'Prius', 'Premio', 'Allion', 'Vitz')) OR (vm.make = 'Honda' AND vm.model IN ('Vezel', 'Grace')) OR (vm.make = 'Mitsubishi' AND vm.model = 'Outlander')))
    -- Air Filter - Panel Type -> Aqua, Axio, Vitz, Prius, Wagon R, Fit, Grace
    OR (sp.partID = 26 AND ((vm.make = 'Toyota' AND vm.model IN ('Aqua', 'Axio', 'Vitz', 'Prius', 'Premio')) OR (vm.make = 'Suzuki' AND vm.model IN ('Wagon R', 'Swift')) OR (vm.make = 'Honda' AND vm.model IN ('Fit', 'Grace'))))
    -- Air Filter - Cylindrical Type -> Hiace, X-Trail, Outlander, Montero, L200
    OR (sp.partID = 27 AND ((vm.make = 'Toyota' AND vm.model = 'Hiace') OR (vm.make = 'Nissan' AND vm.model = 'X-Trail') OR (vm.make = 'Mitsubishi' AND vm.model IN ('Outlander', 'Montero / Pajero', 'L200 / Sportero'))))
    -- Tyres -> Fits multiple models
    OR (sp.partID IN (28, 29, 30) AND vm.make IN ('Toyota', 'Suzuki', 'Honda', 'Nissan'))
    -- SUV Tyre -> Vezel, X-Trail, Outlander, Montero, L200, Hiace
    OR (sp.partID = 31 AND ((vm.make = 'Honda' AND vm.model = 'Vezel') OR (vm.make = 'Nissan' AND vm.model = 'X-Trail') OR (vm.make = 'Mitsubishi') OR (vm.make = 'Toyota' AND vm.model = 'Hiace')))
    -- Compact car tyre -> Aqua, Vitz, Wagon R, Alto, Fit, Dayz
    OR (sp.partID = 32 AND ((vm.make = 'Toyota' AND vm.model IN ('Aqua', 'Vitz')) OR (vm.make = 'Suzuki' AND vm.model IN ('Wagon R', 'Alto', 'Every')) OR (vm.make = 'Honda' AND vm.model = 'Fit') OR (vm.make = 'Nissan' AND vm.model = 'Dayz')))
    -- Alloy Wheels 16/17 -> Axio, Premio, Prius, Fit, Vezel, Swift, Civic
    OR (sp.partID IN (33, 34) AND ((vm.make = 'Toyota' AND vm.model IN ('Axio', 'Prius', 'Premio', 'Allion')) OR (vm.make = 'Honda' AND vm.model IN ('Fit', 'Vezel', 'Grace', 'Civic')) OR (vm.make = 'Suzuki' AND vm.model = 'Swift')))
    -- Spark Plugs -> Aqua, Axio, Vitz, Prius, Wagon R, Fit, Grace
    OR (sp.partID = 35 AND ((vm.make = 'Toyota' AND vm.model IN ('Aqua', 'Axio', 'Vitz', 'Prius', 'Premio')) OR (vm.make = 'Suzuki' AND vm.model IN ('Wagon R', 'Swift', 'Alto')) OR (vm.make = 'Honda' AND vm.model IN ('Fit', 'Grace'))))
    -- Ignition Coil -> Aqua, Axio, Vitz, Wagon R, Fit
    OR (sp.partID = 36 AND ((vm.make = 'Toyota' AND vm.model IN ('Aqua', 'Axio', 'Vitz', 'Corolla')) OR (vm.make = 'Suzuki' AND vm.model = 'Wagon R') OR (vm.make = 'Honda' AND vm.model = 'Fit')))
    -- Radiator Assembly -> Axio, Aqua, Wagon R, Fit
    OR (sp.partID = 37 AND ((vm.make = 'Toyota' AND vm.model IN ('Aqua', 'Axio')) OR (vm.make = 'Suzuki' AND vm.model = 'Wagon R') OR (vm.make = 'Honda' AND vm.model = 'Fit')))
    -- Fuel Injector -> Axio, Premio, Hiace, L200
    OR (sp.partID = 38 AND ((vm.make = 'Toyota' AND vm.model IN ('Axio', 'Premio', 'Hiace')) OR (vm.make = 'Mitsubishi' AND vm.model = 'L200 / Sportero')))
    -- Brake Master Cylinder -> Axio, Aqua, Wagon R, Fit
    OR (sp.partID = 39 AND ((vm.make = 'Toyota' AND vm.model IN ('Aqua', 'Axio')) OR (vm.make = 'Suzuki' AND vm.model = 'Wagon R') OR (vm.make = 'Honda' AND vm.model = 'Fit')))
    -- ABS Wheel Speed Sensor -> Aqua, Axio, Prius, Wagon R, Fit, Vezel
    OR (sp.partID = 40 AND ((vm.make = 'Toyota' AND vm.model IN ('Aqua', 'Axio', 'Prius')) OR (vm.make = 'Suzuki' AND vm.model = 'Wagon R') OR (vm.make = 'Honda' AND vm.model IN ('Fit', 'Vezel'))))
    -- Power Steering Pump -> Corolla, Hiace, Montero, L200
    OR (sp.partID = 41 AND ((vm.make = 'Toyota' AND vm.model IN ('Corolla', 'Hiace')) OR (vm.make = 'Mitsubishi' AND vm.model IN ('Montero / Pajero', 'L200 / Sportero'))))
    -- Tie Rod End Set -> Aqua, Axio, Vitz, Wagon R, Fit
    OR (sp.partID = 42 AND ((vm.make = 'Toyota' AND vm.model IN ('Aqua', 'Axio', 'Vitz')) OR (vm.make = 'Suzuki' AND vm.model IN ('Wagon R', 'Swift')) OR (vm.make = 'Honda' AND vm.model = 'Fit')))
    -- Alternator 90A -> Axio, Premio, Allion, Corolla, Fit
    OR (sp.partID = 43 AND ((vm.make = 'Toyota' AND vm.model IN ('Axio', 'Premio', 'Allion', 'Corolla')) OR (vm.make = 'Honda' AND vm.model = 'Fit')))
    -- Starter Motor -> Aqua, Axio, Vitz, Wagon R, Fit
    OR (sp.partID = 44 AND ((vm.make = 'Toyota' AND vm.model IN ('Aqua', 'Axio', 'Vitz')) OR (vm.make = 'Suzuki' AND vm.model IN ('Wagon R', 'Alto')) OR (vm.make = 'Honda' AND vm.model = 'Fit')))
    -- Engine Oils 5W-30 & 10W-40 -> Universal across all makes
    OR (sp.partID IN (45, 46) AND vm.make IN ('Toyota', 'Suzuki', 'Honda', 'Nissan', 'Mitsubishi'))
    -- Sensor kit -> Universal workshop diagnostic
    OR (sp.partID = 47 AND vm.make IN ('Toyota', 'Suzuki', 'Honda', 'Nissan', 'Mitsubishi'));

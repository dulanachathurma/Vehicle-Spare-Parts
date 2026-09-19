-- =====================================================================
-- database/migrations/007_expanded_vehicles_and_parts.sql
--
-- 1. Adds 'region' column to vehicle_model to categorize makes into
--    Japanese, European, American, Chinese, Indian, and Korean Vehicles.
-- 2. Seeds extensive vehicle makes, models, and chassis codes.
-- 3. Seeds 25+ new spare parts across multiple categories.
-- 4. Maps the new spare parts to compatible vehicle models.
-- =====================================================================

USE vspms_db;

-- 1. Add region column if it doesn't already exist
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists
FROM information_schema.columns
WHERE table_schema = 'vspms_db'
  AND table_name = 'vehicle_model'
  AND column_name = 'region';

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE vehicle_model ADD COLUMN region VARCHAR(50) NOT NULL DEFAULT \'Japanese Vehicles\' AFTER vehicleID, ADD INDEX idx_vehicle_region (region);',
    'SELECT 1;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update existing makes to Japanese Vehicles
UPDATE vehicle_model SET region = 'Japanese Vehicles' WHERE make IN ('Toyota', 'Suzuki', 'Honda', 'Nissan', 'Mitsubishi');

-- Additional Brands for European, Indian, Korean, Chinese applications
INSERT IGNORE INTO brand (brandName, isAuthorized) VALUES
('Brembo', 1),
('Valeo', 1),
('Mahle', 1),
('Mando', 1),
('Lemforder', 1),
('Tata Genuine', 1),
('Mahindra Genuine', 1);

-- ---------------------------------------------------------------------
-- 2. Seed Expanded Vehicles (Grouped by Region)
-- ---------------------------------------------------------------------
INSERT IGNORE INTO vehicle_model (region, make, model, chassisCode, yearRange) VALUES
-- Japanese Vehicles
('Japanese Vehicles', 'Daihatsu', 'Mira', 'L275 / L285', '2006–2018'),
('Japanese Vehicles', 'Daihatsu', 'Hijet', 'S321V / S331V', '2004–2021'),
('Japanese Vehicles', 'Daihatsu', 'Tanto', 'LA600S / LA610S', '2013–2019'),
('Japanese Vehicles', 'Daihatsu', 'Terios', 'J200 / J210', '2006–2016'),
('Japanese Vehicles', 'Daihatsu', 'Rocky', 'A200S / A210S', '2019–present'),
('Japanese Vehicles', 'Hino', 'Dutro', 'XZU / XKC', '2011–2022'),
('Japanese Vehicles', 'Hino', '300 Series', 'WU300 / XZU300', '2006–2018'),
('Japanese Vehicles', 'Hino', '500 Series', 'FG8J / GH8J', '2008–2020'),
('Japanese Vehicles', 'Isuzu', 'D-Max', 'RT50 (2.5L/3.0L)', '2012–2019'),
('Japanese Vehicles', 'Isuzu', 'D-Max', 'RG01 (1.9L Ddi)', '2020–present'),
('Japanese Vehicles', 'Isuzu', 'Elf', 'NKR / NMR / NPR', '2006–present'),
('Japanese Vehicles', 'Isuzu', 'Forward', 'FRR / FSR', '2007–present'),
('Japanese Vehicles', 'Lexus', 'CT200h', 'ZWA10', '2011–2022'),
('Japanese Vehicles', 'Lexus', 'RX350', 'GGL15 / GYL25', '2009–2022'),
('Japanese Vehicles', 'Lexus', 'NX300h', 'AYZ10 / AYZ15', '2014–2021'),
('Japanese Vehicles', 'Lexus', 'ES300h', 'AVV60 / AXZH10', '2012–present'),
('Japanese Vehicles', 'Mazda', 'Demio / Mazda 2', 'DE3FS / DE5FS', '2007–2014'),
('Japanese Vehicles', 'Mazda', 'Demio / Mazda 2', 'DJ5FS / DJ3FS', '2014–present'),
('Japanese Vehicles', 'Mazda', 'Axela / Mazda 3', 'BL5FW / BLEFW', '2009–2013'),
('Japanese Vehicles', 'Mazda', 'Axela / Mazda 3', 'BM5FP / BM5FS', '2013–2019'),
('Japanese Vehicles', 'Mazda', 'CX-5', 'KE2FW / KEEFW', '2012–2017'),
('Japanese Vehicles', 'Mazda', 'CX-5', 'KF2P / KF5P', '2017–present'),
('Japanese Vehicles', 'Mazda', 'CX-3', 'DK5FW / DK5AW', '2015–present'),
('Japanese Vehicles', 'Mitsubishi Fuso', 'Canter', 'FE71 / FE85', '2002–2011'),
('Japanese Vehicles', 'Mitsubishi Fuso', 'Canter', 'FBA00 / FEA50', '2010–present'),
('Japanese Vehicles', 'Subaru', 'XV / Crosstrek', 'GP7 / GPE', '2012–2017'),
('Japanese Vehicles', 'Subaru', 'XV / Crosstrek', 'GT7 / GTE', '2017–2023'),
('Japanese Vehicles', 'Subaru', 'Forester', 'SJ5 / SJG', '2012–2018'),
('Japanese Vehicles', 'Subaru', 'Forester', 'SK9 / SKE', '2018–present'),
('Japanese Vehicles', 'Subaru', 'Impreza', 'GP2 / GP3', '2011–2016'),
('Japanese Vehicles', 'Toyota', 'Land Cruiser Prado', 'TRJ150 / GDJ150', '2009–2023'),
('Japanese Vehicles', 'Toyota', 'Hilux', 'GUN125 / KUN25', '2005–present'),
('Japanese Vehicles', 'Toyota', 'C-HR', 'NGX50 / ZYX10', '2016–present'),
('Japanese Vehicles', 'Toyota', 'Yaris', 'KSP210 / MXPH10', '2020–present'),
('Japanese Vehicles', 'Suzuki', 'Jimny', 'JB64W / JB74W', '2018–present'),
('Japanese Vehicles', 'Suzuki', 'Celerio', 'AV310', '2014–2021'),
('Japanese Vehicles', 'Nissan', 'Caravan / NV350', 'E26', '2012–present'),
('Japanese Vehicles', 'Honda', 'CR-V', 'RM4 / RW2', '2012–2022'),

-- European Vehicles
('European Vehicles', 'Alfa Romeo', 'Giulia', '952', '2016–present'),
('European Vehicles', 'Alfa Romeo', 'Stelvio', '949', '2016–present'),
('European Vehicles', 'Audi', 'A3', '8V / 8Y', '2012–present'),
('European Vehicles', 'Audi', 'A4', 'B8 / B9', '2008–present'),
('European Vehicles', 'Audi', 'A6', 'C7 / C8', '2011–present'),
('European Vehicles', 'Audi', 'Q2', 'GA', '2016–present'),
('European Vehicles', 'Audi', 'Q3', '8U / F3', '2011–present'),
('European Vehicles', 'Audi', 'Q5', '8R / FY', '2008–present'),
('European Vehicles', 'Audi', 'S8', 'D5', '2018–present'),
('European Vehicles', 'BMW', '1 Series', 'F20 / F40', '2011–present'),
('European Vehicles', 'BMW', '3 Series', 'F30 / G20', '2012–present'),
('European Vehicles', 'BMW', '5 Series', 'F10 / G30', '2010–2023'),
('European Vehicles', 'BMW', 'X1', 'F48 / U11', '2015–present'),
('European Vehicles', 'BMW', 'X3', 'F25 / G01', '2010–present'),
('European Vehicles', 'BMW', 'M2', 'G87', '2023–present'),
('European Vehicles', 'Citroen', 'C3', 'A51 / SX', '2016–present'),
('European Vehicles', 'Citroen', 'C5 Aircross', 'CR3', '2017–present'),
('European Vehicles', 'Fiat', '500', '312', '2007–present'),
('European Vehicles', 'Fiat', 'Punto', '199', '2005–2018'),
('European Vehicles', 'Jaguar', 'XE', 'X760', '2015–present'),
('European Vehicles', 'Jaguar', 'F-Pace', 'X761', '2016–present'),
('European Vehicles', 'Land Rover', 'Defender', 'L663', '2020–present'),
('European Vehicles', 'Land Rover', 'Discovery Sport', 'L550', '2014–present'),
('European Vehicles', 'Land Rover', 'Range Rover Evoque', 'L538 / L551', '2011–present'),
('European Vehicles', 'Mercedes-Benz', 'A-Class', 'W176 / W177', '2012–present'),
('European Vehicles', 'Mercedes-Benz', 'C-Class', 'W204 / W205 / W206', '2007–present'),
('European Vehicles', 'Mercedes-Benz', 'E-Class', 'W212 / W213', '2009–2023'),
('European Vehicles', 'Mercedes-Benz', 'GLA', 'X156 / H247', '2013–present'),
('European Vehicles', 'Mercedes-Benz', 'GLC', 'X253 / X254', '2015–present'),
('European Vehicles', 'Mini', 'Cooper', 'R56 / F56', '2006–present'),
('European Vehicles', 'Mini', 'Countryman', 'R60 / F60', '2010–present'),
('European Vehicles', 'Peugeot', '208', 'A9 / P21', '2012–present'),
('European Vehicles', 'Peugeot', '2008', 'A94 / P24', '2013–present'),
('European Vehicles', 'Peugeot', '3008', 'P84', '2016–present'),
('European Vehicles', 'Porsche', 'Cayenne', '92A / 9YA', '2010–present'),
('European Vehicles', 'Porsche', 'Macan', '95B', '2014–present'),
('European Vehicles', 'Renault', 'Kwid', 'BB', '2015–present'),
('European Vehicles', 'Renault', 'Duster', 'H79', '2010–2020'),
('European Vehicles', 'Skoda', 'Octavia', '5E / NX', '2012–present'),
('European Vehicles', 'Volkswagen', 'Golf', 'Mk6 / Mk7 / Mk8', '2008–present'),
('European Vehicles', 'Volkswagen', 'Polo', '6R / AW', '2009–present'),
('European Vehicles', 'Volkswagen', 'Tiguan', '5N / AD1', '2007–present'),
('European Vehicles', 'Volvo', 'XC40', 'XZ', '2017–present'),
('European Vehicles', 'Volvo', 'XC60', 'DZ / UZ', '2008–present'),
('European Vehicles', 'Volvo', 'XC90', 'L', '2015–present'),

-- American Vehicles
('American Vehicles', 'Ford', 'Ranger', 'T6 / P703', '2011–present'),
('American Vehicles', 'Ford', 'Everest', 'U375 / UB', '2015–present'),
('American Vehicles', 'Ford', 'Focus', 'C346', '2010–2018'),
('American Vehicles', 'Ford', 'Mustang', 'S550', '2015–2023'),
('American Vehicles', 'Chevrolet', 'Cruze', 'J300', '2008–2016'),
('American Vehicles', 'Chevrolet', 'Beat', 'M300', '2010–2017'),
('American Vehicles', 'Jeep', 'Wrangler', 'JK / JL', '2007–present'),
('American Vehicles', 'Jeep', 'Grand Cherokee', 'WK2', '2010–2021'),

-- Chinese Vehicles
('Chinese Vehicles', 'BYD', 'Atto 3', 'Express', '2022–present'),
('Chinese Vehicles', 'BYD', 'Dolphin', 'EA1', '2021–present'),
('Chinese Vehicles', 'BYD', 'Seal', 'EV', '2022–present'),
('Chinese Vehicles', 'Chery', 'Tiggo 4', 'T19', '2017–present'),
('Chinese Vehicles', 'Chery', 'QQ', 'S11', '2003–2015'),
('Chinese Vehicles', 'DFSK', 'Glory 580', 'Pro', '2016–present'),
('Chinese Vehicles', 'DFSK', 'Glory 560', 'SUV', '2017–present'),
('Chinese Vehicles', 'Foton', 'Tunland', 'G7 / G9', '2012–present'),
('Chinese Vehicles', 'Geely', 'Coolray', 'SX11', '2018–present'),
('Chinese Vehicles', 'Geely', 'Panda', 'LC', '2008–2016'),
('Chinese Vehicles', 'Haval', 'H6', 'Gen 2 / Gen 3', '2017–present'),
('Chinese Vehicles', 'Haval', 'Jolion', 'A01', '2020–present'),
('Chinese Vehicles', 'JAC', 'T8', 'Pickup', '2018–present'),
('Chinese Vehicles', 'MG', 'MG ZS', 'EV / Petrol', '2017–present'),
('Chinese Vehicles', 'MG', 'MG HS', 'PHEV / Turbo', '2018–present'),
('Chinese Vehicles', 'MG', 'MG 4', 'EV', '2022–present'),

-- Indian Vehicles
('Indian Vehicles', 'Ashok Leyland', 'Dost', 'Dost+ / Strong', '2011–present'),
('Indian Vehicles', 'Ashok Leyland', 'Comet', 'Gold', '2000–present'),
('Indian Vehicles', 'Bajaj', 'RE 4-Stroke', '205cc / 236cc', '2005–present'),
('Indian Vehicles', 'Bajaj', 'Qute', 'RE60', '2018–present'),
('Indian Vehicles', 'Mahindra', 'Bolero', 'Maxi Truck / Camper', '2000–present'),
('Indian Vehicles', 'Mahindra', 'Scorpio', 'S10 / S11', '2002–2022'),
('Indian Vehicles', 'Mahindra', 'XUV500', 'W6 / W8', '2011–2021'),
('Indian Vehicles', 'Mahindra', 'KUV100', 'K2 / K6', '2016–present'),
('Indian Vehicles', 'Maruti Suzuki', 'Alto 800', 'F8D', '2000–2020'),
('Indian Vehicles', 'Maruti Suzuki', 'Wagon R (Indian)', 'K10B / Stingray', '2010–2018'),
('Indian Vehicles', 'Maruti Suzuki', 'Zen', 'Classic / Estilo', '1998–2013'),
('Indian Vehicles', 'Tata', 'Ace (Dimo Batta)', 'HT / Zip', '2005–present'),
('Indian Vehicles', 'Tata', 'Super Ace', 'Mint', '2009–present'),
('Indian Vehicles', 'Tata', 'Xenon', 'DICOR 2.2L / 3.0L', '2008–2018'),
('Indian Vehicles', 'Tata', 'Indica', 'V2 / Vista', '2002–2015'),
('Indian Vehicles', 'TVS', 'King', '200cc 4-Stroke', '2008–present'),

-- Korean Vehicles
('Korean Vehicles', 'Hyundai', 'Tucson', 'TL / NX4', '2015–present'),
('Korean Vehicles', 'Hyundai', 'Santa Fe', 'DM / TM', '2012–2023'),
('Korean Vehicles', 'Hyundai', 'Grand i10', 'BA / NIOS', '2013–present'),
('Korean Vehicles', 'Hyundai', 'Elantra', 'MD / AD', '2010–2020'),
('Korean Vehicles', 'Hyundai', 'Venue', 'QX', '2019–present'),
('Korean Vehicles', 'Kia', 'Sportage', 'SL / QL / NQ5', '2010–present'),
('Korean Vehicles', 'Kia', 'Sorento', 'XM / UM / MQ4', '2009–present'),
('Korean Vehicles', 'Kia', 'Picanto', 'TA / JA', '2011–present'),
('Korean Vehicles', 'Kia', 'Rio', 'UB / YB', '2011–present'),
('Korean Vehicles', 'Kia', 'Seltos', 'SP2', '2019–present'),
('Korean Vehicles', 'SsangYong', 'Korando', 'C200 / C300', '2011–present'),
('Korean Vehicles', 'SsangYong', 'Tivoli', 'X100', '2015–present');

-- ---------------------------------------------------------------------
-- 3. Seed 25+ New Spare Parts Across Categories (Matching Screenshots)
-- ---------------------------------------------------------------------
INSERT IGNORE INTO spare_part (categoryID, brandID, countryID, adminID, partName, partNumber, description, price, size, stockQty, minStockLevel, imageURL, isActive) VALUES
-- European featured parts from Screenshot 4
(14, 2, 2, 1, 'Audi S8 Stabilizer Link (D5)', '80A411317C', 'Heavy-duty front anti-roll stabilizer sway bar link for Audi S8 D5 and A8.', 12900.00, 'Standard', 8, 2, 'assets/images/parts/suspension-steering.svg', 1),
(16, 2, 2, 1, 'Citroen C5 Aircross Tail Lamp Assembly (CR3)', '9815049380', 'OEM rear right tail lamp assembly with integrated LED lighting for Citroen C5 Aircross.', 34500.00, 'Right Side', 4, 1, 'assets/images/parts/electrical-lighting.svg', 1),
(1,  2, 2, 1, 'BMW M2 Radiator Fan (G87)', '17428655078', 'High-performance engine auxiliary radiator cooling fan assembly for BMW M2 G87 and M3/M4 G80.', 44800.00, '400W', 3, 1, 'assets/images/parts/engine-parts.svg', 1),
(21, 2, 2, 1, 'Audi S8 Cabin Filter (D5)', '4M0819439B', 'Activated carbon cabin pollen air filter for Audi S8, A8, and Q7.', 4600.00, 'Standard', 15, 3, 'assets/images/parts/filters-fluids.svg', 1),
(12, 2, 2, 1, 'Mercedes-Benz C200 Front Brake Rotor (W205)', 'A0004213012', 'Perforated high-carbon front brake disc pair for Mercedes-Benz C-Class W205.', 28500.00, '295mm', 6, 2, 'assets/images/parts/brake-system.svg', 1),
(13, 7, 1, 1, 'Peugeot 3008 Front Shock Absorber (P84)', '9818844880', 'KYB Excel-G gas-pressurized front shock absorber for Peugeot 3008 P84 and 5008.', 32000.00, 'Front Left/Right', 8, 2, 'assets/images/parts/suspension-steering.svg', 1),

-- Japanese headlight & lighting items from Screenshot 5
(16, 1, 1, 1, 'Aqua Headlights 25000/=', 'HL-TOY-AQ-25K', 'Complete halogen headlight assembly for Toyota Aqua NHP10 (Left or Right). Clear polycarbonate lens.', 25000.00, 'Pair', 10, 2, 'assets/images/parts/electrical-lighting.svg', 1),
(16, 1, 1, 1, 'Axio 161 Headlight 40000/=', 'HL-TOY-AX161-40K', 'Original specification projector headlight assembly for Toyota Corolla Axio NZE161.', 40000.00, 'Left Side', 7, 2, 'assets/images/parts/electrical-lighting.svg', 1),
(16, 1, 1, 1, 'Axio 165 Headlights 195000/=', 'HL-TOY-AX165-195K', 'Genuine OEM LED dual-beam headlight pair for Toyota Axio Hybrid NKE165 Facelift.', 195000.00, 'Pair (LED)', 4, 1, 'assets/images/parts/electrical-lighting.svg', 1),
(16, 1, 1, 1, 'Allion Headlights 85000/=', 'HL-TOY-AL260-85K', 'Toyota Allion NZT260 / ZRT260 HID projector headlight assembly pair, Japanese import grade.', 85000.00, 'Pair (HID)', 5, 1, 'assets/images/parts/electrical-lighting.svg', 1),
(16, 1, 1, 1, 'Aqua xubran headlights 60000/=', 'HL-TOY-AQ-XURB', 'Toyota Aqua X-Urban crossover edition dark smoked headlight assembly pair.', 60000.00, 'Pair (Smoked)', 3, 1, 'assets/images/parts/electrical-lighting.svg', 1),
(16, 1, 1, 1, 'Aqua Tail light 25000/=', 'TL-TOY-AQ-25K', 'Rear combination LED tail lamp assembly for Toyota Aqua NHP10.', 25000.00, 'Right Side', 6, 2, 'assets/images/parts/electrical-lighting.svg', 1),
(16, 1, 1, 1, 'Axio 165 Headlight 200000/=', 'HL-TOY-AX165-200K', 'Toyota Corolla Axio NKE165 brand new OEM complete LED headlight set with control ballasts.', 200000.00, 'Pair Set', 2, 1, 'assets/images/parts/electrical-lighting.svg', 1),

-- Indian, Chinese, Korean commercial and passenger vehicle parts
(19, 9, 1, 1, 'Tata Ace Clutch Plate & Pressure Plate Kit', '270425000101', 'Heavy-duty 160mm clutch disc, pressure plate, and release bearing kit for Tata Ace (Dimo Batta).', 14500.00, '160mm', 14, 3, 'assets/images/parts/transmission-clutch.svg', 1),
(14, 2, 5, 1, 'Mahindra Bolero Leaf Spring Bush Set', '0304CA0020N', 'Polyurethane heavy load suspension leaf spring shackle bush set for Mahindra Bolero Maxi Truck.', 6800.00, 'Set of 12', 20, 5, 'assets/images/parts/suspension-steering.svg', 1),
(20, 3, 5, 1, 'Ashok Leyland Dost Oil Filter Cartridge', 'B4511005', 'High-efficiency spin-on lube oil filter for Ashok Leyland Dost and Partner light trucks.', 1850.00, 'Standard', 30, 8, 'assets/images/parts/filters-fluids.svg', 1),
(11, 2, 5, 1, 'Bajaj RE 3-Wheeler Brake Shoe Set', 'BA-24151044', 'Asbestos-free front and rear drum brake shoe set for Bajaj RE 4-Stroke three-wheelers.', 2900.00, 'Full Set', 25, 6, 'assets/images/parts/brake-system.svg', 1),
(1,  3, 4, 1, 'Hyundai Tucson Fuel Pump Assembly (NX4)', '31110-N7000', 'In-tank electric high-pressure fuel pump assembly with sending unit for Hyundai Tucson NX4 and Kia Sportage.', 38000.00, 'Complete Unit', 4, 1, 'assets/images/parts/engine-parts.svg', 1),
(1,  8, 1, 1, 'Kia Sportage Water Pump (QL)', '25100-2F000', 'Aisin high-flow engine coolant water pump for Kia Sportage QL 2.0 CRDi and Hyundai Tucson.', 16500.00, 'Standard', 6, 2, 'assets/images/parts/engine-parts.svg', 1),
(21, 2, 4, 1, 'BYD Atto 3 Cabin Air Filter', 'BYD-1025804', 'HEPA CN95 anti-microbial cabin air purifier filter for BYD Atto 3 and Dolphin EV.', 5200.00, 'CN95 Grade', 16, 4, 'assets/images/parts/filters-fluids.svg', 1),
(18, 1, 4, 1, 'MG ZS Front Bumper Grille', '10368142', 'Black diamond patterned honeycomb front radiator bumper grille for MG ZS and ZS EV.', 22000.00, 'Front Grille', 5, 1, 'assets/images/parts/body-exterior.svg', 1),
(11, 2, 2, 1, 'Ford Ranger T6 Front Brake Pad Set', 'AB31-2001-AA', 'Bosch severe-duty semi-metallic brake pad set for Ford Ranger T6 and Everest 2.2L / 3.2L.', 18500.00, 'Front Set', 9, 2, 'assets/images/parts/brake-system.svg', 1),
(14, 7, 2, 1, 'Land Rover Discovery Sport Lower Ball Joint', 'LR059264', 'Front lower suspension control arm ball joint for Land Rover Discovery Sport and Range Rover Evoque.', 21000.00, 'Heavy Duty', 6, 2, 'assets/images/parts/suspension-steering.svg', 1),
(13, 7, 1, 1, 'Toyota Prado 150 Front Shock Absorber Pair', '48510-69415', 'KYB Gas-A-Just heavy-duty offroad front shock absorbers for Land Cruiser Prado 150 and Hilux.', 68000.00, 'Pair', 5, 1, 'assets/images/parts/suspension-steering.svg', 1),
(21, 3, 1, 1, 'Suzuki Jimny JB74 Air Filter Element', '13780-77R00', 'Original Denso washable pleated panel air filter element for Suzuki Jimny 1.5L JB74W.', 4800.00, 'Panel Type', 12, 3, 'assets/images/parts/filters-fluids.svg', 1),
(7,  1, 1, 1, 'Honda Vezel RU3 Dual Clutch Fluid (4L)', '08260-99964', 'Genuine Honda Ultra DW-1 DCT dual clutch automatic transmission fluid, 4-litre can.', 24000.00, '4 Litre Can', 10, 2, 'assets/images/parts/filters-fluids.svg', 1);

-- ---------------------------------------------------------------------
-- 4. Map New Spare Parts to Compatible Vehicles in part_compatibility
-- ---------------------------------------------------------------------
INSERT IGNORE INTO part_compatibility (partID, vehicleID, notes)
SELECT sp.partID, vm.vehicleID, 'Direct OEM fit'
FROM spare_part sp
CROSS JOIN vehicle_model vm
WHERE
    -- Audi S8 Stabilizer Link (D5)
    (sp.partName LIKE '%Audi S8 Stabilizer Link%' AND vm.make = 'Audi' AND vm.model IN ('S8', 'A8', 'A6'))
    -- Citroen C5 Aircross Tail Lamp
    OR (sp.partName LIKE '%Citroen C5 Aircross Tail Lamp%' AND vm.make = 'Citroen' AND vm.model = 'C5 Aircross')
    -- BMW M2 Radiator Fan
    OR (sp.partName LIKE '%BMW M2 Radiator Fan%' AND vm.make = 'BMW' AND vm.model IN ('M2', '3 Series', '1 Series'))
    -- Audi S8 Cabin Filter
    OR (sp.partName LIKE '%Audi S8 Cabin Filter%' AND vm.make = 'Audi' AND vm.model IN ('S8', 'A8', 'Q7', 'A4'))
    -- Mercedes-Benz C200 Front Brake Rotor
    OR (sp.partName LIKE '%Mercedes-Benz C200 Front Brake Rotor%' AND vm.make = 'Mercedes-Benz' AND vm.model IN ('C-Class', 'E-Class', 'GLC'))
    -- Peugeot 3008 Front Shock Absorber
    OR (sp.partName LIKE '%Peugeot 3008 Front Shock Absorber%' AND vm.make = 'Peugeot' AND vm.model IN ('3008', '5008', '2008'))
    -- Aqua Headlights 25000/=
    OR (sp.partName LIKE '%Aqua Headlights 25000%' AND vm.make = 'Toyota' AND vm.model = 'Aqua')
    -- Axio 161 Headlight 40000/=
    OR (sp.partName LIKE '%Axio 161 Headlight 40000%' AND vm.make = 'Toyota' AND vm.model = 'Axio' AND vm.chassisCode LIKE '%NZE161%')
    -- Axio 165 Headlights 195000/=
    OR (sp.partName LIKE '%Axio 165 Headlights 195000%' AND vm.make = 'Toyota' AND vm.model = 'Axio' AND vm.chassisCode LIKE '%NKE165%')
    -- Allion Headlights 85000/=
    OR (sp.partName LIKE '%Allion Headlights 85000%' AND vm.make = 'Toyota' AND vm.model = 'Allion')
    -- Aqua xubran headlights 60000/=
    OR (sp.partName LIKE '%Aqua xubran headlights%' AND vm.make = 'Toyota' AND vm.model = 'Aqua')
    -- Aqua Tail light 25000/=
    OR (sp.partName LIKE '%Aqua Tail light%' AND vm.make = 'Toyota' AND vm.model = 'Aqua')
    -- Axio 165 Headlight 200000/=
    OR (sp.partName LIKE '%Axio 165 Headlight 200000%' AND vm.make = 'Toyota' AND vm.model = 'Axio' AND vm.chassisCode LIKE '%NKE165%')
    -- Tata Ace Clutch Kit
    OR (sp.partName LIKE '%Tata Ace Clutch Plate%' AND vm.make = 'Tata' AND vm.model = 'Ace (Dimo Batta)')
    -- Mahindra Bolero Leaf Spring Bush Set
    OR (sp.partName LIKE '%Mahindra Bolero Leaf Spring%' AND vm.make = 'Mahindra' AND vm.model IN ('Bolero', 'Scorpio'))
    -- Ashok Leyland Dost Oil Filter
    OR (sp.partName LIKE '%Ashok Leyland Dost Oil Filter%' AND vm.make = 'Ashok Leyland' AND vm.model = 'Dost')
    -- Bajaj RE 3-Wheeler Brake Shoe
    OR (sp.partName LIKE '%Bajaj RE 3-Wheeler Brake Shoe%' AND vm.make = 'Bajaj' AND vm.model = 'RE 4-Stroke')
    -- Hyundai Tucson Fuel Pump
    OR (sp.partName LIKE '%Hyundai Tucson Fuel Pump%' AND ((vm.make = 'Hyundai' AND vm.model = 'Tucson') OR (vm.make = 'Kia' AND vm.model = 'Sportage')))
    -- Kia Sportage Water Pump
    OR (sp.partName LIKE '%Kia Sportage Water Pump%' AND ((vm.make = 'Kia' AND vm.model = 'Sportage') OR (vm.make = 'Hyundai' AND vm.model = 'Tucson')))
    -- BYD Atto 3 Cabin Air Filter
    OR (sp.partName LIKE '%BYD Atto 3 Cabin Air Filter%' AND vm.make = 'BYD' AND vm.model IN ('Atto 3', 'Dolphin'))
    -- MG ZS Front Bumper Grille
    OR (sp.partName LIKE '%MG ZS Front Bumper Grille%' AND vm.make = 'MG' AND vm.model = 'MG ZS')
    -- Ford Ranger T6 Front Brake Pad
    OR (sp.partName LIKE '%Ford Ranger T6 Front Brake Pad%' AND vm.make = 'Ford' AND vm.model IN ('Ranger', 'Everest'))
    -- Land Rover Discovery Sport Lower Ball Joint
    OR (sp.partName LIKE '%Land Rover Discovery Sport Lower Ball Joint%' AND vm.make = 'Land Rover' AND vm.model IN ('Discovery Sport', 'Range Rover Evoque'))
    -- Toyota Prado 150 Front Shock Absorber
    OR (sp.partName LIKE '%Toyota Prado 150 Front Shock Absorber%' AND vm.make = 'Toyota' AND vm.model IN ('Land Cruiser Prado', 'Hilux'))
    -- Suzuki Jimny JB74 Air Filter
    OR (sp.partName LIKE '%Suzuki Jimny JB74 Air Filter%' AND vm.make = 'Suzuki' AND vm.model = 'Jimny')
    -- Honda Vezel Dual Clutch Fluid
    OR (sp.partName LIKE '%Honda Vezel RU3 Dual Clutch Fluid%' AND vm.make = 'Honda' AND vm.model IN ('Vezel', 'Fit', 'Grace'));

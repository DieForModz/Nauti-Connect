-- Nauti-Connect Maritime Platform Database
-- Database: maritime_db

CREATE DATABASE IF NOT EXISTS maritime_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE maritime_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    boat_type VARCHAR(100),
    location_lat DECIMAL(10,8),
    location_lng DECIMAL(11,8),
    profile_img VARCHAR(255),
    bio TEXT,
    reputation_points INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Parts listings
CREATE TABLE IF NOT EXISTS parts_listings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2),
    `condition` ENUM('new','like_new','good','fair','poor') NOT NULL,
    category VARCHAR(100),
    images_json JSON,
    status ENUM('active','sold','wanted') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Chart shares
CREATE TABLE IF NOT EXISTS chart_shares (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    region_name VARCHAR(100),
    chart_file VARCHAR(255),
    coordinates_json JSON,
    description TEXT,
    download_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Anchorages
CREATE TABLE IF NOT EXISTS anchorages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    lat DECIMAL(10,8) NOT NULL,
    lng DECIMAL(11,8) NOT NULL,
    depth DECIMAL(5,1),
    holding_quality ENUM('excellent','good','fair','poor'),
    protection_rating TINYINT(1),
    review_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Sightings (wildlife, debris, derelict craft, etc.)
CREATE TABLE IF NOT EXISTS sightings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    sighting_type ENUM('orca','seal','dolphin','whale','other','debris','derelict_craft') NOT NULL,
    lat DECIMAL(10,8) NOT NULL,
    lng DECIMAL(11,8) NOT NULL,
    sighting_time DATETIME NOT NULL,
    notes TEXT,
    image VARCHAR(255),
    verified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Sighting notifications for nearby users
CREATE TABLE IF NOT EXISTS sighting_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    sighting_id INT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (sighting_id) REFERENCES sightings(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_sighting (user_id, sighting_id)
) ENGINE=InnoDB;

-- Boat listings
CREATE TABLE IF NOT EXISTS boat_listings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    type VARCHAR(100),
    length DECIMAL(5,1),
    year YEAR,
    price DECIMAL(12,2),
    description TEXT,
    specs_json JSON,
    images_json JSON,
    status ENUM('active','sold') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Build logs
CREATE TABLE IF NOT EXISTS build_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    boat_name VARCHAR(255),
    description TEXT,
    progress_percent TINYINT DEFAULT 0,
    cover_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Build log entries
CREATE TABLE IF NOT EXISTS build_log_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    log_id INT NOT NULL,
    entry_date DATE,
    content TEXT,
    images_json JSON,
    FOREIGN KEY (log_id) REFERENCES build_logs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Maintenance guides
CREATE TABLE IF NOT EXISTS maintenance_guides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    category ENUM('engine','electrical','hull','sails','other') NOT NULL,
    difficulty_level ENUM('beginner','intermediate','advanced') NOT NULL,
    tools_needed TEXT,
    steps_json JSON,
    video_url VARCHAR(500),
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FULLTEXT(title, tools_needed)
) ENGINE=InnoDB;

-- AI conversations
CREATE TABLE IF NOT EXISTS ai_conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    thread_title VARCHAR(255),
    messages_json JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Messages
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    listing_type VARCHAR(50),
    listing_id INT,
    content TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_status TINYINT DEFAULT 0,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Rate limits
CREATE TABLE IF NOT EXISTS rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action_type VARCHAR(100) NOT NULL,
    last_action TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- Seed Data: 20 Maintenance Guides
-- ============================================================

INSERT INTO maintenance_guides (title, category, difficulty_level, tools_needed, steps_json, video_url) VALUES

('Engine Winterization',
 'engine', 'intermediate',
 'Antifreeze, fuel stabilizer, fogging oil, socket set, drain plug wrench, funnel, shop rags, manual',
 JSON_ARRAY(
   'Change the engine oil and filter while the engine is warm to remove contaminants.',
   'Run fuel stabilizer through the fuel system by adding it to the tank and running the engine for 10 minutes.',
   'Flush the cooling system with clean fresh water by running engine with flushing muffs.',
   'Add marine antifreeze to the cooling system: disconnect the raw water intake hose and run the engine drawing antifreeze until it exits the exhaust.',
   'Spray fogging oil into each cylinder via the spark plug holes to prevent corrosion.',
   'Replace spark plugs if needed; apply a small amount of anti-seize compound.',
   'Disconnect the battery, clean terminals, and store in a cool dry place on a trickle charger.',
   'Drain the bilge completely and leave the drain plug out for winter storage.',
   'Lubricate all throttle and shift cables with marine grease.',
   'Cover the engine with a breathable engine cover and store in a dry location.'
 ),
 NULL),

('Fiberglass Hull Repair',
 'hull', 'intermediate',
 'Fiberglass mat, epoxy resin, hardener, mixing cups, brushes, 80/120/220 grit sandpaper, orbital sander, acetone, gelcoat, wax, gloves, respirator',
 JSON_ARRAY(
   'Clean the damaged area with acetone and allow to dry completely.',
   'Use a grinder or sandpaper to feather out the damaged area at a 12:1 slope ratio.',
   'Cut fiberglass mat pieces slightly larger than the damaged area in graduated sizes.',
   'Mix epoxy resin per manufacturer instructions and wet out the smallest mat piece first.',
   'Apply layers of fiberglass mat, working from smallest to largest, wetting each layer.',
   'Allow to cure for 24 hours, then sand smooth starting with 80-grit.',
   'Apply gelcoat matched to hull color using a brush or roller.',
   'Wet sand with 220-grit when gelcoat is firm but not fully cured.',
   'Buff with 400, 600, then 800-grit wet sandpaper until smooth.',
   'Apply marine wax and polish to a shine.'
 ),
 NULL),

('Rigging Inspection',
 'sails', 'beginner',
 'Binoculars, rigging knife, inspection mirror, penetrating oil, rigging tape, notepad, marina ladder or bosun chair',
 JSON_ARRAY(
   'Start at deck level: inspect turnbuckles for cracking, corrosion, and cotter pins.',
   'Check chainplates for signs of rust staining on the deck or bulkhead below.',
   'Inspect lower shroud attachment points for cracking or corrosion.',
   'Using binoculars, inspect swage fittings on standing rigging for "meat hooks" (broken wire strands).',
   'Check masthead fitting, sheaves, and VHF antenna with binoculars.',
   'Inspect running rigging for chafe, stiffness, and UV degradation.',
   'Squeeze each wire strand along the length looking for broken strands.',
   'Check all clevis pins for wear and proper cotter pin installation.',
   'Inspect spreader boots and spreader end fittings for cracks.',
   'Document any issues and schedule immediate replacement for anything in doubt.'
 ),
 NULL),

('Marine Electrical Troubleshooting',
 'electrical', 'intermediate',
 'Multimeter, wire strippers, crimping tool, marine wire, heat shrink connectors, electrical tape, fuse assortment, terminal block, dielectric grease, wiring diagram',
 JSON_ARRAY(
   'Start with the battery: check voltage (12.6V fully charged) and terminal connections for corrosion.',
   'Clean battery terminals with baking soda solution and wire brush if corroded.',
   'Check the main fuse panel for blown fuses and replace with correct amperage.',
   'Use multimeter in DC voltage mode to test voltage at each circuit breaker.',
   'Trace circuits with the multimeter: measure voltage drop across connections (should be under 0.3V).',
   'Inspect all wire runs for chafe, especially where wires pass through bulkheads.',
   'Check bilge pump float switch by manually triggering the pump.',
   'Test navigation lights by cycling through each one and inspecting bulbs.',
   'Apply dielectric grease to all electrical connections to prevent future corrosion.',
   'Document any repairs made and update the wiring diagram if changes were made.'
 ),
 NULL),

('Engine Oil Change',
 'engine', 'beginner',
 'Correct grade engine oil, oil filter wrench, oil extractor pump or drain pan, funnel, rags, gloves, oil absorbent pads',
 JSON_ARRAY(
   'Run the engine for 5 minutes to warm the oil, which helps it drain more completely.',
   'Shut down the engine and let it cool for 15 minutes so you can handle parts safely.',
   'If using a drain plug: place absorbent pads below, remove drain plug, and drain fully into a pan.',
   'If using an oil extractor pump: insert the extraction tube down the dipstick tube and pump out all oil.',
   'Remove the old oil filter using an oil filter wrench; have rags ready for drips.',
   'Apply a thin film of fresh oil to the new filter gasket before installation.',
   'Install the new filter hand tight, then a quarter-turn more.',
   'Replace drain plug with new washer if applicable.',
   'Add the correct amount and grade of oil per the owner manual.',
   'Run engine for 2 minutes, check for leaks, shut down and recheck oil level on dipstick.'
 ),
 NULL),

('Raw Water Impeller Replacement',
 'engine', 'intermediate',
 'Replacement impeller kit, screwdrivers, pliers, impeller puller tool, petroleum jelly, bucket, rags, hose clamp screwdriver',
 JSON_ARRAY(
   'Close the raw water seacock before starting any work.',
   'Locate the raw water pump, usually mounted on the engine block.',
   'Place a rag and bucket under the pump to catch residual water.',
   'Remove the pump cover plate screws; note the number and position.',
   'Lift off the cover plate carefully and inspect the O-ring or gasket.',
   'Use impeller puller tool or two screwdrivers to gently pry out the old impeller.',
   'Inspect the pump housing and cover plate for scoring or wear.',
   'Apply petroleum jelly to the new impeller vanes and housing.',
   'Install new impeller with vanes bent in the correct rotation direction.',
   'Reinstall cover with new O-ring/gasket, open seacock, run engine briefly and check for leaks.'
 ),
 NULL),

('Antifouling Bottom Paint Application',
 'hull', 'intermediate',
 'Antifouling paint, roller covers, brushes, sandpaper 80/120 grit, tack cloth, masking tape, waterline tape, paint tray, respirator, tyvek suit, gloves, goggles',
 JSON_ARRAY(
   'Haul the boat and pressure wash the hull to remove marine growth.',
   'Allow hull to dry for at least 24-48 hours before painting.',
   'Sand the existing antifouling with 80-grit to scuff and remove any loose paint.',
   'Sand any bare fiberglass spots to 120-grit and apply barrier coat primer.',
   'Apply masking tape along the waterline for a clean edge.',
   'Stir antifouling paint thoroughly; do not shake as it introduces air bubbles.',
   'Apply first coat with a short-nap roller, cutting in at the waterline and keel with a brush.',
   'Allow to dry per manufacturer instructions (usually 2-4 hours).',
   'Apply second coat in the opposite direction for even coverage.',
   'Remove masking tape while paint is still slightly tacky; launch within the time window specified by the paint manufacturer.'
 ),
 NULL),

('Sail Repair - Rips and Tears',
 'sails', 'beginner',
 'Sail repair tape, adhesive sail cloth patches, needle, waxed thread, sailmakers palm, seam ripper, scissors, measuring tape',
 JSON_ARRAY(
   'Dry the damaged area completely before attempting any repair.',
   'For small tears under 6 inches: apply sail repair tape to both sides of the tear.',
   'Press firmly for 2 minutes and allow the adhesive to cure for 24 hours before sailing.',
   'For larger tears: cut a patch from matching sail cloth 2 inches larger on all sides.',
   'Round the corners of the patch to prevent peeling.',
   'Apply adhesive to both the patch and the sail area; allow to become tacky.',
   'Press patch firmly onto sail and smooth out air bubbles from center outward.',
   'For permanent repairs, hand stitch around the patch perimeter with waxed thread using a sailmakers palm.',
   'Use a zigzag or herringbone stitch pattern for maximum strength.',
   'Have a sailmaker professionally inspect and repair significant damage or worn seams.'
 ),
 NULL),

('Bilge Pump Maintenance',
 'hull', 'beginner',
 'Screwdriver, bilge pump rebuild kit, clean water, rags, multimeter, zip ties, bilge cleaner',
 JSON_ARRAY(
   'Locate all bilge pumps on board: automatic submersible and manual backup.',
   'Clean the bilge area with bilge cleaner to remove oily residue that can clog pumps.',
   'Remove the bilge pump from its mount by unscrewing mounting screws.',
   'Inspect the strainer screen and clean any debris.',
   'Test the automatic float switch by lifting it manually; the pump should run.',
   'Check the pump impeller by disassembling per manufacturer instructions.',
   'Test pump amperage draw with multimeter; high draw indicates worn impeller.',
   'Replace any worn parts using rebuild kit.',
   'Check discharge hose for kinks, cracks, and that it is looped above the waterline.',
   'Reinstall and test with fresh water; verify auto-switch activates at correct level.'
 ),
 NULL),

('Zinc Anodes Replacement',
 'hull', 'beginner',
 'Correct zincs for your boat (shaft, rudder, hull), socket set, wrench, wire brush, anti-seize compound, zinc testing meter (optional)',
 JSON_ARRAY(
   'Inspect existing zincs during haulout or while diving; replace when 50% consumed.',
   'Wire brush the mounting surface to bare metal for good electrical contact.',
   'Remove old zinc anode mounting bolts; apply anti-seize to new bolts.',
   'Install new zinc anode ensuring metal-to-metal contact with the substrate.',
   'Check shaft zinc: remove set screws, slide off old zinc, clean shaft, install new zinc.',
   'Check rudder zinc and replace using same process.',
   'Inspect hull zincs and replace any that are more than half depleted.',
   'For boats in fresh water use magnesium anodes; in salt water use zinc; in mixed use aluminum.',
   'Check zinc bonding wire connections: they must be clean and tight.',
   'Record installation date; typically inspect every 6-12 months depending on conditions.'
 ),
 NULL),

('Through-Hull Fitting Inspection',
 'hull', 'advanced',
 'Screwdriver, wrench, seacock grease, wire brush, inspection mirror, flashlight, underwater hull paint, underwater sealant',
 JSON_ARRAY(
   'Close all seacocks and visually inspect the exterior through-hull fittings during haulout.',
   'Check bronze fittings for dezincification (pinkish color indicates zinc loss).',
   'Test each seacock operation: it should open and close smoothly.',
   'If seacock is stiff, disassemble the tapered plug, clean with wire brush, and repack with grease.',
   'Inspect the flange bedding compound for deterioration or cracking.',
   'From inside the boat, check the backing plate for signs of movement or water intrusion.',
   'Replace any through-hull fitting that shows pitting, cracks, or dezincification.',
   'Apply antifouling paint around through-hulls to prevent growth blockage.',
   'Label each seacock with what it serves (engine raw water, cockpit drain, etc.).',
   'Practice closing all seacocks blindfolded in preparation for emergency use.'
 ),
 NULL),

('Propeller Shaft Alignment',
 'engine', 'advanced',
 'Feeler gauges, dial indicator, flexible coupling tool, shaft alignment tool, wrenches, jack stands, rubber mallet',
 JSON_ARRAY(
   'Haul the boat or work with boat in the water (in-water alignment is more accurate).',
   'Remove the propeller and inspect the shaft for straightness using a dial indicator.',
   'Check cutless bearing for wear by moving shaft up and down; more than 1/16" movement requires replacement.',
   'Open the access panel to the shaft coupling and engine mounts.',
   'Measure the gap between the shaft coupling halves using feeler gauges at 12, 3, 6, and 9 o clock positions.',
   'All measurements should be within 0.003 inches (0.08mm) of each other.',
   'Adjust engine mount height using the adjustment nuts to achieve parallel alignment.',
   'Also check for angular alignment by comparing gap measurements.',
   'Recheck alignment after any engine mount adjustments; repeat until within spec.',
   'Torque all coupling bolts to manufacturer specification and recheck after first sea trial.'
 ),
 NULL),

('Marine Toilet (Head) Maintenance',
 'hull', 'intermediate',
 'Head rebuild kit, Teflon tape, wrench, screwdriver, gloves, bucket, Raritan cleaner or white vinegar, joker valve',
 JSON_ARRAY(
   'Pour a cup of white vinegar into the bowl and pump through to dissolve mineral deposits.',
   'Close the intake and discharge seacocks before disassembly.',
   'Remove the pump cover and inspect the joker valve for cracking or sticktion.',
   'Replace joker valve if it does not spring back to shape when squeezed.',
   'Inspect the piston O-rings and seals; replace from rebuild kit if hardened or cracked.',
   'Check intake and discharge hoses for odor permeation (hoses should be replaced every 5-7 years).',
   'Inspect the macerator (if equipped) for blockages; clean impeller.',
   'Reassemble with new O-rings lubricated with waterproof grease.',
   'Open seacocks and test pump operation.',
   'Remind all crew never to flush non-marine-grade toilet paper and keep a log of rebuild dates.'
 ),
 NULL),

('Anchor Chain Maintenance',
 'hull', 'beginner',
 'Wire brush, chain wash bucket, anchor chain galvanizing paint or cold galvanize spray, chain marking paint, shackle inspection tool, swivel, measuring tape',
 JSON_ARRAY(
   'Flake the anchor chain out on the dock and wash thoroughly with fresh water.',
   'Inspect each link for elongation: links stretched more than 10% need replacement.',
   'Check for corrosion pitting; light rust can be wire brushed and treated with cold galvanize.',
   'Inspect the anchor swivel for wear and free rotation.',
   'Check the connecting shackle between anchor and chain: replace if pin is worn.',
   'Mark the chain at 25ft, 50ft, 75ft, and 100ft intervals with colored paint or cable ties.',
   'Inspect the bitter end attachment in the chain locker; ensure it is secured but can be released in emergency.',
   'Oil chain with a water-displacing spray before stowing to reduce corrosion.',
   'Clean chain locker with fresh water; ensure drain is clear.',
   'Keep a log of anchor deployments and inspect before any passage in remote areas.'
 ),
 NULL),

('GPS/Chartplotter Calibration and Care',
 'electrical', 'beginner',
 'Soft cloth, screen cleaner, firmware update (USB or WiFi), owner manual, test waypoints',
 JSON_ARRAY(
   'Check manufacturer website for firmware updates and install per their instructions.',
   'Clean the screen with approved screen cleaner and soft microfiber cloth.',
   'Verify chart card or internal charts are up to date (annual updates recommended).',
   'Check GPS antenna connections: clean with contact cleaner if corroded.',
   'Test waypoint entry by creating a test waypoint at your current location.',
   'Verify MOB (Man Overboard) button function by testing in a safe location.',
   'Confirm the unit displays correct time and position compared to phone GPS.',
   'Check AIS integration if equipped: verify targets are showing correctly.',
   'Test depth sounder calibration against a known depth measurement.',
   'Back up all waypoints, routes, and tracks to USB drive or cloud service.'
 ),
 NULL),

('VHF Radio Check and Maintenance',
 'electrical', 'beginner',
 'Screwdriver, multimeter, antenna connectors, coax cable tester, DSC MMSI registration, manual',
 JSON_ARRAY(
   'Test the radio on Channel 16: listen for traffic and conduct a radio check with marina.',
   'Verify DSC (Digital Selective Calling) is enabled and MMSI is programmed correctly.',
   'Test emergency DSC distress alert procedure (do NOT transmit false distress).',
   'Inspect the antenna connector at the radio for corrosion; clean with contact spray.',
   'Check the coax cable run for chafing, especially where it passes through deck.',
   'Inspect the antenna at the masthead for damage (use binoculars).',
   'Test the weather channel reception (WX1 through WX7).',
   'Check battery connection for handheld VHF backup radio; charge if low.',
   'Clean the radio housing with mild soap; avoid spraying directly onto controls.',
   'Ensure radio is registered with FCC (required for US vessels traveling internationally).'
 ),
 NULL),

('Fuel System Maintenance',
 'engine', 'intermediate',
 'Fuel filter elements, wrench, fuel line, hose clamps, fuel tank vent dye, fuel polishing kit, absorbent pads, gloves, fire extinguisher nearby',
 JSON_ARRAY(
   'Replace the primary fuel filter/water separator element every 100 engine hours or annually.',
   'Check the transparent bowl for water accumulation; drain any water contamination.',
   'Replace the secondary fuel filter on the engine per manufacturer schedule.',
   'Inspect all fuel hose runs for cracking, chafing, and proper clamp tightness.',
   'Check fuel deck fill for a tight cap seal to prevent water ingress.',
   'Inspect fuel tank vents for obstruction: insect screens should be clear.',
   'Test fuel shut-off valve operation: it should open and close smoothly.',
   'If fuel appears cloudy or smells off, consider fuel polishing to remove water and microbial growth.',
   'Check for fuel odors in the bilge which could indicate a leak; investigate immediately.',
   'Keep fuel tanks as full as possible to reduce condensation in the tank.'
 ),
 NULL),

('Standing Rigging Replacement Assessment',
 'sails', 'advanced',
 'Wire cutters, swage tool or Sta-Lok fittings, torque wrench, rigging wire, turnbuckles, cotter pins, rigging tape, bosun chair, safety harness',
 JSON_ARRAY(
   'Inspect all swage fittings carefully for cracks, corrosion, and "meat hook" broken strands at the terminal.',
   'Replace standing rigging every 10 years regardless of appearance, or sooner if any issues are found.',
   'Measure all shroud and stay lengths before removal; photograph the rig setup.',
   'Use a bosun chair to inspect the upper portions of the rig if not visible from deck.',
   'When replacing, start with forestay: release furler, support the mast, remove old forestay.',
   'Install new forestay, ensuring correct length and pin diameter.',
   'Work systematically: cap shrouds, uppers, lowers, backstay.',
   'Re-tension rig per specifications: most fractional rigs use 15-25% of wire breaking load.',
   'Check mast rake and adjust backstay for desired sailing characteristics.',
   'Sea trial and re-check tension after 10 hours of sailing as new wire settles.'
 ),
 NULL),

('Winch Servicing',
 'sails', 'intermediate',
 'Winch grease, light winch oil, screwdrivers, circlip pliers, snap ring pliers, clean rags, parts tray, small brush, camera/phone for photos',
 JSON_ARRAY(
   'Take photos of the winch before disassembly to aid reassembly.',
   'Remove the winch handle and any line, then unscrew the top cap.',
   'Lift off the drum carefully; the pawls and springs may spring out - work over a tray.',
   'Lay all parts out in order on a clean cloth.',
   'Clean all parts with a dry cloth or mineral spirits; never use WD-40 on winch internals.',
   'Inspect pawls for wear and the flat springs for fatigue cracks; replace if in doubt.',
   'Inspect roller bearings or ball bearings for corrosion or roughness.',
   'Apply light winch oil to pawls, springs, and bearings.',
   'Apply winch grease to gear teeth and the drum shaft.',
   'Reassemble in reverse order; test rotation in both directions; drum should spin freely and lock when reversed.'
 ),
 NULL),

('Teak Deck Maintenance',
 'hull', 'beginner',
 'Teak cleaner two-part, teak brightener, scrub brush, teak sealer or oil, masking tape, sandpaper 120 grit, hose',
 JSON_ARRAY(
   'Rinse the teak deck with fresh water to remove salt and loose debris.',
   'Apply Part A of teak cleaner and scrub with a stiff brush along the grain.',
   'Apply Part B (brightener/neutralizer) and scrub again; rinse thoroughly.',
   'Allow deck to dry completely for 24 hours.',
   'Lightly sand along the grain with 120-grit if gray coloring remains.',
   'Inspect caulking between teak planks: replace any that is cracked, shrunk, or missing.',
   'Mask off adjacent surfaces before applying sealer.',
   'Apply teak sealer or oil with a foam brush or cloth, working with the grain.',
   'Allow to dry per manufacturer instructions before applying a second coat.',
   'Avoid using harsh chemicals or sanding across the grain which damages the wood fibers.'
 ),
 NULL);

-- Game module database schema

-- Create games table
CREATE TABLE IF NOT EXISTS games (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    genre VARCHAR(100),
    release_date DATE,
    platform VARCHAR(100),
    price DECIMAL(10, 2),
    image VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_title (title),
    INDEX idx_genre (genre),
    INDEX idx_platform (platform),
    INDEX idx_is_active (is_active)
);

-- Insert sample games for testing
INSERT INTO games (title, description, genre, release_date, platform, price, image, is_active) VALUES
('Starfall Odyssey', 'Sci-fi RPG with open galaxy exploration and faction battles', 'RPG', '2022-11-18', 'PC, PS5', 59.99, 'starfall-odyssey.jpg', 1),
('Ruins of Aether', 'Fantasy dungeon crawler with magic-based combat', 'Adventure', '2021-04-12', 'PC', 39.99, 'ruins-aether.jpg', 1),
('Neon Circuit Racing', 'High-speed futuristic racing game with neon effects', 'Racing', '2023-02-05', 'PC, Xbox Series X', 49.99, 'neon-circuit.jpg', 1),
('Battlecraft Tactics', 'Turn-based tactical strategy on hex-grid maps', 'Strategy', '2020-06-22', 'PC, Switch', 29.99, 'battlecraft.jpg', 1),
('Shadowstrike Ops', 'Stealth shooter with futuristic gadgets and spy missions', 'Shooter', '2023-08-10', 'PS5', 69.99, 'shadowstrike-ops.jpg', 1),

('Mystic Valley', 'Farming simulation with magic-infused crops and creatures', 'Simulation', '2019-03-15', 'Switch', 24.99, 'mystic-valley.jpg', 1),
('Titanfall Legends', 'Mech-based FPS with fast-paced mobility combat', 'Shooter', '2021-12-02', 'PC, PS4', 34.99, 'titanfall-legends.jpg', 1),
('Frozen Line Hockey', 'Realistic ice hockey experience with team management mode', 'Sports', '2022-09-30', 'PS5, Xbox Series X', 59.99, 'frozen-line.jpg', 1),
('Eco Builder', 'City-building game focused on environmental balance', 'Simulation', '2020-01-18', 'PC', 19.99, 'eco-builder.jpg', 1),
('Dragonsteel Reborn', 'Action RPG with dragons, ancient magic, and open exploration', 'RPG', '2023-05-20', 'PC, PS5', 79.99, 'dragonsteel.jpg', 1),

('Quantum Breakpoint', 'Sci-fi shooter involving time manipulation mechanics', 'Shooter', '2021-08-11', 'PC', 44.99, 'quantum-breakpoint.jpg', 1),
('Turbo Drift Underground', 'Street racing with customizable cars and drift battles', 'Racing', '2022-10-09', 'PS4, PS5', 39.99, 'turbo-drift.jpg', 1),
('Monster Keepers', 'Strategy monster-taming game with base defense mode', 'Strategy', '2019-06-14', 'Switch', 19.99, 'monster-keepers.jpg', 1),
('Legends of Norvalia', 'Large-scale RPG set in Nordic-inspired lands', 'RPG', '2020-09-01', 'PC', 49.99, 'norvalia.jpg', 1),
('Sky Warriors: Reborn', 'Air combat action game featuring modern fighter jets', 'Action', '2021-03-05', 'PC, Xbox One', 29.99, 'sky-warriors.jpg', 1),

('Galactic Trader Empire', 'Space trading simulation with dynamic economy', 'Simulation', '2020-11-25', 'PC', 14.99, 'galactic-trader.jpg', 1),
('Last Snow Survivor', 'Survival game set in frozen wasteland with crafting', 'Survival', '2022-01-13', 'PC, PS5', 39.99, 'last-snow.jpg', 1),
('Cyber Arena Champions', '3v3 hero combat in cyberpunk-themed arenas', 'MOBA', '2023-07-06', 'PC', 0.00, 'cyber-arena.jpg', 1),
('Darkroot Hollow', 'Dark fantasy action with deep lore and skill-based combat', 'Action', '2020-04-27', 'PS4', 19.99, 'darkroot-hollow.jpg', 1),
('Velocity Masters', 'Top-down racing game with boosters and hazards', 'Racing', '2021-06-19', 'Switch', 9.99, 'velocity-masters.jpg', 1),

('Kingdom Builders Online', 'MMO strategy game with castle-building and alliances', 'MMO', '2022-03-14', 'PC', 0.00, 'kingdom-builders.jpg', 1),
('Robots at War', 'Mechanical warfare FPS featuring customizable robots', 'Shooter', '2020-10-22', 'PC, Xbox One', 49.99, 'robots-at-war.jpg', 1),
('Forest Whisper VR', 'Immersive VR forest exploration and puzzle solving', 'VR', '2023-04-08', 'PC', 24.99, 'forest-whisper.jpg', 1),
('Card Clash Arena', 'Competitive card battler with unique deck mechanics', 'Card', '2019-12-10', 'Mobile', 0.00, 'card-clash.jpg', 1),
('Eternal Samurai', 'Samurai-themed action game with parry-based combat', 'Action', '2021-02-16', 'PS5', 59.99, 'eternal-samurai.jpg', 1),

('Magic Orchard', 'Colorful puzzle game with magical fruit combinations', 'Puzzle', '2018-11-05', 'Mobile', 0.00, 'magic-orchard.jpg', 1),
('Asteroid Miners', 'Resource-management simulation with asteroid colonies', 'Simulation', '2019-05-30', 'PC', 12.99, 'asteroid-miners.jpg', 1),
('Warfront Reclaim', 'RTS war game with resource capture and base control', 'Strategy', '2021-09-11', 'PC', 29.99, 'warfront-reclaim.jpg', 1),
('Champion Kickboxing', 'Kickboxing fighting game with career progression', 'Fighting', '2020-07-17', 'PS4, PS5', 39.99, 'kickboxing.jpg', 1),
('Robo Rally EXTREME', 'Fast-paced robot racing on dynamic tracks', 'Racing', '2023-01-12', 'PC', 14.99, 'robo-rally.jpg', 1),

('Underwater Dominion', 'Exploration adventure set in the deep ocean', 'Adventure', '2022-04-22', 'PC', 19.99, 'underwater.jpg', 1),
('Wasteland Outrunners', 'Post-apocalyptic vehicle combat and survival', 'Survival', '2020-08-03', 'PC, Xbox One', 34.99, 'wasteland-outrunners.jpg', 1),
('Hero Academy Online', 'Online hero-based RPG with seasonal updates', 'RPG', '2021-11-09', 'PC', 0.00, 'hero-academy.jpg', 1),
('Ancient Relic Quest', 'Adventure puzzle solving in ancient temples', 'Puzzle', '2019-02-01', 'Switch', 9.99, 'ancient-relic.jpg', 1),
('Sonic Velocity Dash', 'High-speed platformer with colorful worlds', 'Platform', '2022-05-08', 'Switch', 49.99, 'velocity-dash.jpg', 1),

('Zombie Arena Defense', 'Wave-based zombie survival shooter', 'Shooter', '2021-01-14', 'PC', 9.99, 'zombie-arena.jpg', 1),
('Royal Chess Masters', 'Classic chess game with advanced AI and online mode', 'Board', '2020-10-01', 'PC, Mobile', 4.99, 'chess-masters.jpg', 1),
('Farming Era Revolution', 'Modern farming simulator with realistic machines', 'Simulation', '2023-03-11', 'PC, PS5', 39.99, 'farming-era.jpg', 1),
('Cosmic Battle Fleet', 'Space fleet strategy with real-time battles', 'Strategy', '2021-07-25', 'PC', 24.99, 'cosmic-fleet.jpg', 1),
('Dungeon of Trials', 'Roguelike dungeon crawler with permadeath', 'Roguelike', '2020-12-03', 'PC, Switch', 16.99, 'dungeon-trials.jpg', 1),

('Mecha Girl Arena', 'Anime-style team shooter with mecha suits', 'Shooter', '2023-06-06', 'PC', 0.00, 'mecha-girl.jpg', 1),
('Pirate Voyage Online', 'Open-sea pirate MMO with naval battles', 'MMO', '2022-02-09', 'PC', 0.00, 'pirate-voyage.jpg', 1),
('Viking Forge', 'Crafting and survival in Viking territories', 'Survival', '2020-05-19', 'PC, PS4', 29.99, 'viking-forge.jpg', 1),
('Future Racer X', 'Tech-based anti-gravity racing game', 'Racing', '2023-09-01', 'PC, PS5', 59.99, 'future-racer-x.jpg', 1),
('Skyborn Tales', 'Story-driven adventure with flying islands', 'Adventure', '2021-03-27', 'Switch', 34.99, 'skyborn-tales.jpg', 1);

-- Create view for active games
CREATE OR REPLACE VIEW v_active_games AS
SELECT 
    id,
    title,
    description,
    genre,
    release_date,
    platform,
    price,
    image,
    is_active,
    created_at,
    updated_at
FROM games
WHERE is_active = 1;
-- AirVent Monitor v2 — Schema
-- Run in phpMyAdmin SQL tab (database u442411629_hangin already exists)

CREATE TABLE IF NOT EXISTS users (
    user_id    INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(50)  NOT NULL UNIQUE,
    email      VARCHAR(100) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    role       ENUM('user','manager','admin') NOT NULL DEFAULT 'user',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sensors (
    sensor_id  INT AUTO_INCREMENT PRIMARY KEY,
    location   VARCHAR(100) NOT NULL,
    latitude   DECIMAL(9,6) DEFAULT 0,
    longitude  DECIMAL(9,6) DEFAULT 0,
    status     ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sensor_readings (
    reading_id         INT AUTO_INCREMENT PRIMARY KEY,
    sensor_id          INT NOT NULL,
    temperature        FLOAT NOT NULL DEFAULT 0,
    humidity           FLOAT NOT NULL DEFAULT 0,
    rainfall           FLOAT DEFAULT 0,
    wind_speed         FLOAT DEFAULT 0,
    sunlight_intensity FLOAT DEFAULT 0,
    weather_condition  ENUM('sunny','cloudy','rainy','storm','unknown') DEFAULT 'unknown',
    vent_state         ENUM('on','off') NOT NULL DEFAULT 'off',
    mode               ENUM('automatic','manual') NOT NULL DEFAULT 'automatic',
    created_at         DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sensor_id) REFERENCES sensors(sensor_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ventilation_logs (
    log_id    INT AUTO_INCREMENT PRIMARY KEY,
    user_id   INT,
    sensor_id INT NOT NULL,
    action    ENUM('on','off') NOT NULL,
    mode      ENUM('manual','automatic') DEFAULT 'manual',
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sensor_id) REFERENCES sensors(sensor_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS activity_logs (
    log_id      INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT,
    action_type VARCHAR(50) NOT NULL,
    description TEXT,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default users (plain passwords for demo — update after login!)
INSERT IGNORE INTO users (username,email,password,role) VALUES
('admin',      'admin@gmail.com',  'admin123', 'admin'),
('jane_smith', 'jane@gmail.com',   'pass123',  'manager'),
('john_doe',   'john@gmail.com',   '123456',   'user');

-- Sample sensors
INSERT IGNORE INTO sensors (location,latitude,longitude,status) VALUES
('Greenhouse Bay A', 8.314000, 125.041000, 'Active'),
('Greenhouse Bay B', 8.314010, 125.041010, 'Active'),
('Greenhouse Bay C', 8.313990, 125.041010, 'Active');

-- Sample readings
INSERT INTO sensor_readings(sensor_id,temperature,humidity,rainfall,wind_speed,sunlight_intensity,weather_condition,vent_state,mode)
VALUES
(1,38.5,42,0,2.1,950,'sunny','on','automatic'),
(2,24.0,88,5,3.4,200,'rainy','off','automatic'),
(3,31.2,60,0,4.8,720,'cloudy','on','automatic');

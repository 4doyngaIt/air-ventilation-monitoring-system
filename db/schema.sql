--  Create Database
CREATE DATABASE IF NOT EXISTS air_ventilation_system;

USE air_ventilation_system;

--  Users Table
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user','manager','admin') NOT NULL DEFAULT 'user',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

--  Sensors Table 

CREATE TABLE IF NOT EXISTS sensors (
    sensor_id INT AUTO_INCREMENT PRIMARY KEY,
    location VARCHAR(100) NOT NULL,
    latitude DECIMAL(9,6) NOT NULL,
    longitude DECIMAL(9,6) NOT NULL,
    status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Sensor Readings Table
CREATE TABLE IF NOT EXISTS sensor_readings (
    reading_id INT AUTO_INCREMENT PRIMARY KEY,
    sensor_id INT NOT NULL,
    temperature FLOAT NOT NULL,
    humidity FLOAT NOT NULL,
    is_raining BOOLEAN NOT NULL,
    vent_state ENUM('on','off') NOT NULL,
    mode ENUM('automatic','manual') NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sensor_id) REFERENCES sensors(sensor_id) ON DELETE CASCADE
);

--  Ventilation Control Logs Table
CREATE TABLE IF NOT EXISTS ventilation_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    sensor_id INT NOT NULL,
    action ENUM('on','off') NOT NULL,
    mode ENUM('manual') NOT NULL DEFAULT 'manual',
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (sensor_id) REFERENCES sensors(sensor_id) ON DELETE CASCADE
);



--  Sample Insertions



-- Users
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@gmail.com', 'admin123', 'admin'),
('john_doe', 'john@gmail.com', '123456', 'user'),
('jane_smith', 'jane@gmail.com', 'pass123', 'manager');

-- Sensors (with sample lat/lng coordinates)
INSERT INTO sensors (location, latitude, longitude, status) VALUES
('Alae', 14.599512, 120.984222, 'Active'),
('Damilag', 14.600100, 120.985500, 'Active'),
('Tankulan', 14.599900, 120.983800, 'Active');

-- Sensor Readings
INSERT INTO sensor_readings (sensor_id, temperature, humidity, is_raining, vent_state, mode) VALUES
(1, 24.0, 55.0, 0, 'off','automatic'),
(2, 22.0, 50.0, 0, 'off','automatic'),
(3, 32.0, 60.0, 1, 'off','automatic');

-- Ventilation Logs (Manual Actions)
INSERT INTO ventilation_logs (user_id, sensor_id, action) VALUES
(1, 1, 'on'),
(2, 2, 'off');
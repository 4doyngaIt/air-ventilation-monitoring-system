
-- CREATE DATABASE

CREATE DATABASE IF NOT EXISTS air_ventilation_system;

USE air_ventilation_system;



-- USERS TABLE

CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user','manager','admin') NOT NULL DEFAULT 'user',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);



-- SENSORS TABLE

CREATE TABLE IF NOT EXISTS sensors (
    sensor_id INT AUTO_INCREMENT PRIMARY KEY,
    location VARCHAR(100) NOT NULL,
    latitude DECIMAL(9,6) NOT NULL,
    longitude DECIMAL(9,6) NOT NULL,
    status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);



-- SENSOR READINGS TABLE


CREATE TABLE IF NOT EXISTS sensor_readings (
    reading_id INT AUTO_INCREMENT PRIMARY KEY,
    sensor_id INT NOT NULL,
    temperature FLOAT NOT NULL,
    humidity FLOAT NOT NULL,
    rainfall FLOAT DEFAULT 0,
    wind_speed FLOAT DEFAULT 0,
    sunlight_intensity FLOAT DEFAULT 0,
    weather_condition ENUM('sunny','cloudy','rainy','storm','unknown') DEFAULT 'unknown',
    vent_state ENUM('on','off') NOT NULL,
    mode ENUM('automatic','manual') NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sensor_id)
    REFERENCES sensors(sensor_id)
    ON DELETE CASCADE
);



-- VENTILATION LOGS TABLE

CREATE TABLE IF NOT EXISTS ventilation_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    sensor_id INT NOT NULL,
    action ENUM('on','off') NOT NULL,
    mode ENUM('manual','automatic') DEFAULT 'manual',
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)
    REFERENCES users(user_id)
    ON DELETE CASCADE,
    FOREIGN KEY (sensor_id)
    REFERENCES sensors(sensor_id)
    ON DELETE CASCADE
);



-- SAMPLE DATA INSERTION



-- USERS
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@gmail.com', 'admin123', 'admin'),
('john_doe', 'john@gmail.com', '123456', 'user'),
('jane_smith', 'jane@gmail.com', 'pass123', 'manager');


-- SENSORS (with coordinates for Leaflet map)
INSERT INTO sensors (location, latitude, longitude, status) VALUES
('sensor 1', 14.599512, 120.984222, 'Active'),
('sensor 2', 14.600100, 120.985500, 'Active'),
('sensor 3', 14.599900, 120.983800, 'Active');


-- SENSOR READINGS (Weather examples)
INSERT INTO sensor_readings 
(sensor_id, temperature, humidity, rainfall, wind_speed, sunlight_intensity, weather_condition, vent_state, mode)
VALUES

(1, 34.5, 45, 0, 3.2, 900, 'sunny', 'on', 'automatic'),
(2, 26.0, 80, 5.5, 4.1, 200, 'rainy', 'off', 'automatic'),
(3, 29.0, 65, 0, 6.5, 400, 'cloudy', 'off', 'automatic');


-- VENTILATION CONTROL LOGS
INSERT INTO ventilation_logs (user_id, sensor_id, action, mode) VALUES
(1,1,'on','manual'),
(2,2,'off','manual');
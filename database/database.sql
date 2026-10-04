-- =====================================================================
-- BayanAlert PH - Database Schema
-- Nationwide Community Safety & Disaster Preparedness Platform
-- Compatible with MySQL / MariaDB (XAMPP)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS bayanalert_ph CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bayanalert_ph;

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- USERS
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('citizen','admin','responder') NOT NULL DEFAULT 'citizen',
    province VARCHAR(100) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    barangay VARCHAR(100) DEFAULT NULL,
    status ENUM('active','suspended') NOT NULL DEFAULT 'active',
    failed_login_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    lockout_until DATETIME DEFAULT NULL,
    must_change_password TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role (role),
    INDEX idx_users_location (province, city, barangay)
) ENGINE=InnoDB;

-- Additional per-user saved locations (optional, for multi-location alerting)
CREATE TABLE user_locations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    label VARCHAR(100) DEFAULT NULL,
    province VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,
    barangay VARCHAR(100) DEFAULT NULL,
    latitude DECIMAL(10,7) DEFAULT NULL,
    longitude DECIMAL(10,7) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_userloc_user (user_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- INCIDENT REPORTS (covers citizen reports + SOS)
-- ---------------------------------------------------------------------
CREATE TABLE incident_reports (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT UNSIGNED DEFAULT NULL,
    incident_type ENUM(
        'Flood','Fire','Road Accident','Landslide','Earthquake Damage',
        'Fallen Tree','Road Blockage','Power Outage','Missing Person',
        'Medical Emergency','Accident','Earthquake','Other'
    ) NOT NULL,
    is_sos TINYINT(1) NOT NULL DEFAULT 0,
    description TEXT DEFAULT NULL,
    location_text VARCHAR(255) DEFAULT NULL,
    province VARCHAR(100) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    barangay VARCHAR(100) DEFAULT NULL,
    latitude DECIMAL(10,7) DEFAULT NULL,
    longitude DECIMAL(10,7) DEFAULT NULL,
    severity ENUM('Low','Moderate','High','Critical') NOT NULL DEFAULT 'Moderate',
    status ENUM('Pending','Under Verification','Verified','Responding','Resolved','Rejected') NOT NULL DEFAULT 'Pending',
    assigned_to INT UNSIGNED DEFAULT NULL,
    admin_notes TEXT DEFAULT NULL,
    incident_datetime DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_reports_status (status),
    INDEX idx_reports_type (incident_type),
    INDEX idx_reports_location (province, city),
    INDEX idx_reports_date (created_at)
) ENGINE=InnoDB;

CREATE TABLE incident_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id INT UNSIGNED NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES incident_reports(id) ON DELETE CASCADE,
    INDEX idx_images_report (report_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- ALERTS (official, admin-issued)
-- ---------------------------------------------------------------------
CREATE TABLE alerts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    category ENUM('Typhoon','Flood','Heavy Rain','Earthquake','Tsunami','Volcanic Activity','Landslide','Extreme Heat','Fire','Other Emergency') NOT NULL,
    alert_level ENUM('LOW','MODERATE','HIGH','CRITICAL') NOT NULL DEFAULT 'LOW',
    province VARCHAR(100) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    source VARCHAR(150) NOT NULL DEFAULT 'BayanAlert PH Admin',
    start_date DATETIME NOT NULL,
    end_date DATETIME DEFAULT NULL,
    status ENUM('Active','Inactive','Expired') NOT NULL DEFAULT 'Active',
    created_by INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_alerts_status (status),
    INDEX idx_alerts_location (province, city)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- EVACUATION CENTERS
-- ---------------------------------------------------------------------
CREATE TABLE evacuation_centers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    address VARCHAR(255) NOT NULL,
    barangay VARCHAR(100) DEFAULT NULL,
    city VARCHAR(100) NOT NULL,
    province VARCHAR(100) NOT NULL,
    latitude DECIMAL(10,7) NOT NULL,
    longitude DECIMAL(10,7) NOT NULL,
    capacity INT UNSIGNED NOT NULL DEFAULT 0,
    current_occupancy INT UNSIGNED NOT NULL DEFAULT 0,
    contact_number VARCHAR(50) DEFAULT NULL,
    facilities TEXT DEFAULT NULL,
    status ENUM('Open','Full','Closed') NOT NULL DEFAULT 'Open',
    is_sample TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_evac_location (province, city),
    INDEX idx_evac_status (status)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- EMERGENCY FACILITIES (hospitals, police, fire, etc.)
-- ---------------------------------------------------------------------
CREATE TABLE emergency_facilities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    type ENUM('Hospital','Police Station','Fire Station','Ambulance Station','Disaster Risk Reduction Office','Emergency Shelter') NOT NULL,
    address VARCHAR(255) NOT NULL,
    province VARCHAR(100) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    contact_number VARCHAR(50) DEFAULT NULL,
    latitude DECIMAL(10,7) NOT NULL,
    longitude DECIMAL(10,7) NOT NULL,
    opening_status ENUM('Open 24/7','Open','Closed') NOT NULL DEFAULT 'Open 24/7',
    description TEXT DEFAULT NULL,
    is_sample TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_facilities_type (type),
    INDEX idx_facilities_location (province, city)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- ANNOUNCEMENTS (admin / responder)
-- ---------------------------------------------------------------------
CREATE TABLE announcements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    body TEXT NOT NULL,
    province VARCHAR(100) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    posted_by INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_announce_location (province, city)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- NOTIFICATIONS
-- ---------------------------------------------------------------------
CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    type ENUM('report_submitted','report_verified','report_rejected','emergency_alert','evacuation_update','responder_update','admin_announcement') NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    related_id INT UNSIGNED DEFAULT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_notif_user (user_id, is_read)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- EMERGENCY CONTACTS (admin-configurable, not hard-coded in views)
-- ---------------------------------------------------------------------
CREATE TABLE emergency_contacts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(150) NOT NULL,
    number VARCHAR(50) NOT NULL,
    category VARCHAR(100) DEFAULT NULL,
    scope VARCHAR(150) DEFAULT 'National',
    display_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- ACTIVITY LOGS (audit trail)
-- ---------------------------------------------------------------------
CREATE TABLE activity_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED DEFAULT NULL,
    action VARCHAR(150) NOT NULL,
    details TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_logs_user (user_id),
    INDEX idx_logs_date (created_at)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- SAMPLE / DEMO DATA
-- All rows below are clearly demo data for development/testing only.
-- =====================================================================

-- Demo accounts
-- Passwords (already hashed with PHP password_hash, bcrypt):
--   Admin123!     -> admin@bayanalert.test
--   Responder123! -> responder@bayanalert.test
--   Citizen123!   -> citizen@bayanalert.test
INSERT INTO users (full_name, email, phone, password_hash, role, province, city, barangay, must_change_password) VALUES
('BayanAlert Administrator', 'admin@bayanalert.test', '09171234567', '$2y$10$EA6.eOHhExh8/Uz3JxIO2O9vv/hUV7QPdsVJ2qgt7Dxd/4Vza6jYG', 'admin', 'Cebu', 'Cebu City', 'Lahug', 1),
('Juan Dela Cruz (Responder)', 'responder@bayanalert.test', '09181234567', '$2y$10$W2Bpi/q3C9QyD2atqbNAS.uFeK6UYsVrj70OwzH7CHf7KCoV0TTB2', 'responder', 'Cebu', 'Cebu City', 'Capitol Site', 1),
('Maria Santos (Citizen)', 'citizen@bayanalert.test', '09191234567', '$2y$10$PBBKw13wAVZeRioGojbOWOhy3BbINhM7lz4SoHBFh0.0ys.W4fIla', 'citizen', 'Cebu', 'Cebu City', 'Guadalupe', 1);

-- These hashes correspond to the demo passwords documented in README.md:
--   admin@bayanalert.test     / Admin123!
--   responder@bayanalert.test / Responder123!
--   citizen@bayanalert.test   / Citizen123!
-- Generated with bcrypt (cost 10) - fully compatible with PHP password_verify().
-- CHANGE THESE PASSWORDS before any real deployment (must_change_password = 1 enforces this).

-- Sample evacuation centers
INSERT INTO evacuation_centers (name, address, barangay, city, province, latitude, longitude, capacity, current_occupancy, contact_number, facilities, status) VALUES
('Cebu City Sports Complex Evacuation Site', 'Osmeña Blvd', 'Sto. Nino', 'Cebu City', 'Cebu', 10.2947, 123.9016, 500, 0, '(032) 255-1234', 'Restrooms, Water Supply, Medical Station', 'Open'),
('Abellana National School Gym', 'V. Rama Ave', 'Guadalupe', 'Cebu City', 'Cebu', 10.3020, 123.8880, 300, 0, '(032) 255-5678', 'Restrooms, Kitchen Area', 'Open'),
('Manila City Hall Covered Court', 'A. Villegas St', 'Ermita', 'Manila', 'Metro Manila', 14.5896, 120.9822, 400, 0, '(02) 8527-1234', 'Restrooms, Generator', 'Open'),
('Quezon City Amoranto Sports Complex', 'Calamba St', 'Santa Mesa Heights', 'Quezon City', 'Metro Manila', 14.6304, 121.0089, 600, 0, '(02) 8988-4242', 'Restrooms, Water Supply, Medical Station', 'Open'),
('Davao City Rizal Memorial Colleges Gym', 'V. Sotto St', 'Poblacion', 'Davao City', 'Davao del Sur', 7.0722, 125.6131, 350, 0, '(082) 227-1234', 'Restrooms, Kitchen Area', 'Open'),
('Baguio City Athletic Bowl', 'Gov. Pack Rd', 'Campo Filipino', 'Baguio', 'Benguet', 16.4093, 120.5991, 250, 0, '(074) 442-1234', 'Restrooms, Heating Area', 'Open'),
('Tacloban Astrodome', 'Real St', 'Downtown', 'Tacloban City', 'Leyte', 11.2472, 125.0000, 700, 0, '(053) 321-1234', 'Restrooms, Water Supply, Medical Station', 'Open'),
('Cagayan de Oro Sports Complex', 'Limketkai Dr', 'Lapasan', 'Cagayan de Oro', 'Misamis Oriental', 8.4822, 124.6572, 450, 0, '(088) 857-1234', 'Restrooms, Generator, Medical Station', 'Open');

-- Sample emergency facilities
INSERT INTO emergency_facilities (name, type, address, province, city, contact_number, latitude, longitude, opening_status, description) VALUES
('Cebu Velez General Hospital', 'Hospital', 'F. Ramos St, Cebu City', 'Cebu', 'Cebu City', '(032) 233-8620', 10.2989, 123.8998, 'Open 24/7', 'Private tertiary hospital with emergency room.'),
('Vicente Sotto Memorial Medical Center', 'Hospital', 'B. Rodriguez St, Cebu City', 'Cebu', 'Cebu City', '(032) 253-9891', 10.3079, 123.8925, 'Open 24/7', 'Government regional hospital.'),
('Cebu City Police Station 1', 'Police Station', 'Pelaez St, Cebu City', 'Cebu', 'Cebu City', '(032) 254-1913', 10.2935, 123.9017, 'Open 24/7', 'Central police station for downtown Cebu City.'),
('Cebu City Fire Station - Central', 'Fire Station', 'N. Bacalso Ave, Cebu City', 'Cebu', 'Cebu City', '(032) 261-1234', 10.2941, 123.8935, 'Open 24/7', 'Bureau of Fire Protection main station.'),
('Cebu City DRRM Office', 'Disaster Risk Reduction Office', 'Cebu City Hall Compound', 'Cebu', 'Cebu City', '(032) 236-4949', 10.2934, 123.9014, 'Open', 'City Disaster Risk Reduction and Management Office.'),
('Philippine General Hospital', 'Hospital', 'Taft Ave, Manila', 'Metro Manila', 'Manila', '(02) 8554-8400', 14.5778, 120.9843, 'Open 24/7', 'National government hospital.'),
('Manila Police District Station 1', 'Police Station', 'U.N. Ave, Manila', 'Metro Manila', 'Manila', '(02) 8524-8235', 14.5823, 120.9822, 'Open 24/7', 'Manila Police District station.'),
('Davao City Fire Station', 'Fire Station', 'San Pedro St, Davao City', 'Davao del Sur', 'Davao City', '(082) 221-1234', 7.0722, 125.6131, 'Open 24/7', 'Bureau of Fire Protection Davao station.');

-- Sample alerts (clearly demo)
INSERT INTO alerts (title, description, category, alert_level, province, city, source, start_date, end_date, status) VALUES
('[DEMO] Heavy Rainfall Advisory', 'Sample advisory for demonstration purposes only. In production this would come from PAGASA.', 'Heavy Rain', 'MODERATE', 'Cebu', 'Cebu City', 'PAGASA (sample/demo)', NOW(), DATE_ADD(NOW(), INTERVAL 2 DAY), 'Active'),
('[DEMO] Typhoon Watch', 'Sample typhoon watch bulletin for demonstration purposes only.', 'Typhoon', 'HIGH', 'Leyte', 'Tacloban City', 'PAGASA (sample/demo)', NOW(), DATE_ADD(NOW(), INTERVAL 3 DAY), 'Active'),
('[DEMO] Minor Earthquake Recorded', 'Sample seismic bulletin for demonstration purposes only.', 'Earthquake', 'LOW', 'Davao del Sur', 'Davao City', 'PHIVOLCS (sample/demo)', NOW(), NULL, 'Active');

-- Sample incident reports (created_by NULL = anonymous/demo)
INSERT INTO incident_reports (reporter_id, incident_type, is_sos, description, location_text, province, city, barangay, latitude, longitude, severity, status, incident_datetime) VALUES
(3, 'Flood', 0, 'Ankle-deep flooding along the main road after heavy rain.', 'Near Guadalupe Public Market', 'Cebu', 'Cebu City', 'Guadalupe', 10.3011, 123.8877, 'Moderate', 'Verified', NOW()),
(3, 'Road Blockage', 0, 'Fallen tree branch blocking one lane.', 'Escario St', 'Cebu', 'Cebu City', 'Capitol Site', 10.3140, 123.8930, 'Low', 'Pending', NOW()),
(NULL, 'Fire', 0, 'Small fire reported near a residential compound, sample data.', 'Sitio Look', 'Cebu', 'Cebu City', 'Lahug', 10.3260, 123.8960, 'High', 'Resolved', NOW());

-- Sample emergency contacts (admin-configurable)
INSERT INTO emergency_contacts (label, number, category, scope, display_order) VALUES
('National Emergency Hotline', '911', 'General', 'National', 1),
('Philippine National Police', '117', 'Police', 'National', 2),
('Bureau of Fire Protection', '(02) 8426-0219', 'Fire', 'National', 3),
('Philippine Red Cross', '143', 'Medical / Disaster Response', 'National', 4),
('NDRRMC Operations Center', '(02) 8911-1406', 'Disaster Response', 'National', 5),
('Cebu City DRRM Office (Sample)', '(032) 236-4949', 'Local Government', 'Cebu City', 6);

-- FarmTrack database setup (MySQL 8.x). Import via phpMyAdmin or: mysql -u root < sql/farmtrack_db.sql
CREATE DATABASE IF NOT EXISTS farmtrack_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE farmtrack_db;

DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS price_history;
DROP TABLE IF EXISTS enquiries;
DROP TABLE IF EXISTS harvests;
DROP TABLE IF EXISTS buyers;
DROP TABLE IF EXISTS farmers;

CREATE TABLE farmers (
  farmer_id   INT AUTO_INCREMENT PRIMARY KEY,
  farm_name   VARCHAR(100) NOT NULL,
  farmer_name VARCHAR(100) NOT NULL,
  location    VARCHAR(100) NOT NULL,
  phone       VARCHAR(20)  NOT NULL,
  email       VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,   -- bcrypt via PHP password_hash()
  bio         VARCHAR(300) NOT NULL DEFAULT '',   -- profile text
  avatar      VARCHAR(60) NULL,                   -- profile photo file name
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE buyers (
  buyer_id      INT AUTO_INCREMENT PRIMARY KEY,
  buyer_name    VARCHAR(100) NOT NULL,
  business_name VARCHAR(100) NOT NULL,
  phone         VARCHAR(20)  NOT NULL,
  email         VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  bio           VARCHAR(300) NOT NULL DEFAULT '',
  avatar        VARCHAR(60) NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Note: `condition` is a reserved word in MySQL, so it is always written with backticks.
-- `status` is a small addition to the proposal so sold produce can leave the market board.
CREATE TABLE harvests (
  harvest_id    INT AUTO_INCREMENT PRIMARY KEY,
  farmer_id     INT NOT NULL,
  crop_name     VARCHAR(80) NOT NULL,
  quantity_kg   DECIMAL(10,2) NOT NULL CHECK (quantity_kg > 0),
  harvest_date  DATE NOT NULL,
  `condition`   ENUM('Excellent','Good','Fair','Sell Quickly') NOT NULL DEFAULT 'Good',
  price_per_kg  DECIMAL(8,2) NOT NULL CHECK (price_per_kg >= 0),
  status        ENUM('available','sold') NOT NULL DEFAULT 'available',
  FOREIGN KEY (farmer_id) REFERENCES farmers(farmer_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE enquiries (
  enquiry_id    INT AUTO_INCREMENT PRIMARY KEY,
  buyer_id      INT NOT NULL,
  harvest_id    INT NOT NULL,
  message       TEXT NOT NULL,
  enquiry_date  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  farmer_read   TINYINT(1) NOT NULL DEFAULT 0,   -- has the farmer opened the first message?
  FOREIGN KEY (buyer_id)   REFERENCES buyers(buyer_id)     ON DELETE CASCADE,
  FOREIGN KEY (harvest_id) REFERENCES harvests(harvest_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Version 2 additions: live chat and price history
CREATE TABLE messages (
  message_id  INT AUTO_INCREMENT PRIMARY KEY,
  enquiry_id  INT NOT NULL,
  sender      ENUM('buyer','farmer') NOT NULL,
  body        TEXT NOT NULL,
  sent_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  read_at     DATETIME NULL,                 -- NULL = not yet read by the recipient
  FOREIGN KEY (enquiry_id) REFERENCES enquiries(enquiry_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE price_history (
  history_id  INT AUTO_INCREMENT PRIMARY KEY,
  harvest_id  INT NOT NULL,
  old_price   DECIMAL(8,2) NOT NULL,
  new_price   DECIMAL(8,2) NOT NULL,
  changed_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (harvest_id) REFERENCES harvests(harvest_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Sample data for demonstration
-- Demo accounts: every password below is  Password123!  (delete or change before any real use)
INSERT INTO farmers (farm_name, farmer_name, location, phone, email, password_hash) VALUES
 ('Green Valley Farm','Ram Sharma','Wagga Wagga NSW','0412345678','ram@greenvalley.example','$2y$10$hS4BmuK5NhDvuBWzcCRKaufC/OFnoqtIjbga85jogSMeYhkADQCnW'),
 ('Sunrise Orchards','Sita Thapa','Orange NSW','0498765432','sita@sunrise.example','$2y$10$hS4BmuK5NhDvuBWzcCRKaufC/OFnoqtIjbga85jogSMeYhkADQCnW');
INSERT INTO buyers (buyer_name, business_name, phone, email, password_hash) VALUES
 ('Anna Lee','Fresh Corner Grocers','0455111222','anna@freshcorner.example','$2y$10$hS4BmuK5NhDvuBWzcCRKaufC/OFnoqtIjbga85jogSMeYhkADQCnW'),
 ('Tom Brown','Regional Café Supplies','0466333444','tom@regionalcafe.example','$2y$10$hS4BmuK5NhDvuBWzcCRKaufC/OFnoqtIjbga85jogSMeYhkADQCnW');
INSERT INTO harvests (farmer_id, crop_name, quantity_kg, harvest_date, `condition`, price_per_kg, status) VALUES
 (1,'Tomatoes',250.00,'2026-09-10','Excellent',4.50,'available'),
 (1,'Potatoes',600.00,'2026-09-05','Good',2.20,'available'),
 (2,'Apples',400.00,'2026-09-12','Good',3.80,'available'),
 (2,'Pumpkins',150.00,'2026-08-28','Fair',1.90,'sold');
INSERT INTO enquiries (buyer_id, harvest_id, message) VALUES
 (1,1,'Interested in 50 kg of tomatoes weekly. Can you deliver to town?');
INSERT INTO messages (enquiry_id, sender, body) VALUES
 (1,'farmer','Hi Anna, yes we can deliver weekly within 50 km. 50 kg is fine.');
INSERT INTO price_history (harvest_id, old_price, new_price) VALUES (1,4.20,4.50),(3,4.00,3.80);

-- Demo profile text
UPDATE farmers SET bio='Third-generation vegetable growers in the Riverina. We pick to order and sell direct to local grocers.' WHERE email='ram@greenvalley.example';
UPDATE farmers SET bio='Family-run orchard growing apples and stone fruit. Chemical-free and picked fresh each week.' WHERE email='sita@sunrise.example';
UPDATE buyers SET bio='Neighbourhood grocer looking for fresh, local produce delivered weekly.' WHERE email='anna@freshcorner.example';

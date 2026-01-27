-- Create database if not exists
CREATE DATABASE IF NOT EXISTS bachelor_meal_system;
USE bachelor_meal_system;

-- Table: persons
CREATE TABLE IF NOT EXISTS persons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: daily_meals
-- meal_type includes: chicken, fish, dim (egg), other (veg), friday
CREATE TABLE IF NOT EXISTS daily_meals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meal_date DATE NOT NULL,
    person_id INT NOT NULL,
    session ENUM('lunch', 'dinner') NOT NULL,
    meal_type ENUM('chicken', 'fish', 'dim', 'other', 'friday') NOT NULL,
    guest_count INT DEFAULT 0,
    FOREIGN KEY (person_id) REFERENCES persons(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: bazar_items
-- category includes: chicken, fish, dim (egg), other, rice (person-wise), friday
CREATE TABLE IF NOT EXISTS bazar_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bazar_date DATE NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    category ENUM('chicken', 'fish', 'dim', 'other', 'rice', 'friday') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    paid_by INT,
    FOREIGN KEY (paid_by) REFERENCES persons(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- MIGRATION: Run these ALTER statements on existing database
-- to add 'dim' and 'rice' without losing data
-- ============================================

-- Add 'dim' to daily_meals.meal_type ENUM
-- ALTER TABLE daily_meals MODIFY COLUMN meal_type ENUM('chicken', 'fish', 'dim', 'other', 'friday') NOT NULL;

-- Add 'dim' and 'rice' to bazar_items.category ENUM
-- ALTER TABLE bazar_items MODIFY COLUMN category ENUM('chicken', 'fish', 'dim', 'other', 'rice', 'friday') NOT NULL;
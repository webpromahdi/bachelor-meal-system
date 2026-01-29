CREATE DATABASE IF NOT EXISTS bachelor_meal_system;
USE bachelor_meal_system;

CREATE TABLE IF NOT EXISTS persons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- meal_type: chicken, fish, dim (egg), other (veg), special
CREATE TABLE IF NOT EXISTS daily_meals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meal_date DATE NOT NULL,
    person_id INT NOT NULL,
    session ENUM('lunch', 'dinner') NOT NULL,
    meal_type ENUM('chicken', 'fish', 'dim', 'other', 'special') NOT NULL,
    guest_count INT DEFAULT 0,
    FOREIGN KEY (person_id) REFERENCES persons(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- category: chicken, fish, dim, other, special (meal-based) | rice (person-wise cost only)
CREATE TABLE IF NOT EXISTS bazar_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bazar_date DATE NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    category ENUM('chicken', 'fish', 'dim', 'other', 'rice', 'special') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    paid_by INT,
    FOREIGN KEY (paid_by) REFERENCES persons(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
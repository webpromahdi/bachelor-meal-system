-- Migration: Replace 'friday' with 'special' in ENUM values
-- Run this script to update the database schema

-- Step 1: Add 'special' to daily_meals.meal_type ENUM (keep 'friday' temporarily)
ALTER TABLE daily_meals 
MODIFY COLUMN meal_type ENUM('chicken', 'fish', 'dim', 'other', 'friday', 'special') NOT NULL;

-- Step 2: Update existing 'friday' values to 'special' in daily_meals
UPDATE daily_meals SET meal_type = 'special' WHERE meal_type = 'friday';

-- Step 3: Remove 'friday' from daily_meals.meal_type ENUM
ALTER TABLE daily_meals 
MODIFY COLUMN meal_type ENUM('chicken', 'fish', 'dim', 'other', 'special') NOT NULL;

-- Step 4: Add 'special' to bazar_items.category ENUM (keep 'friday' temporarily)
ALTER TABLE bazar_items 
MODIFY COLUMN category ENUM('chicken', 'fish', 'dim', 'other', 'rice', 'friday', 'special') NOT NULL;

-- Step 5: Update existing 'friday' values to 'special' in bazar_items
UPDATE bazar_items SET category = 'special' WHERE category = 'friday';

-- Step 6: Remove 'friday' from bazar_items.category ENUM
ALTER TABLE bazar_items 
MODIFY COLUMN category ENUM('chicken', 'fish', 'dim', 'other', 'rice', 'special') NOT NULL;

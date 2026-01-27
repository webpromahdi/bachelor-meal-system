<?php
session_start();
require_once '../config/database.php';

// Check if we have meal count data from previous step
if (!isset($_SESSION['meal_entry'])) {
    // Redirect back to step 1 if no data
    header('Location: meal_count.php');
    exit;
}

// Get stored data
$meal_entry = $_SESSION['meal_entry'];
$meal_date = $meal_entry['meal_date'];
$person_id = $meal_entry['person_id'];
$person_name = $meal_entry['person_name'];
$day_meal_count = $meal_entry['day_meal_count'];
$night_meal_count = $meal_entry['night_meal_count'];

// Initialize variables
$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $day_meal_type = $_POST['day_meal_type'] ?? '';
    $night_meal_type = $_POST['night_meal_type'] ?? '';
    
    // Validation
    $errors = [];
    
    if ($day_meal_count > 0 && empty($day_meal_type)) {
        $errors[] = 'Please select Day Meal Type (you have ' . $day_meal_count . ' day meals)';
    }
    
    if ($night_meal_count > 0 && empty($night_meal_type)) {
        $errors[] = 'Please select Night Meal Type (you have ' . $night_meal_count . ' night meals)';
    }
    
    if (!empty($errors)) {
        $message = implode('<br>', $errors);
        $message_type = 'error';
    } else {
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Prepare statement for inserting meals
            $sql = "INSERT INTO daily_meals (meal_date, person_id, session, meal_type, guest_count) 
                    VALUES (?, ?, ?, ?, 0)";
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $conn->error);
            }
            
            $rows_inserted = 0;
            
            // Insert Day (Lunch) meals - N rows
            if ($day_meal_count > 0) {
                $session_type = 'lunch';
                for ($i = 0; $i < $day_meal_count; $i++) {
                    $stmt->bind_param("siss", $meal_date, $person_id, $session_type, $day_meal_type);
                    if (!$stmt->execute()) {
                        throw new Exception('Execute failed: ' . $stmt->error);
                    }
                    $rows_inserted++;
                }
            }
            
            // Insert Night (Dinner) meals - N rows
            if ($night_meal_count > 0) {
                $session_type = 'dinner';
                for ($i = 0; $i < $night_meal_count; $i++) {
                    $stmt->bind_param("siss", $meal_date, $person_id, $session_type, $night_meal_type);
                    if (!$stmt->execute()) {
                        throw new Exception('Execute failed: ' . $stmt->error);
                    }
                    $rows_inserted++;
                }
            }
            
            $stmt->close();
            
            // Commit transaction
            $conn->commit();
            
            // Clear session data
            unset($_SESSION['meal_entry']);
            
            // Store success message in session for redirect
            $_SESSION['success_message'] = "Successfully added {$rows_inserted} meal entries for {$person_name}!";
            
            // Redirect to meal_count for fresh entry
            header('Location: meal_count.php');
            exit;
            
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $message = 'Database error: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Check for success message from redirect
if (isset($_SESSION['success_message'])) {
    $message = $_SESSION['success_message'];
    $message_type = 'success';
    unset($_SESSION['success_message']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Step 2: Meal Type Entry - Bachelor Meal System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .excel-cell {
            border: 1px solid #d1d5db;
            padding: 8px 12px;
        }
        .excel-header {
            background-color: #e5e7eb;
            font-weight: 600;
            border: 1px solid #d1d5db;
            padding: 8px 12px;
        }
        .excel-input {
            border: none;
            background: transparent;
            width: 100%;
            padding: 4px;
            text-align: center;
        }
        .excel-input:focus {
            outline: 2px solid #3b82f6;
            background: #eff6ff;
        }
        .type-option {
            transition: all 0.2s;
        }
        .type-option:hover {
            transform: scale(1.05);
        }
        .type-option.selected {
            ring: 2px;
            ring-offset: 2px;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Navigation Bar -->
    <nav class="bg-blue-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <div class="text-2xl font-bold">🍽️</div>
                    <div class="ml-2">
                        <span class="font-bold text-lg">Bachelor Meal System</span>
                        <span class="text-sm text-blue-200 block -mt-1">Excel-Style Entry</span>
                    </div>
                </div>
                <div class="flex space-x-4">
                    <a href="index.php" class="px-4 py-2 rounded-lg hover:bg-blue-700 transition">📊 Dashboard</a>
                    <a href="meal_count.php" class="px-4 py-2 rounded-lg bg-blue-800">🍽️ Meal Entry</a>
                    <a href="bazar.php" class="px-4 py-2 rounded-lg hover:bg-blue-700 transition">🛒 Bazar</a>
                    <a href="summary.php" class="px-4 py-2 rounded-lg hover:bg-blue-700 transition">📈 Summary</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto p-6">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Step 2: Meal Type Entry</h1>
            <p class="text-gray-600">Select what type of meal was served</p>
        </div>

        <!-- Progress Steps -->
        <div class="flex items-center mb-8">
            <div class="flex items-center">
                <div class="bg-green-600 text-white rounded-full w-8 h-8 flex items-center justify-center font-bold">✓</div>
                <span class="ml-2 text-green-600">Meal Count</span>
            </div>
            <div class="flex-1 h-1 bg-blue-600 mx-4"></div>
            <div class="flex items-center">
                <div class="bg-blue-600 text-white rounded-full w-8 h-8 flex items-center justify-center font-bold">2</div>
                <span class="ml-2 font-medium text-blue-600">Meal Type</span>
            </div>
        </div>

        <!-- Message Alert -->
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Summary Card -->
        <div class="bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg shadow-lg p-6 mb-6">
            <h3 class="text-lg font-bold mb-4">📋 Entry Summary from Step 1</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white/20 rounded-lg p-3">
                    <div class="text-sm opacity-80">Date</div>
                    <div class="text-xl font-bold"><?php echo date('d M Y', strtotime($meal_date)); ?></div>
                </div>
                <div class="bg-white/20 rounded-lg p-3">
                    <div class="text-sm opacity-80">Person</div>
                    <div class="text-xl font-bold"><?php echo htmlspecialchars($person_name); ?></div>
                </div>
                <div class="bg-white/20 rounded-lg p-3">
                    <div class="text-sm opacity-80">☀️ Day Meals</div>
                    <div class="text-3xl font-bold"><?php echo $day_meal_count; ?></div>
                </div>
                <div class="bg-white/20 rounded-lg p-3">
                    <div class="text-sm opacity-80">🌙 Night Meals</div>
                    <div class="text-3xl font-bold"><?php echo $night_meal_count; ?></div>
                </div>
            </div>
        </div>

        <!-- Excel-Style Form -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-300 bg-gray-50">
                <h2 class="text-lg font-bold text-gray-800">📊 Excel Sheet: Meal Type Selection</h2>
                <p class="text-sm text-gray-600">Select the meal type for each session</p>
            </div>

            <form method="POST">
                <table class="w-full">
                    <thead>
                        <tr>
                            <th class="excel-header text-left">Session</th>
                            <th class="excel-header text-center">Count</th>
                            <th class="excel-header text-left">Meal Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Day Meal Type Row -->
                        <tr class="<?php echo $day_meal_count > 0 ? 'bg-blue-50' : 'bg-gray-100 opacity-50'; ?>">
                            <td class="excel-cell font-medium">
                                ☀️ Day (Lunch)
                            </td>
                            <td class="excel-cell text-center">
                                <span class="text-2xl font-bold text-blue-700"><?php echo $day_meal_count; ?></span>
                            </td>
                            <td class="excel-cell">
                                <?php if ($day_meal_count > 0): ?>
                                    <div class="flex space-x-2">
                                        <label class="flex-1">
                                            <input type="radio" name="day_meal_type" value="fish" class="hidden peer" required>
                                            <div class="peer-checked:bg-blue-600 peer-checked:text-white bg-blue-100 text-blue-800 rounded-lg p-2 text-center cursor-pointer type-option">
                                                🐟 Fish
                                            </div>
                                        </label>
                                        <label class="flex-1">
                                            <input type="radio" name="day_meal_type" value="chicken" class="hidden peer">
                                            <div class="peer-checked:bg-red-600 peer-checked:text-white bg-red-100 text-red-800 rounded-lg p-2 text-center cursor-pointer type-option">
                                                🍗 Chicken
                                            </div>
                                        </label>
                                        <label class="flex-1">
                                            <input type="radio" name="day_meal_type" value="other" class="hidden peer">
                                            <div class="peer-checked:bg-gray-600 peer-checked:text-white bg-gray-100 text-gray-800 rounded-lg p-2 text-center cursor-pointer type-option">
                                                🥗 Other
                                            </div>
                                        </label>
                                        <label class="flex-1">
                                            <input type="radio" name="day_meal_type" value="special" class="hidden peer">
                                            <div class="peer-checked:bg-pink-600 peer-checked:text-white bg-pink-100 text-pink-800 rounded-lg p-2 text-center cursor-pointer type-option">
                                                ⭐ Special
                                            </div>
                                        </label>
                                    </div>
                                <?php else: ?>
                                    <span class="text-gray-400 italic">No day meals</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        
                        <!-- Night Meal Type Row -->
                        <tr class="<?php echo $night_meal_count > 0 ? 'bg-purple-50' : 'bg-gray-100 opacity-50'; ?>">
                            <td class="excel-cell font-medium">
                                🌙 Night (Dinner)
                            </td>
                            <td class="excel-cell text-center">
                                <span class="text-2xl font-bold text-purple-700"><?php echo $night_meal_count; ?></span>
                            </td>
                            <td class="excel-cell">
                                <?php if ($night_meal_count > 0): ?>
                                    <div class="flex space-x-2">
                                        <label class="flex-1">
                                            <input type="radio" name="night_meal_type" value="fish" class="hidden peer" required>
                                            <div class="peer-checked:bg-blue-600 peer-checked:text-white bg-blue-100 text-blue-800 rounded-lg p-2 text-center cursor-pointer type-option">
                                                🐟 Fish
                                            </div>
                                        </label>
                                        <label class="flex-1">
                                            <input type="radio" name="night_meal_type" value="chicken" class="hidden peer">
                                            <div class="peer-checked:bg-red-600 peer-checked:text-white bg-red-100 text-red-800 rounded-lg p-2 text-center cursor-pointer type-option">
                                                🍗 Chicken
                                            </div>
                                        </label>
                                        <label class="flex-1">
                                            <input type="radio" name="night_meal_type" value="other" class="hidden peer">
                                            <div class="peer-checked:bg-gray-600 peer-checked:text-white bg-gray-100 text-gray-800 rounded-lg p-2 text-center cursor-pointer type-option">
                                                🥗 Other
                                            </div>
                                        </label>
                                        <label class="flex-1">
                                            <input type="radio" name="night_meal_type" value="special" class="hidden peer">
                                            <div class="peer-checked:bg-pink-600 peer-checked:text-white bg-pink-100 text-pink-800 rounded-lg p-2 text-center cursor-pointer type-option">
                                                ⭐ Special
                                            </div>
                                        </label>
                                    </div>
                                <?php else: ?>
                                    <span class="text-gray-400 italic">No night meals</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Database Preview -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                    <h4 class="font-medium text-gray-700 mb-2">📝 What will be saved:</h4>
                    <div class="text-sm text-gray-600 bg-white border border-gray-200 rounded p-3">
                        <?php if ($day_meal_count > 0): ?>
                            <p>• <strong><?php echo $day_meal_count; ?></strong> lunch entries (one per meal)</p>
                        <?php endif; ?>
                        <?php if ($night_meal_count > 0): ?>
                            <p>• <strong><?php echo $night_meal_count; ?></strong> dinner entries (one per meal)</p>
                        <?php endif; ?>
                        <p class="text-xs text-gray-500 mt-2">Total: <strong><?php echo $day_meal_count + $night_meal_count; ?></strong> rows in daily_meals table</p>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex space-x-4">
                    <a href="meal_count.php" 
                       class="flex-1 bg-gray-500 text-white py-3 px-4 rounded-lg font-medium hover:bg-gray-600 text-center flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Back to Step 1
                    </a>
                    <button type="submit" 
                            class="flex-1 bg-green-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 flex items-center justify-center">
                        <span>Save Meals</span>
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </button>
                </div>
            </form>
        </div>

        <!-- Category Guide -->
        <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-center">
                <div class="text-2xl mb-1">🐟</div>
                <h4 class="font-bold text-blue-800">Fish</h4>
                <p class="text-xs text-blue-600">All seafood</p>
            </div>
            <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-center">
                <div class="text-2xl mb-1">🍗</div>
                <h4 class="font-bold text-red-800">Chicken</h4>
                <p class="text-xs text-red-600">Meat items</p>
            </div>
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-center">
                <div class="text-2xl mb-1">🥗</div>
                <h4 class="font-bold text-gray-800">Other</h4>
                <p class="text-xs text-gray-600">Veg, eggs, etc.</p>
            </div>
            <div class="bg-pink-50 border border-pink-200 rounded-lg p-3 text-center">
                <div class="text-2xl mb-1">⭐</div>
                <h4 class="font-bold text-pink-800">Special Meal</h4>
                <p class="text-xs text-pink-600">Special occasions</p>
            </div>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>

<?php
/**
 * meals.php - Daily Meal Entry
 * 
 * Business Rules:
 * - Meal type is GLOBAL per session (same for everyone)
 * - Only meal count varies per person
 * - guest_count column unused (guests = increased meal count)
 */

require_once '../config/database.php';

// Initialize variables
$message = '';
$message_type = '';
$selected_date = $_POST['meal_date'] ?? date('Y-m-d');

// Fetch all persons for the grid
$persons = [];
$person_result = $conn->query("SELECT id, name FROM persons ORDER BY name");
if ($person_result) {
    while ($row = $person_result->fetch_assoc()) {
        $persons[] = $row;
    }
    $person_result->free();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_meals'])) {
    
    // Get GLOBAL meal types (same for everyone)
    $global_lunch_type = $_POST['global_lunch_type'] ?? '';
    $global_dinner_type = $_POST['global_dinner_type'] ?? '';
    
    // Validation: At least one global type should be selected if any counts > 0
    $has_lunch_entries = false;
    $has_dinner_entries = false;
    
    foreach ($persons as $person) {
        $pid = $person['id'];
        if (intval($_POST['lunch_count'][$pid] ?? 0) > 0) $has_lunch_entries = true;
        if (intval($_POST['dinner_count'][$pid] ?? 0) > 0) $has_dinner_entries = true;
    }
    
    // Validate that global types are selected for sessions that have entries
    $validation_errors = [];
    if ($has_lunch_entries && empty($global_lunch_type)) {
        $validation_errors[] = 'Please select Lunch Meal Type (you have lunch entries)';
    }
    if ($has_dinner_entries && empty($global_dinner_type)) {
        $validation_errors[] = 'Please select Dinner Meal Type (you have dinner entries)';
    }
    
    if (!empty($validation_errors)) {
        $message = implode('<br>', $validation_errors);
        $message_type = 'error';
    } else {
        // Begin transaction for atomic operation
        $conn->begin_transaction();
        
        try {
            $total_rows_inserted = 0;
            
            // Delete existing entries before re-inserting (allows corrections)
            $delete_sql = "DELETE FROM daily_meals 
                           WHERE meal_date = ? AND person_id = ? AND session = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            
            // guest_count always 0 (guests tracked via increased meal count)
            $insert_sql = "INSERT INTO daily_meals (meal_date, person_id, session, meal_type, guest_count) 
                           VALUES (?, ?, ?, ?, 0)";
            $insert_stmt = $conn->prepare($insert_sql);
            
            if (!$delete_stmt || !$insert_stmt) {
                throw new Exception('Failed to prepare statements: ' . $conn->error);
            }
            
            // Loop through each person
            foreach ($persons as $person) {
                $person_id = $person['id'];
                
                $lunch_count = intval($_POST['lunch_count'][$person_id] ?? 0);
                $dinner_count = intval($_POST['dinner_count'][$person_id] ?? 0);
                
                // Process lunch
                $session_lunch = 'lunch';
                
                $delete_stmt->bind_param("sis", $selected_date, $person_id, $session_lunch);
                $delete_stmt->execute();
                
                // Insert N rows for lunch (using GLOBAL lunch type)
                if ($lunch_count > 0 && !empty($global_lunch_type)) {
                    for ($i = 0; $i < $lunch_count; $i++) {
                        $insert_stmt->bind_param("siss", $selected_date, $person_id, $session_lunch, $global_lunch_type);
                        if (!$insert_stmt->execute()) {
                            throw new Exception('Insert failed: ' . $insert_stmt->error);
                        }
                        $total_rows_inserted++;
                    }
                }
                
                // Process dinner
                $session_dinner = 'dinner';
                
                $delete_stmt->bind_param("sis", $selected_date, $person_id, $session_dinner);
                $delete_stmt->execute();
                
                // Insert M rows for dinner (using GLOBAL dinner type)
                if ($dinner_count > 0 && !empty($global_dinner_type)) {
                    for ($i = 0; $i < $dinner_count; $i++) {
                        $insert_stmt->bind_param("siss", $selected_date, $person_id, $session_dinner, $global_dinner_type);
                        if (!$insert_stmt->execute()) {
                            throw new Exception('Insert failed: ' . $insert_stmt->error);
                        }
                        $total_rows_inserted++;
                    }
                }
            }
            
            $delete_stmt->close();
            $insert_stmt->close();
            
            $conn->commit();
            
            $message = "✅ Successfully saved {$total_rows_inserted} meal entries for " . date('d M Y', strtotime($selected_date)) . "!";
            $message_type = 'success';
            
        } catch (Exception $e) {
            $conn->rollback();
            $message = '❌ Error: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

$existing_data = [];
$existing_lunch_type = '';
$existing_dinner_type = '';

$load_sql = "SELECT person_id, session, meal_type, COUNT(*) as meal_count 
             FROM daily_meals 
             WHERE meal_date = ?
             GROUP BY person_id, session, meal_type";
$load_stmt = $conn->prepare($load_sql);
$load_stmt->bind_param("s", $selected_date);
$load_stmt->execute();
$load_result = $load_stmt->get_result();

while ($row = $load_result->fetch_assoc()) {
    $pid = $row['person_id'];
    $session = $row['session'];
    
    if (!isset($existing_data[$pid])) {
        $existing_data[$pid] = ['lunch_count' => 0, 'dinner_count' => 0];
    }
    
    if ($session === 'lunch') {
        $existing_data[$pid]['lunch_count'] = $row['meal_count'];
        // Capture the global lunch type (should be same for all)
        if (empty($existing_lunch_type)) {
            $existing_lunch_type = $row['meal_type'];
        }
    } else {
        $existing_data[$pid]['dinner_count'] = $row['meal_count'];
        // Capture the global dinner type
        if (empty($existing_dinner_type)) {
            $existing_dinner_type = $row['meal_type'];
        }
    }
}
$load_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Meal Entry - Bachelor Meal System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Excel-like styling */
        .excel-table {
            border-collapse: collapse;
            width: 100%;
        }
        .excel-table th,
        .excel-table td {
            border: 1px solid #d1d5db;
            padding: 10px 14px;
        }
        .excel-table th {
            background-color: #374151;
            color: white;
            font-weight: 600;
            text-align: center;
        }
        .excel-table tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .excel-table tr:hover {
            background-color: #eff6ff;
        }
        .excel-input {
            width: 80px;
            padding: 8px;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            text-align: center;
            font-size: 16px;
            font-weight: 600;
        }
        .excel-input:focus {
            outline: none;
            border-color: #3b82f6;
            background-color: #eff6ff;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        .person-name {
            font-weight: 500;
            text-align: left !important;
            background-color: #f3f4f6;
        }
        .global-type-card {
            transition: all 0.2s;
            cursor: pointer;
        }
        .global-type-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .global-type-card.selected {
            ring: 3px;
            transform: scale(1.02);
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
                        <span class="text-sm text-blue-200 block -mt-1">Simple Meal Entry</span>
                    </div>
                </div>
                <div class="flex space-x-4">
                    <a href="index.php" class="px-4 py-2 rounded-lg hover:bg-blue-700 transition">📊 Dashboard</a>
                    <a href="meals.php" class="px-4 py-2 rounded-lg bg-blue-800">🍽️ Meals</a>
                    <a href="bazar.php" class="px-4 py-2 rounded-lg hover:bg-blue-700 transition">🛒 Bazar</a>
                    <a href="summary.php" class="px-4 py-2 rounded-lg hover:bg-blue-700 transition">📈 Summary</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-5xl mx-auto p-6">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">📊 Daily Meal Entry</h1>
            <p class="text-gray-600">Enter meal counts for all members — Excel-style!</p>
        </div>

        <!-- Message Alert -->
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800 border border-green-300' : 'bg-red-100 text-red-800 border border-red-300'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Main Form -->
        <form method="POST">
            
            <!-- Date Picker -->
            <div class="bg-white rounded-lg shadow-md p-4 mb-6">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex items-center space-x-3">
                        <label for="meal_date" class="font-medium text-gray-700">📅 Date:</label>
                        <input type="date" 
                               id="meal_date" 
                               name="meal_date" 
                               value="<?php echo htmlspecialchars($selected_date); ?>"
                               class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg font-medium"
                               onchange="this.form.submit()">
                    </div>
                    <div class="text-gray-500 text-lg">
                        <strong><?php echo date('l', strtotime($selected_date)); ?></strong>
                        <span class="text-sm">(<?php echo date('F j, Y', strtotime($selected_date)); ?>)</span>
                    </div>
                    <?php if (!empty($existing_data)): ?>
                        <div class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-medium">
                            ⚠️ Editing existing data
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Global Meal Types (Same for Everyone) -->
            <div class="bg-gradient-to-r from-amber-50 to-indigo-50 rounded-lg shadow-md p-6 mb-6 border border-gray-200">
                <h2 class="text-lg font-bold text-gray-800 mb-4">🍽️ Today's Meal Types (Same for Everyone)</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- Global Lunch Type -->
                    <div class="bg-white rounded-lg p-4 border-2 border-amber-300">
                        <h3 class="font-bold text-amber-700 mb-3 flex items-center">
                            <span class="text-2xl mr-2">☀️</span> Lunch Meal Type
                        </h3>
                        <div class="grid grid-cols-3 gap-2">
                            <?php 
                            $lunch_types = [
                                'fish' => ['icon' => '🐟', 'label' => 'Fish', 'color' => 'blue'],
                                'chicken' => ['icon' => '🍗', 'label' => 'Chicken', 'color' => 'red'],
                                'dim' => ['icon' => '🥚', 'label' => 'Dim (Egg)', 'color' => 'yellow'],
                                'other' => ['icon' => '🥗', 'label' => 'Other', 'color' => 'gray'],
                                'special' => ['icon' => '⭐', 'label' => 'Special Meal', 'color' => 'pink']
                            ];
                            foreach ($lunch_types as $value => $type): 
                                $is_selected = ($existing_lunch_type === $value) ? 'checked' : '';
                            ?>
                                <label class="cursor-pointer">
                                    <input type="radio" name="global_lunch_type" value="<?php echo $value; ?>" 
                                           class="hidden peer" <?php echo $is_selected; ?>>
                                    <div class="peer-checked:bg-<?php echo $type['color']; ?>-600 peer-checked:text-white 
                                                peer-checked:border-<?php echo $type['color']; ?>-700 peer-checked:shadow-lg
                                                bg-<?php echo $type['color']; ?>-50 text-<?php echo $type['color']; ?>-800 
                                                border-2 border-<?php echo $type['color']; ?>-200
                                                rounded-lg p-3 text-center transition-all global-type-card">
                                        <div class="text-2xl"><?php echo $type['icon']; ?></div>
                                        <div class="font-medium"><?php echo $type['label']; ?></div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Global Dinner Type -->
                    <div class="bg-white rounded-lg p-4 border-2 border-indigo-300">
                        <h3 class="font-bold text-indigo-700 mb-3 flex items-center">
                            <span class="text-2xl mr-2">🌙</span> Dinner Meal Type
                        </h3>
                        <div class="grid grid-cols-3 gap-2">
                            <?php 
                            foreach ($lunch_types as $value => $type): 
                                $is_selected = ($existing_dinner_type === $value) ? 'checked' : '';
                            ?>
                                <label class="cursor-pointer">
                                    <input type="radio" name="global_dinner_type" value="<?php echo $value; ?>" 
                                           class="hidden peer" <?php echo $is_selected; ?>>
                                    <div class="peer-checked:bg-<?php echo $type['color']; ?>-600 peer-checked:text-white 
                                                peer-checked:border-<?php echo $type['color']; ?>-700 peer-checked:shadow-lg
                                                bg-<?php echo $type['color']; ?>-50 text-<?php echo $type['color']; ?>-800 
                                                border-2 border-<?php echo $type['color']; ?>-200
                                                rounded-lg p-3 text-center transition-all global-type-card">
                                        <div class="text-2xl"><?php echo $type['icon']; ?></div>
                                        <div class="font-medium"><?php echo $type['label']; ?></div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                </div>
            </div>

            <!-- Person-wise Meal Counts Table -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                    <h2 class="text-lg font-bold text-gray-800">📋 Meal Counts Per Person</h2>
                    <p class="text-sm text-gray-600">Enter how many meals each person ate (including any guests they brought)</p>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="excel-table">
                        <thead>
                            <tr>
                                <th class="w-12">#</th>
                                <th class="text-left">Person Name</th>
                                <th class="bg-amber-600 w-32">☀️ Lunch</th>
                                <th class="bg-indigo-600 w-32">🌙 Dinner</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($persons)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-gray-500 py-8">
                                        No persons found. Please add persons to the database first.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($persons as $index => $person): 
                                    $pid = $person['id'];
                                    $existing = $existing_data[$pid] ?? ['lunch_count' => 0, 'dinner_count' => 0];
                                ?>
                                    <tr>
                                        <!-- Row Number -->
                                        <td class="text-center text-gray-500 font-medium">
                                            <?php echo $index + 1; ?>
                                        </td>
                                        
                                        <!-- Person Name (readonly) -->
                                        <td class="person-name text-lg">
                                            <?php echo htmlspecialchars($person['name']); ?>
                                        </td>
                                        
                                        <!-- Lunch Meal Count -->
                                        <td class="text-center bg-amber-50">
                                            <input type="number" 
                                                   name="lunch_count[<?php echo $pid; ?>]" 
                                                   value="<?php echo $existing['lunch_count']; ?>"
                                                   min="0" 
                                                   max="20"
                                                   class="excel-input bg-amber-50 text-amber-800"
                                                   placeholder="0">
                                        </td>
                                        
                                        <!-- Dinner Meal Count -->
                                        <td class="text-center bg-indigo-50">
                                            <input type="number" 
                                                   name="dinner_count[<?php echo $pid; ?>]" 
                                                   value="<?php echo $existing['dinner_count']; ?>"
                                                   min="0" 
                                                   max="20"
                                                   class="excel-input bg-indigo-50 text-indigo-800"
                                                   placeholder="0">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <!-- Totals Footer -->
                        <tfoot class="bg-gray-800 text-white">
                            <tr>
                                <td colspan="2" class="text-right font-bold px-4">TOTALS:</td>
                                <td class="text-center font-bold" id="total-lunch">0</td>
                                <td class="text-center font-bold" id="total-dinner">0</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Submit Button -->
                <div class="p-4 bg-gray-50 border-t border-gray-200">
                    <button type="submit" 
                            name="submit_meals"
                            class="w-full bg-green-600 text-white py-4 px-6 rounded-lg font-bold text-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 flex items-center justify-center transition">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Save All Meals for <?php echo date('d M Y', strtotime($selected_date)); ?>
                    </button>
                </div>
            </div>
        </form>

        <!-- Quick Actions -->
        <div class="mt-6 bg-white rounded-lg shadow-md p-4">
            <h3 class="font-bold text-gray-700 mb-3">⚡ Quick Actions</h3>
            <div class="flex flex-wrap gap-2">
                <button type="button" onclick="setAllCounts(1, 1)" 
                        class="px-4 py-2 bg-green-100 text-green-800 rounded-lg hover:bg-green-200 text-sm font-medium transition">
                    Everyone = 1 Lunch + 1 Dinner
                </button>
                <button type="button" onclick="setAllCounts(1, 0)" 
                        class="px-4 py-2 bg-amber-100 text-amber-800 rounded-lg hover:bg-amber-200 text-sm font-medium transition">
                    Everyone = 1 Lunch Only
                </button>
                <button type="button" onclick="setAllCounts(0, 1)" 
                        class="px-4 py-2 bg-indigo-100 text-indigo-800 rounded-lg hover:bg-indigo-200 text-sm font-medium transition">
                    Everyone = 1 Dinner Only
                </button>
                <button type="button" onclick="setAllCounts(0, 0)" 
                        class="px-4 py-2 bg-red-100 text-red-800 rounded-lg hover:bg-red-200 text-sm font-medium transition">
                    ❌ Clear All
                </button>
            </div>
        </div>

        <!-- Help Section -->
        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h3 class="font-bold text-blue-800 mb-2">💡 How to Use</h3>
            <ul class="text-blue-700 text-sm space-y-1">
                <li>1️⃣ Select the <strong>date</strong> at the top</li>
                <li>2️⃣ Choose <strong>Lunch Type</strong> and <strong>Dinner Type</strong> (applies to everyone)</li>
                <li>3️⃣ Enter <strong>meal count</strong> for each person</li>
                <li>4️⃣ If someone had a guest, increase their count (e.g., 1 person + 2 guests = 3)</li>
                <li>5️⃣ Click <strong>Save</strong></li>
            </ul>
        </div>
    </div>

    <!-- JavaScript for Live Totals & Quick Actions -->
    <script>
        function updateTotals() {
            let lunchTotal = 0;
            let dinnerTotal = 0;
            
            document.querySelectorAll('input[name^="lunch_count"]').forEach(input => {
                lunchTotal += parseInt(input.value) || 0;
            });
            
            document.querySelectorAll('input[name^="dinner_count"]').forEach(input => {
                dinnerTotal += parseInt(input.value) || 0;
            });
            
            document.getElementById('total-lunch').textContent = lunchTotal;
            document.getElementById('total-dinner').textContent = dinnerTotal;
        }

        function setAllCounts(lunch, dinner) {
            document.querySelectorAll('input[name^="lunch_count"]').forEach(input => {
                input.value = lunch;
            });
            document.querySelectorAll('input[name^="dinner_count"]').forEach(input => {
                input.value = dinner;
            });
            updateTotals();
        }

        // Add event listeners to all count inputs for real-time updates
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.excel-input').forEach(input => {
                input.addEventListener('input', updateTotals);
            });
            updateTotals();
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>

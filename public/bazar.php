<?php
/**
 * bazar.php - Category-wise Bazar Entry
 * 
 * Business Rules:
 * - Rice: Person-wise cost (not shared by meals)
 * - Special: Distributed among Special meal eaters only
 */

require_once '../config/database.php';

// Initialize variables
$message = '';
$message_type = '';
$saved_summary = [];
$selected_date = $_POST['bazar_date'] ?? date('Y-m-d');

// Fetch persons for dropdown
$persons = [];
$person_result = $conn->query("SELECT id, name FROM persons ORDER BY name");
if ($person_result) {
    while ($row = $person_result->fetch_assoc()) {
        $persons[] = $row;
    }
    $person_result->free();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_bazar'])) {
    $bazar_date = $_POST['bazar_date'] ?? '';
    $paid_by = $_POST['paid_by'] ?? '';
    
    // Get category amounts
    $chicken_amount = floatval($_POST['chicken_amount'] ?? 0);
    $fish_amount = floatval($_POST['fish_amount'] ?? 0);
    $dim_amount = floatval($_POST['dim_amount'] ?? 0);
    $other_amount = floatval($_POST['other_amount'] ?? 0);
    $special_amount = floatval($_POST['special_amount'] ?? 0);
    $rice_amount = floatval($_POST['rice_amount'] ?? 0);
    
    // Validation
    if (empty($bazar_date) || empty($paid_by)) {
        $message = 'Please select date and who paid';
        $message_type = 'error';
    } else {
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // First, delete existing entries for this date and payer (to allow corrections)
            $delete_sql = "DELETE FROM bazar_items WHERE bazar_date = ? AND paid_by = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("si", $bazar_date, $paid_by);
            $delete_stmt->execute();
            $delete_stmt->close();
            
            // Prepare INSERT statement
            $insert_sql = "INSERT INTO bazar_items (bazar_date, item_name, category, amount, paid_by) 
                           VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            
            if (!$insert_stmt) {
                throw new Exception('Failed to prepare statement: ' . $conn->error);
            }
            
            $total_inserted = 0;
            $saved_summary = [];
            
            if ($chicken_amount > 0) {
                $item_name = 'Chicken/Meat';
                $category = 'chicken';
                $insert_stmt->bind_param("sssdi", $bazar_date, $item_name, $category, $chicken_amount, $paid_by);
                if (!$insert_stmt->execute()) {
                    throw new Exception('Insert failed: ' . $insert_stmt->error);
                }
                $saved_summary['Chicken'] = $chicken_amount;
                $total_inserted++;
            }
            
            if ($fish_amount > 0) {
                $item_name = 'Fish/Seafood';
                $category = 'fish';
                $insert_stmt->bind_param("sssdi", $bazar_date, $item_name, $category, $fish_amount, $paid_by);
                if (!$insert_stmt->execute()) {
                    throw new Exception('Insert failed: ' . $insert_stmt->error);
                }
                $saved_summary['Fish'] = $fish_amount;
                $total_inserted++;
            }
            
            if ($dim_amount > 0) {
                $item_name = 'Dim/Egg';
                $category = 'dim';
                $insert_stmt->bind_param("sssdi", $bazar_date, $item_name, $category, $dim_amount, $paid_by);
                if (!$insert_stmt->execute()) {
                    throw new Exception('Insert failed: ' . $insert_stmt->error);
                }
                $saved_summary['Dim (Egg)'] = $dim_amount;
                $total_inserted++;
            }
            
            if ($other_amount > 0) {
                $item_name = 'Other/Vegetables';
                $category = 'other';
                $insert_stmt->bind_param("sssdi", $bazar_date, $item_name, $category, $other_amount, $paid_by);
                if (!$insert_stmt->execute()) {
                    throw new Exception('Insert failed: ' . $insert_stmt->error);
                }
                $saved_summary['Other'] = $other_amount;
                $total_inserted++;
            }
            
            // Business rule: Special cost distributed among Special meal eaters only
            if ($special_amount > 0) {
                $item_name = 'Special Meal';
                $category = 'special';
                $insert_stmt->bind_param("sssdi", $bazar_date, $item_name, $category, $special_amount, $paid_by);
                if (!$insert_stmt->execute()) {
                    throw new Exception('Insert failed: ' . $insert_stmt->error);
                }
                $saved_summary['Special Meal'] = $special_amount;
                $total_inserted++;
            }
            
            // Business rule: Rice cost goes ONLY to the payer (not shared)
            if ($rice_amount > 0) {
                $item_name = 'Rice (Chal)';
                $category = 'rice';
                $insert_stmt->bind_param("sssdi", $bazar_date, $item_name, $category, $rice_amount, $paid_by);
                if (!$insert_stmt->execute()) {
                    throw new Exception('Insert failed: ' . $insert_stmt->error);
                }
                $saved_summary['Rice'] = $rice_amount;
                $total_inserted++;
            }
            
            $insert_stmt->close();
            
            // Commit transaction
            $conn->commit();
            
            if ($total_inserted > 0) {
                $total_amount = array_sum($saved_summary);
                $message = "✅ Saved {$total_inserted} category entries totaling BDT " . number_format($total_amount, 2);
                $message_type = 'success';
            } else {
                $message = '⚠️ No amounts entered. Nothing was saved.';
                $message_type = 'error';
            }
            
        } catch (Exception $e) {
            $conn->rollback();
            $message = '❌ Error: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

$existing_data = [];
$existing_paid_by = null;
$load_sql = "SELECT category, SUM(amount) as total_amount, paid_by
             FROM bazar_items 
             WHERE bazar_date = ?
             GROUP BY category, paid_by";
$load_stmt = $conn->prepare($load_sql);
$load_stmt->bind_param("s", $selected_date);
$load_stmt->execute();
$load_result = $load_stmt->get_result();

while ($row = $load_result->fetch_assoc()) {
    $existing_data[$row['category']] = $row['total_amount'];
    if ($existing_paid_by === null) {
        $existing_paid_by = $row['paid_by'];
    }
}
$load_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bazar Entry - Bachelor Meal System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .category-card {
            transition: all 0.2s;
        }
        .category-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .amount-input {
            font-size: 1.25rem;
            font-weight: 600;
            text-align: right;
        }
        .amount-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Navigation Bar -->
    <nav class="bg-blue-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <div class="text-2xl font-bold">🛒</div>
                    <div class="ml-2">
                        <span class="font-bold text-lg">Bachelor Meal System</span>
                        <span class="text-sm text-blue-200 block -mt-1">Bazar Entry</span>
                    </div>
                </div>
                <div class="flex space-x-4">
                    <a href="index.php" class="px-4 py-2 rounded-lg hover:bg-blue-700 transition">📊 Dashboard</a>
                    <a href="meals.php" class="px-4 py-2 rounded-lg hover:bg-blue-700 transition">🍽️ Meals</a>
                    <a href="bazar.php" class="px-4 py-2 rounded-lg bg-blue-800">🛒 Bazar</a>
                    <a href="summary.php" class="px-4 py-2 rounded-lg hover:bg-blue-700 transition">📈 Summary</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto p-6">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">🛒 Daily Bazar Entry</h1>
            <p class="text-gray-600">Enter category-wise bazar amounts (Chicken, Fish, Dim, Other, Special, Rice)</p>
        </div>

        <!-- Message Alert -->
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800 border border-green-300' : 'bg-red-100 text-red-800 border border-red-300'; ?>">
                <?php echo $message; ?>
                
                <?php if (!empty($saved_summary)): ?>
                    <div class="mt-3 pt-3 border-t <?php echo $message_type === 'success' ? 'border-green-300' : 'border-red-300'; ?>">
                        <strong>Saved Amounts:</strong>
                        <ul class="mt-1">
                            <?php foreach ($saved_summary as $cat => $amt): ?>
                                <li>• <?php echo $cat; ?>: BDT <?php echo number_format($amt, 2); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="mt-2 font-bold">
                            Total: BDT <?php echo number_format(array_sum($saved_summary), 2); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Main Form -->
        <form method="POST" id="bazarForm">
            
            <!-- Date & Paid By Section -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Date Picker -->
                    <div>
                        <label for="bazar_date" class="block text-sm font-medium text-gray-700 mb-2">
                            📅 Bazar Date *
                        </label>
                        <input type="date" 
                               id="bazar_date" 
                               name="bazar_date" 
                               value="<?php echo htmlspecialchars($selected_date); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg"
                               onchange="this.form.submit()"
                               required>
                        <p class="text-sm text-gray-500 mt-1">
                            <?php echo date('l', strtotime($selected_date)); ?>
                        </p>
                    </div>
                    
                    <!-- Paid By Dropdown -->
                    <div>
                        <label for="paid_by" class="block text-sm font-medium text-gray-700 mb-2">
                            💰 Paid By *
                        </label>
                        <select id="paid_by" 
                                name="paid_by" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg"
                                required>
                            <option value="">-- Select Person --</option>
                            <?php foreach ($persons as $person): ?>
                                <option value="<?php echo $person['id']; ?>"
                                    <?php echo ($existing_paid_by == $person['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($person['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Category Amount Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                
                <!-- Chicken Card -->
                <div class="category-card bg-red-50 border-2 border-red-200 rounded-lg p-5">
                    <div class="flex items-center mb-3">
                        <span class="text-3xl mr-3">🍗</span>
                        <h3 class="text-lg font-bold text-red-800">Chicken / Meat</h3>
                    </div>
                    <div class="flex items-center">
                        <span class="text-red-600 mr-2">BDT</span>
                        <input type="number" 
                               name="chicken_amount" 
                               value="<?php echo $existing_data['chicken'] ?? ''; ?>"
                               step="0.01" 
                               min="0"
                               placeholder="0.00"
                               class="flex-1 px-4 py-3 border-2 border-red-300 rounded-lg amount-input bg-white text-red-800">
                    </div>
                </div>
                
                <!-- Fish Card -->
                <div class="category-card bg-blue-50 border-2 border-blue-200 rounded-lg p-5">
                    <div class="flex items-center mb-3">
                        <span class="text-3xl mr-3">🐟</span>
                        <h3 class="text-lg font-bold text-blue-800">Fish / Seafood</h3>
                    </div>
                    <div class="flex items-center">
                        <span class="text-blue-600 mr-2">BDT</span>
                        <input type="number" 
                               name="fish_amount" 
                               value="<?php echo $existing_data['fish'] ?? ''; ?>"
                               step="0.01" 
                               min="0"
                               placeholder="0.00"
                               class="flex-1 px-4 py-3 border-2 border-blue-300 rounded-lg amount-input bg-white text-blue-800">
                    </div>
                </div>
                
                <!-- Dim (Egg) Card -->
                <div class="category-card bg-yellow-50 border-2 border-yellow-200 rounded-lg p-5">
                    <div class="flex items-center mb-3">
                        <span class="text-3xl mr-3">🥚</span>
                        <h3 class="text-lg font-bold text-yellow-800">Dim (Egg)</h3>
                    </div>
                    <div class="flex items-center">
                        <span class="text-yellow-600 mr-2">BDT</span>
                        <input type="number" 
                               name="dim_amount" 
                               value="<?php echo $existing_data['dim'] ?? ''; ?>"
                               step="0.01" 
                               min="0"
                               placeholder="0.00"
                               class="flex-1 px-4 py-3 border-2 border-yellow-300 rounded-lg amount-input bg-white text-yellow-800">
                    </div>
                    <p class="text-yellow-600 text-xs mt-2">Full meal category (not part of Other)</p>
                </div>
                
                <!-- Other/Vegetables Card -->
                <div class="category-card bg-green-50 border-2 border-green-200 rounded-lg p-5">
                    <div class="flex items-center mb-3">
                        <span class="text-3xl mr-3">🥗</span>
                        <h3 class="text-lg font-bold text-green-800">Other / Vegetables</h3>
                        <span class="ml-2 px-2 py-1 bg-green-200 text-green-800 text-xs font-bold rounded">Meal-based</span>
                    </div>
                    <div class="flex items-center">
                        <span class="text-green-600 mr-2">BDT</span>
                        <input type="number" 
                               name="other_amount" 
                               value="<?php echo $existing_data['other'] ?? ''; ?>"
                               step="0.01" 
                               min="0"
                               placeholder="0.00"
                               class="flex-1 px-4 py-3 border-2 border-green-300 rounded-lg amount-input bg-white text-green-800">
                    </div>
                    <p class="text-green-600 text-xs mt-2">Spices, oil, vegetables - shared by all meals</p>
                </div>
                
                <!-- Special Meal Card -->
                <div class="category-card bg-pink-50 border-2 border-pink-200 rounded-lg p-5">
                    <div class="flex items-center mb-3">
                        <span class="text-3xl mr-3">⭐</span>
                        <h3 class="text-lg font-bold text-pink-800">Special Meal</h3>
                        <span class="ml-2 px-2 py-1 bg-pink-200 text-pink-800 text-xs font-bold rounded">Meal-based</span>
                    </div>
                    <div class="flex items-center">
                        <span class="text-pink-600 mr-2">BDT</span>
                        <input type="number" 
                               name="special_amount" 
                               value="<?php echo $existing_data['special'] ?? ''; ?>"
                               step="0.01" 
                               min="0"
                               placeholder="0.00"
                               class="flex-1 px-4 py-3 border-2 border-pink-300 rounded-lg amount-input bg-white text-pink-800">
                    </div>
                    <p class="text-pink-600 text-xs mt-2">Special occasions - distributed among Special meal eaters</p>
                </div>
                
                <!-- Rice Card -->
                <div class="category-card bg-amber-50 border-2 border-amber-200 rounded-lg p-5">
                    <div class="flex items-center mb-3">
                        <span class="text-3xl mr-3">🍚</span>
                        <h3 class="text-lg font-bold text-amber-800">Rice (Chal)</h3>
                        <span class="ml-2 px-2 py-1 bg-amber-200 text-amber-800 text-xs font-bold rounded">Person-wise</span>
                    </div>
                    <div class="flex items-center">
                        <span class="text-amber-600 mr-2">BDT</span>
                        <input type="number" 
                               name="rice_amount" 
                               value="<?php echo $existing_data['rice'] ?? ''; ?>"
                               step="0.01" 
                               min="0"
                               placeholder="0.00"
                               class="flex-1 px-4 py-3 border-2 border-amber-300 rounded-lg amount-input bg-white text-amber-800">
                    </div>
                    <p class="text-amber-600 text-xs mt-2">⚠️ Rice cost goes ONLY to the payer (not shared by meals)</p>
                </div>
                
            </div>

            <!-- Submit Button -->
            <div class="bg-white rounded-lg shadow-md p-4">
                <button type="submit" 
                        name="submit_bazar"
                        class="w-full bg-green-600 text-white py-4 px-6 rounded-lg font-bold text-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 flex items-center justify-center transition">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Save Bazar Entry for <?php echo date('d M Y', strtotime($selected_date)); ?>
                </button>
            </div>
        </form>

        <!-- Help Section -->
        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h3 class="font-bold text-blue-800 mb-2">💡 Category Guide</h3>
            <ul class="text-blue-700 text-sm space-y-1">
                <li>• <strong>Chicken/Fish/Dim</strong> - Meal categories, cost shared by meal count</li>
                <li>• <strong>Other (Vegetables/Oil/Spices)</strong> - <span class="font-bold text-green-700">Meal-based</span>, cost shared by ALL meals</li>
                <li>• <strong>Special Meal</strong> - <span class="font-bold text-pink-700">Meal-based</span>, cost shared by Special meal eaters only</li>
                <li>• <strong>Rice (Chal)</strong> - <span class="font-bold text-amber-700">PERSON-WISE</span>, cost goes ONLY to the payer</li>
                <li>• Leave amount as 0 or empty if nothing was purchased</li>
                <li>• Re-submitting for same date/person will update existing entries</li>
            </ul>
        </div>

        <!-- Existing Entries for Today -->
        <?php if (!empty($existing_data)): ?>
            <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h3 class="font-bold text-yellow-800 mb-2">📋 Existing Entries for <?php echo date('d M Y', strtotime($selected_date)); ?></h3>
                <ul class="text-yellow-700 text-sm space-y-1">
                    <?php 
                    $existing_total = 0;
                    foreach ($existing_data as $cat => $amount): 
                        $existing_total += $amount;
                    ?>
                        <li>• <strong><?php echo ucfirst($cat); ?>:</strong> BDT <?php echo number_format($amount, 2); ?></li>
                    <?php endforeach; ?>
                </ul>
                <div class="mt-2 pt-2 border-t border-yellow-300 font-bold text-yellow-800">
                    Total: BDT <?php echo number_format($existing_total, 2); ?>
                </div>
                <p class="text-yellow-600 text-xs mt-2">⚠️ Saving new amounts will replace these entries</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php $conn->close(); ?>

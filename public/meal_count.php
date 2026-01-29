<?php
session_start();
require_once '../config/database.php';

// Initialize variables
$message = '';
$message_type = '';

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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $meal_date = $_POST['meal_date'] ?? '';
    $person_id = $_POST['person_id'] ?? '';
    $day_meal_count = intval($_POST['day_meal_count'] ?? 0);
    $night_meal_count = intval($_POST['night_meal_count'] ?? 0);
    
    // Validation
    if (empty($meal_date) || empty($person_id)) {
        $message = 'Please fill all required fields';
        $message_type = 'error';
    } elseif ($day_meal_count < 0 || $night_meal_count < 0) {
        $message = 'Meal counts cannot be negative';
        $message_type = 'error';
    } elseif ($day_meal_count == 0 && $night_meal_count == 0) {
        $message = 'At least one meal count must be greater than 0';
        $message_type = 'error';
    } else {
        // Get person name for display
        $person_name = '';
        foreach ($persons as $p) {
            if ($p['id'] == $person_id) {
                $person_name = $p['name'];
                break;
            }
        }
        
        // Store in session for next page
        $_SESSION['meal_entry'] = [
            'meal_date' => $meal_date,
            'person_id' => $person_id,
            'person_name' => $person_name,
            'day_meal_count' => $day_meal_count,
            'night_meal_count' => $night_meal_count
        ];
        
        // Redirect to meal type page
        header('Location: meal_type.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Step 1: Meal Count Entry - Bachelor Meal System</title>
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
            <h1 class="text-2xl font-bold text-gray-800">Step 1: Meal Count Entry</h1>
            <p class="text-gray-600">Enter how many meals were consumed (including guests)</p>
        </div>

        <!-- Progress Steps -->
        <div class="flex items-center mb-8">
            <div class="flex items-center">
                <div class="bg-blue-600 text-white rounded-full w-8 h-8 flex items-center justify-center font-bold">1</div>
                <span class="ml-2 font-medium text-blue-600">Meal Count</span>
            </div>
            <div class="flex-1 h-1 bg-gray-300 mx-4"></div>
            <div class="flex items-center">
                <div class="bg-gray-300 text-gray-600 rounded-full w-8 h-8 flex items-center justify-center font-bold">2</div>
                <span class="ml-2 text-gray-500">Meal Type</span>
            </div>
        </div>

        <!-- Message Alert -->
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Excel-Style Form -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-300 bg-gray-50">
                <h2 class="text-lg font-bold text-gray-800">📊 Excel Sheet: Meal Count</h2>
                <p class="text-sm text-gray-600">Enter the total number of people who ate each meal</p>
            </div>

            <form method="POST">
                <table class="w-full">
                    <thead>
                        <tr>
                            <th class="excel-header text-left">Field</th>
                            <th class="excel-header text-left">Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Date Row -->
                        <tr>
                            <td class="excel-cell font-medium bg-gray-50">Meal Date *</td>
                            <td class="excel-cell">
                                <input type="date" 
                                       name="meal_date" 
                                       value="<?php echo date('Y-m-d'); ?>"
                                       class="excel-input"
                                       required>
                            </td>
                        </tr>
                        
                        <!-- Person Row -->
                        <tr>
                            <td class="excel-cell font-medium bg-gray-50">Person *</td>
                            <td class="excel-cell">
                                <select name="person_id" class="excel-input" required>
                                    <option value="">-- Select Person --</option>
                                    <?php foreach ($persons as $person): ?>
                                        <option value="<?php echo $person['id']; ?>">
                                            <?php echo htmlspecialchars($person['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        
                        <!-- Day Meal Count Row -->
                        <tr>
                            <td class="excel-cell font-medium bg-blue-50">
                                ☀️ Day Meal Count (Lunch)
                                <p class="text-xs text-gray-500 font-normal">Total eaters including guests</p>
                            </td>
                            <td class="excel-cell bg-blue-50">
                                <input type="number" 
                                       name="day_meal_count" 
                                       value="0"
                                       min="0"
                                       max="50"
                                       class="excel-input text-lg font-bold text-blue-700">
                            </td>
                        </tr>
                        
                        <!-- Night Meal Count Row -->
                        <tr>
                            <td class="excel-cell font-medium bg-purple-50">
                                🌙 Night Meal Count (Dinner)
                                <p class="text-xs text-gray-500 font-normal">Total eaters including guests</p>
                            </td>
                            <td class="excel-cell bg-purple-50">
                                <input type="number" 
                                       name="night_meal_count" 
                                       value="0"
                                       min="0"
                                       max="50"
                                       class="excel-input text-lg font-bold text-purple-700">
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Submit Button -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                    <button type="submit" 
                            class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 flex items-center justify-center">
                        <span>Next: Select Meal Types</span>
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </div>
            </form>
        </div>

        <!-- Help Section -->
        <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <h3 class="font-medium text-yellow-800 mb-2">💡 How This Works</h3>
            <ul class="text-yellow-700 text-sm space-y-1">
                <li>• <strong>Day Meal Count:</strong> Total people who ate lunch (member + guests)</li>
                <li>• <strong>Night Meal Count:</strong> Total people who ate dinner (member + guests)</li>
                <li>• Example: If 1 member + 2 guests ate lunch → Enter "3" as Day Meal Count</li>
                <li>• Guests are NOT tracked separately; just enter total count</li>
            </ul>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>

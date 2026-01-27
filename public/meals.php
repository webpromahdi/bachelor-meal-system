<?php
require_once '../config/database.php';

// Initialize variables
$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $meal_date = $_POST['meal_date'] ?? '';
    $person_id = $_POST['person_id'] ?? '';
    $session = $_POST['session'] ?? '';
    $meal_type = $_POST['meal_type'] ?? '';
    $guest_count = $_POST['guest_count'] ?? 0;
    
    // Basic validation
    if (empty($meal_date) || empty($person_id) || empty($session) || empty($meal_type)) {
        $message = 'Please fill all required fields';
        $message_type = 'error';
    } else {
        // Prepare SQL statement
        $sql = "INSERT INTO daily_meals (meal_date, person_id, session, meal_type, guest_count) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            // Bind parameters
            $stmt->bind_param("sisss", $meal_date, $person_id, $session, $meal_type, $guest_count);
            
            // Execute query
            if ($stmt->execute()) {
                $message = 'Meal entry added successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error: ' . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
        } else {
            $message = 'Database error: ' . $conn->error;
            $message_type = 'error';
        }
    }
}

// Fetch persons for dropdown
$persons = [];
$person_result = $conn->query("SELECT id, name FROM persons ORDER BY name");
if ($person_result) {
    while ($row = $person_result->fetch_assoc()) {
        $persons[] = $row;
    }
    $person_result->free();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Meal Entry - Bachelor Meal System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="max-w-4xl mx-auto p-6">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Daily Meal Entry</h1>
            <p class="text-gray-600">Enter daily meal consumption for each person</p>
            <a href="index.php" class="text-blue-600 hover:text-blue-800 text-sm mt-2 inline-block">← Back to Home</a>
        </div>

        <!-- Message Alert -->
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Meal Entry Form -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <form method="POST" class="space-y-6">
                <!-- Meal Date -->
                <div>
                    <label for="meal_date" class="block text-sm font-medium text-gray-700 mb-2">
                        Meal Date *
                    </label>
                    <input type="date" 
                           id="meal_date" 
                           name="meal_date" 
                           value="<?php echo date('Y-m-d'); ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           required>
                </div>

                <!-- Person Select -->
                <div>
                    <label for="person_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Person *
                    </label>
                    <select id="person_id" 
                            name="person_id" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            required>
                        <option value="">Select a person</option>
                        <?php foreach ($persons as $person): ?>
                            <option value="<?php echo $person['id']; ?>">
                                <?php echo htmlspecialchars($person['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Session -->
                <div>
                    <label for="session" class="block text-sm font-medium text-gray-700 mb-2">
                        Session *
                    </label>
                    <select id="session" 
                            name="session" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            required>
                        <option value="">Select session</option>
                        <option value="lunch">Lunch</option>
                        <option value="dinner">Dinner</option>
                    </select>
                </div>

                <!-- Meal Type -->
                <div>
                    <label for="meal_type" class="block text-sm font-medium text-gray-700 mb-2">
                        Meal Type *
                    </label>
                    <select id="meal_type" 
                            name="meal_type" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            required>
                        <option value="">Select meal type</option>
                        <option value="fish">Fish</option>
                        <option value="chicken">Chicken</option>
                        <option value="other">Other</option>
                        <option value="friday">Friday Special</option>
                    </select>
                </div>

                <!-- Guest Count -->
                <div>
                    <label for="guest_count" class="block text-sm font-medium text-gray-700 mb-2">
                        Guest Count (Optional)
                    </label>
                    <input type="number" 
                           id="guest_count" 
                           name="guest_count" 
                           value="0"
                           min="0"
                           max="10"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="text-sm text-gray-500 mt-1">Number of guests eating with this person</p>
                </div>

                <!-- Submit Button -->
                <div class="pt-4">
                    <button type="submit" 
                            class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Add Meal Entry
                    </button>
                </div>
            </form>
        </div>

        <!-- Quick Info -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h3 class="font-medium text-blue-800 mb-2">How to use:</h3>
            <ul class="text-blue-700 text-sm space-y-1">
                <li>• Select date, person, session (lunch/dinner), and meal type</li>
                <li>• Add guest count if the person had guests</li>
                <li>• Each entry represents ONE meal (lunch or dinner)</li>
                <li>• Friday Special is a separate category</li>
            </ul>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
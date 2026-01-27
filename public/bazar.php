<?php
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
    $bazar_date = $_POST['bazar_date'] ?? '';
    $item_name = $_POST['item_name'] ?? '';
    $category = $_POST['category'] ?? '';
    $amount = $_POST['amount'] ?? '';
    $paid_by = $_POST['paid_by'] ?? '';
    
    // Basic validation
    if (empty($bazar_date) || empty($item_name) || empty($category) || empty($amount) || empty($paid_by)) {
        $message = 'Please fill all required fields';
        $message_type = 'error';
    } elseif (!is_numeric($amount) || $amount <= 0) {
        $message = 'Please enter a valid amount';
        $message_type = 'error';
    } else {
        // Prepare SQL statement with paid_by
        $sql = "INSERT INTO bazar_items (bazar_date, item_name, category, amount, paid_by) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            // Bind parameters (s=string, d=double, i=integer)
            $stmt->bind_param("sssdi", $bazar_date, $item_name, $category, $amount, $paid_by);
            
            // Execute query
            if ($stmt->execute()) {
                $message = 'Bazar item added successfully!';
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bazar Entry - Bachelor Meal System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="max-w-4xl mx-auto p-6">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Bazar Entry</h1>
            <p class="text-gray-600">Enter daily bazar (grocery shopping) items</p>
            <a href="index.php" class="text-blue-600 hover:text-blue-800 text-sm mt-2 inline-block">← Back to Home</a>
        </div>

        <!-- Message Alert -->
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Bazar Entry Form -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <form method="POST" class="space-y-6">
                <!-- Bazar Date -->
                <div>
                    <label for="bazar_date" class="block text-sm font-medium text-gray-700 mb-2">
                        Bazar Date *
                    </label>
                    <input type="date" 
                           id="bazar_date" 
                           name="bazar_date" 
                           value="<?php echo date('Y-m-d'); ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           required>
                </div>

                <!-- Item Name -->
                <div>
                    <label for="item_name" class="block text-sm font-medium text-gray-700 mb-2">
                        Item Name *
                    </label>
                    <input type="text" 
                           id="item_name" 
                           name="item_name" 
                           placeholder="e.g., Chicken, Fish, Rice, Oil, Vegetables"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           required>
                </div>

                <!-- Category -->
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-2">
                        Category *
                    </label>
                    <select id="category" 
                            name="category" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            required>
                        <option value="">Select category</option>
                        <option value="fish">Fish</option>
                        <option value="chicken">Chicken</option>
                        <option value="other">Other</option>
                        <option value="friday">Friday Special</option>
                    </select>
                    <p class="text-sm text-gray-500 mt-1">Category determines which meal category this item belongs to</p>
                </div>

                <!-- Amount -->
                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">
                        Amount (BDT) *
                    </label>
                    <input type="number" 
                           id="amount" 
                           name="amount" 
                           step="0.01"
                           min="0.01"
                           placeholder="0.00"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           required>
                    <p class="text-sm text-gray-500 mt-1">Enter the cost of this item</p>
                </div>

                <!-- Paid By -->
                <div>
                    <label for="paid_by" class="block text-sm font-medium text-gray-700 mb-2">
                        Paid By *
                    </label>
                    <select id="paid_by" 
                            name="paid_by" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            required>
                        <option value="">Select who paid</option>
                        <?php foreach ($persons as $person): ?>
                            <option value="<?php echo $person['id']; ?>">
                                <?php echo htmlspecialchars($person['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-sm text-gray-500 mt-1">Select the person who paid for this bazar item</p>
                </div>

                <!-- Submit Button -->
                <div class="pt-4">
                    <button type="submit" 
                            class="w-full bg-green-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                        Add Bazar Item
                    </button>
                </div>
            </form>
        </div>

        <!-- Category Guide -->
        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Fish Category -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="font-medium text-blue-800 mb-2">Fish Category</h3>
                <ul class="text-blue-700 text-sm space-y-1">
                    <li>• All types of fish</li>
                    <li>• Shrimp, prawn</li>
                    <li>• Other seafood</li>
                </ul>
            </div>

            <!-- Chicken Category -->
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <h3 class="font-medium text-red-800 mb-2">Chicken Category</h3>
                <ul class="text-red-700 text-sm space-y-1">
                    <li>• Chicken meat</li>
                    <li>• Mutton, beef</li>
                    <li>• Other meat items</li>
                </ul>
            </div>

            <!-- Other Category -->
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <h3 class="font-medium text-gray-800 mb-2">Other Category</h3>
                <ul class="text-gray-700 text-sm space-y-1">
                    <li>• Rice, flour</li>
                    <li>• Vegetables</li>
                    <li>• Oil, spices</li>
                    <li>• All non-protein items</li>
                </ul>
            </div>

            <!-- Friday Special -->
            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                <h3 class="font-medium text-purple-800 mb-2">Friday Special</h3>
                <ul class="text-purple-700 text-sm space-y-1">
                    <li>• Special Friday items</li>
                    <li>• Biriyani ingredients</li>
                    <li>• Party/celebration items</li>
                </ul>
            </div>
        </div>

        <!-- Important Note -->
        <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <h3 class="font-medium text-yellow-800 mb-2">Important Note</h3>
            <p class="text-yellow-700 text-sm">
                Each bazar item is assigned to one category. The system will automatically 
                calculate category-wise costs later. Make sure to categorize items correctly 
                for accurate calculation.
            </p>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
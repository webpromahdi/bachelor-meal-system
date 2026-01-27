<?php
require_once '../config/database.php';

// Get monthly statistics
$current_month = date('Y-m');
$year = date('Y');
$month = date('m');

// Count total persons
$person_count = $conn->query("SELECT COUNT(*) as count FROM persons")->fetch_assoc()['count'];

// Get this month's meal count
$meal_sql = "SELECT SUM(1 + guest_count) as total_meals 
             FROM daily_meals 
             WHERE YEAR(meal_date) = ? AND MONTH(meal_date) = ?";
$stmt = $conn->prepare($meal_sql);
$stmt->bind_param("ii", $year, $month);
$stmt->execute();
$meal_result = $stmt->get_result();
$month_meals = $meal_result->fetch_assoc()['total_meals'] ?? 0;
$stmt->close();

// Get this month's bazar total
$bazar_sql = "SELECT SUM(amount) as total_bazar 
              FROM bazar_items 
              WHERE YEAR(bazar_date) = ? AND MONTH(bazar_date) = ?";
$stmt = $conn->prepare($bazar_sql);
$stmt->bind_param("ii", $year, $month);
$stmt->execute();
$bazar_result = $stmt->get_result();
$month_bazar = $bazar_result->fetch_assoc()['total_bazar'] ?? 0;
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Bachelor Meal System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <!-- Navigation Bar -->
    <nav class="bg-blue-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <!-- Logo/Brand -->
                <div class="flex items-center">
                    <div class="text-2xl font-bold">🍽️</div>
                    <div class="ml-2">
                        <span class="font-bold text-lg">Bachelor Meal System</span>
                        <span class="text-sm text-blue-200 block -mt-1">Fair Meal Management</span>
                    </div>
                </div>

                <!-- Navigation Links -->
                <div class="flex space-x-4">
                    <a href="index.php" 
                       class="px-4 py-2 rounded-lg bg-blue-800">
                       📊 Dashboard
                    </a>
                    <a href="meals.php" 
                       class="px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                       🍽️ Meals
                    </a>
                    <a href="bazar.php" 
                       class="px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                       🛒 Bazar
                    </a>
                    <a href="summary.php" 
                       class="px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                       📈 Summary
                    </a>
                </div>

                <!-- Current Month Display -->
                <div class="text-sm bg-blue-800 px-3 py-1 rounded-lg">
                    <?php echo date('F Y'); ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Welcome Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Welcome to Bachelor Meal System</h1>
            <p class="text-gray-600 mt-2">Automated meal expense management for <?php echo $person_count; ?> members</p>
        </div>

        <!-- Stats Dashboard -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Total Members Card -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="text-3xl mr-4">👥</div>
                    <div>
                        <div class="text-2xl font-bold text-gray-800"><?php echo $person_count; ?></div>
                        <div class="text-sm text-gray-500">Total Members</div>
                    </div>
                </div>
            </div>

            <!-- This Month's Meals -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="text-3xl mr-4">🍽️</div>
                    <div>
                        <div class="text-2xl font-bold text-gray-800"><?php echo number_format($month_meals); ?></div>
                        <div class="text-sm text-gray-500">Meals This Month</div>
                    </div>
                </div>
            </div>

            <!-- This Month's Bazar -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="text-3xl mr-4">💰</div>
                    <div>
                        <div class="text-2xl font-bold text-gray-800">BDT <?php echo number_format($month_bazar, 2); ?></div>
                        <div class="text-sm text-gray-500">Bazar This Month</div>
                    </div>
                </div>
            </div>

            <!-- Meal Rate -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="text-3xl mr-4">📊</div>
                    <div>
                        <div class="text-2xl font-bold text-blue-600">
                            <?php 
                            $rate = $month_meals > 0 ? $month_bazar / $month_meals : 0;
                            echo 'BDT ' . number_format($rate, 2); 
                            ?>
                        </div>
                        <div class="text-sm text-gray-500">Average Meal Rate</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Add Meal Card -->
            <a href="meals.php" class="block bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-center">
                    <div class="text-4xl mr-4">➕</div>
                    <div>
                        <h3 class="font-bold text-gray-800 text-lg">Add Today's Meals</h3>
                        <p class="text-gray-600 text-sm mt-1">Record lunch and dinner for all members</p>
                    </div>
                </div>
            </a>

            <!-- Add Bazar Card -->
            <a href="bazar.php" class="block bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-center">
                    <div class="text-4xl mr-4">🛒</div>
                    <div>
                        <h3 class="font-bold text-gray-800 text-lg">Add Bazar Items</h3>
                        <p class="text-gray-600 text-sm mt-1">Record grocery expenses with categories</p>
                    </div>
                </div>
            </a>

            <!-- View Summary Card -->
            <a href="summary.php" class="block bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-center">
                    <div class="text-4xl mr-4">📈</div>
                    <div>
                        <h3 class="font-bold text-gray-800 text-lg">Generate Summary</h3>
                        <p class="text-gray-600 text-sm mt-1">Calculate monthly costs and balances</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- How It Works -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">How It Works</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Step 1 -->
                <div class="text-center p-4">
                    <div class="text-3xl mb-2">1️⃣</div>
                    <h3 class="font-bold text-gray-700">Daily Meal Entry</h3>
                    <p class="text-sm text-gray-600 mt-1">Record each meal with type and guest count</p>
                </div>
                <!-- Step 2 -->
                <div class="text-center p-4">
                    <div class="text-3xl mb-2">2️⃣</div>
                    <h3 class="font-bold text-gray-700">Bazar Tracking</h3>
                    <p class="text-sm text-gray-600 mt-1">Add all grocery expenses with categories</p>
                </div>
                <!-- Step 3 -->
                <div class="text-center p-4">
                    <div class="text-3xl mb-2">3️⃣</div>
                    <h3 class="font-bold text-gray-700">Category Calculation</h3>
                    <p class="text-sm text-gray-600 mt-1">System calculates rates per meal category</p>
                </div>
                <!-- Step 4 -->
                <div class="text-center p-4">
                    <div class="text-3xl mb-2">4️⃣</div>
                    <h3 class="font-bold text-gray-700">Balance Settlement</h3>
                    <p class="text-sm text-gray-600 mt-1">See who owes or receives money</p>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-lg shadow-md p-6 mt-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Quick Start Guide</h2>
            <div class="space-y-4">
                <div class="flex items-start">
                    <div class="bg-blue-100 text-blue-800 rounded-full px-3 py-1 mr-4">1</div>
                    <div>
                        <h4 class="font-medium text-gray-800">Setup Members</h4>
                        <p class="text-gray-600 text-sm">Add all members to the persons table via PHPMyAdmin</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <div class="bg-blue-100 text-blue-800 rounded-full px-3 py-1 mr-4">2</div>
                    <div>
                        <h4 class="font-medium text-gray-800">Daily Routine</h4>
                        <p class="text-gray-600 text-sm">Enter meals after each meal, record bazar when shopping</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <div class="bg-blue-100 text-blue-800 rounded-full px-3 py-1 mr-4">3</div>
                    <div>
                        <h4 class="font-medium text-gray-800">Monthly Summary</h4>
                        <p class="text-gray-600 text-sm">Generate summary at month-end to settle balances</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
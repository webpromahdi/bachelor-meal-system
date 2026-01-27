<?php
require_once '../config/database.php';

// Initialize variables
$selected_month = $_POST['month'] ?? date('Y-m');
$year_month = explode('-', $selected_month);
$year = $year_month[0];
$month = $year_month[1];

$category_data = [];
$person_costs = [];
$persons = [];
$phase2_data = [];
$calculation_done = false;

// Fetch all persons
$person_result = $conn->query("SELECT id, name FROM persons ORDER BY name");
if ($person_result) {
    while ($row = $person_result->fetch_assoc()) {
        $persons[$row['id']] = $row['name'];
        $person_costs[$row['id']] = [
            'name' => $row['name'],
            'fish_meals' => 0,
            'chicken_meals' => 0,
            'other_meals' => 0,
            'friday_meals' => 0,
            'fish_cost' => 0,
            'chicken_cost' => 0,
            'other_cost' => 0,
            'friday_cost' => 0,
            'total_cost' => 0
        ];
    }
    $person_result->free();
}

// Process calculation when form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $calculation_done = true;
    
    // Step 1: Calculate total meals per category for the selected month
    $meal_categories = ['fish', 'chicken', 'other', 'friday'];
    
    foreach ($meal_categories as $category) {
        $category_data[$category] = [
            'total_meals' => 0,
            'total_cost' => 0,
            'meal_rate' => 0
        ];
        
        // Calculate total meals for this category (including guest meals)
        $sql = "SELECT SUM(1 + guest_count) as total_meals 
                FROM daily_meals 
                WHERE meal_type = ? 
                AND YEAR(meal_date) = ? 
                AND MONTH(meal_date) = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $category, $year, $month);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $category_data[$category]['total_meals'] = $row['total_meals'] ?? 0;
        }
        $stmt->close();
        
        // Calculate total bazar cost for this category
        $sql = "SELECT SUM(amount) as total_cost 
                FROM bazar_items 
                WHERE category = ? 
                AND YEAR(bazar_date) = ? 
                AND MONTH(bazar_date) = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $category, $year, $month);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $category_data[$category]['total_cost'] = $row['total_cost'] ?? 0;
        }
        $stmt->close();
        
        // Calculate meal rate for this category
        if ($category_data[$category]['total_meals'] > 0) {
            $category_data[$category]['meal_rate'] = 
                $category_data[$category]['total_cost'] / $category_data[$category]['total_meals'];
        }
    }
    
    // Step 2: Calculate person-wise meals per category
    $sql = "SELECT person_id, meal_type, SUM(1 + guest_count) as meal_count 
            FROM daily_meals 
            WHERE YEAR(meal_date) = ? AND MONTH(meal_date) = ?
            GROUP BY person_id, meal_type";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $year, $month);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $person_id = $row['person_id'];
        $meal_type = $row['meal_type'];
        $meal_count = $row['meal_count'];
        
        // Store meal counts per category
        switch ($meal_type) {
            case 'fish':
                $person_costs[$person_id]['fish_meals'] = $meal_count;
                break;
            case 'chicken':
                $person_costs[$person_id]['chicken_meals'] = $meal_count;
                break;
            case 'other':
                $person_costs[$person_id]['other_meals'] = $meal_count;
                break;
            case 'friday':
                $person_costs[$person_id]['friday_meals'] = $meal_count;
                break;
        }
    }
    $stmt->close();
    
    // Step 3: Calculate person-wise costs (Phase-1)
    foreach ($person_costs as $person_id => &$person) {
        // Fish cost
        $person['fish_cost'] = $person['fish_meals'] * $category_data['fish']['meal_rate'];
        
        // Chicken cost
        $person['chicken_cost'] = $person['chicken_meals'] * $category_data['chicken']['meal_rate'];
        
        // Other cost
        $person['other_cost'] = $person['other_meals'] * $category_data['other']['meal_rate'];
        
        // Friday cost
        $person['friday_cost'] = $person['friday_meals'] * $category_data['friday']['meal_rate'];
        
        // Total cost (Phase-1 method)
        $person['total_cost'] = $person['fish_cost'] + $person['chicken_cost'] + 
                                $person['other_cost'] + $person['friday_cost'];
        
        // Format numbers to 2 decimal places for Phase-1 display
        $person['fish_cost_display'] = number_format($person['fish_cost'], 2);
        $person['chicken_cost_display'] = number_format($person['chicken_cost'], 2);
        $person['other_cost_display'] = number_format($person['other_cost'], 2);
        $person['friday_cost_display'] = number_format($person['friday_cost'], 2);
        $person['total_cost_display'] = number_format($person['total_cost'], 2);
    }
    
    // Format category data for display
    foreach ($category_data as $category => &$data) {
        $data['total_meals_display'] = number_format($data['total_meals'], 2);
        $data['total_cost_display'] = number_format($data['total_cost'], 2);
        $data['meal_rate_display'] = number_format($data['meal_rate'], 2);
    }
    
    // ==============================================
    // PHASE-2: Excel-Style Demo Calculation
    // ==============================================
    
    // Step 1: Calculate total meals across all categories
    $total_meals_all_categories = 0;
    $total_cost_all_categories = 0;
    
    foreach ($category_data as $category => $data) {
        $total_meals_all_categories += $data['total_meals'];
        $total_cost_all_categories += $data['total_cost'];
    }
    
    // Step 2: Calculate single meal rate (demo logic)
    $single_meal_rate = 0;
    if ($total_meals_all_categories > 0) {
        $single_meal_rate = $total_cost_all_categories / $total_meals_all_categories;
    }
    
    // Step 3: Calculate total meals per person (for Phase-2)
    foreach ($person_costs as $person_id => &$person) {
        // Total meals per person (sum of all categories)
        $person['total_meals_phase2'] = $person['fish_meals'] + $person['chicken_meals'] + 
                                        $person['other_meals'] + $person['friday_meals'];
        
        // Person's total cost using single meal rate (demo logic)
        $person['total_cost_phase2'] = $person['total_meals_phase2'] * $single_meal_rate;
    }
    
    // Step 4: Calculate paid amount per person (from bazar_items)
    $paid_amounts = [];
    
    // Assume payer name in bazar_items matches person name
    $sql = "SELECT bi.payer_name, SUM(bi.amount) as total_paid 
            FROM bazar_items bi
            WHERE YEAR(bi.bazar_date) = ? AND MONTH(bi.bazar_date) = ?
            AND bi.payer_name IS NOT NULL
            GROUP BY bi.payer_name";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $year, $month);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $paid_amounts[$row['payer_name']] = $row['total_paid'];
    }
    $stmt->close();
    
    // Step 5: Prepare Phase-2 data array
    $phase2_data['single_meal_rate'] = $single_meal_rate;
    $phase2_data['persons'] = [];
    
    foreach ($person_costs as $person_id => $person) {
        $person_name = $person['name'];
        $paid_amount = $paid_amounts[$person_name] ?? 0;
        $balance = $paid_amount - $person['total_cost_phase2'];
        
        $phase2_data['persons'][] = [
            'name' => $person_name,
            'total_meals' => $person['total_meals_phase2'],
            'meal_rate' => $single_meal_rate,
            'total_cost' => $person['total_cost_phase2'],
            'paid_amount' => $paid_amount,
            'balance' => $balance,
            'balance_status' => $balance >= 0 ? 'receive' : 'owe'
        ];
    }
    
    // Format Phase-2 numbers for display
    $phase2_data['single_meal_rate_display'] = number_format($single_meal_rate, 2);
    
    foreach ($phase2_data['persons'] as &$person) {
        $person['total_meals_display'] = number_format($person['total_meals'], 2);
        $person['total_cost_display'] = number_format($person['total_cost'], 2);
        $person['paid_amount_display'] = number_format($person['paid_amount'], 2);
        $person['balance_display'] = number_format(abs($person['balance']), 2);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Summary - Bachelor Meal System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .receive { color: #059669; }
        .owe { color: #DC2626; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="max-w-7xl mx-auto p-6">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Monthly Summary</h1>
            <p class="text-gray-600">Phase-1 (Category-wise) & Phase-2 (Excel-Style) Calculation</p>
            <a href="index.php" class="text-blue-600 hover:text-blue-800 text-sm mt-2 inline-block">← Back to Home</a>
        </div>

        <!-- Month Selection Form -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <form method="POST" class="flex items-end space-x-4">
                <div class="flex-1">
                    <label for="month" class="block text-sm font-medium text-gray-700 mb-2">
                        Select Month for Calculation
                    </label>
                    <input type="month" 
                           id="month" 
                           name="month" 
                           value="<?php echo htmlspecialchars($selected_month); ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           required>
                </div>
                <div>
                    <button type="submit" 
                            class="bg-blue-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Calculate
                    </button>
                </div>
            </form>
        </div>

        <?php if ($calculation_done): ?>
            <!-- Category Summary Section -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Phase-1: Category-wise Calculation</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <?php foreach ($category_data as $category => $data): ?>
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <h3 class="font-bold text-gray-800 text-lg mb-3 capitalize"><?php echo $category; ?></h3>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Total Meals:</span>
                                    <span class="font-medium"><?php echo $data['total_meals_display']; ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Total Cost:</span>
                                    <span class="font-medium">BDT <?php echo $data['total_cost_display']; ?></span>
                                </div>
                                <div class="flex justify-between border-t border-gray-200 pt-2">
                                    <span class="text-gray-600 font-bold">Meal Rate:</span>
                                    <span class="font-bold text-blue-600">BDT <?php echo $data['meal_rate_display']; ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Formula Explanation -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h4 class="font-medium text-blue-800 mb-2">Phase-1 Formula (Actual Fair Calculation):</h4>
                    <p class="text-blue-700 text-sm">
                        <strong>Category Meal Rate</strong> = Category Total Cost ÷ Category Total Meals<br>
                        <strong>Person's Category Cost</strong> = Person's Category Meals × Category Meal Rate<br>
                        <strong>Total Cost</strong> = Sum of all category costs
                    </p>
                </div>
            </div>

            <!-- Person-wise Cost Table (Phase-1) -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-2xl font-bold text-gray-800">Phase-1: Person-wise Cost Distribution</h2>
                    <p class="text-gray-600 text-sm">For <?php echo date('F Y', strtotime($selected_month . '-01')); ?></p>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Person
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Fish Cost
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Chicken Cost
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Other Cost
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Friday Cost
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-blue-50">
                                    Total Cost
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($person_costs as $person): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-medium text-gray-900"><?php echo htmlspecialchars($person['name']); ?></div>
                                        <div class="text-sm text-gray-500">
                                            Meals: F<?php echo $person['fish_meals']; ?> | 
                                            C<?php echo $person['chicken_meals']; ?> | 
                                            O<?php echo $person['other_meals']; ?> | 
                                            Fr<?php echo $person['friday_meals']; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-700">
                                        BDT <?php echo $person['fish_cost_display']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-700">
                                        BDT <?php echo $person['chicken_cost_display']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-700">
                                        BDT <?php echo $person['other_cost_display']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-700">
                                        BDT <?php echo $person['friday_cost_display']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap font-bold text-blue-600 bg-blue-50">
                                        BDT <?php echo $person['total_cost_display']; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <!-- Totals Row -->
                        <tfoot class="bg-gray-800 text-white">
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap font-bold">Category Totals</td>
                                <td class="px-6 py-4 whitespace-nowrap font-bold">
                                    BDT <?php echo number_format(array_sum(array_column($person_costs, 'fish_cost')), 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-bold">
                                    BDT <?php echo number_format(array_sum(array_column($person_costs, 'chicken_cost')), 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-bold">
                                    BDT <?php echo number_format(array_sum(array_column($person_costs, 'other_cost')), 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-bold">
                                    BDT <?php echo number_format(array_sum(array_column($person_costs, 'friday_cost')), 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-bold bg-blue-700">
                                    BDT <?php echo number_format(array_sum(array_column($person_costs, 'total_cost')), 2); ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- ==============================================
                 PHASE-2: Excel-Style Demo View
                 ============================================== -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
                <div class="px-6 py-4 border-b border-gray-200 bg-green-50">
                    <h2 class="text-2xl font-bold text-gray-800">Phase-2: Excel-Style Summary (Demo View)</h2>
                    <p class="text-gray-600 text-sm">Matching the Excel demo sheets - Simple single meal rate calculation</p>
                </div>
                
                <!-- Phase-2 Summary Stats -->
                <div class="px-6 py-4 bg-green-50 border-b border-green-200">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-white p-4 rounded-lg border border-green-200">
                            <div class="text-sm text-gray-500">Total All Meals</div>
                            <div class="text-2xl font-bold text-gray-800"><?php echo number_format($total_meals_all_categories, 2); ?></div>
                        </div>
                        <div class="bg-white p-4 rounded-lg border border-green-200">
                            <div class="text-sm text-gray-500">Total All Bazar</div>
                            <div class="text-2xl font-bold text-gray-800">BDT <?php echo number_format($total_cost_all_categories, 2); ?></div>
                        </div>
                        <div class="bg-white p-4 rounded-lg border border-green-200">
                            <div class="text-sm text-gray-500">Single Meal Rate (Demo)</div>
                            <div class="text-2xl font-bold text-green-600">BDT <?php echo $phase2_data['single_meal_rate_display']; ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Phase-2 Formula -->
                <div class="px-6 py-4 bg-green-50 border-b border-green-200">
                    <h4 class="font-medium text-green-800 mb-2">Phase-2 Formula (Excel-Style Demo):</h4>
                    <p class="text-green-700 text-sm">
                        <strong>Single Meal Rate</strong> = Total Bazar (All Categories) ÷ Total Meals (All Categories)<br>
                        <strong>Person's Total Cost</strong> = Person's Total Meals × Single Meal Rate<br>
                        <strong>Balance</strong> = Total Bazar Paid − Person's Total Cost
                    </p>
                </div>
                
                <!-- Phase-2 Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-green-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Person
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Total Meals
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Meal Rate
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Total Cost
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Paid Amount
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Balance (Owed / Receive)
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($phase2_data['persons'] as $person): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-medium text-gray-900"><?php echo htmlspecialchars($person['name']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-700 text-center">
                                        <?php echo $person['total_meals_display']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-700 text-center">
                                        BDT <?php echo $phase2_data['single_meal_rate_display']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-700 text-center">
                                        BDT <?php echo $person['total_cost_display']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-700 text-center">
                                        BDT <?php echo $person['paid_amount_display']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <?php if ($person['balance_status'] === 'receive'): ?>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                                Receive: BDT <?php echo $person['balance_display']; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                                Owe: BDT <?php echo $person['balance_display']; ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-green-800 text-white">
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap font-bold">Grand Totals</td>
                                <td class="px-6 py-4 whitespace-nowrap font-bold text-center">
                                    <?php echo number_format(array_sum(array_column($phase2_data['persons'], 'total_meals')), 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-bold text-center">
                                    BDT <?php echo $phase2_data['single_meal_rate_display']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-bold text-center">
                                    BDT <?php echo number_format(array_sum(array_column($phase2_data['persons'], 'total_cost')), 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-bold text-center">
                                    BDT <?php echo number_format(array_sum(array_column($phase2_data['persons'], 'paid_amount')), 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-bold text-center">
                                    <?php 
                                    $total_balance = array_sum(array_column($phase2_data['persons'], 'balance'));
                                    if ($total_balance >= 0): ?>
                                        <span class="text-green-300">System Balance: BDT <?php echo number_format($total_balance, 2); ?></span>
                                    <?php else: ?>
                                        <span class="text-red-300">System Balance: BDT <?php echo number_format(abs($total_balance), 2); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <!-- Phase-2 Notes -->
                <div class="px-6 py-4 bg-yellow-50 border-t border-yellow-200">
                    <h4 class="font-medium text-yellow-800 mb-2">Important Notes:</h4>
                    <ul class="text-yellow-700 text-sm space-y-1">
                        <li>• <strong>Phase-1</strong> is the actual fair calculation (category-wise rates)</li>
                        <li>• <strong>Phase-2</strong> is the demo view matching Excel sheets (single meal rate)</li>
                        <li>• Paid amounts are based on bazar_items.payer_name matching person names</li>
                        <li>• Positive balance = Will receive money | Negative balance = Owes money</li>
                        <li>• In a perfect system, Total Paid should equal Total Cost (zero system balance)</li>
                    </ul>
                </div>
            </div>
            
        <?php else: ?>
            <!-- Initial State -->
            <div class="bg-white rounded-lg shadow-md p-8 text-center">
                <div class="max-w-md mx-auto">
                    <div class="text-5xl mb-4">📊</div>
                    <h3 class="text-xl font-medium text-gray-700 mb-2">Ready to Calculate</h3>
                    <p class="text-gray-600 mb-6">
                        Select a month and click "Calculate" to see both Phase-1 and Phase-2 calculations.
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-left">
                            <h4 class="font-medium text-blue-800 mb-2">Phase-1 (Actual):</h4>
                            <ul class="text-blue-700 text-sm space-y-1">
                                <li>• Category-wise fair calculation</li>
                                <li>• Different rates for fish/chicken/other/friday</li>
                                <li>• Actual consumption-based cost</li>
                            </ul>
                        </div>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-left">
                            <h4 class="font-medium text-green-800 mb-2">Phase-2 (Demo):</h4>
                            <ul class="text-green-700 text-sm space-y-1">
                                <li>• Excel-style single meal rate</li>
                                <li>• Balance calculation (owed/receive)</li>
                                <li>• Matches provided demo sheets</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php $conn->close(); ?>
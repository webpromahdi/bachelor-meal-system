<?php
require_once '../config/database.php';

// Initialize variables
$selected_month = $_POST['month'] ?? date('Y-m');
$active_tab = $_POST['tab'] ?? 'daily';
$year_month = explode('-', $selected_month);
$year = $year_month[0];
$month = $year_month[1];

// Data containers
$daily_data = [];
$monthly_summary = [];
$cost_distribution = [];
$final_balance = [];
$calculation_done = false;

// Process calculation when form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $calculation_done = true;
    
    // ============================================
    // EXCEL SHEET-2: DAILY MEAL LOG (RAW INPUT)
    // ============================================
    $sql = "SELECT 
                dm.meal_date,
                p.name,
                MAX(CASE WHEN dm.session = 'lunch' THEN dm.meal_type END) as day_meal,
                MAX(CASE WHEN dm.session = 'dinner' THEN dm.meal_type END) as night_meal,
                SUM(CASE WHEN dm.session = 'lunch' THEN dm.guest_count ELSE 0 END) as day_guests,
                SUM(CASE WHEN dm.session = 'dinner' THEN dm.guest_count ELSE 0 END) as night_guests
            FROM daily_meals dm
            JOIN persons p ON dm.person_id = p.id
            WHERE YEAR(dm.meal_date) = ? AND MONTH(dm.meal_date) = ?
            GROUP BY dm.meal_date, p.id
            ORDER BY dm.meal_date DESC, p.name";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $year, $month);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $daily_data[] = $row;
    }
    $stmt->close();
    
    // ============================================
    // EXCEL SHEET-3: MONTHLY MEAL SUMMARY (AGGREGATION)
    // ============================================
    $sql = "SELECT 
                p.id,
                p.name,
                SUM(CASE WHEN dm.meal_type = 'fish' THEN (1 + dm.guest_count) ELSE 0 END) as fish_meals,
                SUM(CASE WHEN dm.meal_type = 'chicken' THEN (1 + dm.guest_count) ELSE 0 END) as chicken_meals,
                SUM(CASE WHEN dm.meal_type = 'other' THEN (1 + dm.guest_count) ELSE 0 END) as other_meals,
                SUM(CASE WHEN dm.meal_type = 'friday' THEN (1 + dm.guest_count) ELSE 0 END) as friday_meals,
                SUM(1 + dm.guest_count) as total_meals
            FROM daily_meals dm
            JOIN persons p ON dm.person_id = p.id
            WHERE YEAR(dm.meal_date) = ? AND MONTH(dm.meal_date) = ?
            GROUP BY p.id, p.name
            ORDER BY p.name";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $year, $month);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $monthly_summary[] = $row;
    }
    $stmt->close();
    
    // ============================================
    // EXCEL SHEET-1: MEAL COST DISTRIBUTION (COST ENGINE)
    // ============================================
    // Step 1: Person-wise aggregation with session separation
    $person_aggregation = [];
    $sql = "SELECT 
                dm.person_id,
                p.name,
                dm.meal_type,
                dm.session,
                SUM(1 + dm.guest_count) as meal_count
            FROM daily_meals dm
            JOIN persons p ON dm.person_id = p.id
            WHERE YEAR(dm.meal_date) = ? AND MONTH(dm.meal_date) = ?
            GROUP BY dm.person_id, dm.meal_type, dm.session
            ORDER BY p.name, dm.meal_type, dm.session";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $year, $month);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $person_id = $row['person_id'];
        $name = $row['name'];
        $meal_type = $row['meal_type'];
        $session = $row['session'];
        $meal_count = $row['meal_count'];
        
        if (!isset($person_aggregation[$person_id])) {
            $person_aggregation[$person_id] = [
                'name' => $name,
                'fish' => ['lunch' => 0, 'dinner' => 0],
                'chicken' => ['lunch' => 0, 'dinner' => 0],
                'other' => ['lunch' => 0, 'dinner' => 0],
                'friday' => ['lunch' => 0, 'dinner' => 0]
            ];
        }
        
        $person_aggregation[$person_id][$meal_type][$session] = $meal_count;
    }
    $stmt->close();
    
    // Step 2: Calculate category totals from person aggregation
    $category_totals = [
        'fish' => ['lunch' => 0, 'dinner' => 0, 'total' => 0],
        'chicken' => ['lunch' => 0, 'dinner' => 0, 'total' => 0],
        'other' => ['lunch' => 0, 'dinner' => 0, 'total' => 0],
        'friday' => ['lunch' => 0, 'dinner' => 0, 'total' => 0]
    ];
    
    foreach ($person_aggregation as $person_data) {
        foreach (['fish', 'chicken', 'other', 'friday'] as $category) {
            $category_totals[$category]['lunch'] += $person_data[$category]['lunch'];
            $category_totals[$category]['dinner'] += $person_data[$category]['dinner'];
            $category_totals[$category]['total'] += $person_data[$category]['lunch'] + $person_data[$category]['dinner'];
        }
    }
    
    // Step 3: Get category costs from bazar
    $category_costs = [
        'fish' => 0,
        'chicken' => 0,
        'other' => 0,
        'friday' => 0
    ];
    
    $sql = "SELECT category, SUM(amount) as total_cost 
            FROM bazar_items 
            WHERE YEAR(bazar_date) = ? AND MONTH(bazar_date) = ?
            GROUP BY category";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $year, $month);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $category_costs[$row['category']] = $row['total_cost'];
    }
    $stmt->close();
    
    // Step 4: Calculate category rates (Excel formula)
    $category_rates = [
        'fish' => 0,
        'chicken' => 0,
        'other' => 0,
        'friday' => 0
    ];
    
    foreach (['fish', 'chicken', 'other', 'friday'] as $category) {
        if ($category_totals[$category]['total'] > 0) {
            $category_rates[$category] = $category_costs[$category] / $category_totals[$category]['total'];
        }
    }
    
    // Step 5: Distribute costs person-wise (day/night separately)
    $cost_distribution = [
        'category_totals' => $category_totals,
        'category_costs' => $category_costs,
        'category_rates' => $category_rates,
        'persons' => []
    ];
    
    foreach ($person_aggregation as $person_id => $person_data) {
        $person_cost = [
            'name' => $person_data['name'],
            'fish_cost' => 0,
            'chicken_cost' => 0,
            'other_cost' => 0,
            'friday_cost' => 0,
            'total_cost' => 0
        ];
        
        foreach (['fish', 'chicken', 'other', 'friday'] as $category) {
            // Excel logic: Day cost + Night cost separately
            $day_cost = $person_data[$category]['lunch'] * $category_rates[$category];
            $night_cost = $person_data[$category]['dinner'] * $category_rates[$category];
            $category_cost = $day_cost + $night_cost;
            
            $person_cost[$category . '_cost'] = $category_cost;
            $person_cost['total_cost'] += $category_cost;
        }
        
        $cost_distribution['persons'][$person_id] = $person_cost;
    }
    
    // ============================================
    // EXCEL SHEET-4: FINAL BALANCE SHEET
    // ============================================
    // Step 1: Calculate total meals across all categories
    $total_meals_all = 0;
    $total_bazar_all = 0;
    
    foreach (['fish', 'chicken', 'other', 'friday'] as $category) {
        $total_meals_all += $category_totals[$category]['total'];
        $total_bazar_all += $category_costs[$category];
    }
    
    // Step 2: Single meal rate (Excel Sheet-4 formula)
    $single_meal_rate = 0;
    if ($total_meals_all > 0) {
        $single_meal_rate = $total_bazar_all / $total_meals_all;
    }
    
    // Step 3: Get person paid amounts
    $person_paid = [];
    $sql = "SELECT paid_by, SUM(amount) as total_paid 
            FROM bazar_items 
            WHERE YEAR(bazar_date) = ? AND MONTH(bazar_date) = ? 
            AND paid_by IS NOT NULL
            GROUP BY paid_by";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $year, $month);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $person_paid[$row['paid_by']] = $row['total_paid'];
    }
    $stmt->close();
    
    // Step 4: Prepare final balance data
    $final_balance = [
        'single_meal_rate' => $single_meal_rate,
        'total_meals_all' => $total_meals_all,
        'total_bazar_all' => $total_bazar_all,
        'persons' => []
    ];
    
    foreach ($person_aggregation as $person_id => $person_data) {
        // Calculate person's total meals
        $person_total_meals = 0;
        foreach (['fish', 'chicken', 'other', 'friday'] as $category) {
            $person_total_meals += $person_data[$category]['lunch'] + $person_data[$category]['dinner'];
        }
        
        // Person's cost using single rate
        $person_cost = $person_total_meals * $single_meal_rate;
        
        // Paid amount
        $paid_amount = $person_paid[$person_id] ?? 0;
        
        // Balance calculation
        $balance = $paid_amount - $person_cost;
        
        $final_balance['persons'][$person_id] = [
            'name' => $person_data['name'],
            'total_meals' => $person_total_meals,
            'total_cost' => $person_cost,
            'paid_amount' => $paid_amount,
            'balance' => $balance,
            'balance_status' => $balance >= 0 ? 'receive' : 'owe'
        ];
    }
    
    // Format numbers for display
    foreach ($cost_distribution['persons'] as &$person) {
        $person['fish_cost_display'] = number_format($person['fish_cost'], 2);
        $person['chicken_cost_display'] = number_format($person['chicken_cost'], 2);
        $person['other_cost_display'] = number_format($person['other_cost'], 2);
        $person['friday_cost_display'] = number_format($person['friday_cost'], 2);
        $person['total_cost_display'] = number_format($person['total_cost'], 2);
    }
    
    foreach ($final_balance['persons'] as &$person) {
        $person['total_meals_display'] = number_format($person['total_meals'], 2);
        $person['total_cost_display'] = number_format($person['total_cost'], 2);
        $person['paid_amount_display'] = number_format($person['paid_amount'], 2);
        $person['balance_display'] = number_format(abs($person['balance']), 2);
    }
    
    $final_balance['single_meal_rate_display'] = number_format($single_meal_rate, 2);
    $final_balance['total_bazar_all_display'] = number_format($total_bazar_all, 2);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bachelor Meal System - Excel Sheets</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .excel-tab.active {
            background-color: #1e40af;
            color: white;
            border-bottom: 2px solid #1e40af;
        }
        .excel-tab:not(.active) {
            background-color: #f3f4f6;
            color: #4b5563;
            border-bottom: 1px solid #d1d5db;
        }
        .excel-cell {
            border: 1px solid #d1d5db;
            padding: 4px 8px;
            font-size: 14px;
        }
        .excel-header {
            background-color: #e5e7eb;
            font-weight: 600;
            border: 1px solid #d1d5db;
            padding: 8px 12px;
        }
        .receive { color: #059669; }
        .owe { color: #DC2626; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="max-w-7xl mx-auto p-4">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Bachelor Meal System - Excel Sheets</h1>
            <p class="text-gray-600">Monthly calculation for <?php echo date('F Y', strtotime($selected_month . '-01')); ?></p>
            <a href="index.php" class="text-blue-600 hover:text-blue-800 text-sm mt-1 inline-block">← Back to Dashboard</a>
        </div>

        <!-- Month Selector Form -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-300 mb-6 p-4">
            <form method="POST" class="flex flex-wrap items-end gap-4">
                <input type="hidden" name="tab" value="<?php echo htmlspecialchars($active_tab); ?>">
                
                <div class="flex-1 min-w-[200px]">
                    <label for="month" class="block text-sm font-medium text-gray-700 mb-2">
                        Select Month
                    </label>
                    <input type="month" 
                           id="month" 
                           name="month" 
                           value="<?php echo htmlspecialchars($selected_month); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           required>
                </div>
                
                <div>
                    <button type="submit" 
                            class="bg-blue-600 text-white px-5 py-2 rounded-md font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Load Data
                    </button>
                </div>
            </form>
        </div>

        <!-- Excel Tabs Navigation -->
        <div class="mb-6">
            <div class="flex space-x-1 border-b border-gray-300">
                <!-- Tab 1: Daily Meal Log -->
                <form method="POST" class="inline-block">
                    <input type="hidden" name="month" value="<?php echo htmlspecialchars($selected_month); ?>">
                    <input type="hidden" name="tab" value="daily">
                    <button type="submit" 
                            class="excel-tab px-5 py-3 font-medium rounded-t-md <?php echo $active_tab == 'daily' ? 'active' : ''; ?>">
                        1. Daily Meal Log
                    </button>
                </form>
                
                <!-- Tab 2: Monthly Summary -->
                <form method="POST" class="inline-block">
                    <input type="hidden" name="month" value="<?php echo htmlspecialchars($selected_month); ?>">
                    <input type="hidden" name="tab" value="monthly">
                    <button type="submit" 
                            class="excel-tab px-5 py-3 font-medium rounded-t-md <?php echo $active_tab == 'monthly' ? 'active' : ''; ?>">
                        2. Monthly Meal Summary
                    </button>
                </form>
                
                <!-- Tab 3: Cost Distribution -->
                <form method="POST" class="inline-block">
                    <input type="hidden" name="month" value="<?php echo htmlspecialchars($selected_month); ?>">
                    <input type="hidden" name="tab" value="cost">
                    <button type="submit" 
                            class="excel-tab px-5 py-3 font-medium rounded-t-md <?php echo $active_tab == 'cost' ? 'active' : ''; ?>">
                        3. Meal Cost Distribution
                    </button>
                </form>
                
                <!-- Tab 4: Final Balance -->
                <form method="POST" class="inline-block">
                    <input type="hidden" name="month" value="<?php echo htmlspecialchars($selected_month); ?>">
                    <input type="hidden" name="tab" value="balance">
                    <button type="submit" 
                            class="excel-tab px-5 py-3 font-medium rounded-t-md <?php echo $active_tab == 'balance' ? 'active' : ''; ?>">
                        4. Final Balance Sheet
                    </button>
                </form>
            </div>
        </div>

        <?php if ($calculation_done): ?>
            <!-- Tab 1 Content: Daily Meal Log (Excel Sheet-2) -->
            <?php if ($active_tab == 'daily'): ?>
                <div class="bg-white rounded-lg shadow-sm border border-gray-300 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-300 bg-gray-50">
                        <h2 class="text-lg font-bold text-gray-800">Excel Sheet-2: Daily Meal Log (Raw Input)</h2>
                        <p class="text-sm text-gray-600">Daily meal entries grouped by date and person</p>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="excel-header">Date</th>
                                    <th class="excel-header">Name</th>
                                    <th class="excel-header">Day Meal Type</th>
                                    <th class="excel-header">Day Guests</th>
                                    <th class="excel-header">Night Meal Type</th>
                                    <th class="excel-header">Night Guests</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($daily_data)): ?>
                                    <tr>
                                        <td colspan="6" class="excel-cell text-center text-gray-500 py-8">
                                            No daily meal data found for <?php echo date('F Y', strtotime($selected_month . '-01')); ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($daily_data as $row): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="excel-cell"><?php echo date('d/m/Y', strtotime($row['meal_date'])); ?></td>
                                            <td class="excel-cell font-medium"><?php echo htmlspecialchars($row['name']); ?></td>
                                            <td class="excel-cell text-center">
                                                <?php if (!empty($row['day_meal'])): ?>
                                                    <span class="inline-block px-2 py-1 text-xs rounded-full 
                                                        <?php echo $row['day_meal'] == 'fish' ? 'bg-blue-100 text-blue-800' : ''; ?>
                                                        <?php echo $row['day_meal'] == 'chicken' ? 'bg-red-100 text-red-800' : ''; ?>
                                                        <?php echo $row['day_meal'] == 'other' ? 'bg-gray-100 text-gray-800' : ''; ?>
                                                        <?php echo $row['day_meal'] == 'friday' ? 'bg-purple-100 text-purple-800' : ''; ?>">
                                                        <?php echo ucfirst($row['day_meal']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-gray-400">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="excel-cell text-center"><?php echo $row['day_guests'] ?: '0'; ?></td>
                                            <td class="excel-cell text-center">
                                                <?php if (!empty($row['night_meal'])): ?>
                                                    <span class="inline-block px-2 py-1 text-xs rounded-full 
                                                        <?php echo $row['night_meal'] == 'fish' ? 'bg-blue-100 text-blue-800' : ''; ?>
                                                        <?php echo $row['night_meal'] == 'chicken' ? 'bg-red-100 text-red-800' : ''; ?>
                                                        <?php echo $row['night_meal'] == 'other' ? 'bg-gray-100 text-gray-800' : ''; ?>
                                                        <?php echo $row['night_meal'] == 'friday' ? 'bg-purple-100 text-purple-800' : ''; ?>">
                                                        <?php echo ucfirst($row['night_meal']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-gray-400">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="excel-cell text-center"><?php echo $row['night_guests'] ?: '0'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="px-6 py-4 border-t border-gray-300 bg-gray-50 text-sm text-gray-600">
                        <strong>Excel Sheet-2 Purpose:</strong> Raw daily input showing each person's meal type for day (lunch) and night (dinner).
                        Guest meals are counted separately for accurate aggregation in Sheet-3.
                    </div>
                </div>
            <?php endif; ?>

            <!-- Tab 2 Content: Monthly Meal Summary (Excel Sheet-3) -->
            <?php if ($active_tab == 'monthly'): ?>
                <div class="bg-white rounded-lg shadow-sm border border-gray-300 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-300 bg-gray-50">
                        <h2 class="text-lg font-bold text-gray-800">Excel Sheet-3: Monthly Meal Summary (Aggregation)</h2>
                        <p class="text-sm text-gray-600">Person-wise monthly meal count by category</p>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="excel-header">Name</th>
                                    <th class="excel-header bg-blue-50">Mach (Fish)</th>
                                    <th class="excel-header bg-red-50">Murgi (Chicken)</th>
                                    <th class="excel-header bg-gray-50">Other</th>
                                    <th class="excel-header bg-purple-50">Dim (Friday)</th>
                                    <th class="excel-header bg-green-50">Total Meal</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($monthly_summary)): ?>
                                    <tr>
                                        <td colspan="6" class="excel-cell text-center text-gray-500 py-8">
                                            No monthly summary data found
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($monthly_summary as $row): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="excel-cell font-medium"><?php echo htmlspecialchars($row['name']); ?></td>
                                            <td class="excel-cell text-center bg-blue-50"><?php echo $row['fish_meals']; ?></td>
                                            <td class="excel-cell text-center bg-red-50"><?php echo $row['chicken_meals']; ?></td>
                                            <td class="excel-cell text-center bg-gray-50"><?php echo $row['other_meals']; ?></td>
                                            <td class="excel-cell text-center bg-purple-50"><?php echo $row['friday_meals']; ?></td>
                                            <td class="excel-cell text-center font-bold bg-green-50"><?php echo $row['total_meals']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <?php if (!empty($monthly_summary)): ?>
                                <tfoot class="bg-gray-800 text-white">
                                    <tr>
                                        <td class="excel-header">Totals</td>
                                        <td class="excel-cell text-center bg-blue-900">
                                            <?php echo array_sum(array_column($monthly_summary, 'fish_meals')); ?>
                                        </td>
                                        <td class="excel-cell text-center bg-red-900">
                                            <?php echo array_sum(array_column($monthly_summary, 'chicken_meals')); ?>
                                        </td>
                                        <td class="excel-cell text-center bg-gray-900">
                                            <?php echo array_sum(array_column($monthly_summary, 'other_meals')); ?>
                                        </td>
                                        <td class="excel-cell text-center bg-purple-900">
                                            <?php echo array_sum(array_column($monthly_summary, 'friday_meals')); ?>
                                        </td>
                                        <td class="excel-cell text-center font-bold bg-green-900">
                                            <?php echo array_sum(array_column($monthly_summary, 'total_meals')); ?>
                                        </td>
                                    </tr>
                                </tfoot>
                            <?php endif; ?>
                        </table>
                    </div>
                    
                    <div class="px-6 py-4 border-t border-gray-300 bg-gray-50 text-sm text-gray-600">
                        <strong>Excel Sheet-3 Purpose:</strong> Aggregates daily meals (Sheet-2) into monthly person-wise totals.
                        Each cell = SUM of (1 + guest_count) for that person in that category. This is the input for Sheet-1 calculation.
                    </div>
                </div>
            <?php endif; ?>

            <!-- Tab 3 Content: Meal Cost Distribution (Excel Sheet-1) -->
            <?php if ($active_tab == 'cost'): ?>
                <div class="bg-white rounded-lg shadow-sm border border-gray-300 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-300 bg-gray-50">
                        <h2 class="text-lg font-bold text-gray-800">Excel Sheet-1: Meal Cost Distribution (Cost Engine)</h2>
                        <p class="text-sm text-gray-600">Category-wise cost calculation with day/night separation</p>
                    </div>
                    
                    <!-- Category Summary -->
                    <div class="px-6 py-4 border-b border-gray-300">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                            <?php foreach (['fish', 'chicken', 'other', 'friday'] as $category): ?>
                                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                                    <h3 class="font-bold text-gray-800 text-lg mb-3 capitalize"><?php echo $category; ?></h3>
                                    <div class="space-y-2">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Total Meals:</span>
                                            <span class="font-medium"><?php echo number_format($cost_distribution['category_totals'][$category]['total'], 2); ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Total Cost:</span>
                                            <span class="font-medium">BDT <?php echo number_format($cost_distribution['category_costs'][$category], 2); ?></span>
                                        </div>
                                        <div class="flex justify-between border-t border-gray-200 pt-2">
                                            <span class="text-gray-600 font-bold">Meal Rate:</span>
                                            <span class="font-bold text-blue-600">BDT <?php echo number_format($cost_distribution['category_rates'][$category], 2); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <h4 class="font-medium text-blue-800 mb-2">Excel Formula (Sheet-1):</h4>
                            <p class="text-blue-700 text-sm">
                                <strong>Category Meal Rate</strong> = Category Total Bazar ÷ Category Total Meals<br>
                                <strong>Person's Category Cost</strong> = (Day Meals × Rate) + (Night Meals × Rate)<br>
                                <strong>Total Cost</strong> = Sum of all category costs
                            </p>
                        </div>
                    </div>
                    
                    <!-- Person-wise Cost Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="excel-header">Name</th>
                                    <th class="excel-header bg-blue-50">Fish Cost</th>
                                    <th class="excel-header bg-red-50">Chicken Cost</th>
                                    <th class="excel-header bg-gray-50">Other Cost</th>
                                    <th class="excel-header bg-purple-50">Friday Cost</th>
                                    <th class="excel-header bg-green-50">Total Cost</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($cost_distribution['persons'])): ?>
                                    <tr>
                                        <td colspan="6" class="excel-cell text-center text-gray-500 py-8">
                                            No cost distribution data available
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($cost_distribution['persons'] as $person): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="excel-cell font-medium"><?php echo htmlspecialchars($person['name']); ?></td>
                                            <td class="excel-cell text-center bg-blue-50">BDT <?php echo $person['fish_cost_display']; ?></td>
                                            <td class="excel-cell text-center bg-red-50">BDT <?php echo $person['chicken_cost_display']; ?></td>
                                            <td class="excel-cell text-center bg-gray-50">BDT <?php echo $person['other_cost_display']; ?></td>
                                            <td class="excel-cell text-center bg-purple-50">BDT <?php echo $person['friday_cost_display']; ?></td>
                                            <td class="excel-cell text-center font-bold bg-green-50">BDT <?php echo $person['total_cost_display']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <?php if (!empty($cost_distribution['persons'])): ?>
                                <tfoot class="bg-gray-800 text-white">
                                    <tr>
                                        <td class="excel-header">Totals</td>
                                        <td class="excel-cell text-center bg-blue-900">
                                            BDT <?php echo number_format(array_sum(array_column($cost_distribution['persons'], 'fish_cost')), 2); ?>
                                        </td>
                                        <td class="excel-cell text-center bg-red-900">
                                            BDT <?php echo number_format(array_sum(array_column($cost_distribution['persons'], 'chicken_cost')), 2); ?>
                                        </td>
                                        <td class="excel-cell text-center bg-gray-900">
                                            BDT <?php echo number_format(array_sum(array_column($cost_distribution['persons'], 'other_cost')), 2); ?>
                                        </td>
                                        <td class="excel-cell text-center bg-purple-900">
                                            BDT <?php echo number_format(array_sum(array_column($cost_distribution['persons'], 'friday_cost')), 2); ?>
                                        </td>
                                        <td class="excel-cell text-center font-bold bg-green-900">
                                            BDT <?php echo number_format(array_sum(array_column($cost_distribution['persons'], 'total_cost')), 2); ?>
                                        </td>
                                    </tr>
                                </tfoot>
                            <?php endif; ?>
                        </table>
                    </div>
                    
                    <div class="px-6 py-4 border-t border-gray-300 bg-gray-50 text-sm text-gray-600">
                        <strong>Excel Sheet-1 Purpose:</strong> Takes aggregated meals from Sheet-3 and bazar costs to calculate:
                        1. Category-wise meal rates, 2. Person's cost per category (day/night separately), 3. Person's total cost.
                        This is the FAIR cost distribution engine.
                    </div>
                </div>
            <?php endif; ?>

            <!-- Tab 4 Content: Final Balance Sheet (Excel Sheet-4) -->
            <?php if ($active_tab == 'balance'): ?>
                <div class="bg-white rounded-lg shadow-sm border border-gray-300 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-300 bg-gray-50">
                        <h2 class="text-lg font-bold text-gray-800">Excel Sheet-4: Final Balance Sheet</h2>
                        <p class="text-sm text-gray-600">Final settlement calculation with single meal rate</p>
                    </div>
                    
                    <!-- Summary Stats -->
                    <div class="px-6 py-4 border-b border-gray-300 bg-green-50">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-white p-4 rounded-lg border border-green-200">
                                <div class="text-sm text-gray-500">Total All Meals</div>
                                <div class="text-2xl font-bold text-gray-800"><?php echo number_format($final_balance['total_meals_all'], 2); ?></div>
                            </div>
                            <div class="bg-white p-4 rounded-lg border border-green-200">
                                <div class="text-sm text-gray-500">Total All Bazar</div>
                                <div class="text-2xl font-bold text-gray-800">BDT <?php echo $final_balance['total_bazar_all_display']; ?></div>
                            </div>
                            <div class="bg-white p-4 rounded-lg border border-green-200">
                                <div class="text-sm text-gray-500">Single Meal Rate</div>
                                <div class="text-2xl font-bold text-green-600">BDT <?php echo $final_balance['single_meal_rate_display']; ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Balance Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="excel-header">Name</th>
                                    <th class="excel-header">Total Meal</th>
                                    <th class="excel-header">Meal Rate</th>
                                    <th class="excel-header">Cost</th>
                                    <th class="excel-header">Paid</th>
                                    <th class="excel-header">Balance</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($final_balance['persons'])): ?>
                                    <tr>
                                        <td colspan="6" class="excel-cell text-center text-gray-500 py-8">
                                            No balance data available
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($final_balance['persons'] as $person): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="excel-cell font-medium"><?php echo htmlspecialchars($person['name']); ?></td>
                                            <td class="excel-cell text-center"><?php echo $person['total_meals_display']; ?></td>
                                            <td class="excel-cell text-center">BDT <?php echo $final_balance['single_meal_rate_display']; ?></td>
                                            <td class="excel-cell text-center">BDT <?php echo $person['total_cost_display']; ?></td>
                                            <td class="excel-cell text-center">BDT <?php echo $person['paid_amount_display']; ?></td>
                                            <td class="excel-cell text-center">
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
                                <?php endif; ?>
                            </tbody>
                            <?php if (!empty($final_balance['persons'])): ?>
                                <tfoot class="bg-gray-800 text-white">
                                    <tr>
                                        <td class="excel-header">Grand Totals</td>
                                        <td class="excel-cell text-center">
                                            <?php echo number_format(array_sum(array_column($final_balance['persons'], 'total_meals')), 2); ?>
                                        </td>
                                        <td class="excel-cell text-center">
                                            BDT <?php echo $final_balance['single_meal_rate_display']; ?>
                                        </td>
                                        <td class="excel-cell text-center">
                                            BDT <?php echo number_format(array_sum(array_column($final_balance['persons'], 'total_cost')), 2); ?>
                                        </td>
                                        <td class="excel-cell text-center">
                                            BDT <?php echo number_format(array_sum(array_column($final_balance['persons'], 'paid_amount')), 2); ?>
                                        </td>
                                        <td class="excel-cell text-center">
                                            <?php 
                                            $total_balance = array_sum(array_column($final_balance['persons'], 'balance'));
                                            if ($total_balance >= 0): ?>
                                                <span class="text-green-300">System Balance: BDT <?php echo number_format($total_balance, 2); ?></span>
                                            <?php else: ?>
                                                <span class="text-red-300">System Balance: BDT <?php echo number_format(abs($total_balance), 2); ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </tfoot>
                            <?php endif; ?>
                        </table>
                    </div>
                    
                    <div class="px-6 py-4 border-t border-gray-300 bg-gray-50 text-sm text-gray-600">
                        <strong>Excel Sheet-4 Purpose:</strong> Final settlement calculation using single meal rate.
                        <strong>Formula:</strong> Meal Rate = Total Bazar ÷ Total Meals | Cost = Person Meals × Meal Rate | Balance = Paid − Cost
                        Positive balance = will receive money | Negative balance = owes money
                    </div>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <!-- Initial State (No data loaded) -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-300 p-8 text-center">
                <div class="max-w-md mx-auto">
                    <div class="text-5xl mb-4">📊</div>
                    <h3 class="text-xl font-medium text-gray-700 mb-2">Select a Month and Click "Load Data"</h3>
                    <p class="text-gray-600 mb-6">
                        The system will calculate all 4 Excel sheets for the selected month.
                    </p>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-left">
                        <h4 class="font-medium text-blue-800 mb-2">Excel Workflow:</h4>
                        <ul class="text-blue-700 text-sm space-y-1">
                            <li><strong>Tab 1</strong>: Daily meal entries (raw input)</li>
                            <li><strong>Tab 2</strong>: Monthly aggregation by person</li>
                            <li><strong>Tab 3</strong>: Category-wise cost distribution</li>
                            <li><strong>Tab 4</strong>: Final balance settlement</li>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php $conn->close(); ?>
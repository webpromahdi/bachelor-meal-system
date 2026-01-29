<?php
/**
 * summary.php - Monthly Summary
 * 
 * Business Rules:
 * - Meal categories (shared by meals): Chicken, Fish, Dim, Other, Special
 * - Rice: Person-wise cost (added ONLY to the payer's total)
 * - Special: Distributed only among Special meal eaters
 */

require_once '../config/database.php';

// Get selected month (default to current month)
$selected_month = $_GET['month'] ?? date('Y-m');
$month_start = $selected_month . '-01';
$month_end = date('Y-m-t', strtotime($month_start));

$persons = [];
$person_result = $conn->query("SELECT id, name FROM persons ORDER BY name");
if ($person_result) {
    while ($row = $person_result->fetch_assoc()) {
        $persons[$row['id']] = $row['name'];
    }
    $person_result->free();
}

$daily_meals_sql = "
    SELECT 
        dm.meal_date,
        COUNT(CASE WHEN dm.session = 'lunch' THEN 1 END) as lunch_meals,
        COUNT(CASE WHEN dm.session = 'dinner' THEN 1 END) as dinner_meals,
        COUNT(*) as total_meals
    FROM daily_meals dm
    WHERE dm.meal_date BETWEEN ? AND ?
    GROUP BY dm.meal_date
    ORDER BY dm.meal_date";

$stmt = $conn->prepare($daily_meals_sql);
$stmt->bind_param("ss", $month_start, $month_end);
$stmt->execute();
$daily_meals_result = $stmt->get_result();

$daily_meals = [];
while ($row = $daily_meals_result->fetch_assoc()) {
    $daily_meals[$row['meal_date']] = $row;
}
$stmt->close();

$bazar_sql = "
    SELECT 
        bazar_date,
        category,
        SUM(amount) as total_amount
    FROM bazar_items
    WHERE bazar_date BETWEEN ? AND ?
    GROUP BY bazar_date, category";

$stmt = $conn->prepare($bazar_sql);
$stmt->bind_param("ss", $month_start, $month_end);
$stmt->execute();
$bazar_result = $stmt->get_result();

$bazar_by_date = [];
while ($row = $bazar_result->fetch_assoc()) {
    $date = $row['bazar_date'];
    $cat = $row['category'];
    $amt = floatval($row['total_amount']);
<<<<<<< HEAD

=======
    
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
    if (!isset($bazar_by_date[$date])) {
        $bazar_by_date[$date] = [
            'chicken' => 0,
            'fish' => 0,
            'dim' => 0,
            'other' => 0,
            'special' => 0,
            'rice' => 0
        ];
    }
    $bazar_by_date[$date][$cat] = $amt;
}
$stmt->close();

$category_totals_sql = "
    SELECT 
        category,
        SUM(amount) as total
    FROM bazar_items
    WHERE bazar_date BETWEEN ? AND ?
    GROUP BY category";

$stmt = $conn->prepare($category_totals_sql);
$stmt->bind_param("ss", $month_start, $month_end);
$stmt->execute();
$cat_result = $stmt->get_result();

$category_totals = [
    'chicken' => 0,
    'fish' => 0,
    'dim' => 0,
    'other' => 0,
    'special' => 0,
    'rice' => 0
];

while ($row = $cat_result->fetch_assoc()) {
    $category_totals[$row['category']] = floatval($row['total']);
}
$stmt->close();

// Rice cost by person (person-wise investment)
$rice_by_person_sql = "
    SELECT 
        paid_by,
        SUM(amount) as rice_paid
    FROM bazar_items
    WHERE bazar_date BETWEEN ? AND ?
      AND category = 'rice'
    GROUP BY paid_by";

$stmt = $conn->prepare($rice_by_person_sql);
$stmt->bind_param("ss", $month_start, $month_end);
$stmt->execute();
$rice_result = $stmt->get_result();

$rice_costs = [];
foreach ($persons as $pid => $name) {
    $rice_costs[$pid] = 0;
}
while ($row = $rice_result->fetch_assoc()) {
    $rice_costs[$row['paid_by']] = floatval($row['rice_paid']);
}
$stmt->close();

$total_bazar = array_sum($category_totals);

// Meal-based bazar excludes rice
<<<<<<< HEAD
$meal_based_bazar = $category_totals['chicken'] + $category_totals['fish']
    + $category_totals['dim'] + $category_totals['other']
    + $category_totals['special'];
=======
$meal_based_bazar = $category_totals['chicken'] + $category_totals['fish'] 
                  + $category_totals['dim'] + $category_totals['other'] 
                  + $category_totals['special'];
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7

$person_meals_sql = "
    SELECT 
        person_id,
        session,
        meal_type,
        COUNT(*) as meal_count
    FROM daily_meals
    WHERE meal_date BETWEEN ? AND ?
    GROUP BY person_id, session, meal_type";

$stmt = $conn->prepare($person_meals_sql);
$stmt->bind_param("ss", $month_start, $month_end);
$stmt->execute();
$person_meals_result = $stmt->get_result();

// Initialize person meals structure
$person_meals = [];
foreach ($persons as $pid => $name) {
    $person_meals[$pid] = [
        'name' => $name,
        'lunch_chicken' => 0,
        'lunch_fish' => 0,
        'lunch_dim' => 0,
        'lunch_other' => 0,
        'lunch_special' => 0,
        'dinner_chicken' => 0,
        'dinner_fish' => 0,
        'dinner_dim' => 0,
        'dinner_other' => 0,
        'dinner_special' => 0,
        'total_lunch' => 0,
        'total_dinner' => 0,
        'special_meals' => 0
    ];
}

while ($row = $person_meals_result->fetch_assoc()) {
    $pid = $row['person_id'];
    $session = $row['session'];
    $type = $row['meal_type'];
    $count = $row['meal_count'];
<<<<<<< HEAD

    if (!isset($person_meals[$pid]))
        continue;

=======
    
    if (!isset($person_meals[$pid])) continue;
    
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
    $key = $session . '_' . $type;
    if (isset($person_meals[$pid][$key])) {
        $person_meals[$pid][$key] = $count;
    }
<<<<<<< HEAD

=======
    
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
    if ($session === 'lunch') {
        $person_meals[$pid]['total_lunch'] += $count;
    } else {
        $person_meals[$pid]['total_dinner'] += $count;
    }
<<<<<<< HEAD

=======
    
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
    // Track special meals
    if ($type === 'special') {
        $person_meals[$pid]['special_meals'] += $count;
    }
}
$stmt->close();

$special_meals_sql = "
    SELECT person_id, COUNT(*) as special_count
    FROM daily_meals
    WHERE meal_date BETWEEN ? AND ?
      AND meal_type = 'special'
    GROUP BY person_id";

$stmt = $conn->prepare($special_meals_sql);
$stmt->bind_param("ss", $month_start, $month_end);
$stmt->execute();
$special_result = $stmt->get_result();

$special_by_person = [];
$total_special_meals = 0;
while ($row = $special_result->fetch_assoc()) {
    $special_by_person[$row['person_id']] = $row['special_count'];
    $total_special_meals += $row['special_count'];
    if (isset($person_meals[$row['person_id']])) {
        $person_meals[$row['person_id']]['special_meals'] = $row['special_count'];
    }
}
$stmt->close();

$meal_totals_sql = "
    SELECT 
        session,
        meal_type,
        COUNT(*) as count
    FROM daily_meals
    WHERE meal_date BETWEEN ? AND ?
    GROUP BY session, meal_type";

$stmt = $conn->prepare($meal_totals_sql);
$stmt->bind_param("ss", $month_start, $month_end);
$stmt->execute();
$meal_totals_result = $stmt->get_result();

$meal_totals = [
<<<<<<< HEAD
    'lunch_chicken' => 0,
    'lunch_fish' => 0,
    'lunch_dim' => 0,
    'lunch_other' => 0,
    'lunch_special' => 0,
    'dinner_chicken' => 0,
    'dinner_fish' => 0,
    'dinner_dim' => 0,
    'dinner_other' => 0,
    'dinner_special' => 0
=======
    'lunch_chicken' => 0, 'lunch_fish' => 0, 'lunch_dim' => 0, 'lunch_other' => 0, 'lunch_special' => 0,
    'dinner_chicken' => 0, 'dinner_fish' => 0, 'dinner_dim' => 0, 'dinner_other' => 0, 'dinner_special' => 0
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
];
$total_all_meals = 0;

while ($row = $meal_totals_result->fetch_assoc()) {
    $key = $row['session'] . '_' . $row['meal_type'];
    if (isset($meal_totals[$key])) {
        $meal_totals[$key] = $row['count'];
    }
    $total_all_meals += $row['count'];
}
$stmt->close();

$chicken_meals = $meal_totals['lunch_chicken'] + $meal_totals['dinner_chicken'];
$fish_meals = $meal_totals['lunch_fish'] + $meal_totals['dinner_fish'];
$dim_meals = $meal_totals['lunch_dim'] + $meal_totals['dinner_dim'];
$other_meals = $meal_totals['lunch_other'] + $meal_totals['dinner_other'];
$special_meals = $meal_totals['lunch_special'] + $meal_totals['dinner_special'];

// All regular meals (chicken + fish + dim + other) - excluding special
$all_regular_meals = $chicken_meals + $fish_meals + $dim_meals + $other_meals;

// Category rates
$chicken_rate = ($chicken_meals > 0) ? $category_totals['chicken'] / $chicken_meals : 0;
$fish_rate = ($fish_meals > 0) ? $category_totals['fish'] / $fish_meals : 0;
$dim_rate = ($dim_meals > 0) ? $category_totals['dim'] / $dim_meals : 0;
$other_rate = ($all_regular_meals > 0) ? $category_totals['other'] / $all_regular_meals : 0;
$special_rate = ($total_special_meals > 0) ? $category_totals['special'] / $total_special_meals : 0;

// Overall meal rate
$total_monthly_meals = $all_regular_meals + $total_special_meals;
$total_meal_based_cost = $meal_based_bazar;
$overall_rate = ($total_monthly_meals > 0) ? $total_meal_based_cost / $total_monthly_meals : 0;

// Rice meal rate calculation (rice is shared based on total meals eaten)
// Rice Meal Count = Total Meals (everyone who eats uses rice)
$total_rice_cost = $category_totals['rice'];
$total_rice_meals = $total_monthly_meals; // Rice meals = All meals eaten
$rice_meal_rate = ($total_rice_meals > 0) ? $total_rice_cost / $total_rice_meals : 0;

// Cost distribution per person
$cost_distribution = [];
foreach ($person_meals as $pid => $data) {
    $person_chicken = $data['lunch_chicken'] + $data['dinner_chicken'];
    $person_fish = $data['lunch_fish'] + $data['dinner_fish'];
    $person_dim = $data['lunch_dim'] + $data['dinner_dim'];
    $person_other = $data['lunch_other'] + $data['dinner_other'];
    $person_regular = $data['total_lunch'] + $data['total_dinner'];
    $person_special = $data['special_meals'];
    $person_total_meals = $person_regular; // Total meals for this person (includes special in regular count already)
<<<<<<< HEAD

=======
    
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
    $chicken_cost = $person_chicken * $chicken_rate;
    $fish_cost = $person_fish * $fish_rate;
    $dim_cost = $person_dim * $dim_rate;
    $other_cost = $person_regular * $other_rate;
    $special_cost = $person_special * $special_rate;
<<<<<<< HEAD

=======
    
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
    // Rice cost is calculated based on meals eaten (not rice paid)
    // Person Rice Cost = Person Total Meals × Rice Meal Rate
    $rice_cost_calculated = $person_total_meals * $rice_meal_rate;
    $rice_paid = $rice_costs[$pid] ?? 0; // Investment tracking
<<<<<<< HEAD

    // Total cost includes calculated rice cost (not rice paid)
    $total_person_cost = $chicken_cost + $fish_cost + $dim_cost + $other_cost + $special_cost + $rice_cost_calculated;

=======
    
    // Total cost includes calculated rice cost (not rice paid)
    $total_person_cost = $chicken_cost + $fish_cost + $dim_cost + $other_cost + $special_cost + $rice_cost_calculated;
    
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
    $cost_distribution[$pid] = [
        'name' => $data['name'],
        'chicken_meals' => $person_chicken,
        'chicken_cost' => $chicken_cost,
        'fish_meals' => $person_fish,
        'fish_cost' => $fish_cost,
        'dim_meals' => $person_dim,
        'dim_cost' => $dim_cost,
        'other_cost' => $other_cost,
        'rice_paid' => $rice_paid,           // Amount invested in rice
        'rice_cost' => $rice_cost_calculated, // Calculated cost based on meals
        'special_meals' => $person_special,
        'special_cost' => $special_cost,
        'total_meals' => $person_regular,
        'total_cost' => $total_person_cost
    ];
}

$payments_sql = "
    SELECT 
        paid_by,
        SUM(amount) as total_paid
    FROM bazar_items
    WHERE bazar_date BETWEEN ? AND ?
    GROUP BY paid_by";

$stmt = $conn->prepare($payments_sql);
$stmt->bind_param("ss", $month_start, $month_end);
$stmt->execute();
$payments_result = $stmt->get_result();

$payments = [];
foreach ($persons as $pid => $name) {
    $payments[$pid] = 0;
}
while ($row = $payments_result->fetch_assoc()) {
    $payments[$row['paid_by']] = floatval($row['total_paid']);
}
$stmt->close();

// Balance sheet
$balance_sheet = [];
foreach ($cost_distribution as $pid => $data) {
    $paid = $payments[$pid] ?? 0;
    $should_pay = $data['total_cost'];
    $balance = $paid - $should_pay;
<<<<<<< HEAD

=======
    
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
    $balance_sheet[$pid] = [
        'name' => $data['name'],
        'total_paid' => $paid,
        'should_pay' => $should_pay,
        'balance' => $balance,
        'rice_paid' => $data['rice_paid'],  // Investment amount
        'rice_cost' => $data['rice_cost']   // Calculated cost
    ];
}

// Meal matrix data
$meal_matrix_sql = "
    SELECT 
        meal_date,
        person_id,
        session,
        meal_type,
        SUM(1 + COALESCE(guest_count, 0)) as meal_count
    FROM daily_meals
    WHERE meal_date BETWEEN ? AND ?
    GROUP BY meal_date, person_id, session, meal_type
    ORDER BY meal_date, person_id, session";

$stmt = $conn->prepare($meal_matrix_sql);
$stmt->bind_param("ss", $month_start, $month_end);
$stmt->execute();
$matrix_result = $stmt->get_result();

$meal_matrix = [];
while ($row = $matrix_result->fetch_assoc()) {
    $date = $row['meal_date'];
    $pid = $row['person_id'];
    $session = $row['session'];
    $type = $row['meal_type'];
    $count = intval($row['meal_count']);
<<<<<<< HEAD

=======
    
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
    if (!isset($meal_matrix[$date])) {
        $meal_matrix[$date] = [];
    }
    if (!isset($meal_matrix[$date][$pid])) {
        $meal_matrix[$date][$pid] = [];
    }
    $meal_matrix[$date][$pid][$session] = [
        'type' => $type,
        'count' => $count
    ];
}
$stmt->close();

<<<<<<< HEAD
// Bazar Details Query - Item-wise breakdown for Bazar Details tab
$bazar_details_sql = "
    SELECT 
        bi.id,
        bi.bazar_date,
        bi.item_name,
        bi.category,
        bi.amount,
        bi.paid_by,
        p.name as person_name
    FROM bazar_items bi
    LEFT JOIN persons p ON bi.paid_by = p.id
    WHERE bi.bazar_date BETWEEN ? AND ?
    ORDER BY bi.bazar_date DESC, p.name, bi.category";

$stmt = $conn->prepare($bazar_details_sql);
$stmt->bind_param("ss", $month_start, $month_end);
$stmt->execute();
$bazar_details_result = $stmt->get_result();

$bazar_details = [];
$bazar_by_person = [];
while ($row = $bazar_details_result->fetch_assoc()) {
    $bazar_details[] = $row;
    $pid = $row['paid_by'];
    if (!isset($bazar_by_person[$pid])) {
        $bazar_by_person[$pid] = [
            'name' => $row['person_name'] ?? 'Unknown',
            'items' => [],
            'total' => 0
        ];
    }
    $bazar_by_person[$pid]['items'][] = $row;
    $bazar_by_person[$pid]['total'] += floatval($row['amount']);
}
$stmt->close();

=======
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
$meal_type_codes = [
    'chicken' => ['code' => 'C', 'color' => 'text-red-600', 'bg' => 'bg-red-50'],
    'fish' => ['code' => 'F', 'color' => 'text-blue-600', 'bg' => 'bg-blue-50'],
    'dim' => ['code' => 'D', 'color' => 'text-yellow-600', 'bg' => 'bg-yellow-50'],
    'other' => ['code' => 'O', 'color' => 'text-green-600', 'bg' => 'bg-green-50'],
    'special' => ['code' => 'S', 'color' => 'text-pink-600', 'bg' => 'bg-pink-50']
];

$person_colors = [
    ['bg' => 'bg-orange-400', 'text' => 'text-white'],
    ['bg' => 'bg-green-400', 'text' => 'text-white'],
    ['bg' => 'bg-blue-400', 'text' => 'text-white'],
    ['bg' => 'bg-pink-400', 'text' => 'text-white'],
    ['bg' => 'bg-yellow-400', 'text' => 'text-gray-800'],
    ['bg' => 'bg-purple-400', 'text' => 'text-white'],
    ['bg' => 'bg-teal-400', 'text' => 'text-white'],
    ['bg' => 'bg-red-400', 'text' => 'text-white'],
    ['bg' => 'bg-indigo-400', 'text' => 'text-white'],
    ['bg' => 'bg-cyan-400', 'text' => 'text-gray-800']
];

// Get active tab
$active_tab = $_GET['tab'] ?? 'daily';
?>

<!DOCTYPE html>
<html lang="en">
<<<<<<< HEAD

=======
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Summary - Bachelor Meal System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .tab-active {
            background-color: #3b82f6;
            color: white;
            border-bottom: 3px solid #1d4ed8;
        }
<<<<<<< HEAD

=======
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
        .tab-inactive {
            background-color: #e5e7eb;
            color: #4b5563;
        }
<<<<<<< HEAD

        .tab-inactive:hover {
            background-color: #d1d5db;
        }

=======
        .tab-inactive:hover {
            background-color: #d1d5db;
        }
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
        .excel-table {
            border-collapse: collapse;
            width: 100%;
        }
<<<<<<< HEAD

        .excel-table th,
        .excel-table td {
=======
        .excel-table th, .excel-table td {
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
            border: 1px solid #d1d5db;
            padding: 8px 12px;
            text-align: center;
        }
<<<<<<< HEAD

=======
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
        .excel-header {
            background-color: #374151;
            color: white;
            font-weight: 600;
        }
<<<<<<< HEAD

        .excel-row:nth-child(even) {
            background-color: #f9fafb;
        }

        .excel-row:hover {
            background-color: #fef3c7;
        }

=======
        .excel-row:nth-child(even) {
            background-color: #f9fafb;
        }
        .excel-row:hover {
            background-color: #fef3c7;
        }
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
        .total-row {
            background-color: #dbeafe;
            font-weight: 600;
        }
<<<<<<< HEAD

        .positive {
            color: #059669;
            font-weight: 600;
        }

        .negative {
            color: #dc2626;
            font-weight: 600;
        }

        .zero {
            color: #6b7280;
        }
    </style>
</head>

=======
        .positive { color: #059669; font-weight: 600; }
        .negative { color: #dc2626; font-weight: 600; }
        .zero { color: #6b7280; }
    </style>
</head>
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
<body class="bg-gray-100">
    <!-- Navigation Bar -->
    <nav class="bg-blue-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <div class="text-2xl font-bold">📊</div>
                    <div class="ml-2">
                        <span class="font-bold text-lg">Bachelor Meal System</span>
                        <span class="text-sm text-blue-200 block -mt-1">Summary</span>
                    </div>
                </div>
                <div class="flex space-x-4">
                    <a href="index.php" class="px-4 py-2 rounded-lg hover:bg-blue-700 transition">📊 Dashboard</a>
                    <a href="meals.php" class="px-4 py-2 rounded-lg hover:bg-blue-700 transition">🍽️ Meals</a>
                    <a href="bazar.php" class="px-4 py-2 rounded-lg hover:bg-blue-700 transition">🛒 Bazar</a>
                    <a href="summary.php" class="px-4 py-2 rounded-lg bg-blue-800">📈 Summary</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto p-6">
        <!-- Header with Month Selector -->
        <div class="flex flex-wrap items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">📈 Monthly Summary</h1>
                <p class="text-gray-600">Excel-style reports for meal and bazar tracking</p>
            </div>
            <form method="GET" class="flex items-center gap-2">
                <input type="hidden" name="tab" value="<?php echo htmlspecialchars($active_tab); ?>">
<<<<<<< HEAD
                <input type="month" name="month" value="<?php echo $selected_month; ?>"
                    class="px-4 py-2 border border-gray-300 rounded-lg" onchange="this.form.submit()">
=======
                <input type="month" 
                       name="month" 
                       value="<?php echo $selected_month; ?>"
                       class="px-4 py-2 border border-gray-300 rounded-lg"
                       onchange="this.form.submit()">
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
            </form>
        </div>

        <!-- Overall Stats Banner -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white rounded-lg p-6 mb-6 shadow-lg">
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4 text-center">
                <div>
                    <div class="text-3xl font-bold"><?php echo $total_monthly_meals; ?></div>
                    <div class="text-blue-200 text-sm">Total Meals</div>
                </div>
                <div>
                    <div class="text-3xl font-bold">৳<?php echo number_format($total_bazar, 0); ?></div>
                    <div class="text-blue-200 text-sm">Total Bazar</div>
                </div>
                <div class="bg-white/20 rounded-lg p-2">
<<<<<<< HEAD
                    <div class="text-3xl font-bold text-yellow-300">
                        ৳<?php echo number_format($overall_rate + $rice_meal_rate, 2); ?></div>
=======
                    <div class="text-3xl font-bold text-yellow-300">৳<?php echo number_format($overall_rate + $rice_meal_rate, 2);?></div>
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
                    <div class="text-yellow-200 text-sm font-medium">Overall Meal Rate</div>
                </div>
                <div>
                    <div class="text-3xl font-bold"><?php echo count($persons); ?></div>
                    <div class="text-blue-200 text-sm">Members</div>
                </div>
                <div>
                    <div class="text-3xl font-bold"><?php echo count($daily_meals); ?></div>
                    <div class="text-blue-200 text-sm">Active Days</div>
                </div>
                <div>
                    <div class="text-3xl font-bold"><?php echo $total_special_meals; ?></div>
                    <div class="text-blue-200 text-sm">Special Meals</div>
                </div>
                <div>
<<<<<<< HEAD
                    <div class="text-3xl font-bold text-amber-200">৳<?php echo number_format($total_rice_cost, 0); ?>
                    </div>
=======
                    <div class="text-3xl font-bold text-amber-200">৳<?php echo number_format($total_rice_cost, 0); ?></div>
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
                    <div class="text-amber-200 text-sm">Total Rice</div>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="flex border-b border-gray-200 mb-6 overflow-x-auto">
<<<<<<< HEAD
            <a href="?tab=daily&month=<?php echo $selected_month; ?>"
                class="px-6 py-3 font-medium rounded-t-lg transition whitespace-nowrap <?php echo $active_tab === 'daily' ? 'tab-active' : 'tab-inactive'; ?>">
                📋 Daily Meal Log
            </a>
            <a href="?tab=matrix&month=<?php echo $selected_month; ?>"
                class="px-6 py-3 font-medium rounded-t-lg transition whitespace-nowrap <?php echo $active_tab === 'matrix' ? 'tab-active' : 'tab-inactive'; ?>">
                📅 Meal Matrix
            </a>
            <a href="?tab=summary&month=<?php echo $selected_month; ?>"
                class="px-6 py-3 font-medium rounded-t-lg transition whitespace-nowrap <?php echo $active_tab === 'summary' ? 'tab-active' : 'tab-inactive'; ?>">
                📊 Monthly Summary
            </a>
            <a href="?tab=cost&month=<?php echo $selected_month; ?>"
                class="px-6 py-3 font-medium rounded-t-lg transition whitespace-nowrap <?php echo $active_tab === 'cost' ? 'tab-active' : 'tab-inactive'; ?>">
                💰 Cost Distribution
            </a>
            <a href="?tab=balance&month=<?php echo $selected_month; ?>"
                class="px-6 py-3 font-medium rounded-t-lg transition whitespace-nowrap <?php echo $active_tab === 'balance' ? 'tab-active' : 'tab-inactive'; ?>">
                💳 Balance Sheet
            </a>
            <a href="?tab=bazar_details&month=<?php echo $selected_month; ?>"
                class="px-6 py-3 font-medium rounded-t-lg transition whitespace-nowrap <?php echo $active_tab === 'bazar_details' ? 'tab-active' : 'tab-inactive'; ?>">
                🛒 Bazar Details
            </a>
=======
            <a href="?tab=daily&month=<?php echo $selected_month; ?>" 
               class="px-6 py-3 font-medium rounded-t-lg transition whitespace-nowrap <?php echo $active_tab === 'daily' ? 'tab-active' : 'tab-inactive'; ?>">
                📋 Daily Meal Log
            </a>
            <a href="?tab=matrix&month=<?php echo $selected_month; ?>" 
               class="px-6 py-3 font-medium rounded-t-lg transition whitespace-nowrap <?php echo $active_tab === 'matrix' ? 'tab-active' : 'tab-inactive'; ?>">
                📅 Meal Matrix
            </a>
            <a href="?tab=summary&month=<?php echo $selected_month; ?>" 
               class="px-6 py-3 font-medium rounded-t-lg transition whitespace-nowrap <?php echo $active_tab === 'summary' ? 'tab-active' : 'tab-inactive'; ?>">
                📊 Monthly Summary
            </a>
            <a href="?tab=cost&month=<?php echo $selected_month; ?>" 
               class="px-6 py-3 font-medium rounded-t-lg transition whitespace-nowrap <?php echo $active_tab === 'cost' ? 'tab-active' : 'tab-inactive'; ?>">
                💰 Cost Distribution
            </a>
            <a href="?tab=balance&month=<?php echo $selected_month; ?>" 
               class="px-6 py-3 font-medium rounded-t-lg transition whitespace-nowrap <?php echo $active_tab === 'balance' ? 'tab-active' : 'tab-inactive'; ?>">
                💳 Balance Sheet
            </a>
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
        </div>

        <!-- Daily Meal Log Tab -->
        <?php if ($active_tab === 'daily'): ?>
            <div class="bg-white rounded-lg shadow-md overflow-x-auto">
                <table class="excel-table">
                    <thead>
                        <tr>
                            <th class="excel-header">Date</th>
                            <th class="excel-header">Day</th>
                            <th class="excel-header">Lunch</th>
                            <th class="excel-header">Dinner</th>
                            <th class="excel-header">Total Meals</th>
                            <th class="excel-header">🍗 Chicken</th>
                            <th class="excel-header">🐟 Fish</th>
                            <th class="excel-header">🥚 Dim</th>
                            <th class="excel-header">⭐ Special</th>
                            <th class="excel-header">🥗 Other</th>
                            <th class="excel-header">🍚 Rice</th>
                            <th class="excel-header">Daily Total</th>
                        </tr>
                    </thead>
                    <tbody>
<<<<<<< HEAD
                        <?php
=======
                        <?php 
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
                        $grand_lunch = 0;
                        $grand_dinner = 0;
                        $grand_meals = 0;
                        $grand_chicken = 0;
                        $grand_fish = 0;
                        $grand_dim = 0;
                        $grand_special = 0;
                        $grand_other = 0;
                        $grand_rice = 0;
                        $grand_bazar_total = 0;
<<<<<<< HEAD

=======
                        
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
                        $current_date = $month_start;
                        while ($current_date <= $month_end):
                            $meal_data = $daily_meals[$current_date] ?? null;
                            $bazar_data = $bazar_by_date[$current_date] ?? null;
<<<<<<< HEAD

                            $lunch = $meal_data['lunch_meals'] ?? 0;
                            $dinner = $meal_data['dinner_meals'] ?? 0;
                            $total = $lunch + $dinner;

=======
                            
                            $lunch = $meal_data['lunch_meals'] ?? 0;
                            $dinner = $meal_data['dinner_meals'] ?? 0;
                            $total = $lunch + $dinner;
                            
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
                            $chicken = $bazar_data['chicken'] ?? 0;
                            $fish = $bazar_data['fish'] ?? 0;
                            $dim = $bazar_data['dim'] ?? 0;
                            $special = $bazar_data['special'] ?? 0;
                            $other = $bazar_data['other'] ?? 0;
                            $rice = $bazar_data['rice'] ?? 0;
<<<<<<< HEAD

                            $daily_bazar = $chicken + $fish + $dim + $special + $other + $rice;

=======
                            
                            $daily_bazar = $chicken + $fish + $dim + $special + $other + $rice;
                            
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
                            $grand_lunch += $lunch;
                            $grand_dinner += $dinner;
                            $grand_meals += $total;
                            $grand_chicken += $chicken;
                            $grand_fish += $fish;
                            $grand_dim += $dim;
                            $grand_special += $special;
                            $grand_other += $other;
                            $grand_rice += $rice;
                            $grand_bazar_total += $daily_bazar;
<<<<<<< HEAD

                            $day_name = date('D', strtotime($current_date));
                            ?>
=======
                            
                            $day_name = date('D', strtotime($current_date));
                        ?>
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
                            <tr class="excel-row">
                                <td class="font-medium"><?php echo date('d M', strtotime($current_date)); ?></td>
                                <td><?php echo $day_name; ?></td>
                                <td><?php echo $lunch ?: '-'; ?></td>
                                <td><?php echo $dinner ?: '-'; ?></td>
                                <td class="font-medium"><?php echo $total ?: '-'; ?></td>
                                <td class="text-red-600"><?php echo $chicken ? '৳' . number_format($chicken, 0) : '-'; ?></td>
                                <td class="text-blue-600"><?php echo $fish ? '৳' . number_format($fish, 0) : '-'; ?></td>
                                <td class="text-yellow-600"><?php echo $dim ? '৳' . number_format($dim, 0) : '-'; ?></td>
                                <td class="text-pink-600"><?php echo $special ? '৳' . number_format($special, 0) : '-'; ?></td>
                                <td class="text-green-600"><?php echo $other ? '৳' . number_format($other, 0) : '-'; ?></td>
                                <td class="text-amber-600"><?php echo $rice ? '৳' . number_format($rice, 0) : '-'; ?></td>
<<<<<<< HEAD
                                <td class="font-bold"><?php echo $daily_bazar ? '৳' . number_format($daily_bazar, 0) : '-'; ?>
                                </td>
                            </tr>
                            <?php
                            $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
                        endwhile;
=======
                                <td class="font-bold"><?php echo $daily_bazar ? '৳' . number_format($daily_bazar, 0) : '-'; ?></td>
                            </tr>
                        <?php 
                            $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
                        endwhile; 
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
                        ?>
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td colspan="2" class="font-bold">TOTAL</td>
                            <td class="font-bold"><?php echo $grand_lunch; ?></td>
                            <td class="font-bold"><?php echo $grand_dinner; ?></td>
                            <td class="font-bold"><?php echo $grand_meals; ?></td>
                            <td class="font-bold text-red-600">৳<?php echo number_format($grand_chicken, 0); ?></td>
                            <td class="font-bold text-blue-600">৳<?php echo number_format($grand_fish, 0); ?></td>
                            <td class="font-bold text-yellow-600">৳<?php echo number_format($grand_dim, 0); ?></td>
                            <td class="font-bold text-pink-600">৳<?php echo number_format($grand_special, 0); ?></td>
                            <td class="font-bold text-green-600">৳<?php echo number_format($grand_other, 0); ?></td>
                            <td class="font-bold text-amber-600">৳<?php echo number_format($grand_rice, 0); ?></td>
                            <td class="font-bold">৳<?php echo number_format($grand_bazar_total, 0); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
<<<<<<< HEAD

=======
            
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
            <!-- Legend -->
            <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                <h4 class="font-medium mb-2">Legend:</h4>
                <div class="flex flex-wrap gap-4 text-sm">
                    <span class="text-red-600">🍗 Chicken/Meat</span>
                    <span class="text-blue-600">🐟 Fish</span>
                    <span class="text-yellow-600">🥚 Dim (Egg)</span>
                    <span class="text-pink-600">⭐ Special Meal</span>
                    <span class="text-green-600">🥗 Other/Veg</span>
                    <span class="text-amber-600 font-medium">🍚 Rice (Shared Cost)</span>
                </div>
                <p class="text-xs text-gray-500 mt-2">* Rice cost is shared among all members based on meals consumed</p>
            </div>
        <?php endif; ?>

        <!-- Monthly Meal Matrix Tab -->
        <?php if ($active_tab === 'matrix'): ?>
            <div class="bg-white rounded-lg shadow-md overflow-x-auto">
                <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 text-white">
                    <h2 class="text-xl font-bold">📅 Monthly Meal Matrix</h2>
                    <p class="text-blue-100 text-sm">Person × Day × Session view of all meals</p>
                </div>
<<<<<<< HEAD

=======
                
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
                <div class="overflow-x-auto">
                    <table class="excel-table" style="min-width: 100%;">
                        <thead>
                            <tr>
                                <th class="excel-header" rowspan="2" style="min-width: 80px;">Date</th>
<<<<<<< HEAD
                                <?php
                                $person_index = 0;
                                foreach ($persons as $pid => $name):
                                    $color = $person_colors[$person_index % count($person_colors)];
                                    $person_index++;
                                    ?>
                                    <th colspan="2"
=======
                                <?php 
                                $person_index = 0;
                                foreach ($persons as $pid => $name): 
                                    $color = $person_colors[$person_index % count($person_colors)];
                                    $person_index++;
                                ?>
                                    <th colspan="2" 
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
                                        class="<?php echo $color['bg']; ?> <?php echo $color['text']; ?> font-bold text-center px-4 py-2"
                                        style="min-width: 120px;">
                                        <?php echo strtoupper(htmlspecialchars($name)); ?>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                            <tr>
                                <?php foreach ($persons as $pid => $name): ?>
<<<<<<< HEAD
                                    <th class="bg-amber-100 text-amber-800 font-medium text-center text-sm px-2 py-1"
                                        style="min-width: 50px;">Day</th>
                                    <th class="bg-indigo-100 text-indigo-800 font-medium text-center text-sm px-2 py-1"
                                        style="min-width: 50px;">Night</th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>

                        <tbody>
                            <?php
=======
                                    <th class="bg-amber-100 text-amber-800 font-medium text-center text-sm px-2 py-1" style="min-width: 50px;">Day</th>
                                    <th class="bg-indigo-100 text-indigo-800 font-medium text-center text-sm px-2 py-1" style="min-width: 50px;">Night</th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        
                        <tbody>
                            <?php 
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
                            $current_date = $month_start;
                            while ($current_date <= $month_end):
                                $day_num = date('j', strtotime($current_date));
                                $day_name = date('D', strtotime($current_date));
<<<<<<< HEAD
                                ?>
=======
                            ?>
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
                                <tr class="excel-row">
                                    <td class="font-medium text-center text-gray-700" style="white-space: nowrap;">
                                        <?php echo $day_num; ?>
                                        <span class="text-xs text-gray-500"><?php echo $day_name; ?></span>
                                    </td>
<<<<<<< HEAD

                                    <?php
                                    foreach ($persons as $pid => $name):
                                        $lunch_data = $meal_matrix[$current_date][$pid]['lunch'] ?? null;
                                        $dinner_data = $meal_matrix[$current_date][$pid]['dinner'] ?? null;

=======
                                    
                                    <?php 
                                    foreach ($persons as $pid => $name): 
                                        $lunch_data = $meal_matrix[$current_date][$pid]['lunch'] ?? null;
                                        $dinner_data = $meal_matrix[$current_date][$pid]['dinner'] ?? null;
                                        
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
                                        $lunch_type = $lunch_data['type'] ?? null;
                                        $lunch_count = $lunch_data['count'] ?? 0;
                                        $dinner_type = $dinner_data['type'] ?? null;
                                        $dinner_count = $dinner_data['count'] ?? 0;
<<<<<<< HEAD

                                        $lunch_info = $lunch_type ? ($meal_type_codes[$lunch_type] ?? ['code' => '?', 'color' => 'text-gray-500', 'bg' => '']) : null;
                                        $dinner_info = $dinner_type ? ($meal_type_codes[$dinner_type] ?? ['code' => '?', 'color' => 'text-gray-500', 'bg' => '']) : null;
                                        ?>
                                        <td
                                            class="text-center <?php echo $lunch_info ? $lunch_info['bg'] . ' ' . $lunch_info['color'] . ' font-bold' : 'text-gray-300'; ?>">
                                            <?php echo ($lunch_count > 0 && $lunch_info) ? $lunch_count . $lunch_info['code'] : '-'; ?>
                                        </td>
                                        <td
                                            class="text-center <?php echo $dinner_info ? $dinner_info['bg'] . ' ' . $dinner_info['color'] . ' font-bold' : 'text-gray-300'; ?>">
=======
                                        
                                        $lunch_info = $lunch_type ? ($meal_type_codes[$lunch_type] ?? ['code' => '?', 'color' => 'text-gray-500', 'bg' => '']) : null;
                                        $dinner_info = $dinner_type ? ($meal_type_codes[$dinner_type] ?? ['code' => '?', 'color' => 'text-gray-500', 'bg' => '']) : null;
                                    ?>
                                        <td class="text-center <?php echo $lunch_info ? $lunch_info['bg'] . ' ' . $lunch_info['color'] . ' font-bold' : 'text-gray-300'; ?>">
                                            <?php echo ($lunch_count > 0 && $lunch_info) ? $lunch_count . $lunch_info['code'] : '-'; ?>
                                        </td>
                                        <td class="text-center <?php echo $dinner_info ? $dinner_info['bg'] . ' ' . $dinner_info['color'] . ' font-bold' : 'text-gray-300'; ?>">
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
                                            <?php echo ($dinner_count > 0 && $dinner_info) ? $dinner_count . $dinner_info['code'] : '-'; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
<<<<<<< HEAD
                                <?php
                                $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
                            endwhile;
                            ?>
                        </tbody>

                        <tfoot>
                            <tr class="total-row">
                                <td class="font-bold">Total</td>
                                <?php
                                foreach ($persons as $pid => $name):
                                    $person_lunch_count = 0;
                                    $person_dinner_count = 0;

=======
                            <?php 
                                $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
                            endwhile; 
                            ?>
                        </tbody>
                        
                        <tfoot>
                            <tr class="total-row">
                                <td class="font-bold">Total</td>
                                <?php 
                                foreach ($persons as $pid => $name): 
                                    $person_lunch_count = 0;
                                    $person_dinner_count = 0;
                                    
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
                                    foreach ($meal_matrix as $date => $person_meals_data) {
                                        if (isset($person_meals_data[$pid]['lunch'])) {
                                            $person_lunch_count += $person_meals_data[$pid]['lunch']['count'] ?? 0;
                                        }
                                        if (isset($person_meals_data[$pid]['dinner'])) {
                                            $person_dinner_count += $person_meals_data[$pid]['dinner']['count'] ?? 0;
                                        }
                                    }
<<<<<<< HEAD
                                    ?>
=======
                                ?>
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
                                    <td class="font-bold text-amber-700 bg-amber-50"><?php echo $person_lunch_count; ?></td>
                                    <td class="font-bold text-indigo-700 bg-indigo-50"><?php echo $person_dinner_count; ?></td>
                                <?php endforeach; ?>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
<<<<<<< HEAD

=======
            
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
            <!-- Matrix Legend -->
            <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                <h4 class="font-medium mb-3">📌 Cell Format: <span class="text-blue-600">COUNT + TYPE CODE</span></h4>
                <div class="flex flex-wrap gap-4 text-sm mb-3">
                    <span class="px-3 py-1 bg-red-50 text-red-600 font-bold rounded">1C = 1 Chicken</span>
                    <span class="px-3 py-1 bg-blue-50 text-blue-600 font-bold rounded">2F = 2 Fish</span>
                    <span class="px-3 py-1 bg-yellow-50 text-yellow-600 font-bold rounded">3D = 3 Dim (Egg)</span>
                    <span class="px-3 py-1 bg-green-50 text-green-600 font-bold rounded">1O = 1 Other/Veg</span>
                    <span class="px-3 py-1 bg-pink-50 text-pink-600 font-bold rounded">2S = 2 Special</span>
                    <span class="px-3 py-1 bg-gray-100 text-gray-400 rounded">- = No Meal</span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Monthly Summary Tab -->
        <?php if ($active_tab === 'summary'): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Category Summary -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-bold mb-4 text-gray-800">📦 Category-wise Summary</h3>
                    <table class="excel-table">
                        <thead>
                            <tr>
                                <th class="excel-header">Category</th>
                                <th class="excel-header">Total Cost</th>
                                <th class="excel-header">Meals</th>
                                <th class="excel-header">Rate/Meal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="excel-row">
                                <td class="text-left">🍗 Chicken/Meat</td>
<<<<<<< HEAD
                                <td class="text-red-600 font-medium">
                                    ৳<?php echo number_format($category_totals['chicken'], 0); ?></td>
=======
                                <td class="text-red-600 font-medium">৳<?php echo number_format($category_totals['chicken'], 0); ?></td>
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
                                <td><?php echo $chicken_meals; ?></td>
                                <td class="font-medium">৳<?php echo number_format($chicken_rate, 2); ?></td>
                            </tr>
                            <tr class="excel-row">
                                <td class="text-left">🐟 Fish</td>
<<<<<<< HEAD
                                <td class="text-blue-600 font-medium">
                                    ৳<?php echo number_format($category_totals['fish'], 0); ?></td>
=======
                                <td class="text-blue-600 font-medium">৳<?php echo number_format($category_totals['fish'], 0); ?></td>
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
                                <td><?php echo $fish_meals; ?></td>
                                <td class="font-medium">৳<?php echo number_format($fish_rate, 2); ?></td>
                            </tr>
                            <tr class="excel-row">
                                <td class="text-left">🥚 Dim (Egg)</td>
<<<<<<< HEAD
                                <td class="text-yellow-600 font-medium">
                                    ৳<?php echo number_format($category_totals['dim'], 0); ?></td>
=======
                                <td class="text-yellow-600 font-medium">৳<?php echo number_format($category_totals['dim'], 0); ?></td>
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
                                <td><?php echo $dim_meals; ?></td>
                                <td class="font-medium">৳<?php echo number_format($dim_rate, 2); ?></td>
                            </tr>
                            <tr class="excel-row">
                                <td class="text-left">⭐ Special Meal</td>
<<<<<<< HEAD
                                <td class="text-pink-600 font-medium">
                                    ৳<?php echo number_format($category_totals['special'], 0); ?></td>
=======
                                <td class="text-pink-600 font-medium">৳<?php echo number_format($category_totals['special'], 0); ?></td>
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
                                <td><?php echo $total_special_meals; ?></td>
                                <td class="font-medium">৳<?php echo number_format($special_rate, 2); ?></td>
                            </tr>
                            <tr class="excel-row">
                                <td class="text-left">🥗 Other/Veg</td>
<<<<<<< HEAD
                                <td class="text-green-600 font-medium">
                                    ৳<?php echo number_format($category_totals['other'], 0); ?></td>
=======
                                <td class="text-green-600 font-medium">৳<?php echo number_format($category_totals['other'], 0); ?></td>
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
                                <td><?php echo $all_regular_meals; ?> <span class="text-xs text-gray-500">(all)</span></td>
                                <td class="font-medium">৳<?php echo number_format($other_rate, 2); ?></td>
                            </tr>
                            <tr class="excel-row bg-amber-50">
<<<<<<< HEAD
                                <td class="text-left">🍚 Rice (Chal) <span class="text-xs text-amber-600 font-bold">- Shared
                                        Cost</span></td>
                                <td class="text-amber-600 font-medium">৳<?php echo number_format($total_rice_cost, 0); ?>
                                </td>
                                <td class="text-amber-700 font-medium"><?php echo $total_rice_meals; ?></td>
                                <td class="font-bold text-amber-700 bg-amber-100">
                                    ৳<?php echo number_format($rice_meal_rate, 2); ?></td>
=======
                                <td class="text-left">🍚 Rice (Chal) <span class="text-xs text-amber-600 font-bold">- Shared Cost</span></td>
                                <td class="text-amber-600 font-medium">৳<?php echo number_format($total_rice_cost, 0); ?></td>
                                <td class="text-amber-700 font-medium"><?php echo $total_rice_meals; ?></td>
                                <td class="font-bold text-amber-700 bg-amber-100">৳<?php echo number_format($rice_meal_rate, 2); ?></td>
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td class="font-bold text-left">MEAL-BASED</td>
                                <td class="font-bold">৳<?php echo number_format($meal_based_bazar, 0); ?></td>
                                <td class="font-bold"><?php echo $total_monthly_meals; ?></td>
                                <td class="font-bold bg-yellow-100">৳<?php echo number_format($overall_rate, 2); ?></td>
                            </tr>
                            <tr class="bg-amber-100">
                                <td class="font-bold text-left">GRAND TOTAL</td>
                                <td class="font-bold" colspan="3">৳<?php echo number_format($total_bazar, 0); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <!-- Meal Type Summary -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-bold mb-4 text-gray-800">🍽️ Meal Type Summary</h3>
                    <table class="excel-table">
                        <thead>
                            <tr>
                                <th class="excel-header">Session / Type</th>
                                <th class="excel-header">🍗</th>
                                <th class="excel-header">🐟</th>
                                <th class="excel-header">🥚</th>
                                <th class="excel-header">🥗</th>
                                <th class="excel-header">⭐</th>
                                <th class="excel-header">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="excel-row">
                                <td class="text-left font-medium">☀️ Lunch</td>
                                <td><?php echo $meal_totals['lunch_chicken']; ?></td>
                                <td><?php echo $meal_totals['lunch_fish']; ?></td>
                                <td><?php echo $meal_totals['lunch_dim']; ?></td>
                                <td><?php echo $meal_totals['lunch_other']; ?></td>
                                <td><?php echo $meal_totals['lunch_special']; ?></td>
<<<<<<< HEAD
                                <td class="font-medium">
                                    <?php echo $meal_totals['lunch_chicken'] + $meal_totals['lunch_fish'] + $meal_totals['lunch_dim'] + $meal_totals['lunch_other'] + $meal_totals['lunch_special']; ?>
                                </td>
=======
                                <td class="font-medium"><?php echo $meal_totals['lunch_chicken'] + $meal_totals['lunch_fish'] + $meal_totals['lunch_dim'] + $meal_totals['lunch_other'] + $meal_totals['lunch_special']; ?></td>
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
                            </tr>
                            <tr class="excel-row">
                                <td class="text-left font-medium">🌙 Dinner</td>
                                <td><?php echo $meal_totals['dinner_chicken']; ?></td>
                                <td><?php echo $meal_totals['dinner_fish']; ?></td>
                                <td><?php echo $meal_totals['dinner_dim']; ?></td>
                                <td><?php echo $meal_totals['dinner_other']; ?></td>
                                <td><?php echo $meal_totals['dinner_special']; ?></td>
<<<<<<< HEAD
                                <td class="font-medium">
                                    <?php echo $meal_totals['dinner_chicken'] + $meal_totals['dinner_fish'] + $meal_totals['dinner_dim'] + $meal_totals['dinner_other'] + $meal_totals['dinner_special']; ?>
                                </td>
=======
                                <td class="font-medium"><?php echo $meal_totals['dinner_chicken'] + $meal_totals['dinner_fish'] + $meal_totals['dinner_dim'] + $meal_totals['dinner_other'] + $meal_totals['dinner_special']; ?></td>
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td class="font-bold text-left">TOTAL</td>
                                <td class="font-bold"><?php echo $chicken_meals; ?></td>
                                <td class="font-bold"><?php echo $fish_meals; ?></td>
                                <td class="font-bold"><?php echo $dim_meals; ?></td>
                                <td class="font-bold"><?php echo $other_meals; ?></td>
                                <td class="font-bold"><?php echo $special_meals; ?></td>
                                <td class="font-bold"><?php echo $total_monthly_meals; ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
<<<<<<< HEAD

=======
            
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
            <!-- Overall Meal Rate -->
            <div class="mt-6 bg-gradient-to-r from-yellow-400 to-orange-500 rounded-lg p-6 shadow-lg text-center">
                <h3 class="text-xl font-bold text-white mb-2">📊 Overall Meal Rate (Excludes Rice)</h3>
                <div class="text-5xl font-bold text-white">৳<?php echo number_format($overall_rate, 2); ?></div>
                <p class="text-white/90 mt-2">
                    (Chicken + Fish + Dim + Other + Special) ÷ Total Monthly Meals
                </p>
                <p class="text-white/80 text-sm mt-1">
                    ৳<?php echo number_format($meal_based_bazar, 0); ?> ÷ <?php echo $total_monthly_meals; ?> meals
                </p>
            </div>
        <?php endif; ?>

        <!-- Cost Distribution Tab -->
        <?php if ($active_tab === 'cost'): ?>
            <div class="bg-white rounded-lg shadow-md overflow-x-auto">
                <table class="excel-table">
                    <thead>
                        <tr>
                            <th class="excel-header">Name</th>
                            <th class="excel-header">🍗 Meals</th>
                            <th class="excel-header">🍗 Cost</th>
                            <th class="excel-header">🐟 Meals</th>
                            <th class="excel-header">🐟 Cost</th>
                            <th class="excel-header">🥚 Meals</th>
                            <th class="excel-header">🥚 Cost</th>
                            <th class="excel-header">🥗 Cost</th>
                            <th class="excel-header">⭐ Meals</th>
                            <th class="excel-header">⭐ Cost</th>
                            <th class="excel-header bg-amber-700">🍚 Rice Cost</th>
                            <th class="excel-header">Total Meals</th>
                            <th class="excel-header">Total Cost</th>
                        </tr>
                    </thead>
                    <tbody>
<<<<<<< HEAD
                        <?php
=======
                        <?php 
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
                        $total_chicken_cost = 0;
                        $total_fish_cost = 0;
                        $total_dim_cost = 0;
                        $total_other_cost = 0;
                        $total_rice_paid_sum = 0;
                        $total_rice_cost_sum = 0;
                        $total_special_cost = 0;
                        $total_overall_cost = 0;
                        $total_all_meals_dist = 0;
<<<<<<< HEAD

                        foreach ($cost_distribution as $pid => $data):
=======
                        
                        foreach ($cost_distribution as $pid => $data): 
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
                            $total_chicken_cost += $data['chicken_cost'];
                            $total_fish_cost += $data['fish_cost'];
                            $total_dim_cost += $data['dim_cost'];
                            $total_other_cost += $data['other_cost'];
                            $total_rice_paid_sum += $data['rice_paid'];
                            $total_rice_cost_sum += $data['rice_cost'];
                            $total_special_cost += $data['special_cost'];
                            $total_overall_cost += $data['total_cost'];
                            $total_all_meals_dist += $data['total_meals'];
<<<<<<< HEAD
                            ?>
=======
                        ?>
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
                            <tr class="excel-row">
                                <td class="text-left font-medium"><?php echo htmlspecialchars($data['name']); ?></td>
                                <td><?php echo $data['chicken_meals']; ?></td>
                                <td class="text-red-600">৳<?php echo number_format($data['chicken_cost'], 0); ?></td>
                                <td><?php echo $data['fish_meals']; ?></td>
                                <td class="text-blue-600">৳<?php echo number_format($data['fish_cost'], 0); ?></td>
                                <td><?php echo $data['dim_meals']; ?></td>
                                <td class="text-yellow-600">৳<?php echo number_format($data['dim_cost'], 0); ?></td>
                                <td class="text-green-600">৳<?php echo number_format($data['other_cost'], 0); ?></td>
                                <td class="text-pink-600"><?php echo $data['special_meals']; ?></td>
                                <td class="text-pink-600">৳<?php echo number_format($data['special_cost'], 0); ?></td>
<<<<<<< HEAD
                                <td class="text-amber-700 font-medium bg-amber-100">
                                    ৳<?php echo number_format($data['rice_cost'], 0); ?></td>
=======
                                <td class="text-amber-700 font-medium bg-amber-100">৳<?php echo number_format($data['rice_cost'], 0); ?></td>
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
                                <td class="font-medium"><?php echo $data['total_meals']; ?></td>
                                <td class="font-bold">৳<?php echo number_format($data['total_cost'], 0); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td class="font-bold text-left">TOTAL</td>
                            <td class="font-bold"><?php echo $chicken_meals; ?></td>
                            <td class="font-bold text-red-600">৳<?php echo number_format($total_chicken_cost, 0); ?></td>
                            <td class="font-bold"><?php echo $fish_meals; ?></td>
                            <td class="font-bold text-blue-600">৳<?php echo number_format($total_fish_cost, 0); ?></td>
                            <td class="font-bold"><?php echo $dim_meals; ?></td>
                            <td class="font-bold text-yellow-600">৳<?php echo number_format($total_dim_cost, 0); ?></td>
                            <td class="font-bold text-green-600">৳<?php echo number_format($total_other_cost, 0); ?></td>
                            <td class="font-bold"><?php echo $total_special_meals; ?></td>
                            <td class="font-bold text-pink-600">৳<?php echo number_format($total_special_cost, 0); ?></td>
<<<<<<< HEAD
                            <td class="font-bold text-amber-700 bg-amber-200">
                                ৳<?php echo number_format($total_rice_cost_sum, 0); ?></td>
=======
                            <td class="font-bold text-amber-700 bg-amber-200">৳<?php echo number_format($total_rice_cost_sum, 0); ?></td>
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
                            <td class="font-bold"><?php echo $total_all_meals_dist; ?></td>
                            <td class="font-bold">৳<?php echo number_format($total_overall_cost, 0); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
<<<<<<< HEAD

=======
            
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
            <!-- Cost Calculation Explanation -->
            <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <h4 class="font-bold text-blue-800 mb-2">💡 How Costs Are Calculated</h4>
                <ul class="text-blue-700 text-sm space-y-1">
<<<<<<< HEAD
                    <li>• <strong>Chicken Cost:</strong> Person's chicken meals ×
                        ৳<?php echo number_format($chicken_rate, 2); ?>/meal</li>
                    <li>• <strong>Fish Cost:</strong> Person's fish meals ×
                        ৳<?php echo number_format($fish_rate, 2); ?>/meal</li>
                    <li>• <strong>Dim Cost:</strong> Person's dim meals × ৳<?php echo number_format($dim_rate, 2); ?>/meal
                    </li>
                    <li>• <strong>Other Cost:</strong> Person's ALL meals ×
                        ৳<?php echo number_format($other_rate, 2); ?>/meal (shared cost)</li>
                    <li>• <strong>Special Cost:</strong> Person's special meals ×
                        ৳<?php echo number_format($special_rate, 2); ?>/meal</li>
                    <li class="bg-amber-100 p-2 rounded mt-2">
                        <strong class="text-amber-800">🍚 Rice Cost:</strong> Person's total meals ×
                        ৳<?php echo number_format($rice_meal_rate, 2); ?>/meal
=======
                    <li>• <strong>Chicken Cost:</strong> Person's chicken meals × ৳<?php echo number_format($chicken_rate, 2); ?>/meal</li>
                    <li>• <strong>Fish Cost:</strong> Person's fish meals × ৳<?php echo number_format($fish_rate, 2); ?>/meal</li>
                    <li>• <strong>Dim Cost:</strong> Person's dim meals × ৳<?php echo number_format($dim_rate, 2); ?>/meal</li>
                    <li>• <strong>Other Cost:</strong> Person's ALL meals × ৳<?php echo number_format($other_rate, 2); ?>/meal (shared cost)</li>
                    <li>• <strong>Special Cost:</strong> Person's special meals × ৳<?php echo number_format($special_rate, 2); ?>/meal</li>
                    <li class="bg-amber-100 p-2 rounded mt-2">
                        <strong class="text-amber-800">🍚 Rice Cost:</strong> Person's total meals × ৳<?php echo number_format($rice_meal_rate, 2); ?>/meal 
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
                        <span class="text-amber-600">(Rice is shared based on meals eaten)</span>
                    </li>
                </ul>
                <div class="mt-3 pt-3 border-t border-blue-200">
                    <p class="text-blue-600 text-xs">
                        <strong>Note:</strong> Rice Cost is calculated based on meals consumed and included in Total Cost.
                        Rice investments are tracked separately and flow into the Balance Sheet.
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Balance Sheet Tab -->
        <?php if ($active_tab === 'balance'): ?>
            <div class="bg-white rounded-lg shadow-md overflow-x-auto">
                <table class="excel-table">
                    <thead>
                        <tr>
                            <th class="excel-header">Name</th>
                            <th class="excel-header">Total Paid</th>
<<<<<<< HEAD
=======
                            <th class="excel-header bg-amber-600">🍚 Rice Paid</th>
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
                            <th class="excel-header">Should Pay</th>
                            <th class="excel-header">Balance</th>
                            <th class="excel-header">Status</th>
                        </tr>
                    </thead>
                    <tbody>
<<<<<<< HEAD
                        <?php
                        $total_paid = 0;
                        $total_should = 0;

                        foreach ($balance_sheet as $pid => $data):
                            $total_paid += $data['total_paid'];
                            $total_should += $data['should_pay'];

=======
                        <?php 
                        $total_paid = 0;
                        $total_should = 0;
                        $total_rice_paid_bs = 0;
                        $total_rice_cost_bs = 0;
                        
                        foreach ($balance_sheet as $pid => $data): 
                            $total_paid += $data['total_paid'];
                            $total_should += $data['should_pay'];
                            $total_rice_paid_bs += $data['rice_paid'];
                            $total_rice_cost_bs += $data['rice_cost'];
                            
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
                            $balance_class = 'zero';
                            $status = '⚖️ Settled';
                            if ($data['balance'] > 0.01) {
                                $balance_class = 'positive';
                                $status = '✅ To Receive';
                            } elseif ($data['balance'] < -0.01) {
                                $balance_class = 'negative';
                                $status = '❌ To Pay';
                            }
<<<<<<< HEAD
                            ?>
                            <tr class="excel-row">
                                <td class="text-left font-medium"><?php echo htmlspecialchars($data['name']); ?></td>
                                <td class="text-green-600 font-medium">৳<?php echo number_format($data['total_paid'], 0); ?>
                                </td>
                                <td class="text-red-600 font-medium">৳<?php echo number_format($data['should_pay'], 0); ?></td>
                                <td class="<?php echo $balance_class; ?>">
                                    <?php
=======
                        ?>
                            <tr class="excel-row">
                                <td class="text-left font-medium"><?php echo htmlspecialchars($data['name']); ?></td>
                                <td class="text-green-600 font-medium">৳<?php echo number_format($data['total_paid'], 0); ?></td>
                                <td class="text-amber-500 bg-amber-50">৳<?php echo number_format($data['rice_paid'], 0); ?></td>
                                <td class="text-red-600 font-medium">৳<?php echo number_format($data['should_pay'], 0); ?></td>
                                <td class="<?php echo $balance_class; ?>">
                                    <?php 
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
                                    if ($data['balance'] > 0) {
                                        echo '+৳' . number_format($data['balance'], 0);
                                    } elseif ($data['balance'] < 0) {
                                        echo '-৳' . number_format(abs($data['balance']), 0);
                                    } else {
                                        echo '৳0';
                                    }
                                    ?>
                                </td>
                                <td><?php echo $status; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td class="font-bold text-left">TOTAL</td>
                            <td class="font-bold text-green-600">৳<?php echo number_format($total_paid, 0); ?></td>
<<<<<<< HEAD
=======
                            <td class="font-bold text-amber-500 bg-amber-50">৳<?php echo number_format($total_rice_paid_bs, 0); ?></td>
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
                            <td class="font-bold text-red-600">৳<?php echo number_format($total_should, 0); ?></td>
                            <td class="font-bold">৳<?php echo number_format($total_paid - $total_should, 0); ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
<<<<<<< HEAD

=======
            
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
            <!-- Balance Legend -->
            <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                <h4 class="font-medium mb-2">Understanding Balance:</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div class="flex items-center">
                        <span class="w-4 h-4 bg-green-500 rounded mr-2"></span>
                        <span><strong class="positive">+৳X</strong> = Person has overpaid, should receive money</span>
                    </div>
                    <div class="flex items-center">
                        <span class="w-4 h-4 bg-red-500 rounded mr-2"></span>
                        <span><strong class="negative">-৳X</strong> = Person has underpaid, should pay money</span>
                    </div>
                    <div class="flex items-center">
                        <span class="w-4 h-4 bg-gray-400 rounded mr-2"></span>
                        <span><strong class="zero">৳0</strong> = Account is settled</span>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-t border-gray-300">
                    <p class="text-gray-600 text-xs">
<<<<<<< HEAD
                        <strong>Should Pay:</strong> Includes calculated rice cost
                        (৳<?php echo number_format($rice_meal_rate, 2); ?>/meal) distributed based on meals eaten.
                    </p>
                </div>
            </div>

=======
                        <strong>🍚 Rice Paid:</strong> Amount invested for rice (included in Total Paid) | 
                        <strong>Should Pay:</strong> Includes calculated rice cost (৳<?php echo number_format($rice_meal_rate, 2); ?>/meal)
                    </p>
                </div>
            </div>
            
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
            <!-- Settlement Suggestions -->
            <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h4 class="font-bold text-yellow-800 mb-3">💰 Settlement Suggestions</h4>
                <?php
                $creditors = [];
                $debtors = [];
                foreach ($balance_sheet as $pid => $data) {
                    if ($data['balance'] > 1) {
                        $creditors[] = ['name' => $data['name'], 'amount' => $data['balance']];
                    } elseif ($data['balance'] < -1) {
                        $debtors[] = ['name' => $data['name'], 'amount' => abs($data['balance'])];
                    }
                }
<<<<<<< HEAD

                if (empty($creditors) && empty($debtors)):
                    ?>
=======
                
                if (empty($creditors) && empty($debtors)):
                ?>
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
                    <p class="text-yellow-700">✅ All accounts are settled! No pending payments.</p>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php if (!empty($debtors)): ?>
                            <div>
                                <h5 class="font-medium text-red-700 mb-2">❌ Should Pay:</h5>
                                <ul class="text-red-600 text-sm space-y-1">
                                    <?php foreach ($debtors as $debtor): ?>
<<<<<<< HEAD
                                        <li>• <?php echo htmlspecialchars($debtor['name']); ?> →
                                            ৳<?php echo number_format($debtor['amount'], 0); ?></li>
=======
                                        <li>• <?php echo htmlspecialchars($debtor['name']); ?> → ৳<?php echo number_format($debtor['amount'], 0); ?></li>
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($creditors)): ?>
                            <div>
                                <h5 class="font-medium text-green-700 mb-2">✅ Should Receive:</h5>
                                <ul class="text-green-600 text-sm space-y-1">
                                    <?php foreach ($creditors as $creditor): ?>
<<<<<<< HEAD
                                        <li>• <?php echo htmlspecialchars($creditor['name']); ?> →
                                            ৳<?php echo number_format($creditor['amount'], 0); ?></li>
=======
                                        <li>• <?php echo htmlspecialchars($creditor['name']); ?> → ৳<?php echo number_format($creditor['amount'], 0); ?></li>
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
<<<<<<< HEAD

        <!-- Bazar Details Tab -->
        <?php if ($active_tab === 'bazar_details'): ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <!-- Header with Filter -->
                <div class="px-6 py-4 bg-gradient-to-r from-green-600 to-teal-600 text-white">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-bold">🛒 Bazar Details</h2>
                            <p class="text-green-100 text-sm">Item-wise breakdown of all bazar purchases for
                                <?php echo date('F Y', strtotime($month_start)); ?>
                            </p>
                        </div>

                        <!-- Person Filter Dropdown -->
                        <div class="flex items-center gap-2">
                            <label for="personFilter" class="text-green-100 text-sm font-medium whitespace-nowrap">
                                👤 Filter by Person:
                            </label>
                            <select id="personFilter"
                                class="px-4 py-2 rounded-lg bg-white text-gray-800 border-0 focus:ring-2 focus:ring-green-300 text-sm font-medium min-w-[160px]"
                                onchange="filterByPerson(this.value)">
                                <option value="all">All Persons</option>
                                <?php foreach ($bazar_by_person as $pid => $pdata): ?>
                                    <option value="<?php echo $pid; ?>">
                                        <?php echo htmlspecialchars($pdata['name']); ?>
                                        (৳<?php echo number_format($pdata['total'], 0); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" onclick="resetPersonFilter()"
                                class="px-3 py-2 bg-white/20 hover:bg-white/30 rounded-lg text-sm transition"
                                title="Reset Filter">
                                ↺
                            </button>
                        </div>
                    </div>
                </div>

                <?php if (empty($bazar_details)): ?>
                    <!-- Empty State -->
                    <div class="p-12 text-center">
                        <div class="text-6xl mb-4">🛒</div>
                        <h3 class="text-xl font-bold text-gray-600 mb-2">No Bazar Data Found</h3>
                        <p class="text-gray-500">No bazar entries have been recorded for
                            <?php echo date('F Y', strtotime($month_start)); ?>.
                        </p>
                        <a href="bazar.php"
                            class="inline-block mt-4 px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                            ➕ Add Bazar Entry
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Filtered Person Summary (shown when filtering) -->
                    <div id="filteredSummary" class="hidden p-4 bg-blue-50 border-b border-blue-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="text-3xl">👤</span>
                                <div>
                                    <h3 class="font-bold text-blue-800" id="filteredPersonName">-</h3>
                                    <p class="text-blue-600 text-sm"><span id="filteredEntryCount">0</span> entries</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl font-bold text-blue-800">৳<span id="filteredTotal">0</span></div>
                                <div class="text-blue-600 text-sm">Total Contribution</div>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Stats -->
                    <div id="allPersonsStats" class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 bg-gray-50 border-b">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600"><?php echo count($bazar_details); ?></div>
                            <div class="text-gray-500 text-sm">Total Entries</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600"><?php echo count($bazar_by_person); ?></div>
                            <div class="text-gray-500 text-sm">Contributors</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-purple-600">
                                <?php echo count(array_unique(array_column($bazar_details, 'bazar_date'))); ?>
                            </div>
                            <div class="text-gray-500 text-sm">Days with Bazar</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-amber-600">৳<?php echo number_format($total_bazar, 0); ?></div>
                            <div class="text-gray-500 text-sm">Total Spent</div>
                        </div>
                    </div>

                    <!-- Person-wise Breakdown -->
                    <div class="p-4" id="personBreakdown">
                        <?php foreach ($bazar_by_person as $pid => $person_data): ?>
                            <div class="mb-6 border rounded-lg overflow-hidden person-group" data-person-id="<?php echo $pid; ?>"
                                data-person-name="<?php echo htmlspecialchars($person_data['name']); ?>"
                                data-person-total="<?php echo $person_data['total']; ?>"
                                data-entry-count="<?php echo count($person_data['items']); ?>">
                                <!-- Person Header -->
                                <div
                                    class="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-4 py-3 flex justify-between items-center">
                                    <div class="flex items-center">
                                        <span class="text-2xl mr-3">👤</span>
                                        <div>
                                            <h3 class="font-bold text-lg"><?php echo htmlspecialchars($person_data['name']); ?></h3>
                                            <p class="text-blue-100 text-sm"><?php echo count($person_data['items']); ?> entries</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-2xl font-bold">৳<?php echo number_format($person_data['total'], 0); ?>
                                        </div>
                                        <div class="text-blue-100 text-sm">Total Contribution</div>
                                    </div>
                                </div>

                                <!-- Items Table -->
                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead>
                                            <tr class="bg-gray-100">
                                                <th class="text-left px-4 py-2 text-gray-600 font-medium">Date</th>
                                                <th class="text-left px-4 py-2 text-gray-600 font-medium">Item</th>
                                                <th class="text-left px-4 py-2 text-gray-600 font-medium">Category</th>
                                                <th class="text-right px-4 py-2 text-gray-600 font-medium">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $category_icons = [
                                                'chicken' => '🍗',
                                                'fish' => '🐟',
                                                'dim' => '🥚',
                                                'other' => '🥗',
                                                'special' => '⭐',
                                                'rice' => '🍚'
                                            ];
                                            $category_colors = [
                                                'chicken' => 'text-red-600 bg-red-50',
                                                'fish' => 'text-blue-600 bg-blue-50',
                                                'dim' => 'text-yellow-600 bg-yellow-50',
                                                'other' => 'text-green-600 bg-green-50',
                                                'special' => 'text-pink-600 bg-pink-50',
                                                'rice' => 'text-amber-600 bg-amber-50'
                                            ];

                                            foreach ($person_data['items'] as $index => $item):
                                                $icon = $category_icons[$item['category']] ?? '📦';
                                                $color = $category_colors[$item['category']] ?? 'text-gray-600 bg-gray-50';
                                                ?>
                                                <tr
                                                    class="<?php echo $index % 2 === 0 ? 'bg-white' : 'bg-gray-50'; ?> border-t hover:bg-yellow-50 transition">
                                                    <td class="px-4 py-3">
                                                        <span
                                                            class="font-medium"><?php echo date('d M', strtotime($item['bazar_date'])); ?></span>
                                                        <span
                                                            class="text-gray-400 text-sm ml-1"><?php echo date('D', strtotime($item['bazar_date'])); ?></span>
                                                    </td>
                                                    <td class="px-4 py-3 font-medium">
                                                        <?php echo htmlspecialchars($item['item_name']); ?>
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <span
                                                            class="inline-flex items-center px-2 py-1 rounded-full text-sm font-medium <?php echo $color; ?>">
                                                            <?php echo $icon; ?>                 <?php echo ucfirst($item['category']); ?>
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-3 text-right font-bold text-gray-800">
                                                        ৳<?php echo number_format($item['amount'], 0); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr class="bg-blue-50 border-t-2 border-blue-200">
                                                <td colspan="3" class="px-4 py-3 font-bold text-blue-800">Subtotal for
                                                    <?php echo htmlspecialchars($person_data['name']); ?>
                                                </td>
                                                <td class="px-4 py-3 text-right font-bold text-blue-800 text-lg">
                                                    ৳<?php echo number_format($person_data['total'], 0); ?></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Grand Total -->
                    <div id="grandTotalSection" class="p-4 bg-gradient-to-r from-green-600 to-teal-600 text-white">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="font-bold text-lg">Grand Total (All Members)</h3>
                                <p class="text-green-100 text-sm"><?php echo count($bazar_details); ?> items from
                                    <?php echo count($bazar_by_person); ?> contributors
                                </p>
                            </div>
                            <div class="text-3xl font-bold">৳<?php echo number_format($total_bazar, 0); ?></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Category Legend -->
            <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                <h4 class="font-medium mb-3">📌 Category Legend</h4>
                <div class="flex flex-wrap gap-3 text-sm">
                    <span class="inline-flex items-center px-3 py-1 rounded-full bg-red-50 text-red-600">🍗
                        Chicken/Meat</span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full bg-blue-50 text-blue-600">🐟 Fish</span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full bg-yellow-50 text-yellow-600">🥚 Dim
                        (Egg)</span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full bg-green-50 text-green-600">🥗
                        Other/Vegetables</span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full bg-pink-50 text-pink-600">⭐ Special
                        Meal</span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full bg-amber-50 text-amber-600">🍚 Rice</span>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-4 mt-8">
        <div class="max-w-7xl mx-auto px-4 text-center text-sm">
            <p>Bachelor Meal System &copy; <?php echo date('Y'); ?> | 📊 Generated on
                <?php echo date('d M Y, h:i A'); ?>
            </p>
        </div>
    </footer>

    <!-- Person Filter Script -->
    <script>
        function filterByPerson(personId) {
            const personGroups = document.querySelectorAll('.person-group');
            const filteredSummary = document.getElementById('filteredSummary');
            const allPersonsStats = document.getElementById('allPersonsStats');
            const grandTotalSection = document.getElementById('grandTotalSection');

            if (personId === 'all') {
                // Show all persons
                personGroups.forEach(group => {
                    group.style.display = 'block';
                });

                // Hide filtered summary, show all-persons stats
                if (filteredSummary) filteredSummary.classList.add('hidden');
                if (allPersonsStats) allPersonsStats.classList.remove('hidden');
                if (grandTotalSection) grandTotalSection.classList.remove('hidden');
            } else {
                // Show only selected person
                let selectedGroup = null;
                personGroups.forEach(group => {
                    if (group.dataset.personId === personId) {
                        group.style.display = 'block';
                        selectedGroup = group;
                    } else {
                        group.style.display = 'none';
                    }
                });

                // Update filtered summary
                if (selectedGroup && filteredSummary) {
                    const personName = selectedGroup.dataset.personName;
                    const personTotal = parseFloat(selectedGroup.dataset.personTotal);
                    const entryCount = selectedGroup.dataset.entryCount;

                    document.getElementById('filteredPersonName').textContent = personName;
                    document.getElementById('filteredTotal').textContent = formatNumber(personTotal);
                    document.getElementById('filteredEntryCount').textContent = entryCount;

                    filteredSummary.classList.remove('hidden');
                    if (allPersonsStats) allPersonsStats.classList.add('hidden');
                    if (grandTotalSection) grandTotalSection.classList.add('hidden');
                }
            }
        }

        function resetPersonFilter() {
            const dropdown = document.getElementById('personFilter');
            if (dropdown) {
                dropdown.value = 'all';
                filterByPerson('all');
            }
        }

        function formatNumber(num) {
            return num.toLocaleString('en-BD', { maximumFractionDigits: 0 });
        }
    </script>
</body>

</html>
<?php $conn->close(); ?>
=======
    </div>
    
    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-4 mt-8">
        <div class="max-w-7xl mx-auto px-4 text-center text-sm">
            <p>Bachelor Meal System &copy; <?php echo date('Y'); ?> | 📊 Generated on <?php echo date('d M Y, h:i A'); ?></p>
        </div>
    </footer>
</body>
</html>
<?php $conn->close(); ?>
>>>>>>> 35f112edd6abb6d005e40b6e2a81677083e2edf7

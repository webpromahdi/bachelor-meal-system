<?php
/**
 * summary.php - Monthly Summary with Excel-Style Tabs
 * 
 * DESIGN PRINCIPLES:
 * 1. 4 tabs matching Excel sheets: Daily Log, Monthly Summary, Cost Distribution, Balance Sheet
 * 2. Guest columns COMPLETELY REMOVED
 * 3. Friday Special handling - distributed only among Friday lunch eaters
 * 
 * CATEGORY CLASSIFICATION:
 * ============================================
 * MEAL CATEGORIES (have meal counts, shared by meals):
 *   - Chicken: Has meals, has cost, included in total meals
 *   - Fish: Has meals, has cost, included in total meals
 *   - Dim (Egg): FULL MEAL CATEGORY - has meals, has cost, included in total meals
 *   - Other/Veg: Has meals, has cost, included in total meals
 *   - Friday Special: Has meals (Friday lunch only), has cost
 * 
 * PERSON-WISE INVESTMENT CATEGORIES (NOT shared, NOT a meal):
 *   - Rice (Chal): PERSON-WISE COST - added ONLY to the payer's total cost
 *     * Rice is NOT a meal
 *     * Rice has NO meal count
 *     * Rice is NOT shared by meals
 *     * Rice amount is added ONLY to the person who paid for it
 * ============================================
 * 
 * PERSON TOTAL COST FORMULA:
 *   Person Total Cost =
 *       Meal-Based Cost (Chicken + Fish + Dim + Other + Friday distributed by meals)
 *     + Rice Cost Paid By That Person (DIRECT PERSON-WISE cost)
 */

require_once '../config/database.php';

// Get selected month (default to current month)
$selected_month = $_GET['month'] ?? date('Y-m');
$month_start = $selected_month . '-01';
$month_end = date('Y-m-t', strtotime($month_start));

// ============================================
// FETCH ALL PERSONS
// ============================================
$persons = [];
$person_result = $conn->query("SELECT id, name FROM persons ORDER BY name");
if ($person_result) {
    while ($row = $person_result->fetch_assoc()) {
        $persons[$row['id']] = $row['name'];
    }
    $person_result->free();
}

// ============================================
// FETCH DAILY MEALS DATA (No Guest Columns - guests removed)
// ============================================
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

// ============================================
// FETCH BAZAR DATA BY CATEGORY (FOR DAILY MEAL LOG)
// ============================================
// This query fetches ALL bazar categories including DIM
// Categories fetched: chicken, fish, dim, other, friday, rice
// The query groups by bazar_date and category to get daily totals
// DIM DAILY COST: Fetched here with category = 'dim' from bazar_items
// ============================================
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

// ============================================
// STORE BAZAR DATA BY DATE
// ============================================
// Structure: $bazar_by_date[date][category] = amount
// Example: $bazar_by_date['2026-01-15']['dim'] = 150.00
// DIM is stored with key 'dim' exactly as saved in bazar_items.category
// ============================================
$bazar_by_date = [];
while ($row = $bazar_result->fetch_assoc()) {
    $date = $row['bazar_date'];
    $cat = $row['category'];
    $amt = floatval($row['total_amount']);
    
    if (!isset($bazar_by_date[$date])) {
        // Initialize all categories with 0 for this date
        // This ensures dim and all other categories have a default value
        $bazar_by_date[$date] = [
            'chicken' => 0,
            'fish' => 0,
            'dim' => 0,      // DIM initialized to 0
            'other' => 0,
            'friday' => 0,
            'rice' => 0
        ];
    }
    // Set the category amount from database
    // DIM: When category = 'dim', this sets $bazar_by_date[$date]['dim'] = amount
    $bazar_by_date[$date][$cat] = $amt;
}
$stmt->close();

// ============================================
// MONTHLY CATEGORY TOTALS
// ============================================
// IMPORTANT:
// - Meal categories: chicken, fish, dim, other, friday (shared by meals)
// - Person-wise categories: rice (NOT shared, added to payer only)
// ============================================
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

// Initialize ALL categories including DIM
// Meal categories = shared by meals
// Rice = PERSON-WISE (not shared, added to payer only)
$category_totals = [
    'chicken' => 0,   // Meal category (shared)
    'fish' => 0,      // Meal category (shared)
    'dim' => 0,       // Meal category (shared) - DIM/EGG
    'other' => 0,     // Meal category (shared)
    'friday' => 0,    // Meal category (shared)
    'rice' => 0       // PERSON-WISE category (NOT shared)
];

while ($row = $cat_result->fetch_assoc()) {
    $category_totals[$row['category']] = floatval($row['total']);
}
$stmt->close();

// ============================================
// RICE COST BY PERSON (PERSON-WISE INVESTMENT)
// ============================================
// RICE LOGIC:
// - Rice is NOT a meal, has NO meal count
// - Rice is NOT shared by meals
// - Rice is a DIRECT PERSON-WISE cost
// - Rice amount is added ONLY to the person who paid (paid_by)
// - This query fetches rice cost grouped by paid_by (person_id)
// ============================================
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

// Map: rice_costs[person_id] = SUM(amount) paid by that person for rice
// Handle cases where a person paid zero rice (initialize all to 0)
$rice_costs = [];
foreach ($persons as $pid => $name) {
    $rice_costs[$pid] = 0;  // Default to 0 if person paid no rice
}
while ($row = $rice_result->fetch_assoc()) {
    $rice_costs[$row['paid_by']] = floatval($row['rice_paid']);
}
$stmt->close();

// ============================================
// TOTAL BAZAR CALCULATION
// ============================================
// Total bazar = ALL categories including rice and DIM
// (Rice is still part of total expenditure, just distributed differently)
$total_bazar = array_sum($category_totals);

// ============================================
// MEAL-BASED BAZAR (excludes rice for meal rate calculation)
// ============================================
// For calculating meal rates, we use only meal-based categories
// Rice is excluded because it's person-wise, not meal-based
// DIM IS INCLUDED in meal-based bazar (it's a meal category)
$meal_based_bazar = $category_totals['chicken'] + $category_totals['fish'] 
                  + $category_totals['dim'] + $category_totals['other'] 
                  + $category_totals['friday'];

// ============================================
// PERSON-WISE MEAL COUNTS BY TYPE
// meal_type values: chicken, fish, dim, veg
// NOTE: Rice has NO meal type - it's a person-wise cost
// ============================================
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
        'lunch_veg' => 0,
        'dinner_chicken' => 0,
        'dinner_fish' => 0,
        'dinner_dim' => 0,
        'dinner_veg' => 0,
        'total_lunch' => 0,
        'total_dinner' => 0,
        'friday_lunch' => 0    // For Friday Special distribution
    ];
}

while ($row = $person_meals_result->fetch_assoc()) {
    $pid = $row['person_id'];
    $session = $row['session'];
    $type = $row['meal_type'];
    $count = $row['meal_count'];
    
    if (!isset($person_meals[$pid])) continue;
    
    // Map meal_type to structure key
    $key = $session . '_' . $type;
    if (isset($person_meals[$pid][$key])) {
        $person_meals[$pid][$key] = $count;
    }
    
    // Update totals
    if ($session === 'lunch') {
        $person_meals[$pid]['total_lunch'] += $count;
    } else {
        $person_meals[$pid]['total_dinner'] += $count;
    }
}
$stmt->close();

// ============================================
// COUNT FRIDAY LUNCH EATERS (for Friday Special distribution)
// DAYOFWEEK: 6 = Friday in MySQL
// ============================================
$friday_lunch_sql = "
    SELECT person_id, COUNT(*) as friday_meals
    FROM daily_meals
    WHERE meal_date BETWEEN ? AND ?
      AND session = 'lunch'
      AND DAYOFWEEK(meal_date) = 6
    GROUP BY person_id";

$stmt = $conn->prepare($friday_lunch_sql);
$stmt->bind_param("ss", $month_start, $month_end);
$stmt->execute();
$friday_result = $stmt->get_result();

$friday_lunch_by_person = [];
$total_friday_lunch = 0;
while ($row = $friday_result->fetch_assoc()) {
    $friday_lunch_by_person[$row['person_id']] = $row['friday_meals'];
    $total_friday_lunch += $row['friday_meals'];
    if (isset($person_meals[$row['person_id']])) {
        $person_meals[$row['person_id']]['friday_lunch'] = $row['friday_meals'];
    }
}
$stmt->close();

// ============================================
// CALCULATE CATEGORY-WISE MEAL TOTALS
// NOTE: These are MEAL categories only
// Rice has NO meal count - it's a person-wise cost
// ============================================
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

// Initialize all meal type totals
$meal_totals = [
    'lunch_chicken' => 0, 'lunch_fish' => 0, 'lunch_dim' => 0, 'lunch_veg' => 0,
    'dinner_chicken' => 0, 'dinner_fish' => 0, 'dinner_dim' => 0, 'dinner_veg' => 0
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

// ============================================
// CALCULATE MEAL RATES PER CATEGORY
// Formula: Category Cost ÷ Category Meals
// 
// MEAL CATEGORIES (have their own meal counts):
// - Chicken, Fish, Dim, Veg/Other, Friday
// 
// RICE IS NOT INCLUDED IN MEAL RATE:
// - Rice is a PERSON-WISE cost (paid_by person only)
// - Rice is NOT divided by meal count
// - Rice is NOT shared among eaters
// ============================================

// Count meals per MEAL category (lunch + dinner combined)
$chicken_meals = $meal_totals['lunch_chicken'] + $meal_totals['dinner_chicken'];
$fish_meals = $meal_totals['lunch_fish'] + $meal_totals['dinner_fish'];
$dim_meals = $meal_totals['lunch_dim'] + $meal_totals['dinner_dim'];
$veg_meals = $meal_totals['lunch_veg'] + $meal_totals['dinner_veg'];

// All regular meals (chicken + fish + dim + veg)
// NOTE: Friday Special is ADDITIONAL, not part of regular meals
$all_regular_meals = $chicken_meals + $fish_meals + $dim_meals + $veg_meals;

// ============================================
// CATEGORY RATES CALCULATION
// NOTE: Rice has NO rate - it's person-wise, not meal-based
// ============================================

// Chicken rate = Chicken cost ÷ Chicken meals
$chicken_rate = ($chicken_meals > 0) ? $category_totals['chicken'] / $chicken_meals : 0;

// Fish rate = Fish cost ÷ Fish meals
$fish_rate = ($fish_meals > 0) ? $category_totals['fish'] / $fish_meals : 0;

// Dim rate = Dim cost ÷ Dim meals
$dim_rate = ($dim_meals > 0) ? $category_totals['dim'] / $dim_meals : 0;

// Other rate = Other cost ÷ ALL regular meals (applies to everyone)
$other_rate = ($all_regular_meals > 0) ? $category_totals['other'] / $all_regular_meals : 0;

// Friday rate = Friday cost ÷ Friday lunch meals (only Friday lunch eaters)
$friday_rate = ($total_friday_lunch > 0) ? $category_totals['friday'] / $total_friday_lunch : 0;

// ============================================
// RICE HAS NO RATE - IT'S PERSON-WISE
// ============================================
// Rice is NOT distributed by meals
// Rice cost goes ONLY to the person who paid (paid_by)
// $rice_costs[person_id] already contains the rice amount paid by each person
// No rice_rate calculation needed

// ============================================
// OVERALL MEAL RATE CALCULATION
// ============================================
// Formula:
//   (Chicken + Fish + Dim + Other + Friday costs)
//   ÷
//   (Chicken + Fish + Dim + Veg + Friday meals)
// 
// IMPORTANT:
// - Rice cost is NOT included in overall meal rate
// - Rice is person-wise, not meal-based
// - Rice is added separately to each payer's total
// ============================================

// Total monthly meals = regular meals + friday special meals
$total_monthly_meals = $all_regular_meals + $total_friday_lunch;

// Total meal-based cost = ALL category costs EXCEPT rice
// Rice is excluded from meal rate calculation
$total_meal_based_cost = $meal_based_bazar;

// Overall meal rate (excludes rice - rice is person-wise)
$overall_rate = ($total_monthly_meals > 0) ? $total_meal_based_cost / $total_monthly_meals : 0;

// ============================================
// CALCULATE COST DISTRIBUTION PER PERSON
// ============================================
// FORMULA:
//   Person Total Cost =
//       Meal-Based Cost (Chicken + Fish + Dim + Other + Friday)
//     + Rice Cost Paid By That Person (DIRECT PERSON-WISE)
// ============================================
$cost_distribution = [];
foreach ($person_meals as $pid => $data) {
    // Count person's meals by type
    $person_chicken = $data['lunch_chicken'] + $data['dinner_chicken'];
    $person_fish = $data['lunch_fish'] + $data['dinner_fish'];
    $person_dim = $data['lunch_dim'] + $data['dinner_dim'];
    $person_regular = $data['total_lunch'] + $data['total_dinner'];
    $person_friday = $data['friday_lunch'];
    
    // ============================================
    // MEAL-BASED COST CALCULATION
    // ============================================
    
    // Chicken cost = person's chicken meals × chicken rate
    $chicken_cost = $person_chicken * $chicken_rate;
    
    // Fish cost = person's fish meals × fish rate
    $fish_cost = $person_fish * $fish_rate;
    
    // Dim cost = person's dim meals × dim rate
    $dim_cost = $person_dim * $dim_rate;
    
    // Other cost = ALL person's regular meals × other rate (shared cost)
    $other_cost = $person_regular * $other_rate;
    
    // Friday Special cost = person's Friday lunch × friday rate
    $friday_cost = $person_friday * $friday_rate;
    
    // ============================================
    // RICE COST - PERSON-WISE (NOT meal-based)
    // ============================================
    // Rice cost = Amount this person paid for rice (from bazar_items.paid_by)
    // This is NOT divided by meals - it's a direct person-wise cost
    // Rice increases ONLY the payer's cost
    $rice_cost = $rice_costs[$pid] ?? 0;
    
    // ============================================
    // TOTAL PERSON COST
    // ============================================
    // = Meal-Based Costs + Rice Cost Paid By This Person
    $total_person_cost = $chicken_cost + $fish_cost + $dim_cost + $other_cost + $friday_cost + $rice_cost;
    
    $cost_distribution[$pid] = [
        'name' => $data['name'],
        'chicken_meals' => $person_chicken,
        'chicken_cost' => $chicken_cost,
        'fish_meals' => $person_fish,
        'fish_cost' => $fish_cost,
        'dim_meals' => $person_dim,
        'dim_cost' => $dim_cost,
        'other_cost' => $other_cost,
        'rice_cost' => $rice_cost,            // Rice = PERSON-WISE (paid by this person)
        'friday_meals' => $person_friday,
        'friday_cost' => $friday_cost,
        'total_meals' => $person_regular,
        'total_cost' => $total_person_cost
    ];
}

// ============================================
// FETCH PAYMENTS (Who paid how much for bazar - ALL categories)
// ============================================
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

// ============================================
// CALCULATE BALANCE SHEET
// Balance = Total Paid - Should Pay
// Positive = should receive money
// Negative = should pay money
// ============================================
$balance_sheet = [];
foreach ($cost_distribution as $pid => $data) {
    $paid = $payments[$pid] ?? 0;
    $should_pay = $data['total_cost'];
    $balance = $paid - $should_pay;
    
    $balance_sheet[$pid] = [
        'name' => $data['name'],
        'total_paid' => $paid,
        'should_pay' => $should_pay,
        'balance' => $balance,
        'rice_cost' => $data['rice_cost']  // Include rice cost for display
    ];
}

// ============================================
// FETCH MEAL MATRIX DATA (For Excel-style Person × Day × Session view)
// ============================================
// This query fetches meal records grouped by date + person + session
// For each group, it calculates:
//   - meal_type: The type of meal (chicken, fish, dim, etc.)
//   - meal_count: Total meals = SUM(1 + guest_count) to include guests
// Used to build: $meal_matrix[date][person_id][session] = ['type' => ..., 'count' => ...]
// ============================================
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

// ============================================
// BUILD MEAL MATRIX LOOKUP MAP
// ============================================
// Structure: $meal_matrix[date][person_id][session] = ['type' => meal_type, 'count' => meal_count]
// Example: $meal_matrix['2026-01-15'][3]['lunch'] = ['type' => 'chicken', 'count' => 2]
// This allows O(1) lookup when rendering the matrix table
// ============================================
$meal_matrix = [];
while ($row = $matrix_result->fetch_assoc()) {
    $date = $row['meal_date'];
    $pid = $row['person_id'];
    $session = $row['session'];
    $type = $row['meal_type'];
    $count = intval($row['meal_count']);
    
    // Initialize date array if not exists
    if (!isset($meal_matrix[$date])) {
        $meal_matrix[$date] = [];
    }
    // Initialize person array if not exists
    if (!isset($meal_matrix[$date][$pid])) {
        $meal_matrix[$date][$pid] = [];
    }
    // Store BOTH meal_type AND meal_count for this date + person + session
    // Format: ['type' => 'chicken', 'count' => 2]
    $meal_matrix[$date][$pid][$session] = [
        'type' => $type,
        'count' => $count
    ];
}
$stmt->close();

// ============================================
// MEAL TYPE SHORT CODES FOR MATRIX DISPLAY
// ============================================
// Maps meal_type values to short display codes
// Used in the matrix cells to save space
// ============================================
$meal_type_codes = [
    'chicken' => ['code' => 'C', 'color' => 'text-red-600', 'bg' => 'bg-red-50'],
    'fish' => ['code' => 'F', 'color' => 'text-blue-600', 'bg' => 'bg-blue-50'],
    'dim' => ['code' => 'D', 'color' => 'text-yellow-600', 'bg' => 'bg-yellow-50'],
    'other' => ['code' => 'O', 'color' => 'text-green-600', 'bg' => 'bg-green-50'],
    'friday' => ['code' => 'Fr', 'color' => 'text-purple-600', 'bg' => 'bg-purple-50']
];

// ============================================
// PERSON COLORS FOR MATRIX HEADERS
// ============================================
// Assigns distinct colors to each person for visual differentiation
// Colors cycle if more persons than colors available
// ============================================
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
        .tab-inactive {
            background-color: #e5e7eb;
            color: #4b5563;
        }
        .tab-inactive:hover {
            background-color: #d1d5db;
        }
        .excel-table {
            border-collapse: collapse;
            width: 100%;
        }
        .excel-table th, .excel-table td {
            border: 1px solid #d1d5db;
            padding: 8px 12px;
            text-align: center;
        }
        .excel-header {
            background-color: #374151;
            color: white;
            font-weight: 600;
        }
        .excel-row:nth-child(even) {
            background-color: #f9fafb;
        }
        .excel-row:hover {
            background-color: #fef3c7;
        }
        .total-row {
            background-color: #dbeafe;
            font-weight: 600;
        }
        .positive { color: #059669; font-weight: 600; }
        .negative { color: #dc2626; font-weight: 600; }
        .zero { color: #6b7280; }
    </style>
</head>
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
                <input type="month" 
                       name="month" 
                       value="<?php echo $selected_month; ?>"
                       class="px-4 py-2 border border-gray-300 rounded-lg"
                       onchange="this.form.submit()">
            </form>
        </div>

        <!-- Overall Stats Banner with Overall Meal Rate -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white rounded-lg p-6 mb-6 shadow-lg">
            <div class="grid grid-cols-2 md:grid-cols-7 gap-4 text-center">
                <div>
                    <div class="text-3xl font-bold"><?php echo $total_monthly_meals; ?></div>
                    <div class="text-blue-200 text-sm">Total Meals</div>
                </div>
                <div>
                    <div class="text-3xl font-bold">৳<?php echo number_format($total_bazar, 0); ?></div>
                    <div class="text-blue-200 text-sm">Total Bazar</div>
                </div>
                <div class="bg-white/20 rounded-lg p-2">
                    <div class="text-3xl font-bold text-yellow-300">৳<?php echo number_format($overall_rate, 2); ?></div>
                    <div class="text-yellow-200 text-sm font-medium">Meal Rate</div>
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
                    <div class="text-3xl font-bold"><?php echo $total_friday_lunch; ?></div>
                    <div class="text-blue-200 text-sm">Friday Specials</div>
                </div>
                <div class="bg-amber-500/30 rounded-lg p-2">
                    <div class="text-3xl font-bold text-amber-300">৳<?php echo number_format($category_totals['rice'], 0); ?></div>
                    <div class="text-amber-200 text-sm font-medium">Rice (Person-wise)</div>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="flex border-b border-gray-200 mb-6 overflow-x-auto">
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
        </div>

        <!-- ============================================ -->
        <!-- TAB 1: DAILY MEAL LOG (No Guest Columns) -->
        <!-- ============================================ -->
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
                            <th class="excel-header">🍛 Friday</th>
                            <th class="excel-header">🥗 Other</th>
                            <th class="excel-header">🍚 Rice</th>
                            <th class="excel-header">Daily Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // ============================================
                        // DAILY MEAL LOG - GRAND TOTALS INITIALIZATION
                        // ============================================
                        // Initialize grand totals for all categories including DIM
                        $grand_lunch = 0;
                        $grand_dinner = 0;
                        $grand_meals = 0;
                        $grand_chicken = 0;
                        $grand_fish = 0;
                        $grand_dim = 0;      // DIM grand total initialized
                        $grand_friday = 0;
                        $grand_other = 0;
                        $grand_rice = 0;
                        $grand_bazar_total = 0;
                        
                        // Generate all days in month
                        $current_date = $month_start;
                        while ($current_date <= $month_end):
                            $meal_data = $daily_meals[$current_date] ?? null;
                            
                            // ============================================
                            // GET BAZAR DATA FOR THIS DATE
                            // ============================================
                            // $bazar_by_date contains all categories including 'dim'
                            // If date exists in $bazar_by_date, get the array
                            // Otherwise use empty array (defaults handled below)
                            $bazar_data = $bazar_by_date[$current_date] ?? null;
                            
                            $lunch = $meal_data['lunch_meals'] ?? 0;
                            $dinner = $meal_data['dinner_meals'] ?? 0;
                            $total = $lunch + $dinner;
                            
                            // ============================================
                            // EXTRACT DAILY CATEGORY COSTS
                            // ============================================
                            // DIM DAILY COST: Fetched from $bazar_data['dim']
                            // Default to 0 if not present for this date
                            // ============================================
                            $chicken = $bazar_data['chicken'] ?? 0;
                            $fish = $bazar_data['fish'] ?? 0;
                            $dim = $bazar_data['dim'] ?? 0;      // DIM daily cost - key fix
                            $friday = $bazar_data['friday'] ?? 0;
                            $other = $bazar_data['other'] ?? 0;
                            $rice = $bazar_data['rice'] ?? 0;
                            
                            // ============================================
                            // DAILY BAZAR TOTAL - INCLUDES DIM
                            // ============================================
                            // Sum all categories for this day including dim
                            $daily_bazar = $chicken + $fish + $dim + $friday + $other + $rice;
                            
                            // ============================================
                            // ACCUMULATE GRAND TOTALS - INCLUDES DIM
                            // ============================================
                            $grand_lunch += $lunch;
                            $grand_dinner += $dinner;
                            $grand_meals += $total;
                            $grand_chicken += $chicken;
                            $grand_fish += $fish;
                            $grand_dim += $dim;      // DIM added to grand total
                            $grand_friday += $friday;
                            $grand_other += $other;
                            $grand_rice += $rice;
                            $grand_bazar_total += $daily_bazar;
                            
                            $day_name = date('D', strtotime($current_date));
                            $is_friday = (date('N', strtotime($current_date)) == 5);
                        ?>
                            <tr class="excel-row <?php echo $is_friday ? 'bg-purple-50' : ''; ?>">
                                <td class="font-medium"><?php echo date('d M', strtotime($current_date)); ?></td>
                                <td class="<?php echo $is_friday ? 'text-purple-600 font-bold' : ''; ?>">
                                    <?php echo $day_name; ?>
                                    <?php if ($is_friday): ?>🍛<?php endif; ?>
                                </td>
                                <td><?php echo $lunch ?: '-'; ?></td>
                                <td><?php echo $dinner ?: '-'; ?></td>
                                <td class="font-medium"><?php echo $total ?: '-'; ?></td>
                                <td class="text-red-600"><?php echo $chicken ? '৳' . number_format($chicken, 0) : '-'; ?></td>
                                <td class="text-blue-600"><?php echo $fish ? '৳' . number_format($fish, 0) : '-'; ?></td>
                                <!-- DIM COLUMN - Displays daily dim cost from bazar_items -->
                                <td class="text-yellow-600"><?php echo $dim ? '৳' . number_format($dim, 0) : '-'; ?></td>
                                <td class="text-purple-600"><?php echo $friday ? '৳' . number_format($friday, 0) : '-'; ?></td>
                                <td class="text-green-600"><?php echo $other ? '৳' . number_format($other, 0) : '-'; ?></td>
                                <td class="text-amber-600"><?php echo $rice ? '৳' . number_format($rice, 0) : '-'; ?></td>
                                <td class="font-bold"><?php echo $daily_bazar ? '৳' . number_format($daily_bazar, 0) : '-'; ?></td>
                            </tr>
                        <?php 
                            $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
                        endwhile; 
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
                            <!-- DIM GRAND TOTAL - Sum of all daily dim costs -->
                            <td class="font-bold text-yellow-600">৳<?php echo number_format($grand_dim, 0); ?></td>
                            <td class="font-bold text-purple-600">৳<?php echo number_format($grand_friday, 0); ?></td>
                            <td class="font-bold text-green-600">৳<?php echo number_format($grand_other, 0); ?></td>
                            <td class="font-bold text-amber-600">৳<?php echo number_format($grand_rice, 0); ?></td>
                            <td class="font-bold">৳<?php echo number_format($grand_bazar_total, 0); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <!-- Legend -->
            <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                <h4 class="font-medium mb-2">Legend:</h4>
                <div class="flex flex-wrap gap-4 text-sm">
                    <span class="text-red-600">🍗 Chicken/Meat</span>
                    <span class="text-blue-600">🐟 Fish</span>
                    <span class="text-yellow-600">🥚 Dim (Egg)</span>
                    <span class="text-purple-600">🍛 Friday Special</span>
                    <span class="text-green-600">🥗 Other/Veg</span>
                    <span class="text-amber-600 font-medium">🍚 Rice (Person-wise Investment)</span>
                </div>
                <p class="text-xs text-gray-500 mt-2">* Rice cost is NOT shared - it's added to the payer's total only</p>
            </div>
        <?php endif; ?>

        <!-- ============================================ -->
        <!-- TAB: MONTHLY MEAL MATRIX (Excel-style Person × Day × Session) -->
        <!-- ============================================ -->
        <!-- 
            DESIGN: Visual matrix matching the raw daily_meals table
            ROWS: One row per calendar date (1-31)
            COLUMNS: Date | Person1 Day | Person1 Night | Person2 Day | Person2 Night | ...
            CELL: meal_type code (C/F/D/O/Fr) or '-' if no meal
            READ-ONLY: No edit/delete buttons, no cost calculations
        -->
        <?php if ($active_tab === 'matrix'): ?>
            <div class="bg-white rounded-lg shadow-md overflow-x-auto">
                <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 text-white">
                    <h2 class="text-xl font-bold">📅 Monthly Meal Matrix</h2>
                    <p class="text-blue-100 text-sm">Person × Day × Session view of all meals</p>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="excel-table" style="min-width: 100%;">
                        <!-- ============================================ -->
                        <!-- MATRIX HEADER ROW 1: Person Names (Colored Blocks) -->
                        <!-- Each person spans 2 columns (Day + Night) -->
                        <!-- ============================================ -->
                        <thead>
                            <tr>
                                <th class="excel-header" rowspan="2" style="min-width: 80px;">Date</th>
                                <?php 
                                // ============================================
                                // RENDER PERSON NAME HEADERS
                                // ============================================
                                // Each person gets a colored header spanning 2 columns
                                // Colors are assigned from $person_colors array (cycles)
                                $person_index = 0;
                                foreach ($persons as $pid => $name): 
                                    $color = $person_colors[$person_index % count($person_colors)];
                                    $person_index++;
                                ?>
                                    <th colspan="2" 
                                        class="<?php echo $color['bg']; ?> <?php echo $color['text']; ?> font-bold text-center px-4 py-2"
                                        style="min-width: 120px;">
                                        <?php echo strtoupper(htmlspecialchars($name)); ?>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                            <!-- ============================================ -->
                            <!-- MATRIX HEADER ROW 2: Day/Night Sub-headers -->
                            <!-- Under each person: Day (Lunch) | Night (Dinner) -->
                            <!-- ============================================ -->
                            <tr>
                                <?php foreach ($persons as $pid => $name): ?>
                                    <th class="bg-amber-100 text-amber-800 font-medium text-center text-sm px-2 py-1" style="min-width: 50px;">
                                        Day
                                    </th>
                                    <th class="bg-indigo-100 text-indigo-800 font-medium text-center text-sm px-2 py-1" style="min-width: 50px;">
                                        Night
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        
                        <tbody>
                            <?php 
                            // ============================================
                            // MATRIX BODY: One Row Per Date
                            // ============================================
                            // Generate all dates for the selected month
                            // For each date, show meal_type for each person + session
                            // Lookup: $meal_matrix[date][person_id][session]
                            // ============================================
                            $current_date = $month_start;
                            $row_count = 0;
                            
                            while ($current_date <= $month_end):
                                $day_num = date('j', strtotime($current_date));
                                $day_name = date('D', strtotime($current_date));
                                $is_friday = (date('N', strtotime($current_date)) == 5);
                                $row_count++;
                            ?>
                                <tr class="excel-row <?php echo $is_friday ? 'bg-purple-50' : ''; ?>">
                                    <!-- ============================================ -->
                                    <!-- DATE COLUMN -->
                                    <!-- Shows: Day number + Day name (e.g., "15 Mon") -->
                                    <!-- Friday rows are highlighted with purple -->
                                    <!-- ============================================ -->
                                    <td class="font-medium text-center <?php echo $is_friday ? 'text-purple-700 font-bold' : 'text-gray-700'; ?>" style="white-space: nowrap;">
                                        <?php echo $day_num; ?>
                                        <span class="text-xs <?php echo $is_friday ? 'text-purple-600' : 'text-gray-500'; ?>">
                                            <?php echo $day_name; ?>
                                        </span>
                                        <?php if ($is_friday): ?>🍛<?php endif; ?>
                                    </td>
                                    
                                    <?php 
                                    // ============================================
                                    // PERSON COLUMNS (Day + Night for each person)
                                    // ============================================
                                    // For each person, render two cells:
                                    //   1. Day (Lunch) meal
                                    //   2. Night (Dinner) meal
                                    // Lookup: $meal_matrix[$current_date][$pid]['lunch'/'dinner']
                                    // Each cell stores: ['type' => meal_type, 'count' => meal_count]
                                    // ============================================
                                    foreach ($persons as $pid => $name): 
                                        // Get lunch meal data for this date + person
                                        // Returns: ['type' => 'chicken', 'count' => 2] or null
                                        $lunch_data = $meal_matrix[$current_date][$pid]['lunch'] ?? null;
                                        // Get dinner meal data for this date + person
                                        $dinner_data = $meal_matrix[$current_date][$pid]['dinner'] ?? null;
                                        
                                        // Extract lunch type and count
                                        $lunch_type = $lunch_data['type'] ?? null;
                                        $lunch_count = $lunch_data['count'] ?? 0;
                                        
                                        // Extract dinner type and count
                                        $dinner_type = $dinner_data['type'] ?? null;
                                        $dinner_count = $dinner_data['count'] ?? 0;
                                        
                                        // Get display info for lunch (code, color, bg)
                                        $lunch_info = $lunch_type ? ($meal_type_codes[$lunch_type] ?? ['code' => '?', 'color' => 'text-gray-500', 'bg' => '']) : null;
                                        // Get display info for dinner (code, color, bg)
                                        $dinner_info = $dinner_type ? ($meal_type_codes[$dinner_type] ?? ['code' => '?', 'color' => 'text-gray-500', 'bg' => '']) : null;
                                    ?>
                                        <!-- ============================================ -->
                                        <!-- DAY (LUNCH) CELL -->
                                        <!-- CELL FORMAT: meal_count + meal_type_code -->
                                        <!-- Examples: 1C (1 Chicken), 2F (2 Fish), 3D (3 Dim) -->
                                        <!-- Shows '-' if no meal (count = 0) -->
                                        <!-- ============================================ -->
                                        <td class="text-center <?php echo $lunch_info ? $lunch_info['bg'] . ' ' . $lunch_info['color'] . ' font-bold' : 'text-gray-300'; ?>">
                                            <?php echo ($lunch_count > 0 && $lunch_info) ? $lunch_count . $lunch_info['code'] : '-'; ?>
                                        </td>
                                        
                                        <!-- ============================================ -->
                                        <!-- NIGHT (DINNER) CELL -->
                                        <!-- CELL FORMAT: meal_count + meal_type_code -->
                                        <!-- Examples: 1C (1 Chicken), 2F (2 Fish), 3D (3 Dim) -->
                                        <!-- Shows '-' if no meal (count = 0) -->
                                        <!-- ============================================ -->
                                        <td class="text-center <?php echo $dinner_info ? $dinner_info['bg'] . ' ' . $dinner_info['color'] . ' font-bold' : 'text-gray-300'; ?>">
                                            <?php echo ($dinner_count > 0 && $dinner_info) ? $dinner_count . $dinner_info['code'] : '-'; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php 
                                $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
                            endwhile; 
                            ?>
                        </tbody>
                        
                        <!-- ============================================ -->
                        <!-- MATRIX FOOTER: Meal Count Totals per Person -->
                        <!-- ============================================ -->
                        <tfoot>
                            <tr class="total-row">
                                <td class="font-bold">Total</td>
                                <?php 
                                // ============================================
                                // COUNT MEALS PER PERSON + SESSION
                                // ============================================
                                // For each person, SUM total Day (lunch) and Night (dinner) meals
                                // Uses the 'count' value from matrix data (includes guests)
                                // ============================================
                                foreach ($persons as $pid => $name): 
                                    $person_lunch_count = 0;
                                    $person_dinner_count = 0;
                                    
                                    // Sum meal counts from the matrix data
                                    foreach ($meal_matrix as $date => $person_meals_data) {
                                        if (isset($person_meals_data[$pid]['lunch'])) {
                                            // Add the meal count (not just 1, but actual count including guests)
                                            $person_lunch_count += $person_meals_data[$pid]['lunch']['count'] ?? 0;
                                        }
                                        if (isset($person_meals_data[$pid]['dinner'])) {
                                            // Add the meal count (not just 1, but actual count including guests)
                                            $person_dinner_count += $person_meals_data[$pid]['dinner']['count'] ?? 0;
                                        }
                                    }
                                ?>
                                    <td class="font-bold text-amber-700 bg-amber-50"><?php echo $person_lunch_count; ?></td>
                                    <td class="font-bold text-indigo-700 bg-indigo-50"><?php echo $person_dinner_count; ?></td>
                                <?php endforeach; ?>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            
            <!-- Matrix Legend -->
            <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                <h4 class="font-medium mb-3">📌 Cell Format: <span class="text-blue-600">COUNT + TYPE CODE</span></h4>
                <div class="flex flex-wrap gap-4 text-sm mb-3">
                    <span class="px-3 py-1 bg-red-50 text-red-600 font-bold rounded">1C = 1 Chicken</span>
                    <span class="px-3 py-1 bg-blue-50 text-blue-600 font-bold rounded">2F = 2 Fish</span>
                    <span class="px-3 py-1 bg-yellow-50 text-yellow-600 font-bold rounded">3D = 3 Dim (Egg)</span>
                    <span class="px-3 py-1 bg-green-50 text-green-600 font-bold rounded">1O = 1 Other/Veg</span>
                    <span class="px-3 py-1 bg-purple-50 text-purple-600 font-bold rounded">2Fr = 2 Friday</span>
                    <span class="px-3 py-1 bg-gray-100 text-gray-400 rounded">- = No Meal</span>
                </div>
                <div class="text-xs text-gray-500 space-y-1">
                    <p>• <strong>Day</strong> = Lunch session | <strong>Night</strong> = Dinner session</p>
                    <p>• Number indicates meal count (including guests)</p>
                    <p>• Purple-highlighted rows = Friday</p>
                    <p>• This is a READ-ONLY view of the daily_meals table</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- ============================================ -->
        <!-- TAB 2: MONTHLY SUMMARY -->
        <!-- ============================================ -->
        <?php if ($active_tab === 'summary'): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Category Summary with All Categories -->
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
                            <!-- MEAL CATEGORIES (have meal counts, shared) -->
                            <tr class="excel-row">
                                <td class="text-left">🍗 Chicken/Meat</td>
                                <td class="text-red-600 font-medium">৳<?php echo number_format($category_totals['chicken'], 0); ?></td>
                                <td><?php echo $chicken_meals; ?></td>
                                <td class="font-medium">৳<?php echo number_format($chicken_rate, 2); ?></td>
                            </tr>
                            <tr class="excel-row">
                                <td class="text-left">🐟 Fish</td>
                                <td class="text-blue-600 font-medium">৳<?php echo number_format($category_totals['fish'], 0); ?></td>
                                <td><?php echo $fish_meals; ?></td>
                                <td class="font-medium">৳<?php echo number_format($fish_rate, 2); ?></td>
                            </tr>
                            <tr class="excel-row">
                                <td class="text-left">🥚 Dim (Egg)</td>
                                <td class="text-yellow-600 font-medium">৳<?php echo number_format($category_totals['dim'], 0); ?></td>
                                <td><?php echo $dim_meals; ?></td>
                                <td class="font-medium">৳<?php echo number_format($dim_rate, 2); ?></td>
                            </tr>
                            <tr class="excel-row">
                                <td class="text-left">🍛 Friday Special</td>
                                <td class="text-purple-600 font-medium">৳<?php echo number_format($category_totals['friday'], 0); ?></td>
                                <td><?php echo $total_friday_lunch; ?></td>
                                <td class="font-medium">৳<?php echo number_format($friday_rate, 2); ?></td>
                            </tr>
                            <tr class="excel-row">
                                <td class="text-left">🥗 Other/Veg</td>
                                <td class="text-green-600 font-medium">৳<?php echo number_format($category_totals['other'], 0); ?></td>
                                <td><?php echo $all_regular_meals; ?> <span class="text-xs text-gray-500">(all)</span></td>
                                <td class="font-medium">৳<?php echo number_format($other_rate, 2); ?></td>
                            </tr>
                            <!-- Rice = PERSON-WISE INVESTMENT (NOT shared, NOT a meal) -->
                            <tr class="excel-row bg-amber-50">
                                <td class="text-left">🍚 Rice (Chal) <span class="text-xs text-amber-600 font-bold">- Person-wise</span></td>
                                <td class="text-amber-600 font-medium">৳<?php echo number_format($category_totals['rice'], 0); ?></td>
                                <td class="text-gray-400">N/A</td>
                                <td class="text-gray-400">N/A</td>
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
                                <td class="font-bold" colspan="3">৳<?php echo number_format($total_bazar, 0); ?> <span class="text-xs font-normal">(incl. Rice ৳<?php echo number_format($category_totals['rice'], 0); ?>)</span></td>
                            </tr>
                        </tfoot>
                    </table>
                    <div class="mt-2 text-xs text-gray-500 space-y-1">
                        <p>* Meal-based costs are shared based on meal counts</p>
                        <p class="text-amber-700 font-medium">* Rice is PERSON-WISE: cost goes ONLY to the payer, NOT shared</p>
                    </div>
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
                                <th class="excel-header">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="excel-row">
                                <td class="text-left font-medium">☀️ Lunch</td>
                                <td><?php echo $meal_totals['lunch_chicken']; ?></td>
                                <td><?php echo $meal_totals['lunch_fish']; ?></td>
                                <td><?php echo $meal_totals['lunch_dim']; ?></td>
                                <td><?php echo $meal_totals['lunch_veg']; ?></td>
                                <td class="font-medium"><?php echo $meal_totals['lunch_chicken'] + $meal_totals['lunch_fish'] + $meal_totals['lunch_dim'] + $meal_totals['lunch_veg']; ?></td>
                            </tr>
                            <tr class="excel-row">
                                <td class="text-left font-medium">🌙 Dinner</td>
                                <td><?php echo $meal_totals['dinner_chicken']; ?></td>
                                <td><?php echo $meal_totals['dinner_fish']; ?></td>
                                <td><?php echo $meal_totals['dinner_dim']; ?></td>
                                <td><?php echo $meal_totals['dinner_veg']; ?></td>
                                <td class="font-medium"><?php echo $meal_totals['dinner_chicken'] + $meal_totals['dinner_fish'] + $meal_totals['dinner_dim'] + $meal_totals['dinner_veg']; ?></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td class="font-bold text-left">TOTAL</td>
                                <td class="font-bold"><?php echo $chicken_meals; ?></td>
                                <td class="font-bold"><?php echo $fish_meals; ?></td>
                                <td class="font-bold"><?php echo $dim_meals; ?></td>
                                <td class="font-bold"><?php echo $veg_meals; ?></td>
                                <td class="font-bold"><?php echo $all_regular_meals; ?></td>
                            </tr>
                        </tfoot>
                    </table>
                    <p class="mt-2 text-xs text-gray-500">Note: Rice has no meal count (person-wise investment)</p>
                    
                    <!-- Rice Distribution by Person -->
                    <h4 class="text-md font-bold mt-4 mb-2 text-amber-700">🍚 Rice Investment by Person</h4>
                    <table class="excel-table">
                        <thead>
                            <tr>
                                <th class="excel-header">Person</th>
                                <th class="excel-header">Rice Paid</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($persons as $pid => $name): ?>
                                <?php if ($rice_costs[$pid] > 0): ?>
                                <tr class="excel-row">
                                    <td class="text-left"><?php echo htmlspecialchars($name); ?></td>
                                    <td class="text-amber-600 font-medium">৳<?php echo number_format($rice_costs[$pid], 0); ?></td>
                                </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="total-row bg-amber-100">
                                <td class="font-bold text-left">TOTAL RICE</td>
                                <td class="font-bold text-amber-600">৳<?php echo number_format($category_totals['rice'], 0); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            
            <!-- Overall Meal Rate Highlight Box -->
            <div class="mt-6 bg-gradient-to-r from-yellow-400 to-orange-500 rounded-lg p-6 shadow-lg text-center">
                <h3 class="text-xl font-bold text-white mb-2">📊 Overall Meal Rate (Excludes Rice)</h3>
                <div class="text-5xl font-bold text-white">৳<?php echo number_format($overall_rate, 2); ?></div>
                <p class="text-white/90 mt-2">
                    (Chicken + Fish + Dim + Other + Friday) ÷ Total Monthly Meals
                </p>
                <p class="text-white/80 text-sm mt-1">
                    ৳<?php echo number_format($meal_based_bazar, 0); ?> ÷ <?php echo $total_monthly_meals; ?> meals
                </p>
                <p class="text-white/70 text-xs mt-2">
                    * Rice (৳<?php echo number_format($category_totals['rice'], 0); ?>) is EXCLUDED - it's person-wise investment
                </p>
            </div>
            
            <!-- Person-wise Meal Breakdown -->
            <div class="bg-white rounded-lg shadow-md p-6 mt-6">
                <h3 class="text-lg font-bold mb-4 text-gray-800">👥 Person-wise Meal Breakdown</h3>
                <div class="overflow-x-auto">
                    <table class="excel-table">
                        <thead>
                            <tr>
                                <th class="excel-header">Name</th>
                                <th class="excel-header" colspan="4">Lunch</th>
                                <th class="excel-header" colspan="4">Dinner</th>
                                <th class="excel-header">Friday</th>
                                <th class="excel-header">Total</th>
                                <th class="excel-header bg-amber-600">Rice Paid</th>
                            </tr>
                            <tr>
                                <th class="excel-header"></th>
                                <th class="excel-header text-xs">🍗</th>
                                <th class="excel-header text-xs">🐟</th>
                                <th class="excel-header text-xs">🥚</th>
                                <th class="excel-header text-xs">🥗</th>
                                <th class="excel-header text-xs">🍗</th>
                                <th class="excel-header text-xs">🐟</th>
                                <th class="excel-header text-xs">🥚</th>
                                <th class="excel-header text-xs">🥗</th>
                                <th class="excel-header text-xs">🍛</th>
                                <th class="excel-header"></th>
                                <th class="excel-header text-xs bg-amber-600">🍚</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($person_meals as $pid => $data): ?>
                                <tr class="excel-row">
                                    <td class="text-left font-medium"><?php echo htmlspecialchars($data['name']); ?></td>
                                    <td><?php echo $data['lunch_chicken'] ?: '-'; ?></td>
                                    <td><?php echo $data['lunch_fish'] ?: '-'; ?></td>
                                    <td><?php echo $data['lunch_dim'] ?: '-'; ?></td>
                                    <td><?php echo $data['lunch_veg'] ?: '-'; ?></td>
                                    <td><?php echo $data['dinner_chicken'] ?: '-'; ?></td>
                                    <td><?php echo $data['dinner_fish'] ?: '-'; ?></td>
                                    <td><?php echo $data['dinner_dim'] ?: '-'; ?></td>
                                    <td><?php echo $data['dinner_veg'] ?: '-'; ?></td>
                                    <td class="text-purple-600"><?php echo $data['friday_lunch'] ?: '-'; ?></td>
                                    <td class="font-bold"><?php echo $data['total_lunch'] + $data['total_dinner']; ?></td>
                                    <td class="text-amber-600 font-medium bg-amber-50"><?php echo $rice_costs[$pid] > 0 ? '৳' . number_format($rice_costs[$pid], 0) : '-'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- ============================================ -->
        <!-- TAB 3: COST DISTRIBUTION -->
        <!-- ============================================ -->
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
                            <th class="excel-header">🍛 Meals</th>
                            <th class="excel-header">🍛 Cost</th>
                            <th class="excel-header bg-amber-600">🍚 Rice Paid</th>
                            <th class="excel-header">Total Meals</th>
                            <th class="excel-header">Total Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_chicken_cost = 0;
                        $total_fish_cost = 0;
                        $total_dim_cost = 0;
                        $total_other_cost = 0;
                        $total_rice_cost = 0;
                        $total_friday_cost = 0;
                        $total_overall_cost = 0;
                        $total_all_meals_dist = 0;
                        
                        foreach ($cost_distribution as $pid => $data): 
                            $total_chicken_cost += $data['chicken_cost'];
                            $total_fish_cost += $data['fish_cost'];
                            $total_dim_cost += $data['dim_cost'];
                            $total_other_cost += $data['other_cost'];
                            $total_rice_cost += $data['rice_cost'];
                            $total_friday_cost += $data['friday_cost'];
                            $total_overall_cost += $data['total_cost'];
                            $total_all_meals_dist += $data['total_meals'];
                        ?>
                            <tr class="excel-row">
                                <td class="text-left font-medium"><?php echo htmlspecialchars($data['name']); ?></td>
                                <td><?php echo $data['chicken_meals']; ?></td>
                                <td class="text-red-600">৳<?php echo number_format($data['chicken_cost'], 0); ?></td>
                                <td><?php echo $data['fish_meals']; ?></td>
                                <td class="text-blue-600">৳<?php echo number_format($data['fish_cost'], 0); ?></td>
                                <td><?php echo $data['dim_meals']; ?></td>
                                <td class="text-yellow-600">৳<?php echo number_format($data['dim_cost'], 0); ?></td>
                                <td class="text-green-600">৳<?php echo number_format($data['other_cost'], 0); ?></td>
                                <td class="text-purple-600"><?php echo $data['friday_meals']; ?></td>
                                <td class="text-purple-600">৳<?php echo number_format($data['friday_cost'], 0); ?></td>
                                <td class="text-amber-600 font-medium bg-amber-50">৳<?php echo number_format($data['rice_cost'], 0); ?></td>
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
                            <td class="font-bold"><?php echo $total_friday_lunch; ?></td>
                            <td class="font-bold text-purple-600">৳<?php echo number_format($total_friday_cost, 0); ?></td>
                            <td class="font-bold text-amber-600 bg-amber-100">৳<?php echo number_format($total_rice_cost, 0); ?></td>
                            <td class="font-bold"><?php echo $total_all_meals_dist; ?></td>
                            <td class="font-bold">৳<?php echo number_format($total_overall_cost, 0); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <!-- Cost Calculation Explanation -->
            <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <h4 class="font-bold text-blue-800 mb-2">💡 How Costs Are Calculated</h4>
                <ul class="text-blue-700 text-sm space-y-1">
                    <li>• <strong>Chicken Cost:</strong> Person's chicken meals × ৳<?php echo number_format($chicken_rate, 2); ?>/meal</li>
                    <li>• <strong>Fish Cost:</strong> Person's fish meals × ৳<?php echo number_format($fish_rate, 2); ?>/meal</li>
                    <li>• <strong>Dim Cost:</strong> Person's dim meals × ৳<?php echo number_format($dim_rate, 2); ?>/meal</li>
                    <li>• <strong>Other Cost:</strong> Person's ALL meals × ৳<?php echo number_format($other_rate, 2); ?>/meal (shared cost)</li>
                    <li>• <strong>Friday Cost:</strong> Person's Friday lunch × ৳<?php echo number_format($friday_rate, 2); ?>/meal</li>
                </ul>
            </div>
            
            <!-- Rice Cost Explanation (PERSON-WISE) -->
            <div class="mt-4 p-4 bg-amber-50 border border-amber-300 rounded-lg">
                <h4 class="font-bold text-amber-800 mb-2">🍚 Rice Cost (Person-wise Investment)</h4>
                <ul class="text-amber-700 text-sm space-y-1">
                    <li>• <strong>Rice is NOT a meal</strong> - it has no meal count</li>
                    <li>• <strong>Rice is NOT shared</strong> - it's not divided by meals</li>
                    <li>• <strong>Rice is PERSON-WISE</strong> - added ONLY to the payer's total cost</li>
                    <li>• <strong>Formula:</strong> Rice Cost = Amount paid by this person for rice (from bazar entries)</li>
                </ul>
                <div class="mt-2 pt-2 border-t border-amber-200">
                    <strong class="text-amber-800">Person Total Cost = Meal-Based Cost + Rice Paid By That Person</strong>
                </div>
            </div>
        <?php endif; ?>

        <!-- ============================================ -->
        <!-- TAB 4: BALANCE SHEET -->
        <!-- ============================================ -->
        <?php if ($active_tab === 'balance'): ?>
            <div class="bg-white rounded-lg shadow-md overflow-x-auto">
                <table class="excel-table">
                    <thead>
                        <tr>
                            <th class="excel-header">Name</th>
                            <th class="excel-header">Total Paid</th>
                            <th class="excel-header bg-amber-600">Rice Paid</th>
                            <th class="excel-header">Should Pay</th>
                            <th class="excel-header">Balance</th>
                            <th class="excel-header">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_paid = 0;
                        $total_should = 0;
                        $total_rice_paid = 0;
                        
                        foreach ($balance_sheet as $pid => $data): 
                            $total_paid += $data['total_paid'];
                            $total_should += $data['should_pay'];
                            $total_rice_paid += $data['rice_cost'];
                            
                            $balance_class = 'zero';
                            $status = '⚖️ Settled';
                            if ($data['balance'] > 0.01) {
                                $balance_class = 'positive';
                                $status = '✅ To Receive';
                            } elseif ($data['balance'] < -0.01) {
                                $balance_class = 'negative';
                                $status = '❌ To Pay';
                            }
                        ?>
                            <tr class="excel-row">
                                <td class="text-left font-medium"><?php echo htmlspecialchars($data['name']); ?></td>
                                <td class="text-green-600 font-medium">৳<?php echo number_format($data['total_paid'], 0); ?></td>
                                <td class="text-amber-600 font-medium bg-amber-50">৳<?php echo number_format($data['rice_cost'], 0); ?></td>
                                <td class="text-red-600 font-medium">৳<?php echo number_format($data['should_pay'], 0); ?></td>
                                <td class="<?php echo $balance_class; ?>">
                                    <?php 
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
                            <td class="font-bold text-amber-600 bg-amber-100">৳<?php echo number_format($total_rice_paid, 0); ?></td>
                            <td class="font-bold text-red-600">৳<?php echo number_format($total_should, 0); ?></td>
                            <td class="font-bold">৳<?php echo number_format($total_paid - $total_should, 0); ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
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
            </div>
            
            <!-- Rice Cost Explanation for Balance Sheet -->
            <div class="mt-4 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                <h4 class="font-bold text-amber-800 mb-2">🍚 Rice in Balance Calculation</h4>
                <p class="text-amber-700 text-sm">
                    Rice cost is a <strong>person-wise investment</strong>. When a person pays for rice, that amount is added ONLY to their "Should Pay" total.
                    This means rice cost balances out automatically - the payer's total payment includes rice, and their "Should Pay" also includes rice.
                </p>
            </div>
            
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
                
                if (empty($creditors) && empty($debtors)):
                ?>
                    <p class="text-yellow-700">✅ All accounts are settled! No pending payments.</p>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php if (!empty($debtors)): ?>
                            <div>
                                <h5 class="font-medium text-red-700 mb-2">❌ Should Pay:</h5>
                                <ul class="text-red-600 text-sm space-y-1">
                                    <?php foreach ($debtors as $debtor): ?>
                                        <li>• <?php echo htmlspecialchars($debtor['name']); ?> → ৳<?php echo number_format($debtor['amount'], 0); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($creditors)): ?>
                            <div>
                                <h5 class="font-medium text-green-700 mb-2">✅ Should Receive:</h5>
                                <ul class="text-green-600 text-sm space-y-1">
                                    <?php foreach ($creditors as $creditor): ?>
                                        <li>• <?php echo htmlspecialchars($creditor['name']); ?> → ৳<?php echo number_format($creditor['amount'], 0); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
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

# 🔍 Bachelor Meal System — Audit Report

**Audit Date:** January 27, 2026  
**Project:** Bachelor Meal System (PHP + MySQL)  
**Auditor:** System Architect Review

---

## 1. Overall Verdict

| Status                | Decision                                    |
| --------------------- | ------------------------------------------- |
| **Result**            | ⚠️ **PARTIAL FAIL**                         |
| **Production Ready?** | ❌ **NOT READY**                            |
| **Blocking Issue**    | `paid_by` field not captured in bazar entry |

The system implements the core Excel logic correctly but has a **critical issue** in the bazar data entry that prevents the Final Balance Sheet from functioning.

---

## 2. Excel → Web Mapping Verification

| Excel Sheet | Web Tab                       | Status     |
| ----------- | ----------------------------- | ---------- |
| Sheet-2     | Tab-1: Daily Meal Log         | ✅ Matched |
| Sheet-3     | Tab-2: Monthly Meal Summary   | ✅ Matched |
| Sheet-1     | Tab-3: Meal Cost Distribution | ✅ Matched |
| Sheet-4     | Tab-4: Final Balance Sheet    | ✅ Matched |

---

## 3. Database Schema Review

### Tables Verified

```sql
-- persons: Member information
CREATE TABLE persons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

-- daily_meals: Raw meal facts only
CREATE TABLE daily_meals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meal_date DATE NOT NULL,
    person_id INT NOT NULL,
    session ENUM('lunch', 'dinner') NOT NULL,
    meal_type ENUM('fish', 'chicken', 'other', 'friday') NOT NULL,
    guest_count INT DEFAULT 0
);

-- bazar_items: Raw cost facts only
CREATE TABLE bazar_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bazar_date DATE NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    category ENUM('fish', 'chicken', 'other', 'friday') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    paid_by INT  -- WHO PAID FOR THIS ITEM
);
```

### Schema Compliance

| Rule                              | Status  |
| --------------------------------- | ------- |
| daily_meals = only raw meal facts | ✅ PASS |
| bazar_items = only raw cost facts | ✅ PASS |
| No derived values stored in DB    | ✅ PASS |

---

## 4. Tab-wise Audit Results

### ✅ CHECK-1: UI / Workflow

| Criteria                            | Status  | Details                   |
| ----------------------------------- | ------- | ------------------------- |
| Exactly 4 tabs?                     | ✅ PASS | All 4 tabs present        |
| Tabs behave like Excel sheets?      | ✅ PASS | Form-based tab switching  |
| ONE shared month selector?          | ✅ PASS | Single month input at top |
| Each tab has single responsibility? | ✅ PASS | No overlap between tabs   |

**Result: ✅ PASS**

---

### ✅ CHECK-2: Tab-1 (Daily Meal Log) → Excel Sheet-2

**File:** `public/summary.php` (Lines 23-45)

| Criteria                      | Status  | Details                           |
| ----------------------------- | ------- | --------------------------------- |
| Data source = daily_meals     | ✅ PASS | Direct SELECT from daily_meals    |
| Shows raw daily entries only  | ✅ PASS | No aggregation performed          |
| Day & Night meals separate    | ✅ PASS | Separate columns for lunch/dinner |
| Guest meals counted correctly | ✅ PASS | guest_count displayed separately  |
| NO calculation here           | ✅ PASS | Only raw data display             |

**SQL Query:**

```sql
SELECT
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
ORDER BY dm.meal_date DESC, p.name
```

**Result: ✅ PASS**

---

### ✅ CHECK-3: Tab-2 (Monthly Meal Summary) → Excel Sheet-3

**File:** `public/summary.php` (Lines 47-72)

| Criteria                    | Status  | Details                           |
| --------------------------- | ------- | --------------------------------- |
| Aggregation is person-wise  | ✅ PASS | GROUP BY p.id, p.name             |
| Category counts match Excel | ✅ PASS | SUM(1 + guest_count) per category |
| Guest meals included        | ✅ PASS | Formula includes guest_count      |
| No money calculation        | ✅ PASS | Only meal counts                  |

**SQL Query:**

```sql
SELECT
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
ORDER BY p.name
```

**Result: ✅ PASS**

---

### ✅ CHECK-4: Tab-3 (Meal Cost Distribution) → Excel Sheet-1

**File:** `public/summary.php` (Lines 76-189)

| Criteria                         | Status  | Details                         |
| -------------------------------- | ------- | ------------------------------- |
| Category totals from aggregation | ✅ PASS | Summed from person_aggregation  |
| Bazar cost by category           | ✅ PASS | SUM(amount) GROUP BY category   |
| Meal rate = cost ÷ meals         | ✅ PASS | Division with zero protection   |
| Day & Night logic respected      | ✅ PASS | Separate aggregation by session |
| Cost distributed person-wise     | ✅ PASS | Per-person category costs       |

**Calculation Logic:**

```php
// Step 1: Aggregate person meals by category and session
// Step 2: Sum category totals from all persons
// Step 3: Get category costs from bazar_items
// Step 4: Calculate rates
$category_rates[$category] = $category_costs[$category] / $category_totals[$category]['total'];

// Step 5: Distribute costs
$day_cost = $person_data[$category]['lunch'] * $category_rates[$category];
$night_cost = $person_data[$category]['dinner'] * $category_rates[$category];
$category_cost = $day_cost + $night_cost;
```

**Excel Formula Match:**

```
Category Meal Rate = Category Total Bazar ÷ Category Total Meals
Person's Category Cost = (Day Meals × Rate) + (Night Meals × Rate)
Total Cost = Sum of all category costs
```

**Result: ✅ PASS**

---

### ✅ CHECK-5: Tab-4 (Final Balance Sheet) → Excel Sheet-4

**File:** `public/summary.php` (Lines 192-249)

| Criteria                             | Status  | Details                       |
| ------------------------------------ | ------- | ----------------------------- |
| Total meals = sum of all categories  | ✅ PASS | Loop sums all category totals |
| Single meal rate calculation         | ✅ PASS | total_bazar / total_meals     |
| Paid amount from bazar_items.paid_by | ✅ PASS | Query uses paid_by correctly  |
| Balance = Paid − Cost                | ✅ PASS | Exact formula match           |
| Positive = receive, Negative = owe   | ✅ PASS | Correct status assignment     |

**Calculation Logic:**

```php
// Single meal rate (Excel Sheet-4 formula)
$single_meal_rate = $total_bazar_all / $total_meals_all;

// Person's cost
$person_cost = $person_total_meals * $single_meal_rate;

// Balance calculation
$balance = $paid_amount - $person_cost;
$balance_status = $balance >= 0 ? 'receive' : 'owe';
```

**Result: ✅ PASS** (Logic correct, but input broken — see Critical Issues)

---

### ✅ CHECK-6: Calculation Order (CRITICAL)

| Step | Location      | Description                                      | Status |
| ---- | ------------- | ------------------------------------------------ | ------ |
| 1    | Lines 23-45   | Daily raw input fetched                          | ✅     |
| 2    | Lines 47-72   | Monthly aggregation by person                    | ✅     |
| 3    | Lines 76-145  | Person aggregation with session + category costs | ✅     |
| 4    | Lines 147-189 | Cost rate calculation & distribution             | ✅     |
| 5    | Lines 192-249 | Final balance calculation                        | ✅     |

**Order Verification:**

```
Daily raw input → Monthly aggregation → Cost rate calculation → Cost distribution → Final balance
```

**Result: ✅ PASS**

---

### ✅ CHECK-7: Edge Cases

| Edge Case                       | Status  | Protection                                      |
| ------------------------------- | ------- | ----------------------------------------------- |
| Zero meal month                 | ✅ PASS | `if ($total_meals_all > 0)`                     |
| Category with cost but no meals | ✅ PASS | `if ($category_totals[$category]['total'] > 0)` |
| Guest-only days                 | ✅ PASS | Formula: `(1 + guest_count)`                    |
| Friday special isolation        | ✅ PASS | Separate category enum                          |

**Result: ✅ PASS**

---

## 5. Critical Issues Found

### 🔴 ISSUE #1: Bazar Entry Missing `paid_by` Field (BLOCKING)

**Severity:** 🔴 CRITICAL  
**File:** `public/bazar.php`  
**Impact:** Final Balance Sheet shows BDT 0.00 for everyone's "Paid" column

**Problem:**
The bazar entry form does not have a field to capture who paid for the items. The INSERT query also doesn't include the `paid_by` column.

**Current Code (bazar.php lines 23-28):**

```php
$sql = "INSERT INTO bazar_items (bazar_date, item_name, category, amount)
        VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssd", $bazar_date, $item_name, $category, $amount);
```

**Required Fix:**

1. Add person dropdown for `paid_by` in the form
2. Update INSERT query:

```php
$sql = "INSERT INTO bazar_items (bazar_date, item_name, category, amount, paid_by)
        VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssdi", $bazar_date, $item_name, $category, $amount, $paid_by);
```

---

### 🟡 ISSUE #2: Navigation Inconsistency

**Severity:** 🟡 Medium  
**Files:** `public/meals.php`, `public/bazar.php`  
**Impact:** User experience inconsistency

**Problem:**
The meals.php and bazar.php pages only have a simple "Back to Home" link, while index.php has a full navigation bar with all tabs.

**Recommendation:**
Add the same navigation bar from index.php to meals.php and bazar.php.

---

### 🟡 ISSUE #3: No Duplicate Entry Prevention

**Severity:** 🟡 Medium  
**File:** `public/meals.php`  
**Impact:** Data integrity risk

**Problem:**
The system allows adding multiple meal entries for the same person, date, and session (e.g., two lunch entries for the same day).

**Recommendation:**
Add a UNIQUE constraint on `(meal_date, person_id, session)` or check before INSERT.

---

### 🟢 ISSUE #4: Decimal Display for Integer Values

**Severity:** 🟢 Low  
**File:** `public/summary.php` (Line 237)  
**Impact:** Visual only

**Problem:**
Meal counts displayed with decimals (e.g., "15.00" instead of "15").

**Current:**

```php
$person['total_meals_display'] = number_format($person['total_meals'], 2);
```

**Should be:**

```php
$person['total_meals_display'] = number_format($person['total_meals'], 0);
```

---

## 6. File Structure Overview

```
bachelor-meal-system/
├── assets/
│   └── css/                    # (Empty - using Tailwind CDN)
├── config/
│   └── database.php            # ✅ MySQL connection
├── public/
│   ├── index.php               # ✅ Dashboard
│   ├── meals.php               # ⚠️ Meal entry (needs nav bar)
│   ├── bazar.php               # 🔴 Missing paid_by field
│   └── summary.php             # ✅ All 4 Excel sheets
├── sql/
│   └── schema.sql              # ✅ Correct schema
└── AUDIT_REPORT.md             # This file
```

---

## 7. Summary Table

| Check   | Component                | Status               |
| ------- | ------------------------ | -------------------- |
| CHECK-1 | UI/Workflow              | ✅ PASS              |
| CHECK-2 | Tab-1: Daily Meal Log    | ✅ PASS              |
| CHECK-3 | Tab-2: Monthly Summary   | ✅ PASS              |
| CHECK-4 | Tab-3: Cost Distribution | ✅ PASS              |
| CHECK-5 | Tab-4: Final Balance     | ⚠️ PASS (logic only) |
| CHECK-6 | Calculation Order        | ✅ PASS              |
| CHECK-7 | Edge Cases               | ✅ PASS              |

---

## 8. Final Recommendation

### Before Production Deployment:

1. **🔴 MANDATORY:** Add `paid_by` dropdown to bazar.php form
2. **🔴 MANDATORY:** Update bazar INSERT query to include `paid_by`
3. **🟡 RECOMMENDED:** Add consistent navigation bar to all pages
4. **🟡 RECOMMENDED:** Add duplicate entry prevention for meals
5. **🟢 OPTIONAL:** Fix decimal display for integer meal counts

### Estimated Fix Effort:

| Fix                    | Time            |
| ---------------------- | --------------- |
| Add paid_by field      | 15 minutes      |
| Navigation consistency | 10 minutes      |
| Duplicate prevention   | 20 minutes      |
| **Total**              | **~45 minutes** |

---

## 9. Conclusion

The **calculation engine is correctly implemented** and matches all Excel formulas precisely. The calculation order is correct, edge cases are handled, and all 4 tabs display the correct data.

However, the system **cannot be used in production** until the `paid_by` field is added to the bazar entry form. Without this field, the Final Balance Sheet will always show everyone as "Owe" with their full meal cost, since no payments can be recorded.

Once the `paid_by` fix is implemented, the system will be **ready for production use**.

---

_Report generated on January 27, 2026_

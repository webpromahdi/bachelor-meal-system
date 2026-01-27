<?php
/**
 * Quick script to check bazar_items categories
 */
require_once '../config/database.php';

echo "<pre>\n";
echo "=== BAZAR ITEMS BY CATEGORY ===\n\n";

$sql = "SELECT category, COUNT(*) as cnt, SUM(amount) as total FROM bazar_items GROUP BY category";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo sprintf("%-15s: %3d items, Total: ৳%s\n", 
            $row['category'], 
            $row['cnt'], 
            number_format($row['total'], 0)
        );
    }
} else {
    echo "No bazar items found.\n";
}

echo "\n=== RECENT 10 BAZAR ENTRIES ===\n\n";
$sql2 = "SELECT bazar_date, category, item_name, amount FROM bazar_items ORDER BY id DESC LIMIT 10";
$result2 = $conn->query($sql2);

if ($result2 && $result2->num_rows > 0) {
    while ($row = $result2->fetch_assoc()) {
        echo sprintf("%s | %-10s | %-20s | ৳%s\n", 
            $row['bazar_date'], 
            $row['category'], 
            $row['item_name'],
            number_format($row['amount'], 0)
        );
    }
}

echo "</pre>";
$conn->close();

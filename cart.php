<?php
session_start();
include 'Connection.php'; // Corrected

header('Content-Type: application/json');

// Get session ID
$session_id = isset($_SESSION['userid']) ? $_SESSION['userid'] : session_id();

$query = "SELECT c.ID, c.AccessoryID, c.Quantity, c.AddedOnDATETIME, 
                 a.name, a.price, a.description, a.image 
          FROM Cart c 
          LEFT JOIN Accessories a ON c.AccessoryID = a.id 
          WHERE c.SessionID = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $session_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$cartItems = [];
while ($row = mysqli_fetch_assoc($result)) {
    $cartItems[] = [
        'ID' => (int)$row['ID'],
        'AccessoryID' => (int)$row['AccessoryID'],
        'Quantity' => (int)$row['Quantity'],
        'AddedOnDATETIME' => $row['AddedOnDATETIME'],
        'name' => htmlspecialchars($row['name'] ?? 'Unknown'),
        'price' => (float)($row['price'] ?? 0),
        'description' => htmlspecialchars($row['description'] ?? ''),
        'image' => htmlspecialchars($row['image'] ?? '')
    ];
}

// Sync with session cart for consistency
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $sessionItem) {
        $found = false;
        foreach ($cartItems as &$dbItem) { // Use reference to update if needed
            if ($dbItem['AccessoryID'] == $sessionItem['id']) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $cartItems[] = [
                'ID' => null,
                'AccessoryID' => (int)$sessionItem['id'],
                'Quantity' => 1,
                'AddedOnDATETIME' => date('Y-m-d H:i:s'),
                'name' => htmlspecialchars($sessionItem['name'] ?? 'Unknown'),
                'price' => (float)($sessionItem['price'] ?? 0),
                'description' => htmlspecialchars($sessionItem['description'] ?? ''),
                'image' => htmlspecialchars($sessionItem['image'] ?? '')
            ];
        }
    }
}

echo json_encode(['success' => true, 'cartItems' => $cartItems]);

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
<?php
session_start();
include "Conection.php"; // Corrected

// Prevent any output before JSON
ob_start(); // Start output buffering to capture unintended output

header('Content-Type: application/json'); // Set JSON header

$response = ['success' => false, 'error' => '', 'cartcount' => 0];

if (!isset($_SESSION['userid'])) {
    $response['error'] = 'Please log in to add items to the cart.';
    echo json_encode($response);
    ob_end_flush(); // Send output and stop buffering
    exit;
}

$userid = $_SESSION['userid'];
$accessory_id = isset($_POST['id']) ? (int)$_POST['id'] : 0; // Fixed from 'accessory_id' to 'id' as per form

if ($accessory_id <= 0) {
    $response['error'] = 'Invalid accessory ID.';
    echo json_encode($response);
    ob_end_flush();
    exit;
}

// Check if the item already exists in the cart
$sql = "SELECT Quantity FROM Cart WHERE SessionID = ? AND AccessoryID = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "si", $userid, $accessory_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    // Item exists, update quantity
    $row = mysqli_fetch_assoc($result);
    $new_quantity = $row['Quantity'] + 1;
    $sql = "UPDATE Cart SET Quantity = ? WHERE SessionID = ? AND AccessoryID = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "isi", $new_quantity, $userid, $accessory_id);
} else {
    // Item does not exist, insert new record
    $sql = "INSERT INTO Cart (SessionID, AccessoryID, Quantity) VALUES (?, ?, 1)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $userid, $accessory_id);
}

if (mysqli_stmt_execute($stmt)) {
    // Get updated cart count
    $sql = "SELECT SUM(Quantity) AS total FROM Cart WHERE SessionID = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $userid);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $response['cartcount'] = (int)($row['total'] ?? 0);
    $response['success'] = true;
} else {
    $response['error'] = 'Failed to add item to cart: ' . mysqli_error($conn);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

// Clear output buffer and send JSON
ob_end_clean(); // Clear any unintended output
echo json_encode($response);
exit;
?>
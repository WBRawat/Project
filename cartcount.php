<?php
session_start();
require_once 'Connection.php'; // Corrected

$session_id = isset($_SESSION['userid']) ? $_SESSION['userid'] : session_id();
$query = "SELECT SUM(Quantity) as total, COUNT(DISTINCT AccessoryID) as unique_items FROM Cart WHERE SessionID = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $session_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$cart_summary = mysqli_fetch_assoc($result);
$total_items = (int)($cart_summary['total'] ?? 0);
$unique_items = (int)($cart_summary['unique_items'] ?? 0);
mysqli_stmt_close($stmt);
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart Count</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="container">
        <h2>Cart Summary</h2>
        <a href="Index.php">Back to Products</a> <!-- Fixed to Index.php -->
        <table>
            <tr>
                <th>Total Items</th>
                <td><?php echo htmlspecialchars($total_items); ?></td>
            </tr>
            <tr>
                <th>Unique Products</th>
                <td><?php echo htmlspecialchars($unique_items); ?></td>
            </tr>
        </table>
        <a href="cartitem.php">View Cart Details</a> <!-- Fixed link -->
    </div>
</body>
</html>
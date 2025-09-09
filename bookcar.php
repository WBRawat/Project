<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');
ini_set('session.cookie_lifetime', 86400);
ini_set('session.gc_maxlifetime', 86400);
session_start();

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

$response = ['success' => false, 'message' => ''];

try {
    if (!include 'Conection.php') {
        throw new Exception('Failed to include Conection.php');
    }
    if (!$conn) {
        throw new Exception('Database connection failed: ' . mysqli_connect_error());
    }

    if (!isset($_SESSION['userid'])) {
        http_response_code(401);
        $response['message'] = 'Please log in to book a car.';
        echo json_encode($response);
        ob_end_flush();
        exit();
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $productId = isset($data['carId']) ? (int)$data['carId'] : null;
    $userId = (int)$_SESSION['userid'];

    error_log("bookcar.php: productId=$productId, userId=$userId");

    if (!$productId) {
        http_response_code(400);
        $response['message'] = 'Product ID is required.';
        echo json_encode($response);
        ob_end_flush();
        exit();
    }

    $sql = "SELECT ID FROM Product WHERE ID = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        throw new Exception('Product query preparation failed: ' . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, "i", $productId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($result) == 0) {
        http_response_code(400);
        $response['message'] = 'Invalid Product ID.';
        echo json_encode($response);
        mysqli_stmt_close($stmt);
        ob_end_flush();
        exit();
    }
    mysqli_stmt_close($stmt);

    $sql = "INSERT INTO bookings (userid, carid, bookingdate) VALUES (?, ?, NOW())";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        throw new Exception('Booking query preparation failed: ' . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, "ii", $userId, $productId);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Booking insert failed: ' . mysqli_error($conn));
    }
    mysqli_stmt_close($stmt);

    http_response_code(200);
    $response['success'] = true;
    $response['message'] = 'Car booked successfully!';
} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Server error: ' . $e->getMessage();
    error_log('bookcar.php error: ' . $e->getMessage());
}

error_log('bookcar.php response: ' . json_encode($response));
mysqli_close($conn);
ob_end_clean();
echo json_encode($response);
?>
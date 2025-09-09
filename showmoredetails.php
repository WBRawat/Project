<?php
// Enable error logging and display for debugging
ini_set('display_errors', 1); // Temporarily enable to diagnose blank page
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

session_start();
include "Conection.php"; // Corrected typo: Conection.php -> Connection.php

// Initialize variables
$cartCount = 0;
$profileImage = "default.png";
$notification = "";
$notificationType = "success";

// Calculate cart count and fetch profile image
if (isset($_SESSION['userid'])) {
    $userid = $_SESSION['userid'];
    $sql = "SELECT SUM(Quantity) AS total FROM Cart WHERE SessionID = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt === false) {
        error_log("Cart count prepare failed: " . mysqli_error($conn));
        $notification = "Database error.";
        $notificationType = "error";
    } else {
        mysqli_stmt_bind_param($stmt, "s", $userid);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                $cartCount = $row['total'] ?? 0;
            }
        } else {
            error_log("Cart query failed: " . mysqli_error($conn));
            $notification = "Error fetching cart count.";
            $notificationType = "error";
        }
        mysqli_stmt_close($stmt);
    }
    if (!empty($_SESSION['userimage'])) {
        $profileImage = $_SESSION['userimage'];
    }
}

// Handle Add to Cart (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accessories']) && isset($_POST['car_id'])) {
    header('Content-Type: application/json; charset=utf-8');
    $response = ['success' => false, 'message' => '', 'type' => 'error', 'cartcount' => $cartCount];

    if (!isset($_SESSION['userid'])) {
        $response['message'] = "Please log in to add items to the cart.";
        echo json_encode($response);
        exit;
    }

    $userid = $_SESSION['userid'];
    $car_id = (int)$_POST['car_id'];
    $accessories = is_array($_POST['accessories']) ? array_map('intval', $_POST['accessories']) : [];

    if (empty($accessories)) {
        $response['message'] = "Please select at least one accessory.";
        $response['type'] = "error";
        echo json_encode($response);
        exit;
    } elseif ($car_id <= 0) {
        $response['message'] = "Invalid Car ID.";
        $response['type'] = "error";
        echo json_encode($response);
        exit;
    }

    $success = true;
    foreach ($accessories as $accessory_id) {
        if ($accessory_id <= 0) {
            continue;
        }

        $sql = "SELECT id FROM Accessories WHERE id = ? AND carid = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt === false) {
            $response['message'] = "Database error: " . mysqli_error($conn);
            error_log("Accessory check prepare failed: " . mysqli_error($conn));
            $success = false;
            echo json_encode($response);
            exit;
        }
        mysqli_stmt_bind_param($stmt, "ii", $accessory_id, $car_id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) == 0) {
                $response['message'] = "Invalid accessory for this car.";
                $response['type'] = "error";
                $success = false;
                mysqli_stmt_close($stmt);
                echo json_encode($response);
                exit;
            }
        } else {
            $response['message'] = "Database error: " . mysqli_error($conn);
            $success = false;
            error_log("Accessory check failed: " . mysqli_error($conn));
        }
        mysqli_stmt_close($stmt);

        $sql = "SELECT Quantity FROM Cart WHERE SessionID = ? AND AccessoryID = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt === false) {
            $response['message'] = "Database error: " . mysqli_error($conn);
            error_log("Cart check prepare failed: " . mysqli_error($conn));
            $success = false;
            echo json_encode($response);
            exit;
        }
        mysqli_stmt_bind_param($stmt, "si", $userid, $accessory_id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                $new_quantity = $row['Quantity'] + 1;
                $sql = "UPDATE Cart SET Quantity = ? WHERE SessionID = ? AND AccessoryID = ?";
                $stmt = mysqli_prepare($conn, $sql);
                if ($stmt === false) {
                    $response['message'] = "Database error: " . mysqli_error($conn);
                    error_log("Cart update prepare failed: " . mysqli_error($conn));
                    $success = false;
                    echo json_encode($response);
                    exit;
                }
                mysqli_stmt_bind_param($stmt, "isi", $new_quantity, $userid, $accessory_id);
            } else {
                $sql = "INSERT INTO Cart (SessionID, AccessoryID, Quantity) VALUES (?, ?, 1)";
                $stmt = mysqli_prepare($conn, $sql);
                if ($stmt === false) {
                    $response['message'] = "Database error: " . mysqli_error($conn);
                    error_log("Cart insert prepare failed: " . mysqli_error($conn));
                    $success = false;
                    echo json_encode($response);
                    exit;
                }
                mysqli_stmt_bind_param($stmt, "si", $userid, $accessory_id);
            }
            if (!mysqli_stmt_execute($stmt)) {
                $response['message'] = "Error adding accessory to cart: " . mysqli_error($conn);
                $response['type'] = "error";
                $success = false;
                error_log("Cart execute failed: " . mysqli_error($conn));
            }
            mysqli_stmt_close($stmt);
        } else {
            $response['message'] = "Database error: " . mysqli_error($conn);
            $success = false;
            error_log("Cart check failed: " . mysqli_error($conn));
        }
    }

    if ($success) {
        $sql = "SELECT SUM(Quantity) AS total FROM Cart WHERE SessionID = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt === false) {
            $response['message'] = "Database error: " . mysqli_error($conn);
            error_log("Cart count update prepare failed: " . mysqli_error($conn));
            echo json_encode($response);
            exit;
        }
        mysqli_stmt_bind_param($stmt, "s", $userid);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                $cartCount = $row['total'] ?? 0;
            }
            $response['success'] = true;
            $response['message'] = "Accessories added to cart!";
            $response['type'] = "success";
            $response['cartcount'] = $cartCount;
        } else {
            $response['message'] = "Error updating cart count: " . mysqli_error($conn);
            error_log("Cart count update failed: " . mysqli_error($conn));
        }
        mysqli_stmt_close($stmt);
    }
    echo json_encode($response);
    exit;
}

// Fetch car details from Details table
$car = null;
if (isset($_GET['id'])) {
    $car_id = (int)$_GET['id'];
    $sql = "SELECT ID, EngineType, Image1, Image2, Image3, Horsepower, Transmission, FuelType, FuelTankCapacity, Mileage, TopSpeed, DriveType, ColorOptions, Dimensions, SeatingCapacity, InfotainmentFeatures, SafetyFeatures, Suspension, BreakingSystem, WheelsType FROM Details WHERE carid = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt === false) {
        $notification = "Database error: " . mysqli_error($conn);
        $notificationType = "error";
        error_log("Details fetch prepare failed: " . mysqli_error($conn));
    } else {
        mysqli_stmt_bind_param($stmt, "i", $car_id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($result->num_rows == 1) {
                $car = mysqli_fetch_assoc($result);
                $car['Image1'] = !empty($car['Image1']) && file_exists("images/" . $car['Image1']) ? $car['Image1'] : 'default.png';
            } else {
                $notification = "Car details not found.";
                $notificationType = "error";
            }
        } else {
            $notification = "Database error: " . mysqli_error($conn);
            $notificationType = "error";
            error_log("Details fetch failed: " . mysqli_error($conn));
        }
        mysqli_stmt_close($stmt);
    }

    // Fetch accessories
    $sql = "SELECT id, name, price, image, description FROM Accessories WHERE carid = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt === false) {
        $notification = "Database error: " . mysqli_error($conn);
        $notificationType = "error";
        error_log("Accessories fetch prepare failed: " . mysqli_error($conn));
    } else {
        mysqli_stmt_bind_param($stmt, "i", $car_id);
        if (mysqli_stmt_execute($stmt)) {
            $accessories_result = mysqli_stmt_get_result($stmt);
        } else {
            $notification = "Error fetching accessories: " . mysqli_error($conn);
            $notificationType = "error";
            error_log("Accessories fetch failed: " . mysqli_error($conn));
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Details - Lux Car Showroom 🚘</title>
    <link rel="stylesheet" href="Luxstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
</head>
<body>
    <header class="site-header" style="background: #000000; padding: 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);">
        <div class="logo-nav">
            <img src="logo.jpeg" alt="Logo" style="height: 60px;">
        </div>
        <h1 class="glow-text" style="color: #D4A017; font-family: 'Playfair Display', serif; font-size: 2rem; text-shadow: 0 0 10px rgba(212, 160, 23, 0.5);">🚘 Lux Car Showroom</h1>
        <div class="nav-right" style="display: flex; align-items: center; gap: 15px;">
            <a href="cartitem.php" class="cart-glow" title="Cart" style="color: #D4A017; font-size: 1.5rem;">
                <i class="fas fa-shopping-cart"></i>
                <span class="cart-count" id="cart-count"><?php echo $cartCount; ?></span>
            </a>
            <a href="Login.php" title="Login" style="color: #FFFFFF; font-size: 1.5rem;">🔑</a>
            <a href="Register.php" title="Register" style="color: #FFFFFF; font-size: 1.5rem;">📝</a>
            <img src="images/<?php echo htmlspecialchars($profileImage); ?>" alt="Profile" class="profile-icon" style="width: 40px; height: 40px; border-radius: 50%; border: 2px solid #D4A017;">
            <div class="dots-menu" style="color: #FFFFFF; font-size: 1.5rem; cursor: pointer; position: relative;">
                ⋮
                <div class="dropdown" style="display: none; position: absolute; right: 0; background: #1A1A1A; border-radius: 5px; padding: 10px;">
                    <a href="profile.php" style="color: #FFFFFF; display: block; padding: 5px; text-decoration: none;">Users</a>
                    <a href="admin.php" style="color: #FFFFFF; display: block; padding: 5px; text-decoration: none;">Admin</a>
                    <a href="Login.php" style="color: #FFFFFF; display: block; padding: 5px; text-decoration: none;">Login</a>
                    <a href="Contact.php" style="color: #FFFFFF; display: block; padding: 5px; text-decoration: none;">Contact</a>
                    <a href="track.php" style="color: #FFFFFF; display: block; padding: 5px; text-decoration: none;">Track Orders</a>
                </div>
            </div>
        </div>
    </header>

    <nav style="background: #1A1A1A; padding: 15px; text-align: center;">
        <input type="checkbox" id="menu-toggle" style="display: none;">
        <label class="hamburger" for="menu-toggle" style="color: #D4A017; font-size: 1.5rem; cursor: pointer;">☰</label>
        <ul class="menu" style="list-style: none; display: flex; justify-content: center; gap: 20px; margin: 0;">
            <li><a href="Index.php" style="color: #FFFFFF; font-family: 'Roboto', sans-serif; text-decoration: none;">🏠 Home</a></li>
            <li><a href="Inventory.php" style="color: #FFFFFF; font-family: 'Roboto', sans-serif; text-decoration: none;">🚗 Inventory</a></li>
            <li><a href="AboutUs.php" style="color: #FFFFFF; font-family: 'Roboto', sans-serif; text-decoration: none;">📄 About</a></li>
            <li><a href="Contact.php" style="color: #FFFFFF; font-family: 'Roboto', sans-serif; text-decoration: none;">📞 Contact</a></li>
            <li><a href="ShowAccessories.php" style="color: #FFFFFF; font-family: 'Roboto', sans-serif; text-decoration: none;">🛠️ Accessories</a></li>
            <li><a href="Logout.php" style="color: #FFFFFF; font-family: 'Roboto', sans-serif; text-decoration: none;">🚪 Logout</a></li>
        </ul>
    </nav>

    <main>
        <div class="profile-container" style="background: #1A1A1A; padding: 40px; border-radius: 15px; box-shadow: 0 6px 20px rgba(0, 0, 0, 0.5); max-width: 1200px; margin: 30px auto;">
            <?php if (isset($car)) { ?>
                <div class="profile-card" style="display: flex; flex-wrap: wrap; gap: 30px; align-items: center; background: #000000; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(212, 160, 23, 0.3);">
                    <div class="profile-image" style="flex: 1; min-width: 300px;">
                        <img src="images/<?php echo htmlspecialchars($car['Image1']); ?>" alt="Car Image" style="width: 400px; height: 300px; object-fit: contain; border-radius: 10px;">
                    </div>
                    <div class="profile-details" style="flex: 1; min-width: 300px; color: #FFFFFF; font-family: 'Roboto', sans-serif;">
                        <h2 style="color: #D4A017; font-family: 'Playfair Display', serif; font-size: 2.5rem; margin-bottom: 20px;">Car Details 🚘</h2>
                        <p style="font-size: 1.4rem; margin-bottom: 10px;"><strong>ID:</strong> <?php echo htmlspecialchars($car['ID']); ?></p>
                        <p style="font-size: 1.4rem; margin-bottom: 10px;"><strong>Engine Type:</strong> <?php echo htmlspecialchars($car['EngineType']); ?></p>
                        <p style="font-size: 1.4rem; margin-bottom: 10px;"><strong>Horsepower:</strong> <?php echo htmlspecialchars($car['Horsepower']); ?></p>
                        <p style="font-size: 1.4rem; margin-bottom: 10px;"><strong>Transmission:</strong> <?php echo htmlspecialchars($car['Transmission']); ?></p>
                        <p style="font-size: 1.4rem; margin-bottom: 10px;"><strong>Fuel Type:</strong> <?php echo htmlspecialchars($car['FuelType']); ?></p>
                        <p style="font-size: 1.4rem; margin-bottom: 10px;"><strong>Fuel Tank Capacity:</strong> <?php echo htmlspecialchars($car['FuelTankCapacity']); ?></p>
                        <p style="font-size: 1.4rem; margin-bottom: 10px;"><strong>Mileage:</strong> <?php echo htmlspecialchars($car['Mileage']); ?></p>
                        <p style="font-size: 1.4rem; margin-bottom: 10px;"><strong>Top Speed:</strong> <?php echo htmlspecialchars($car['TopSpeed']); ?></p>
                        <p style="font-size: 1.4rem; margin-bottom: 10px;"><strong>Drive Type:</strong> <?php echo htmlspecialchars($car['DriveType']); ?></p>
                        <p style="font-size: 1.4rem; margin-bottom: 10px;"><strong>Color Options:</strong> <?php echo htmlspecialchars($car['ColorOptions']); ?></p>
                        <p style="font-size: 1.4rem; margin-bottom: 10px;"><strong>Dimensions:</strong> <?php echo htmlspecialchars($car['Dimensions']); ?></p>
                        <p style="font-size: 1.4rem; margin-bottom: 10px;"><strong>Seating Capacity:</strong> <?php echo htmlspecialchars($car['SeatingCapacity']); ?></p>
                        <p style="font-size: 1.4rem; margin-bottom: 10px;"><strong>Infotainment Features:</strong> <?php echo htmlspecialchars($car['InfotainmentFeatures']); ?></p>
                        <p style="font-size: 1.4rem; margin-bottom: 10px;"><strong>Safety Features:</strong> <?php echo htmlspecialchars($car['SafetyFeatures']); ?></p>
                        <p style="font-size: 1.4rem; margin-bottom: 10px;"><strong>Suspension:</strong> <?php echo htmlspecialchars($car['Suspension']); ?></p>
                        <p style="font-size: 1.4rem; margin-bottom: 10px;"><strong>Braking System:</strong> <?php echo htmlspecialchars($car['BreakingSystem']); ?></p>
                        <p style="font-size: 1.4rem; margin-bottom: 20px;"><strong>Wheels Type:</strong> <?php echo htmlspecialchars($car['WheelsType']); ?></p>
                        <?php if (!empty($car['Image2']) || !empty($car['Image3'])) { ?>
                            <h3 style="color: #D4A017; font-family: 'Playfair Display', serif; font-size: 1.8rem; margin-bottom: 20px;">Additional Images</h3>
                            <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                                <?php if (!empty($car['Image2']) && file_exists("images/" . $car['Image2'])) { ?>
                                    <img src="images/<?php echo htmlspecialchars($car['Image2']); ?>" alt="Image 2" style="width: 150px; height: 150px; object-fit: contain; border-radius: 10px;">
                                <?php } ?>
                                <?php if (!empty($car['Image3']) && file_exists("images/" . $car['Image3'])) { ?>
                                    <img src="images/<?php echo htmlspecialchars($car['Image3']); ?>" alt="Image 3" style="width: 150px; height: 150px; object-fit: contain; border-radius: 10px;">
                                <?php } ?>
                            </div>
                        <?php } ?>
                        <div style="margin-top: 20px;">
                            <a href="ShowMoreDetails.php?id=<?php echo $car_id; ?>" style="background: #D4A017; color: #000000; padding: 15px; font-size: 1.2rem; border-radius: 10px; border: none; cursor: pointer; box-shadow: 0 4px 12px rgba(212, 160, 23, 0.4); text-decoration: none; display: inline-block; margin-right: 10px; transition: background 0.3s ease, transform 0.2s ease;" onmouseover="this.style.background='#b38b12'; this.style.transform='translateY(-3px)';" onmouseout="this.style.background='#D4A017'; this.style.transform='translateY(0)';">Show More Details</a>
                            <a href="AddMoreDetails.php?id=<?php echo $car_id; ?>" style="background: #D4A017; color: #000000; padding: 15px; font-size: 1.2rem; border-radius: 10px; border: none; cursor: pointer; box-shadow: 0 4px 12px rgba(212, 160, 23, 0.4); text-decoration: none; display: inline-block; margin-right: 10px; transition: background 0.3s ease, transform 0.2s ease;" onmouseover="this.style.background='#b38b12'; this.style.transform='translateY(-3px)';" onmouseout="this.style.background='#D4A017'; this.style.transform='translateY(0)';">Add More Details</a>
                            <a class="back-link" href="Inventory.php" style="color: #D4A017; font-size: 1.2rem; text-decoration: none; display: inline-block;">← Back to Inventory</a>
                        </div>
                    </div>
                </div>
                <?php if ($accessories_result && mysqli_num_rows($accessories_result) > 0) { ?>
                    <button id="show-accessories-btn" style="background: #A30000; color: #FFFFFF; padding: 15px; font-size: 1.2rem; border-radius: 10px; border: none; width: 100%; margin: 20px 0; cursor: pointer; box-shadow: 0 4px 12px rgba(163, 0, 0, 0.4); transition: background 0.3s ease, transform 0.2s ease;" onmouseover="this.style.background='#800000'; this.style.transform='translateY(-3px)';" onmouseout="this.style.background='#A30000'; this.style.transform='translateY(0)';">🛠️ Show Accessories</button>
                    <div id="accessories-section" style="display: none;">
                        <h3 style="color: #D4A017; font-family: 'Playfair Display', serif; font-size: 1.8rem; margin-bottom: 20px;">Available Accessories</h3>
                        <form method="post" class="add-to-cart-form">
                            <input type="hidden" name="car_id" value="<?php echo $car_id; ?>">
                            <div class="swiper mySwiper" style="margin-bottom: 30px;">
                                <div class="swiper-wrapper">
                                    <?php while ($accessory = mysqli_fetch_assoc($accessories_result)) { ?>
                                        <div class="swiper-slide accessory-card" style="background: #1A1A1A; padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.4); text-align: center;">
                                            <input type="checkbox" name="accessories[]" value="<?php echo $accessory['id']; ?>" style="margin-bottom: 10px;">
                                            <img src="images/<?php echo htmlspecialchars($accessory['image'] ?? 'default.png'); ?>" alt="Accessory Image" style="width: 100%; max-height: 150px; object-fit: contain; border-radius: 10px; margin-bottom: 10px;">
                                            <h4 style="color: #FFFFFF; font-family: 'Roboto', sans-serif; font-size: 1.3rem; margin-bottom: 10px;"><?php echo htmlspecialchars($accessory['name']); ?></h4>
                                            <p style="color: #D4A017; font-size: 1.1rem; margin-bottom: 10px;">₹<?php echo htmlspecialchars(number_format($accessory['price'], 2)); ?></p>
                                            <p style="color: #FFFFFF; font-size: 1rem;"><?php echo htmlspecialchars($accessory['description']); ?></p>
                                        </div>
                                    <?php } ?>
                                </div>
                                <div class="swiper-pagination" style="position: static; margin-top: 20px;"></div>
                                <div class="swiper-button-next" style="color: #D4A017;"></div>
                                <div class="swiper-button-prev" style="color: #D4A017;"></div>
                            </div>
                            <button type="submit" class="btn btn-primary" style="background: #A30000; color: #FFFFFF; padding: 15px; font-size: 1.2rem; border-radius: 10px; border: none; width: 100%; cursor: pointer; box-shadow: 0 4px 12px rgba(163, 0, 0, 0.4); transition: background 0.3s ease, transform 0.2s ease;" onmouseover="this.style.background='#800000'; this.style.transform='translateY(-3px)';" onmouseout="this.style.background='#A30000'; this.style.transform='translateY(0)';">🛒 Add Selected to Cart</button>
                        </form>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <p style="color: #FFFFFF; text-align: center; font-size: 1.2rem; font-family: 'Roboto', sans-serif;">Car details not found.</p>
            <?php } ?>
            <div class="notification <?php echo $notificationType; ?>" id="notification" style="position: fixed; top: 20px; right: 20px; padding: 15px; border-radius: 5px; display: none; font-family: 'Roboto', sans-serif; background: <?php echo $notificationType === 'success' ? '#28a745' : '#dc3545'; ?>; color: #FFFFFF;"><?php echo htmlspecialchars($notification); ?></div>
        </div>
    </main>

    <footer style="background: #000000; padding: 30px; color: #FFFFFF; font-family: 'Roboto', sans-serif;">
        <div class="footer-section" style="margin-bottom: 20px;">
            <h3 style="color: #D4A017; font-family: 'Playfair Display', serif;">📄 About Us</h3>
            <p>Luxury Car Showroom brings you the best and most exclusive cars worldwide.</p>
        </div>
        <div class="footer-section footer-links" style="margin-bottom: 20px;">
            <h3 style="color: #D4A017; font-family: 'Playfair Display', serif;">🔗 Quick Links</h3>
            <a href="Index.php" style="color: #FFFFFF; display: block; margin: 5px 0; text-decoration: none;">🏠 Home</a>
            <a href="Inventory.php" style="color: #FFFFFF; display: block; margin: 5px 0; text-decoration: none;">🚗 Inventory</a>
            <a href="AboutUs.php" style="color: #FFFFFF; display: block; margin: 5px 0; text-decoration: none;">📄 About</a>
            <a href="Contact.php" style="color: #FFFFFF; display: block; margin: 5px 0; text-decoration: none;">📞 Contact</a>
            <a href="admin.php" style="color: #FFFFFF; display: block; margin: 5px 0; text-decoration: none;">🛠️ Admin</a>
            <a href="Login.php" style="color: #FFFFFF; display: block; margin: 5px 0; text-decoration: none;">🔑 Login</a>
            <a href="Register.php" style="color: #FFFFFF; display: block; margin: 5px 0; text-decoration: none;">📝 Register</a>
            <a href="track.php" style="color: #FFFFFF; display: block; margin: 5px 0; text-decoration: none;">📍 Track Orders</a>
        </div>
        <div class="footer-section footer-services" style="margin-bottom: 20px;">
            <h3 style="color: #D4A017; font-family: 'Playfair Display', serif;">🛠️ Our Services</h3>
            <p style="margin: 5px 0;">Luxury Car Sales</p>
            <p style="margin: 5px 0;">Certified Pre-Owned</p>
            <p style="margin: 5px 0;">Flexible Financing</p>
            <p style="margin: 5px 0;">24x7 Maintenance</p>
            <p style="margin: 5px 0;">VIP Customization</p>
        </div>
        <div class="footer-section footer-contact" style="margin-bottom: 20px;">
            <h3 style="color: #D4A017; font-family: 'Playfair Display', serif;">📞 Contact Us</h3>
            <p style="margin: 5px 0;">📍 123 Luxury St, Beverly Hills, CA</p>
            <p style="margin: 5px 0;">📞 +1 234 567 890</p>
            <p style="margin: 5px 0;">📧 info@luxurycars.com</p>
        </div>
        <div class="footer-section newsletter" style="margin-bottom: 20px;">
            <h3 style="color: #D4A017; font-family: 'Playfair Display', serif;">📬 Subscribe to Our Newsletter</h3>
            <input type="email" placeholder="Enter your email" style="padding: 10px; border-radius: 5px; border: none; background: #333; color: #FFFFFF; font-family: 'Roboto', sans-serif;">
            <button style="background: #D4A017; color: #000000; padding: 10px; border-radius: 5px; border: none; margin-top: 10px; cursor: pointer; font-family: 'Roboto', sans-serif;">Subscribe</button>
        </div>
        <div class="footer-bottom" style="text-align: center; margin-top: 20px;">
            <p>© 2025 Luxury Car Showroom | Designed by Nitin Rawat</p>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            var swiper = new Swiper(".mySwiper", {
                loop: true,
                slidesPerView: 3,
                spaceBetween: 20,
                pagination: { el: ".swiper-pagination", clickable: true },
                navigation: { nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev" },
                breakpoints: {
                    320: { slidesPerView: 1 },
                    768: { slidesPerView: 2 },
                    1024: { slidesPerView: 3 }
                }
            });

            $("#show-accessories-btn").on("click", function() {
                $("#accessories-section").slideToggle(300);
                $(this).text($(this).text() === "🛠️ Show Accessories" ? "🛠️ Hide Accessories" : "🛠️ Show Accessories");
            });

            $(".add-to-cart-form").on("submit", function(e) {
                e.preventDefault();
                var form = $(this);
                $.ajax({
                    url: form.attr("action"),
                    type: "POST",
                    data: form.serialize(),
                    dataType: "json",
                    success: function(response) {
                        $("#notification").text(response.message).removeClass("success error").addClass(response.type).addClass("show");
                        if (response.success) {
                            $("#cart-count").text(response.cartcount);
                        }
                        setTimeout(() => { $("#notification").removeClass("show"); }, 3000);
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX error:", xhr.responseText);
                        $("#notification").text("Error adding to cart: " + error).removeClass("success").addClass("error").addClass("show");
                        setTimeout(() => { $("#notification").removeClass("show"); }, 3000);
                    }
                });
            });

            if ($("#notification").text().trim() !== "") {
                $("#notification").addClass("show");
                setTimeout(() => { $("#notification").removeClass("show"); }, 3000);
            }

            $(".dots-menu").on("click", function() {
                $(this).find(".dropdown").slideToggle(200);
            });
        });
    </script>
</body>
</html>
<?php
mysqli_close($conn);
?>
<?php
session_start();
include "Conection.php"; // Corrected typo: Conection.php -> Connection.php

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$cartCount = 0;
$profileImage = "default.png";

if (isset($_SESSION['userid'])) {
    $userid = $_SESSION['userid'];
    $sql = "SELECT SUM(Quantity) AS total FROM Cart WHERE SessionID = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $userid);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                $cartCount = $row['total'] ?? 0;
            }
        } else {
            error_log("Cart query failed: " . mysqli_error($conn));
        }
        mysqli_stmt_close($stmt);
    }

    $sql = "SELECT Image FROM user WHERE ID = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $userid);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                $profileImage = !empty($row['Image']) && file_exists("images/" . $row['Image']) ? $row['Image'] : 'default.png';
            }
        } else {
            error_log("User image query failed: " . mysqli_error($conn));
        }
        mysqli_stmt_close($stmt);
    }
}

$carid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($carid <= 0) {
    echo "<div class='alert alert-danger' style='background: #dc3545; color: #fff; padding: 10px; border-radius: 5px; text-align: center;'>Invalid car ID</div>";
    exit;
}

// Validate carid exists in Product table
$sql = "SELECT ID FROM Product WHERE ID = ?";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $carid);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if ($result->num_rows != 1) {
            echo "<div class='alert alert-danger' style='background: #dc3545; color: #fff; padding: 10px; border-radius: 5px; text-align: center;'>Product not found</div>";
            exit;
        }
    } else {
        error_log("Product validation query failed: " . mysqli_error($conn));
        echo "<div class='alert alert-danger' style='background: #dc3545; color: #fff; padding: 10px; border-radius: 5px; text-align: center;'>Database error</div>";
        exit;
    }
    mysqli_stmt_close($stmt);
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $enginetype = filter_input(INPUT_POST, 'enginetype', FILTER_SANITIZE_STRING);
    $horsepower = filter_input(INPUT_POST, 'horsepower', FILTER_SANITIZE_STRING);
    $transmission = filter_input(INPUT_POST, 'transmission', FILTER_SANITIZE_STRING);
    $fueltype = filter_input(INPUT_POST, 'fueltype', FILTER_SANITIZE_STRING);
    $fueltankcapacity = filter_input(INPUT_POST, 'fueltankcapacity', FILTER_SANITIZE_STRING);
    $mileage = filter_input(INPUT_POST, 'mileage', FILTER_SANITIZE_STRING);
    $topspeed = filter_input(INPUT_POST, 'topspeed', FILTER_SANITIZE_STRING);
    $drivetype = filter_input(INPUT_POST, 'drivetype', FILTER_SANITIZE_STRING);
    $coloropts = filter_input(INPUT_POST, 'coloropt', FILTER_SANITIZE_STRING);
    $dimensions = filter_input(INPUT_POST, 'dimensions', FILTER_SANITIZE_STRING);
    $seatingcapacity = filter_input(INPUT_POST, 'seatingcapacity', FILTER_SANITIZE_STRING);
    $infotainmentfeatures = filter_input(INPUT_POST, 'infotainmentfeatures', FILTER_SANITIZE_STRING);
    $safetyfeatures = filter_input(INPUT_POST, 'safetyfeatures', FILTER_SANITIZE_STRING);
    $suspensiontype = filter_input(INPUT_POST, 'suspensiontype', FILTER_SANITIZE_STRING);
    $breakingsystem = filter_input(INPUT_POST, 'breakingsystem', FILTER_SANITIZE_STRING);
    $wheelstype = filter_input(INPUT_POST, 'wheelstype', FILTER_SANITIZE_STRING);

    // Validate all required fields
    if (empty($enginetype) || empty($horsepower) || empty($transmission) || empty($fueltype) ||
        empty($fueltankcapacity) || empty($mileage) || empty($topspeed) || empty($drivetype) ||
        empty($coloropts) || empty($dimensions) || empty($seatingcapacity) ||
        empty($infotainmentfeatures) || empty($safetyfeatures) || empty($suspensiontype) ||
        empty($breakingsystem) || empty($wheelstype)) {
        $errors[] = "All fields are required.";
    }

    // Handle multiple image uploads
    $image1 = $image2 = $image3 = '';
    $upload_dir = "images/";
    $allowed_types = ['image/jpeg', 'image/png'];
    $maxSize = 5000000; // 5MB

    foreach (['image1', 'image2', 'image3'] as $key) {
        if (isset($_FILES[$key]) && $_FILES[$key]['error'] == 0) {
            if (in_array($_FILES[$key]['type'], $allowed_types) && $_FILES[$key]['size'] <= $maxSize) {
                $filename = uniqid() . '_' . basename($_FILES[$key]['name']);
                if (move_uploaded_file($_FILES[$key]['tmp_name'], $upload_dir . $filename)) {
                    $$key = $filename;
                } else {
                    $errors[] = "Failed to upload $key.";
                }
            } else {
                $errors[] = "Invalid file type or size for $key.";
            }
        }
    }

    if (empty($errors)) {
        $sql = "INSERT INTO Details (EngineType, Image1, Image2, Image3, Horsepower, Transmission, FuelType, FuelTankCapacity, Mileage, TopSpeed, DriveType, ColorOptions, Dimensions, SeatingCapacity, InfotainmentFeatures, SafetyFeatures, Suspension, BreakingSystem, WheelsType, carid) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sssssssssssssssssssi", 
                $enginetype, $image1, $image2, $image3, $horsepower, $transmission, $fueltype, 
                $fueltankcapacity, $mileage, $topspeed, $drivetype, $coloropts, $dimensions, 
                $seatingcapacity, $infotainmentfeatures, $safetyfeatures, $suspensiontype, 
                $breakingsystem, $wheelstype, $carid
            );
            if (mysqli_stmt_execute($stmt)) {
                echo "<script>document.getElementById('notification').textContent = 'Details added successfully!'; document.getElementById('notification').classList.add('show');</script>";
            } else {
                $errors[] = "Database error: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        } else {
            $errors[] = "Failed to prepare query: " . mysqli_error($conn);
        }
    }

    if (!empty($errors)) {
        echo "<script>document.getElementById('notification').textContent = '" . implode(' ', array_map('addslashes', $errors)) . "'; document.getElementById('notification').classList.add('show'); document.getElementById('notification').style.background = '#dc3545';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product Details - Lux Car Showroom 🚘</title>
    <link rel="stylesheet" href="Luxstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <header class="site-header" style="background: #1a1a1a; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);">
        <div class="logo-nav">
            <img src="logo.jpeg" alt="Logo" style="height: 50px;" />
        </div>
        <h1 class="glow-text" style="color: #e0e0e0; font-size: 24px; font-family: 'Roboto', 'Arial', sans-serif; font-weight: 700;">🚘 Lux Car Showroom</h1>
        <div class="nav-right" style="display: flex; align-items: center; gap: 15px;">
            <a href="cartitem.php" class="cart-glow" title="Cart" style="color: #4da8ff; text-decoration: none;">
                <i class="fas fa-shopping-cart"></i>
                <span class="cart-count" style="background: #4da8ff; color: #fff; border-radius: 50%; padding: 2px 6px; font-size: 12px;"><?php echo $cartCount; ?></span>
            </a>
            <a href="Login.php" title="Login" style="color: #4da8ff; font-size: 20px;">🔑</a>
            <a href="Register.php" title="Register" style="color: #4da8ff; font-size: 20px;">📝</a>
            <img src="images/<?php echo htmlspecialchars($profileImage); ?>" alt="Profile" class="profile-icon" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;" />
            <div class="dots-menu" style="color: #e0e0e0; font-size: 24px; cursor: pointer; position: relative;">⋮
                <div class="dropdown" style="display: none; position: absolute; right: 0; background: #2c2c2c; border: 1px solid #444; border-radius: 5px; padding: 10px;">
                    <a href="profile.php" style="display: block; color: #e0e0e0; padding: 5px; text-decoration: none;">Users</a>
                    <a href="admin.php" style="display: block; color: #e0e0e0; padding: 5px; text-decoration: none;">Admin</a>
                    <a href="Login.php" style="display: block; color: #e0e0e0; padding: 5px; text-decoration: none;">Login</a>
                    <a href="Contact.php" style="display: block; color: #e0e0e0; padding: 5px; text-decoration: none;">Contact</a>
                    <a href="track.php" style="display: block; color: #e0e0e0; padding: 5px; text-decoration: none;">Track Orders</a>
                </div>
            </div>
        </div>
    </header>

    <nav style="background: #1a1a1a; padding: 10px 20px;">
        <input type="checkbox" id="menu-toggle" style="display: none;" />
        <label class="hamburger" for="menu-toggle" style="color: #e0e0e0; font-size: 24px; cursor: pointer;">☰</label>
        <ul class="menu" style="list-style: none; padding: 0; display: none;">
            <li><a href="Index.php" style="color: #4da8ff; text-decoration: none; font-family: 'Roboto', 'Arial', sans-serif;">🏠 Home</a></li>
            <li><a href="Inventory.php" style="color: #4da8ff; text-decoration: none; font-family: 'Roboto', 'Arial', sans-serif;">🚗 Inventory</a></li>
            <li><a href="AboutUs.php" style="color: #4da8ff; text-decoration: none; font-family: 'Roboto', 'Arial', sans-serif;">📄 About</a></li>
            <li><a href="Contact.php" style="color: #4da8ff; text-decoration: none; font-family: 'Roboto', 'Arial', sans-serif;">📞 Contact</a></li>
            <li><a href="Logout.php" style="color: #4da8ff; text-decoration: none; font-family: 'Roboto', 'Arial', sans-serif;">🚪 Logout</a></li>
        </ul>
    </nav>

    <main style="display: flex; justify-content: center; align-items: center; min-height: 80vh; background: #1a1a1a; padding: 20px; margin-top: 20px;">
        <div class="form-box" style="max-width: 800px; width: 100%; background: #2c2c2c; border-radius: 12px; box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3); padding: 30px; border: 1px solid #444;">
            <h2 class="form-title" style="font-size: 28px; color: #e0e0e0; margin: 0 0 20px; font-family: 'Roboto', 'Arial', sans-serif; font-weight: 700; text-align: center;">➕ Add Product Details</h2>
            <form method="post" enctype="multipart/form-data" class="styled-form" style="display: flex; flex-wrap: wrap; gap: 20px;">
                <div class="form-group" style="flex: 1 1 45%; min-width: 300px;">
                    <label for="enginetype" style="font-size: 16px; color: #e0e0e0; font-family: 'Roboto', 'Arial', sans-serif;">Engine Type</label>
                    <input type="text" id="enginetype" name="enginetype" required style="width: 100%; padding: 10px; border: 1px solid #444; border-radius: 5px; background: #333; color: #e0e0e0; font-family: 'Roboto', 'Arial', sans-serif;">
                </div>
                <div class="form-group" style="flex: 1 1 45%; min-width: 300px;">
                    <label for="image1" style="font-size: 16px; color: #e0e0e0; font-family: 'Roboto', 'Arial', sans-serif;">Image 1 (JPEG/PNG, max 5MB)</label>
                    <input type="file" id="image1" name="image1" accept="image/jpeg,image/png" style="width: 100%; padding: 10px; border: 1px solid #444; border-radius: 5px; background: #333; color: #e0e0e0; font-family: 'Roboto', 'Arial', sans-serif;">
                </div>
                <div class="form-group" style="flex: 1 1 45%; min-width: 300px;">
                    <label for="image2" style="font-size: 16px; color: #e0e0e0; font-family: 'Roboto', 'Arial', sans-serif;">Image 2 (JPEG/PNG, max 5MB)</label>
                    <input type="file" id="image2" name="image2" accept="image/jpeg,image/png" style="width: 100%; padding: 10px; border: 1px solid #444; border-radius: 5px; background: #333; color: #e0e0e0; font-family: 'Roboto', 'Arial', sans-serif;">
                </div>
                <div class="form-group" style="flex: 1 1 45%; min-width: 300px;">
                    <label for="image3" style="font-size: 16px; color: #e0e0e0; font-family: 'Roboto', 'Arial', sans-serif;">Image 3 (JPEG/PNG, max 5MB)</label>
                    <input type="file" id="image3" name="image3" accept="image/jpeg,image/png" style="width: 100%; padding: 10px; border: 1px solid #444; border-radius: 5px; background: #333; color: #e0e0e0; font-family: 'Roboto', 'Arial', sans-serif;">
                </div>
                <div class="form-group" style="flex: 1 1 45%; min-width: 300px;">
                    <label for="horsepower" style="font-size: 16px; color: #e0e0e0; font-family: 'Roboto', 'Arial', sans-serif;">Horsepower</label>
                    <input type="text" id="horsepower" name="horsepower" required style="width: 100%; padding: 10px; border: 1px solid #444; border-radius: 5px; background: #333; color: #e0e0e0; font-family: 'Roboto', 'Arial', sans-serif;">
                </div>
                <div class="form-group" style="flex: 1 1 45%; min-width: 300px;">
                    <label for="transmission" style="font-size: 16px; color: #e0e0e0; font-family: 'Roboto', 'Arial', sans-serif;">Transmission</label>
                    <input type="text" id="transmission" name="transmission" required style="width: 100%; padding: 10px; border: 1px solid #444; border-radius: 5px; background: #333; color: #e0e0e0; font-family: 'Roboto', 'Arial', sans-serif;">
                </div>
                <div class="form-group" style="flex: 1 1 45%; min-width: 300px;">
                    <label for="fueltype" style="font-size: 16px; color: #e0e0e0; font-family: 'Roboto', 'Arial', sans-serif;">Fuel Type</label>
                    <input type="text" id="fueltype" name="fueltype" required style="width: 100%; padding: 10px; border: 1px solid #444; border-radius: 5px; background: #333; color: #e0e0e0; font-family: 'Roboto', 'Arial', sans-serif;">
                </div>
                <div class="form-group" style="flex: 1 1 45%; min-width: 300px;">
                    <label for="fueltankcapacity" style="font-size: 16px; color: #e0e0e0; font-family: 'Roboto', 'Arial', sans-serif;">Fuel Tank Capacity</label>
                    <input type="text" id="fueltankcapacity" name="fueltankcapacity" required style="width: 100%; padding: 10px; border: 1px solid #444; border-radius: 5px; background: #333; color: #e0e0e0; font-family: 'Roboto', 'Arial', sans-serif;">
                </div>
                <div class="form-group" style="flex: 1 1 45%; min-width: 300px;">
                    <label for="mileage" style="font-size: 16px; color: #e0e0e0; font-family: 'Roboto', 'Arial', sans-serif;">Mileage</label>
                    <input type="text" id="mileage" name="mileage" required style="width: 100%; padding: 10px; border: 1px solid #444; border-radius: 5px; background: #333; color: #e0e0e0; font-family: 'Roboto', 'Arial', sans-serif;">
                </div>
                <div class="form-group" style="flex: 1 1 45%; min-width: 300px;">
                    <label for="topspeed" style="font-size: 16px; color: #e0e0e0; font-family: 'Roboto', 'Arial', sans-serif;">Top Speed</label>
                    <input type="text" id="topspeed" name="topspeed" required style="width: 100%; padding: 10px; border: 1px solid #444; border-radius: 5px; background: #333; color: #e0e0e0; font-family: 'Roboto', 'Arial', sans-serif;">
                </div>
                <div class="form-group" style="flex: 1 1 45%; min-width: 300px;">
                    <label for="drivetype" style="font-size: 16px; color: #e0e0e0; font-family: 'Roboto', 'Arial', sans-serif;">Drive Type</label>
                    <input type="text" id="drivetype" name="drivetype" required style="width: 100%; padding: 10px; border: 1px solid #444; border-radius: 5px; background: #333; color: #e0e0e0; font-family: 'Roboto', 'Arial', sans-serif;">
                </div>
                <div class="form-group" style="flex: 1 1 45%; min-width: 300px;">
                    <label for="coloroptions" style="font-size: 16px; color: #e0e0e0; font-family: 'Roboto', 'Arial', sans-serif;">Color Options</label>
                    <input type="text" id="coloroptions" name="coloropt" required style="width: 100%; padding: 10px; border: 1px solid #444; border-radius: 5px; background: #333; color: #e0e0e0; font-family: 'Roboto', 'Arial', sans-serif;">
                </div>
                <div class="form-group" style="flex: 1 1 45%; min-width: 300px;">
                    <label for="dimensions" style="font-size: 16px; color: #e0e0e0; font-family: 'Roboto', 'Arial', sans-serif;">Dimensions</label>
                    <input type="text" id="dimensions" name="dimensions" required style="width: 100%; padding: 10px; border: 1px solid #444; border-radius: 5px; background: #333; color: #e0e0e0; font-family: 'Roboto', 'Arial', sans-serif;">
                </div>
                <div class="form-group" style="flex: 1 1 45%; min-width: 300px;">
                    <label for="seatingcapacity" style="font-size: 16px; color: #e0e0e0; font-family: 'Roboto', 'Arial', sans-serif;">Seating Capacity</label>
                    <input type="text" id="seatingcapacity" name="seatingcapacity" required style="width: 100%; padding: 10px; border: 1px solid #444; border-radius: 5px; background: #333; color: #e0e0e0; font-family: 'Roboto', 'Arial', sans-serif;">
                </div>
                <div class="form-group" style="flex: 1 1 45%; min-width: 300px;">
                    <label for="infotainmentfeatures" style="font-size: 16px; color: #e0e0e0; font-family: 'Roboto', 'Arial', sans-serif;">Infotainment Features</label>
                    <input type="text" id="infotainmentfeatures" name="infotainmentfeatures" required style="width: 100%; padding: 10px; border: 1px solid #444; border-radius: 5px; background: #333; color: #e0e0e0; font-family: 'Roboto', 'Arial', sans-serif;">
                </div>
                <div class="form-group" style="flex: 1 1 45%; min-width: 300px;">
                    <label for="safetyfeatures" style="font-size: 16px; color: #e0e0e0; font-family: 'Roboto', 'Arial', sans-serif;">Safety Features</label>
                    <input type="text" id="safetyfeatures" name="safetyfeatures" required style="width: 100%; padding: 10px; border: 1px solid #444; border-radius: 5px; background: #333; color: #e0e0e0; font-family: 'Roboto', 'Arial', sans-serif;">
                </div>
                <div class="form-group" style="flex: 1 1 45%; min-width: 300px;">
                    <label for="suspensiontype" style="font-size: 16px; color: #e0e0e0; font-family: 'Roboto', 'Arial', sans-serif;">Suspension Type</label>
                    <input type="text" id="suspensiontype" name="suspensiontype" required style="width: 100%; padding: 10px; border: 1px solid #444; border-radius: 5px; background: #333; color: #e0e0e0; font-family: 'Roboto', 'Arial', sans-serif;">
                </div>
                <div class="form-group" style="flex: 1 1 45%; min-width: 300px;">
                    <label for="breakingsystem" style="font-size: 16px; color: #e0e0e0; font-family: 'Roboto', 'Arial', sans-serif;">Braking System</label>
                    <input type="text" id="breakingsystem" name="breakingsystem" required style="width: 100%; padding: 10px; border: 1px solid #444; border-radius: 5px; background: #333; color: #e0e0e0; font-family: 'Roboto', 'Arial', sans-serif;">
                </div>
                <div class="form-group" style="flex: 1 1 45%; min-width: 300px;">
                    <label for="wheelstype" style="font-size: 16px; color: #e0e0e0; font-family: 'Roboto', 'Arial', sans-serif;">Wheels Type</label>
                    <input type="text" id="wheelstype" name="wheelstype" required style="width: 100%; padding: 10px; border: 1px solid #444; border-radius: 5px; background: #333; color: #e0e0e0; font-family: 'Roboto', 'Arial', sans-serif;">
                </div>
                <div style="flex: 1 1 100%; text-align: center;">
                    <button type="submit" name="submit" class="btn-submit" style="padding: 10px 20px; background: #4da8ff; color: #fff; border: none; border-radius: 5px; font-size: 16px; font-family: 'Roboto', 'Arial', sans-serif; cursor: pointer; transition: background 0.3s;">➕ Submit</button>
                    <a href="product.php?id=<?php echo $carid; ?>" class="back-link" style="display: inline-block; margin-left: 10px; color: #4da8ff; text-decoration: none; font-size: 16px; font-family: 'Roboto', 'Arial', sans-serif; transition: color 0.3s;">← Back to Product</a>
                </div>
            </form>
        </div>
    </main>

    <div class="notification" id="notification" style="position: fixed; bottom: 20px; right: 20px; background: #28a745; color: #fff; padding: 10px 20px; border-radius: 5px; display: none; z-index: 1000;"></div>

    <footer style="background: #1a1a1a; padding: 20px; color: #e0e0e0; font-family: 'Roboto', 'Arial', sans-serif;">
        <div class="footer-section" style="margin-bottom: 20px;">
            <h3 style="font-size: 18px; margin-bottom: 10px;">📄 About Us</h3>
            <p>Luxury Car Showroom brings you the best and most exclusive cars worldwide.</p>
        </div>
        <div class="footer-section footer-links" style="margin-bottom: 20px;">
            <h3 style="font-size: 18px; margin-bottom: 10px;">🔗 Quick Links</h3>
            <a href="Index.php" style="display: block; color: #4da8ff; text-decoration: none; margin: 5px 0;">🏠 Home</a>
            <a href="Inventory.php" style="display: block; color: #4da8ff; text-decoration: none; margin: 5px 0;">🚗 Inventory</a>
            <a href="AboutUs.php" style="display: block; color: #4da8ff; text-decoration: none; margin: 5px 0;">📄 About</a>
            <a href="Contact.php" style="display: block; color: #4da8ff; text-decoration: none; margin: 5px 0;">📞 Contact</a>
            <a href="track.php" style="display: block; color: #4da8ff; text-decoration: none; margin: 5px 0;">📦 Track Orders</a>
        </div>
        <div class="footer-section footer-services" style="margin-bottom: 20px;">
            <h3 style="font-size: 18px; margin-bottom: 10px;">🛠️ Our Services</h3>
            <p style="margin: 5px 0;">Luxury Car Sales</p>
            <p style="margin: 5px 0;">Pre-Owned Cars</p>
            <p style="margin: 5px 0;">Car Financing</p>
            <p style="margin: 5px 0;">Maintenance & Repair</p>
        </div>
        <div class="footer-section footer-contact" style="margin-bottom: 20px;">
            <h3 style="font-size: 18px; margin-bottom: 10px;">📞 Contact Us</h3>
            <p style="margin: 5px 0;">📍 123 Luxury St, Beverly Hills, CA</p>
            <p style="margin: 5px 0;">📞 +1 234 567 890</p>
            <p style="margin: 5px 0;">📧 info@luxurycars.com</p>
        </div>
        <div class="footer-section newsletter" style="margin-bottom: 20px;">
            <h3 style="font-size: 18px; margin-bottom: 10px;">📬 Subscribe to Our Newsletter</h3>
            <input type="email" placeholder="Enter your email" style="padding: 10px; border: 1px solid #444; border-radius: 5px; background: #333; color: #e0e0e0; font-family: 'Roboto', 'Arial', sans-serif;">
            <button style="padding: 10px 20px; background: #4da8ff; color: #fff; border: none; border-radius: 5px; font-size: 16px; font-family: 'Roboto', 'Arial', sans-serif; cursor: pointer; margin-top: 10px;">Subscribe</button>
        </div>
        <div class="footer-bottom" style="text-align: center;">
            <p>© 2025 Luxury Car Showroom | Designed by Nitin Rawat</p>
        </div>
    </footer>

    <script>
        // Hover effect for submit button and back link
        document.querySelector('.btn-submit').addEventListener('mouseover', function() {
            this.style.background = '#66b3ff';
        });
        document.querySelector('.btn-submit').addEventListener('mouseout', function() {
            this.style.background = '#4da8ff';
        });
        document.querySelector('.back-link').addEventListener('mouseover', function() {
            this.style.color = '#66b3ff';
        });
        document.querySelector('.back-link').addEventListener('mouseout', function() {
            this.style.color = '#4da8ff';
        });

        // Dropdown menu toggle
        document.querySelector('.dots-menu').addEventListener('click', function() {
            const dropdown = this.querySelector('.dropdown');
            dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        });
    </script>
    <?php mysqli_close($conn); ?>
</body>
</html>
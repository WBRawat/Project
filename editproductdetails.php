<?php
session_start();
include("Conection.php");

// Initialize variables
$cartCount = 0;
$profileImage = "default.png";
$msg = "";

// Check session for cart count and profile image
if (isset($_SESSION['userid'])) {
    $userid = $_SESSION['userid'];
    $sql = "SELECT SUM(Quantity) AS total FROM Cart WHERE SessionID = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $userid);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $cartCount = $row['total'] ?? 0;
    }
    mysqli_stmt_close($stmt);

    if (!empty($_SESSION['userimage'])) {
        $profileImage = $_SESSION['userimage'];
    }
}

// Fetch product details
if (isset($_GET['id'])) {
    $carid = intval($_GET['id']);
    $sql = "SELECT * FROM Details WHERE CarID = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $carid);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result->num_rows == 1) {
        $row = mysqli_fetch_assoc($result);
    } else {
        $msg = "<div class='alert alert-danger'>Record not found!</div>";
        exit;
    }
    mysqli_stmt_close($stmt);
} else {
    $msg = "<div class='alert alert-danger'>ID not provided!</div>";
    exit;
}

// Handle form submission
if (isset($_POST['updt'])) {
    $carid = intval($_POST['CarID']);
    $enginetype = trim($_POST['EngineType']);
    $horsepower = trim($_POST['Horsepower']);
    $transmission = trim($_POST['Transmission']);
    $fueltype = trim($_POST['FuelType']);
    $fueltankcapacity = trim($_POST['FuelTankCapacity']);
    $mileage = trim($_POST['Mileage']);
    $topspeed = trim($_POST['TopSpeed']);
    $drivetype = trim($_POST['DriveType']);
    $coloroptions = trim($_POST['ColorOptions']);
    $dimensions = trim($_POST['Dimensions']);
    $seatingcapacity = trim($_POST['SeatingCapacity']);
    $infotainmentfeatures = trim($_POST['InfotainmentFeatures']);
    $safetyfeatures = trim($_POST['SafetyFeatures']);
    $suspensiontype = trim($_POST['SuspensionType']);
    $breakingsystem = trim($_POST['BreakingSystem']);
    $wheelstype = trim($_POST['WheelsType']);

    // Handle file uploads
    $image1 = $row['Image1'];
    $image2 = $row['Image2'];
    $image3 = $row['Image3'];

    if (!empty($_FILES['Image1']['name'])) {
        $image1 = $_FILES['Image1']['name'];
        $tempname1 = $_FILES['Image1']['tmp_name'];
        $folder1 = "images/" . $image1;
        if (is_writable('images')) {
            move_uploaded_file($tempname1, $folder1);
        } else {
            $msg = "<div class='alert alert-danger'>Image directory is not writable!</div>";
        }
    }

    if (!empty($_FILES['Image2']['name'])) {
        $image2 = $_FILES['Image2']['name'];
        $tempname2 = $_FILES['Image2']['tmp_name'];
        $folder2 = "images/" . $image2;
        if (is_writable('images')) {
            move_uploaded_file($tempname2, $folder2);
        } else {
            $msg = "<div class='alert alert-danger'>Image directory is not writable!</div>";
        }
    }

    if (!empty($_FILES['Image3']['name'])) {
        $image3 = $_FILES['Image3']['name'];
        $tempname3 = $_FILES['Image3']['tmp_name'];
        $folder3 = "images/" . $image3;
        if (is_writable('images')) {
            move_uploaded_file($tempname3, $folder3);
        } else {
            $msg = "<div class='alert alert-danger'>Image directory is not writable!</div>";
        }
    }

    // Update database with prepared statement
    $sql = "UPDATE Details SET EngineType = ?, Image1 = ?, Image2 = ?, Image3 = ?, Horsepower = ?, Transmission = ?, FuelType = ?, FuelTankCapacity = ?, Mileage = ?, TopSpeed = ?, DriveType = ?, ColorOptions = ?, Dimensions = ?, SeatingCapacity = ?, InfotainmentFeatures = ?, SafetyFeatures = ?, Suspension = ?, BreakingSystem = ?, WheelsType = ? WHERE CarID = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssssssssssssssssssi", $enginetype, $image1, $image2, $image3, $horsepower, $transmission, $fueltype, $fueltankcapacity, $mileage, $topspeed, $drivetype, $coloroptions, $dimensions, $seatingcapacity, $infotainmentfeatures, $safetyfeatures, $suspensiontype, $breakingsystem, $wheelstype, $carid);

    if (mysqli_stmt_execute($stmt)) {
        $msg = "<div class='alert alert-success'>Record updated successfully!</div>";
        header("Location: admin.php");
        exit;
    } else {
        $msg = "<div class='alert alert-danger'>Error: " . mysqli_error($conn) . "</div>";
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product Details - Luxury Car Showroom 🚘</title>
    <link rel="stylesheet" href="Styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <header class="site-header">
        <div class="logo-nav">
            <img src="logo.jpeg" alt="Logo" />
        </div>
        <h1 class="glow-text">🚘 Luxury Car Showroom</h1>
        <div class="nav-right">
            <a href="cartitem.php" class="cart-glow" title="Cart">
                <i class="fas fa-shopping-cart"></i>
                <span class="cart-count"><?php echo $cartCount; ?></span>
            </a>
            <a href="Login.php" title="Login">🔑</a>
            <a href="Register.php" title="Register">📝</a>
            <img src="images/<?php echo htmlspecialchars($profileImage); ?>" alt="Profile" class="profile-icon" />
            <div class="dots-menu">⋮
                <div class="dropdown">
                    <a href="profile.php">Users</a>
                    <a href="admin.php">Admin</a>
                    <a href="Login.php">Login</a>
                    <a href="Contact.php">Contact</a>
                </div>
            </div>
        </div>
    </header>

    <nav>
        <input type="checkbox" id="menu-toggle" />
        <label class="hamburger" for="menu-toggle">☰</label>
        <ul class="menu">
            <li><a href="Index.php">🏠 Home</a></li>
            <li><a href="Inventory.php">🚗 Inventory</a></li>
            <li><a href="AboutUs.php">📄 About</a></li>
            <li><a href="Contact.php">📞 Contact</a></li>
            <li><a href="Logout.php">🚪 Logout</a></li>
        </ul>
    </nav>

    <div class="form-container">
        <h2>Edit Product Details</h2>
        <?php echo $msg; ?>
        <form action="" method="post" enctype="multipart/form-data">
            <input type="hidden" name="CarID" value="<?php echo htmlspecialchars($row['CarID']); ?>">
            <div class="mb-3">
                <label class="form-label">Engine Type</label>
                <input type="text" name="EngineType" class="form-control" value="<?php echo htmlspecialchars($row['EngineType']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Image 1</label>
                <input type="file" name="Image1" class="form-control">
                <img src="images/<?php echo htmlspecialchars($row['Image1']); ?>" alt="Image 1" style="width:100px; margin-top:10px;">
            </div>
            <div class="mb-3">
                <label class="form-label">Image 2</label>
                <input type="file" name="Image2" class="form-control">
                <img src="images/<?php echo htmlspecialchars($row['Image2']); ?>" alt="Image 2" style="width:100px; margin-top:10px;">
            </div>
            <div class="mb-3">
                <label class="form-label">Image 3</label>
                <input type="file" name="Image3" class="form-control">
                <img src="images/<?php echo htmlspecialchars($row['Image3']); ?>" alt="Image 3" style="width:100px; margin-top:10px;">
            </div>
            <div class="mb-3">
                <label class="form-label">Horsepower</label>
                <input type="text" name="Horsepower" class="form-control" value="<?php echo htmlspecialchars($row['Horsepower']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Transmission</label>
                <input type="text" name="Transmission" class="form-control" value="<?php echo htmlspecialchars($row['Transmission']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Fuel Type</label>
                <input type="text" name="FuelType" class="form-control" value="<?php echo htmlspecialchars($row['FuelType']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Fuel Tank Capacity</label>
                <input type="text" name="FuelTankCapacity" class="form-control" value="<?php echo htmlspecialchars($row['FuelTankCapacity']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Mileage</label>
                <input type="text" name="Mileage" class="form-control" value="<?php echo htmlspecialchars($row['Mileage']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Top Speed</label>
                <input type="text" name="TopSpeed" class="form-control" value="<?php echo htmlspecialchars($row['TopSpeed']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Drive Type</label>
                <input type="text" name="DriveType" class="form-control" value="<?php echo htmlspecialchars($row['DriveType']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Color Options</label>
                <input type="text" name="ColorOptions" class="form-control" value="<?php echo htmlspecialchars($row['ColorOptions']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Dimensions</label>
                <input type="text" name="Dimensions" class="form-control" value="<?php echo htmlspecialchars($row['Dimensions']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Seating Capacity</label>
                <input type="text" name="SeatingCapacity" class="form-control" value="<?php echo htmlspecialchars($row['SeatingCapacity']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Infotainment Features</label>
                <input type="text" name="InfotainmentFeatures" class="form-control" value="<?php echo htmlspecialchars($row['InfotainmentFeatures']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Safety Features</label>
                <input type="text" name="SafetyFeatures" class="form-control" value="<?php echo htmlspecialchars($row['SafetyFeatures']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Suspension Type</label>
                <input type="text" name="SuspensionType" class="form-control" value="<?php echo htmlspecialchars($row['Suspension']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Braking System</label>
                <input type="text" name="BreakingSystem" class="form-control" value="<?php echo htmlspecialchars($row['BreakingSystem']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Wheels Type</label>
                <input type="text" name="WheelsType" class="form-control" value="<?php echo htmlspecialchars($row['WheelsType']); ?>" required>
            </div>
            <button type="submit" name="updt" class="btn btn-primary w-100">Update Product</button>
        </form>
    </div>

    <footer>
        <div class="footer-section">
            <h3>📄 About Us</h3>
            <p>Luxury Car Showroom brings you the best and most exclusive cars worldwide.</p>
        </div>
        <div class="footer-section footer-links">
            <h3>🔗 Quick Links</h3>
            <a href="Index.php">🏠 Home</a>
            <a href="Inventory.php">🚗 Inventory</a>
            <a href="AboutUs.php">📄 About</a>
            <a href="Contact.php">📞 Contact</a>
            <a href="Login.php">🔑 Login</a>
            <a href="Register.php">📝 Register</a>
        </div>
        <div class="footer-section footer-services">
            <h3>🛠️ Our Services</h3>
            <p>Luxury Car Sales</p>
            <p>Pre-Owned Cars</p>
            <p>Car Financing</p>
            <p>Maintenance & Repair</p>
        </div>
        <div class="footer-section footer-contact">
            <h3>📞 Contact Us</h3>
            <p>📍 123 Luxury St, Beverly Hills, CA</p>
            <p>📞 +1 234 567 890</p>
            <p>📧 info@luxurycars.com</p>
        </div>
        <div class="footer-section newsletter">
            <h3>📬 Subscribe to Our Newsletter</h3>
            <input type="email" placeholder="Enter your email">
            <button>Subscribe</button>
        </div>
        <div class="footer-bottom">
            <p>© 2025 Luxury Car Showroom | Designed by Nitin Rawat</p>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="script.js"></script>
</body>
</html>
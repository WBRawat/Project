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

// Fetch user details
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "SELECT * FROM User WHERE ID = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
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
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    $username = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $dob = trim($_POST['dob']);
    $favouritecar = trim($_POST['favouritecar']);
    $address = trim($_POST['address']);
    $image = $row['Image'];

    if (!empty($_FILES['image']['name'])) {
        $image = $_FILES['image']['name'];
        $tempname = $_FILES['image']['tmp_name'];
        $folder = "images/" . $image;
        if (is_writable('images')) {
            move_uploaded_file($tempname, $folder);
        } else {
            $msg = "<div class='alert alert-danger'>Image directory is not writable!</div>";
        }
    }

    // Update database with prepared statement
    $sql = "UPDATE User SET FullName = ?, Image = ?, Email = ?, PhoneNumber = ?, DateOfBirth = ?, FavouriteCar = ?, Address = ? WHERE ID = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssssssi", $username, $image, $email, $phone, $dob, $favouritecar, $address, $id);

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
    <title>Edit User - Luxury Car Showroom 🧑</title>
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
        <h2>Edit User</h2>
        <?php echo $msg; ?>
        <form action="" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($row['FullName']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Image</label>
                <input type="file" name="image" class="form-control">
                <img src="images/<?php echo htmlspecialchars($row['Image']); ?>" alt="User Image" style="width:100px; margin-top:10px;">
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($row['Email']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($row['PhoneNumber']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Date of Birth</label>
                <input type="date" name="dob" class="form-control" value="<?php echo htmlspecialchars($row['DateOfBirth']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Favourite Car</label>
                <input type="text" name="favouritecar" class="form-control" value="<?php echo htmlspecialchars($row['FavouriteCar']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Address</label>
                <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($row['Address']); ?>">
            </div>
            <button type="submit" name="update" class="btn btn-primary w-100">Update User</button>
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
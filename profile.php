<?php
session_start();
include("Conection.php");

$cartCount = 0;
$profileImage = "default.png";

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

if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    $sql = "SELECT * FROM User WHERE ID = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result->num_rows == 1) {
        $row = mysqli_fetch_assoc($result);
    } else {
        echo "<div class='alert alert-danger'>User not found.</div>";
        exit;
    }
    mysqli_stmt_close($stmt);
} else {
    echo "<div class='alert alert-danger'>Invalid request.</div>";
    exit;
}

// Check if profile image exists, fallback to default
$profileImagePath = !empty($row['Image']) && file_exists("images/" . $row['Image']) ? $row['Image'] : 'default.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - Lux Car Showroom 🧑</title>
    <link rel="stylesheet" href="Luxstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <header class="site-header">
        <div class="logo-nav">
            <img src="logo.jpeg" alt="Logo" />
        </div>
        <h1 class="glow-text">🚘 Lux Car Showroom</h1>
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
                    <a href="track.php">Track Orders</a>
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

    <main style="display: flex; justify-content: center; align-items: center; min-height: 80vh; background: #1a1a1a; padding: 20px; margin-top: 20px;">
        <div class="profile-container" style="max-width: 700px; width: 100%; margin: 0 auto;">
            <div class="profile-card" style="background: #2c2c2c; border-radius: 12px; box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3); padding: 30px; text-align: center; border: 1px solid #444;">
                <div class="profile-image" style="margin-bottom: 25px;">
                    <img src="images/<?php echo htmlspecialchars($profileImagePath); ?>" alt="User Profile" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);">
                </div>
                <h2 style="font-size: 28px; color: #e0e0e0; margin: 10px 0; font-family: 'Roboto', 'Arial', sans-serif; font-weight: 700;"><?php echo htmlspecialchars($row['FullName']); ?> 🧑</h2>
                <p style="font-size: 16px; color: #e0e0e0; margin: 8px 0; line-height: 1.6; font-family: 'Roboto', 'Arial', sans-serif;"><strong>Email:</strong> <?php echo htmlspecialchars($row['Email']); ?></p>
                <p style="font-size: 16px; color: #e0e0e0; margin: 8px 0; line-height: 1.6; font-family: 'Roboto', 'Arial', sans-serif;"><strong>Phone:</strong> <?php echo htmlspecialchars($row['PhoneNumber']); ?></p>
                <p style="font-size: 16px; color: #e0e0e0; margin: 8px 0; line-height: 1.6; font-family: 'Roboto', 'Arial', sans-serif;"><strong>Date of Birth:</strong> <?php echo htmlspecialchars($row['DateOfBirth']); ?></p>
                <p style="font-size: 16px; color: #e0e0e0; margin: 8px 0; line-height: 1.6; font-family: 'Roboto', 'Arial', sans-serif;"><strong>Favourite Car:</strong> <?php echo htmlspecialchars($row['FavouriteCar']); ?> 🚗</p>
                <p style="font-size: 16px; color: #e0e0e0; margin: 8px 0; line-height: 1.6; font-family: 'Roboto', 'Arial', sans-serif;"><strong>Address:</strong> <?php echo htmlspecialchars($row['Address']); ?></p>
                <a class="back-link" href="admin.php" style="display: inline-block; margin-top: 25px; color: #4da8ff; text-decoration: none; font-size: 16px; font-family: 'Roboto', 'Arial', sans-serif; transition: color 0.3s;">← Back to Users List</a>
            </div>
        </div>
    </main>

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
            <a href="admin.php">🛠️ Admin</a>
            <a href="Login.php">🔑 Login</a>
            <a href="Register.php">📝 Register</a>
            <a href="track.php">📍 Track Orders</a>
        </div>
        <div class="footer-section footer-services">
            <h3>🛠️ Our Services</h3>
            <p>Luxury Car Sales</p>
            <p>Certified Pre-Owned</p>
            <p>Flexible Financing</p>
            <p>24x7 Maintenance</p>
            <p>VIP Customization</p>
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
            <p>© 2025 Lux Car Showroom | Designed by Nitin Rawat</p>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="script.js"></script>
    <script>
        // Hover effect for back link
        document.querySelector('.back-link').addEventListener('mouseover', function() {
            this.style.color = '#66b3ff';
        });
        document.querySelector('.back-link').addEventListener('mouseout', function() {
            this.style.color = '#4da8ff';
        });
    </script>
</body>
</html>
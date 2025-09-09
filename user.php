<?php
include("Conection.php");

$sql = "SELECT ID, FullName, Email, PhoneNumber, DateOfBirth, FavouriteCar, Address FROM User";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - Luxury Car Showroom 🧑</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header class="site-header">
        <div class="logo-nav">
            <img src="logo.jpeg" alt="Luxury Car Showroom Logo">
        </div>
        <h1 class="glow-text">🚘 Luxury Car Showroom</h1>
        <div class="nav-right">
            <a href="cart.php" class="cart-glow" title="Cart">🛒</a>
            <a href="Login.php" title="Login">🔑</a>
            <a href="Register.php" title="Register">📝</a>
            <img src="images/profile.jpg" alt="Profile" class="profile-icon">
        </div>
    </header>

    <nav>
        <ul class="menu">
            <li><a href="Index.php">🏠 Home</a></li>
            <li><a href="Inventory.php">🚗 Inventory</a></li>
            <li><a href="AboutUs.php">📄 About</a></li>
            <li><a href="Contact.php">📞 Contact</a></li>
            <li><a href="Logout.php">🚪 Logout</a></li>
        </ul>
    </nav>

    <main>
        <h2 class="animate-welcome">🧑 Users List</h2>
        <div class="section">
            <?php
            if ($result->num_rows > 0) {
                echo "<table class='user-table'>";
                echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Phone Number</th><th>Date Of Birth</th><th>Favourite Car</th><th>Address</th></tr>";
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row["ID"]) . "</td>";
                    echo "<td>" . htmlspecialchars($row["FullName"]) . "</td>";
                    echo "<td>" . htmlspecialchars($row["Email"]) . "</td>";
                    echo "<td>" . htmlspecialchars($row["PhoneNumber"]) . "</td>";
                    echo "<td>" . htmlspecialchars($row["DateOfBirth"]) . "</td>";
                    echo "<td>" . htmlspecialchars($row["FavouriteCar"]) . " 🚗</td>";
                    echo "<td>" . htmlspecialchars($row["Address"]) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p>No users found.</p>";
            }
            $conn->close();
            ?>
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
</body>
</html>
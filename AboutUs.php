<?php
session_start();
include "Conection.php";

// Initialize variables
$cartCount = 0;
$profileImage = "default.png";

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>About Us - Luxury Car Showroom</title>
  <link rel="stylesheet" href="Luxstyle.css" />
</head>
<body>
    <header class="site-header">
        <div class="logo-nav">
            <img src="logo.jpeg" alt="Logo" />
        </div>
        <h1 class="glow-text">🚘 Lux Car Showroom</h1>
        <div class="nav-right">
            <a href="cartitem.php" class="cart-glow" title="Cart">
                <i class="fas fa-shopping-cart">🛒</i>
                <span class="cart-count" id="cartcount"><?php echo $cartCount; ?></span>
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

  <!-- Main Content -->
  <main>
    <h2 class="animate-welcome">📄 About Us</h2>

    <div class="section">
      <p>
        Welcome to <strong>Luxury Car Showroom</strong> – your ultimate destination for top-tier automobiles. 
        Established in <strong>1990</strong> by visionary car enthusiast <strong>Mr. Aryan Singh</strong>, 
        our showroom has consistently delivered premium automotive experiences to clients across the globe.
      </p>

      <p>
        Our curated collection includes the world’s most iconic and high-performance vehicles, 
        from sports cars and convertibles to luxury sedans and SUVs. We ensure every car meets the highest standards of quality and design.
      </p>

      <p>
        At Luxury Car Showroom, we believe in more than just selling cars. 
        We build relationships by offering:
      </p>
      <ul>
        <li>✅ Certified Pre-Owned Vehicles</li>
        <li>⚙️ Maintenance & Servicing Plans</li>
        <li>💰 Flexible Financing Options</li>
        <li>🎁 Seasonal Offers & Loyalty Programs</li>
      </ul>

      <p>
        With a passionate team and state-of-the-art facility, we are proud to serve enthusiasts, collectors, and luxury seekers alike.
        Thank you for trusting us to drive your dream forward! 🚗✨
      </p>
    </div>
  </main>

  <!-- Footer -->
  <footer>
    <div class="footer-section">
      <h3>About Us</h3>
      <p>Luxury Car Showroom brings you the best and most exclusive cars worldwide.</p>
    </div>

    <div class="footer-section footer-links">
      <h3>Quick Links</h3>
      <a href="Index.php">🏠 Home</a>
      <a href="Inventory.php">🚗 Inventory</a>
      <a href="AboutUs.php">📄 About</a>
      <a href="Contact.php">📞 Contact</a>
      <a href="Login.php">🔑 Login</a>
      <a href="Register.php">📝 Register</a>
    </div>

    <div class="footer-section footer-services">
      <h3>Our Services</h3>
      <p>Luxury Car Sales</p>
      <p>Pre-Owned Cars</p>
      <p>Car Financing</p>
      <p>Maintenance & Repair</p>
    </div>

    <div class="footer-section footer-contact">
      <h3>Contact Us</h3>
      <p>📍 123 Luxury St, Beverly Hills, CA</p>
      <p>📞 +1 234 567 890</p>
      <p>📧 info@luxurycars.com</p>
    </div>

    <div class="footer-section newsletter">
      <h3>Subscribe to Our Newsletter</h3>
      <input type="email" placeholder="Enter your email">
      <button>Subscribe</button>
    </div>

    <div class="footer-bottom">
      <p>© 2025 Luxury Car Showroom | Designed by Nitin Rawat</p>
    </div>
  </footer>

</body>
</html>
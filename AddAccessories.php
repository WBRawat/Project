<?php
include("Connection.php"); // Corrected
session_start();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);

    if (!$name || $price === false || !$description) {
        $errors[] = "Invalid input data.";
    }

    // Image Upload
    if (empty($errors) && isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['image/jpeg', 'image/png'];
        $maxSize = 5000000; // 5MB
        if (in_array($_FILES['image']['type'], $allowed) && $_FILES['image']['size'] <= $maxSize) {
            $image = uniqid() . '_' . basename($_FILES['image']['name']);
            $path = "images/" . $image;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $path)) {
                $sql = "INSERT INTO Accessories (name, price, image, description) VALUES (?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sdss", $name, $price, $image, $description);
                if (mysqli_stmt_execute($stmt)) {
                    echo "<script>alert('Accessory added successfully');</script>";
                } else {
                    $errors[] = mysqli_error($conn);
                }
                mysqli_stmt_close($stmt);
            } else {
                $errors[] = "Failed to upload image.";
            }
        } else {
            $errors[] = "Invalid image type or size.";
        }
    }

    if (!empty($errors)) {
        echo "<script>alert('Error: " . implode(', ', $errors) . "');</script>";
    }
}

$cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
$profileImage = isset($_SESSION['userimage']) ? $_SESSION['userimage'] : 'profile.jpg';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Lux Car Showroom</title>
  <link rel="stylesheet" href="Styles.css" />
  
</head>
<body>

    <style> 
        .form-box {
  max-width: 600px;
  margin: 40px auto;
  padding: 30px;
  background: #ffffff;
  border-radius: 16px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
  color: #000;
}

.form-title {
  text-align: center;
  font-size: 26px;
  color: #b30000;
  margin-bottom: 25px;
}

.styled-form .form-group {
  margin-bottom: 20px;
}

.styled-form label {
  display: block;
  margin-bottom: 6px;
  font-weight: bold;
  color: #000;
}

.styled-form input[type="text"],
.styled-form input[type="number"],
.styled-form input[type="file"],
.styled-form textarea {
  width: 100%;
  padding: 10px 14px;
  border: 1px solid #aaa;
  border-radius: 8px;
  font-size: 15px;
  background: #f9f9f9;
  color: #000;
}

.styled-form textarea {
  height: 100px;
  resize: vertical;
}

.btn-submit {
  width: 100%;
  background-color: #cc0000;
  color: white;
  padding: 12px;
  font-size: 16px;
  border: none;
  border-radius: 10px;
  cursor: pointer;
  transition: background 0.3s ease;
}

.btn-submit:hover {
  background-color: #a80000;
}
</style>

  <!-- Header with Glow Text -->
  <header class="site-header">
    <div class="logo-nav">
      <img src="logo.jpeg" alt="Lux Car Showroom Logo" />
    </div>
    <h1 class="glow-text">🚘 Lux Car Showroom</h1>
    <div class="nav-right">
      <!-- Cart with Count -->
      <a href="cartitem.php" class="cart-glow" title="Cart"> <!-- Fixed link -->
        🛒 <span class="cart-count" id="cart-count"><?php echo $cartCount; ?></span>
      </a>
      <a href="Login.php" title="Login">🔑</a>
      <a href="Register.php" title="Register">📝</a>
      <img src="images/<?php echo htmlspecialchars($profileImage); ?>" alt="Profile Icon" class="profile-icon" />

      <!-- 3 Dot Menu -->
      <div class="dots-menu">⋮
        <div class="dropdown">
          <a href="Index.php">🏠 Home</a>
          <a href="Admin.php">🛠️ Admin</a>
          <a href="Login.php">🔑 Login</a>
          <a href="Contact.php">📞 Contact</a>
        </div>
      </div>
    </div>
  </header>

  <!-- Navigation -->
  <nav>
    <input type="checkbox" id="menu-toggle" />
    <label class="hamburger" for="menu-toggle">☰</label>
    <ul class="menu">
      <li><a href="Index.php">🏠 Home</a></li>
      <li><a href="Inventory.php">🚗 Inventory</a></li>
      <li><a href="Admin.php">🛠️ Admin</a></li>
      <li><a href="AboutUs.php">📄 About</a></li>
      <li><a href="Contact.php">📞 Contact</a></li>
    </ul>
  </nav>

<div class="form-box">
    <h2 class="form-title">➕ Add New Accessory</h2>
    <form method="post" enctype="multipart/form-data" class="styled-form">
        <div class="form-group">
            <label for="name">Accessory Name</label>
            <input type="text" id="name" name="name" required>
        </div>

        <div class="form-group">
            <label for="price">Price (₹)</label>
            <input type="number" step="0.01" id="price" name="price" required min="0">
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" required></textarea>
        </div>

        <div class="form-group">
            <label for="image">Upload Image (JPEG/PNG, max 5MB)</label>
            <input type="file" id="image" name="image" accept="image/jpeg, image/png" required>
        </div>

        <button type="submit" class="btn-submit">➕ Add Accessory</button>
    </form>
</div>

 <!-- Footer -->
  <footer>
    <div class="footer-section">
      <h3>About Us</h3>
      <p>Luxury Car Showroom brings you the best and most exclusive cars worldwide.</p>
    </div>

    <div class="footer-section footer-links">
      <h3>Quick Links</h3>
      <a href="Inventory.php">🚗 Inventory</a>
      <a href="AboutUs.php">📄 About</a>
      <a href="Contact.php">📞 Contact</a>
      <a href="Admin.php">🛠️ Admin</a>
      <a href="Login.php">🔑 Login</a>
      <a href="Register.php">📝 Register</a>
    </div>

    <div class="footer-section">
      <h3>Our Services</h3>
      <p>Luxury Car Sales</p>
      <p>Certified Pre-Owned</p>
      <p>Flexible Financing</p>
      <p>24x7 Maintenance</p>
    </div>

    <div class="footer-section footer-contact">
      <h3>Contact Us</h3>
      <p>📍 123 Luxury St, Beverly Hills, CA</p>
      <p>📞 +1 234 567 890</p>
      <p>📧 info@luxurycars.com</p>
    </div>

    <div class="footer-section newsletter">
      <h3>Subscribe to Our Newsletter</h3>
      <input type="email" placeholder="Enter your email" />
      <button>Subscribe</button>
    </div>

    <div class="footer-bottom">
      <p>© 2025 Lux Car Showroom | Designed by Nitin Rawat</p>
    </div>
  </footer>


</body>
</html>
<?php mysqli_close($conn); ?>
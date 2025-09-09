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
    $id = intval($_GET['id']);
    $sql = "SELECT * FROM product WHERE ID = ?";
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
if (isset($_POST['updt'])) {
    $productName = trim($_POST['ProductName']);
    $price = trim($_POST['Price']);
    $category = trim($_POST['Category']);
    $stock = trim($_POST['Stock']);
    $description = trim($_POST['Description']);
    $status = trim($_POST['Status']);
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
    $sql = "UPDATE product SET ProductName = ?, Price = ?, Image = ?, Category = ?, Stock = ?, Description = ?, Status = ? WHERE ID = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssssssi", $productName, $price, $image, $category, $stock, $description, $status, $id);

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
    <title>Edit Product - Luxury Car Showroom 🚘</title>
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
        <h2>Edit Product</h2>
        <?php echo $msg; ?>
        <form action="" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Product Name</label>
                <input type="text" name="ProductName" class="form-control" value="<?php echo htmlspecialchars($row['ProductName']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Price</label>
                <input type="text" name="Price" class="form-control" value="<?php echo htmlspecialchars($row['Price']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Image</label>
                <input type="file" name="image" class="form-control">
                <img src="images/<?php echo htmlspecialchars($row['Image']); ?>" alt="Product Image" style="width:100px; margin-top:10px;">
            </div>
            <div class="mb-3">
                <label class="form-label">Category</label>
                <input type="text" name="Category" class="form-control" value="<?php echo htmlspecialchars($row['Category']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Stock</label>
                <input type="text" name="Stock" class="form-control" value="<?php echo htmlspecialchars($row['Stock']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <input type="text" name="Description" class="form-control" value="<?php echo htmlspecialchars($row['Description']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <input type="text" name="Status" class="form-control" value="<?php echo htmlspecialchars($row['Status']); ?>">
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
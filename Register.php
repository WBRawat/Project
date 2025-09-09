<?php
ini_set('session.cookie_lifetime', 86400);
ini_set('session.gc_maxlifetime', 86400);
session_start();
include 'Conection.php';

$cartCount = 0;
$profileImage = "default.png";
$msg = "";

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

if (isset($_POST['submit'])) {
    $username = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $dob = $_POST['dob'];
    $favouritecar = trim($_POST['favcar']);
    $address = trim($_POST['address']);
    $pass = $_POST['password'];
    $confirmpass = $_POST['confirm-password'];
    $usertype = "public";
    $image = 'default.png';

    if ($pass !== $confirmpass) {
        $msg = "<div class='alert alert-danger'>Confirm password does not match.</div>";
    } else {
        if (!empty($_FILES['image']['name'])) {
            $image = $_FILES['image']['name'];
            $tempname = $_FILES['image']['tmp_name'];
            $folder = "images/" . basename($image);
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 2 * 1024 * 1024;

            $file_type = mime_content_type($tempname);
            $file_size = $_FILES['image']['size'];

            if (!in_array($file_type, $allowed_types)) {
                $msg = "<div class='alert alert-danger'>Invalid image type. Only JPEG, PNG, or GIF allowed.</div>";
            } elseif ($file_size > $max_size) {
                $msg = "<div class='alert alert-danger'>Image size exceeds 2MB limit.</div>";
            } elseif (!move_uploaded_file($tempname, $folder)) {
                $msg = "<div class='alert alert-danger'>Failed to upload image.</div>";
            }
        }

        $sql_check = "SELECT ID FROM User WHERE Email = ?";
        $stmt = mysqli_prepare($conn, $sql_check);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            $msg = "<div class='alert alert-danger'>Email already exists.</div>";
        } else {
            $sql = "INSERT INTO User (FullName, Email, PhoneNumber, DateOfBirth, FavouriteCar, Address, Password, usertype, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sssssssss", $username, $email, $phone, $dob, $favouritecar, $address, $pass, $usertype, $image);
            if (mysqli_stmt_execute($stmt)) {
                $msg = "<div class='alert alert-success'>Registration successful! Please login.</div>";
                header("Location: Login.php");
                exit;
            } else {
                $msg = "<div class='alert alert-danger'>Error: " . mysqli_error($conn) . "</div>";
            }
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
    <title>Register - Lux Car Showroom 📝</title>
    <link rel="stylesheet" href="Luxstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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

    <div class="form-container">
        <h2>Register</h2>
        <?php echo $msg; ?>
        <form action="" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="name" class="form-label">Full Name</label>
                <input type="text" id="name" name="name" class="form-control" placeholder="Enter your full name" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
            </div>
            <div class="mb-3">
                <label for="phone" class="form-label">Phone Number</label>
                <input type="text" id="phone" name="phone" class="form-control" placeholder="Enter your phone number" required>
            </div>
            <div class="mb-3">
                <label for="dob" class="form-label">Date of Birth</label>
                <input type="date" id="dob" name="dob" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="favcar" class="form-label">Favourite Car</label>
                <input type="text" id="favcar" name="favcar" class="form-control" placeholder="Enter your favourite car" required>
            </div>
            <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <textarea id="address" name="address" class="form-control" placeholder="Enter your address" required></textarea>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
            </div>
            <div class="mb-3">
                <label for="confirm-password" class="form-label">Confirm Password</label>
                <input type="password" id="confirm-password" name="confirm-password" class="form-control" placeholder="Confirm your password" required>
            </div>
            <div class="mb-3">
                <label for="image" class="form-label">Profile Image</label>
                <input type="file" id="image" name="image" class="form-control">
            </div>
            <button type="submit" name="submit" class="btn btn-primary w-100">Register 📝</button>
        </form>
        <p class="mt-3">Already have an account? <a href="Login.php">Login here</a></p>
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
</body>
</html>
<?php ob_end_flush(); ?>
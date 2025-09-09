<?php
session_start();
include 'Conection.php';

if (!isset($_SESSION['userid'])) {
    header("Location: Login.php");
    exit();
}

$cartCount = 0;
$profileImage = "default.png";
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory - Lux Car Showroom 🚗</title>
    <link rel="stylesheet" href="Luxstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
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

    <main>
        <h2 class="animate-welcome">🚗 Inventory</h2>
        <input type="text" id="carSearch" class="form-control" placeholder="Search by car name or category" style="width: 100%; margin-bottom: 30px; font-size: 1.2rem; padding: 15px;">
        <div id="carList" style="display: flex; flex-wrap: wrap; gap: 20px; justify-content: center;">
            <?php
            $sql = "SELECT * FROM Product";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<div class='car-card' data-name='" . htmlspecialchars($row['ProductName']) . "' data-category='" . htmlspecialchars($row['Category']) . "' onclick='window.location.href=\"showmoredetails.php?id=" . $row['ID'] . "\"' style='cursor: pointer;'>";
                echo "<img src='images/" . htmlspecialchars($row['Image'] ?? 'default.png') . "' alt='Car Image' style='max-height: 200px; width: 100%; object-fit: contain;'>";
                echo "<h3>" . htmlspecialchars($row['ProductName']) . "</h3>";
                echo "<p>Price: ₹" . htmlspecialchars($row['Price']) . "</p>";
                echo "<p>Category: " . htmlspecialchars($row['Category']) . "</p>";
                echo "<a href='showmoredetails.php?id=" . $row['ID'] . "'><button style='background: #D4A017; color: #000000; padding: 12px; margin-bottom: 10px; font-size: 1.1rem; border-radius: 8px; border: none; width: 100%; cursor: pointer;'>Show More Details</button></a>";
                echo "<button onclick='bookCar(" . $row['ID'] . "); event.stopPropagation();' style='background: #A30000; color: #FFFFFF; padding: 15px; font-size: 1.2rem; border-radius: 10px; border: none; width: 100%; cursor: pointer; box-shadow: 0 4px 12px rgba(163, 0, 0, 0.4);'>Book Now</button>";
                echo "</div>";
            }
            mysqli_stmt_close($stmt);
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
    <script>
        $(document).ready(function() {
            $("#carSearch").on("input", function() {
                var value = $(this).val().toLowerCase();
                $("#carList .car-card").filter(function() {
                    $(this).toggle($(this).data("name").toLowerCase().indexOf(value) > -1 || $(this).data("category").toLowerCase().indexOf(value) > -1);
                });
            });
        });

        function bookCar(id) {
            $.ajax({
                url: 'add_to_cart.php',
                type: 'POST',
                data: { product_id: id, quantity: 1 },
                success: function(response) {
                    var result = JSON.parse(response);
                    if (result.success) {
                        $(".cart-count").text(result.cartcount);
                        alert("Car added to cart!");
                    } else {
                        alert("Error adding to cart: " + result.error);
                    }
                },
                error: function() {
                    alert("Error connecting to server.");
                }
            });
        }
    </script>
</body>
</html>
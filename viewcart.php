<?php
session_start();
include "Conection.php";

$cartCount = 0;
$profileImage = "default.png";
$cartItems = [];

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

    $sql = "SELECT c.AccessoryID, c.Quantity, a.name, a.price, a.image, a.description 
            FROM Cart c 
            JOIN Accessories a ON c.AccessoryID = a.id 
            WHERE c.SessionID = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $userid);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $cartItems[] = $row;
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - Lux Car Showroom 🛒</title>
    <link rel="stylesheet" href="Styles.css">
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
            <li><a href="ShowAccessories.php">🛠️ Accessories</a></li>
            <li><a href="Logout.php">🚪 Logout</a></li>
        </ul>
    </nav>

    <main>
        <h2 class="animate-welcome">🛒 Your Cart</h2>
        <div class="section">
            <?php if (!empty($cartItems)) { ?>
                <?php foreach ($cartItems as $item) { ?>
                    <div class="cart-card">
                        <img src="images/<?php echo htmlspecialchars($item['image'] ?? 'default.png'); ?>" alt="Item Image">
                        <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                        <p>Price: ₹<?php echo htmlspecialchars($item['price']); ?></p>
                        <p>Quantity: <?php echo htmlspecialchars($item['Quantity']); ?></p>
                        <p><?php echo htmlspecialchars($item['description']); ?></p>
                        <form class="remove-from-cart-form" method="post">
                            <input type="hidden" name="accessory_id" value="<?php echo $item['AccessoryID']; ?>">
                            <button type="submit" class="btn btn-danger">Remove</button>
                        </form>
                    </div>
                <?php } ?>
                <div class="cart-actions">
                    <a href="checkout.php" class="btn btn-primary">Proceed to Checkout</a>
                </div>
            <?php } else { ?>
                <p>No accessories in cart.</p>
            <?php } ?>
        </div>
        <div class="notification success" id="notification"></div>
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
            $(".remove-from-cart-form").on("submit", function(e) {
                e.preventDefault();
                var form = $(this);
                $.ajax({
                    url: "removecart.php",
                    type: "POST",
                    data: form.serialize(),
                    success: function(response) {
                        var result = JSON.parse(response);
                        $("#notification").text(result.message).removeClass("success error").addClass(result.type).addClass("show");
                        if (result.success) {
                            form.closest(".cart-card").remove();
                            $(".cart-count").text(result.cartcount);
                            if ($(".cart-card").length === 0) {
                                $(".section").html("<p>No accessories in cart.</p>");
                            }
                        }
                        setTimeout(() => { $("#notification").removeClass("show"); }, 3000);
                    },
                    error: function() {
                        $("#notification").text("Error connecting to server.").removeClass("success").addClass("error").addClass("show");
                        setTimeout(() => { $("#notification").removeClass("show"); }, 3000);
                    }
                });
            });
        });
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>
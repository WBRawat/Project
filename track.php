<?php
session_start();
include "Conection.php";

$cartCount = 0;
$profileImage = "default.png";
$notification = "";
$notificationType = "success";
$orders = [];

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

    $sql = "SELECT image FROM User WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $userid);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $profileImage = $row['image'] ?? 'default.png';
        $_SESSION['userimage'] = $profileImage;
    }
    mysqli_stmt_close($stmt);

    $sql = "SELECT orderid, totalamount, orderdate, status FROM Orders WHERE userid = ? ORDER BY orderdate DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $userid);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($order = mysqli_fetch_assoc($result)) {
        $order_id = $order['orderid'];
        $item_sql = "SELECT accessoryid, oi.quantity, oi.price, a.name, a.image 
                     FROM Orderitems oi 
                     JOIN Accessories a ON accessoryid = a.id 
                     WHERE oi.orderid = ?";
        $item_stmt = mysqli_prepare($conn, $item_sql);
        mysqli_stmt_bind_param($item_stmt, "i", $order_id);
        mysqli_stmt_execute($item_stmt);
        $item_result = mysqli_stmt_get_result($item_stmt);
        $items = [];
        while ($item = mysqli_fetch_assoc($item_result)) {
            $items[] = $item;
        }
        $order['items'] = $items;
        $orders[] = $order;
        mysqli_stmt_close($item_stmt);
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Orders - Lux Car Showroom 📍</title>
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
        <h2 class="animate-welcome">📍 Track Your Orders</h2>
        <div class="section">
            <?php if (isset($_SESSION['userid']) && !empty($orders)) : ?>
                <?php foreach ($orders as $order) : ?>
                    <div class="order-card">
                        <h3>Order ID: <?php echo htmlspecialchars($order['orderid']); ?></h3>
                        <p><strong>Date:</strong> <?php echo htmlspecialchars($order['orderdate']); ?></p>
                        <p><strong>Total Amount:</strong> ₹<?php echo htmlspecialchars($order['totalamount']); ?></p>
                        <p><strong>Status:</strong> <?php echo htmlspecialchars($order['status']); ?></p>
                        <h4>Items:</h4>
                        <ul>
                            <?php foreach ($order['items'] as $item) : ?>
                                <li>
                                    <img src="images/<?php echo htmlspecialchars($item['image'] ?? 'default.png'); ?>" alt="Item Image" style="width: 50px; height: 50px;">
                                    <?php echo htmlspecialchars($item['name']); ?> - ₹<?php echo htmlspecialchars($item['price']); ?> x <?php echo htmlspecialchars($item['quantity']); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            <?php elseif (isset($_SESSION['userid'])) : ?>
                <p>No orders found.</p>
            <?php else : ?>
                <p>Please log in to track your orders. 🔑</p>
            <?php endif; ?>
            <div class="back-to-products">
                <a href="Index.php" class="btn btn-primary">🏠 Back to Products</a>
            </div>
        </div>
        <div class="notification <?php echo $notificationType; ?>" id="notification"><?php echo htmlspecialchars($notification); ?></div>
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
</body>
</html>
<?php mysqli_close($conn); ?>
<?php
// Enable error logging, suppress display
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log'); // Adjust path as needed

ob_start(); // Start output buffering
session_start();
include "Conection.php"; // Corrected spelling from 'Conection.php'

// Initialize variables
$cartCount = 0;
$profileImage = isset($_SESSION['userimage']) ? $_SESSION['userimage'] : 'profile.jpg';
$notification = "";
$notificationType = "success";

// Calculate cart count from database
if (isset($_SESSION['userid'])) {
    $userid = $_SESSION['userid'];
    $sql = "SELECT SUM(Quantity) AS total FROM Cart WHERE SessionID = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt === false) {
        error_log("Cart count prepare failed: " . mysqli_error($conn));
        $notification = "Database error.";
        $notificationType = "error";
    } else {
        mysqli_stmt_bind_param($stmt, "s", $userid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $cartCount = $row['total'] ?? 0;
        }
        mysqli_stmt_close($stmt);
    }
}

// Handle Add to Cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    header('Content-Type: application/json; charset=utf-8');
    ob_end_clean(); // Clear buffered output
    $response = ['success' => false, 'message' => '', 'type' => 'error', 'cartcount' => $cartCount];

    if (!isset($_SESSION['userid'])) {
        $response['message'] = "Please log in to add items to the cart.";
        echo json_encode($response);
        exit;
    }

    $userid = $_SESSION['userid'];
    $accessory_id = (int)$_POST['id'];

    if ($accessory_id <= 0) {
        $response['message'] = "Invalid accessory ID.";
        echo json_encode($response);
        exit;
    }

    // Check if accessory exists
    $sql = "SELECT id FROM accessories WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt === false) {
        $response['message'] = "Database error: " . mysqli_error($conn);
        error_log("Accessory check prepare failed: " . mysqli_error($conn));
        echo json_encode($response);
        exit;
    }
    mysqli_stmt_bind_param($stmt, "i", $accessory_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($result) === 0) {
        $response['message'] = "Accessory not found.";
        echo json_encode($response);
        exit;
    }
    mysqli_stmt_close($stmt);

    // Check if item is already in cart
    $sql = "SELECT Quantity FROM Cart WHERE SessionID = ? AND AccessoryID = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt === false) {
        $response['message'] = "Database error: " . mysqli_error($conn);
        error_log("Cart check prepare failed: " . mysqli_error($conn));
        echo json_encode($response);
        exit;
    }
    mysqli_stmt_bind_param($stmt, "si", $userid, $accessory_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $new_quantity = $row['Quantity'] + 1;
        $sql = "UPDATE Cart SET Quantity = ? WHERE SessionID = ? AND AccessoryID = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt === false) {
            $response['message'] = "Database error: " . mysqli_error($conn);
            error_log("Cart update prepare failed: " . mysqli_error($conn));
            echo json_encode($response);
            exit;
        }
        mysqli_stmt_bind_param($stmt, "isi", $new_quantity, $userid, $accessory_id);
    } else {
        $sql = "INSERT INTO Cart (SessionID, AccessoryID, Quantity) VALUES (?, ?, 1)";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt === false) {
            $response['message'] = "Database error: " . mysqli_error($conn);
            error_log("Cart insert prepare failed: " . mysqli_error($conn));
            echo json_encode($response);
            exit;
        }
        mysqli_stmt_bind_param($stmt, "si", $userid, $accessory_id);
    }

    if (mysqli_stmt_execute($stmt)) {
        // Update cart count
        $sql = "SELECT SUM(Quantity) AS total FROM Cart WHERE SessionID = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt === false) {
            $response['message'] = "Database error: " . mysqli_error($conn);
            error_log("Cart count update prepare failed: " . mysqli_error($conn));
            echo json_encode($response);
            exit;
        }
        mysqli_stmt_bind_param($stmt, "s", $userid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $cartCount = $row['total'] ?? 0;
        }
        $response['success'] = true;
        $response['message'] = "Item added to cart!";
        $response['type'] = "success";
        $response['cartcount'] = $cartCount;
    } else {
        $response['message'] = "Error adding to cart: " . mysqli_error($conn);
        error_log("Cart execute failed: " . mysqli_error($conn));
    }
    mysqli_stmt_close($stmt);
    echo json_encode($response);
    exit;
}

// Fetch accessories
$sql = "SELECT id, name, price, image, description, carid FROM accessories";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt === false) {
    $notification = "Database error: " . mysqli_error($conn);
    $notificationType = "error";
    error_log("Accessories fetch prepare failed: " . mysqli_error($conn));
} else {
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
}
ob_end_clean(); // Clear buffer before HTML output
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accessories - Lux Car Showroom 🛠️</title>
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
                <span class="cart-count" id="cart-count"><?php echo $cartCount; ?></span>
            </a>
            <a href="Login.php" title="Login">🔑</a>
            <a href="Register.php" title="Register">📝</a>
            <img src="images/<?php echo htmlspecialchars($profileImage); ?>" alt="Profile" class="profile-icon" />
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

    <main>
        <h2 class="animate-welcome" style="color: #D4A017; font-family: 'Playfair Display', serif; font-size: 2rem; text-align: center; margin-bottom: 30px; text-shadow: 0 0 8px rgba(212, 160, 23, 0.3);">🛠️ Available Accessories</h2>
        <input type="text" id="accessorySearch" class="form-control" placeholder="Search by accessory name or car ID" style="width: 100%; margin-bottom: 30px; font-size: 1.2rem; padding: 15px; border: 2px solid #D4A017; border-radius: 10px; background: #000000; color: #FFFFFF;">
        <div class="section" style="background: #1A1A1A; padding: 40px; border-radius: 15px; box-shadow: 0 6px 15px rgba(0, 0, 0, 0.4);">
            <div id="accessoryList" class="car-image-preview" style="display: flex; flex-wrap: wrap; gap: 20px; justify-content: center;">
                <?php if ($result && $result->num_rows > 0) { ?>
                    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                        <div class="accessory-card" data-name="<?php echo htmlspecialchars($row['name']); ?>" data-carid="<?php echo htmlspecialchars($row['carid']); ?>" style="background: #1A1A1A; padding: 25px; border-radius: 15px; box-shadow: 0 6px 15px rgba(0, 0, 0, 0.4); width: 300px; text-align: center; transition: transform 0.3s ease, box-shadow 0.3s ease;" onmouseover="this.style.transform='translateY(-8px)'; this.style.boxShadow='0 8px 20px rgba(212, 160, 23, 0.3)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 6px 15px rgba(0, 0, 0, 0.4)';">
                            <img src="images/<?php echo htmlspecialchars($row['image'] ?? 'default.png'); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" style="width: 100%; max-height: 200px; object-fit: contain; border-radius: 10px; margin-bottom: 15px;">
                            <h3 style="color: #FFFFFF; font-family: 'Roboto', sans-serif; font-size: 1.4rem; margin-bottom: 10px;"><?php echo htmlspecialchars($row['name']); ?></h3>
                            <p style="color: #FFFFFF; font-size: 1rem; margin-bottom: 10px;"><?php echo htmlspecialchars($row['description']); ?></p>
                            <p style="color: #D4A017; font-size: 1.2rem; font-weight: 600; margin-bottom: 15px;">₹<?php echo number_format($row['price'], 2); ?></p>
                            <form class="add-to-cart-form" method="post" action="ShowAccessories.php">
                                <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                                <button type="submit" class="add-to-cart-btn" style="background: #A30000; color: #FFFFFF; padding: 15px; font-size: 1.2rem; border-radius: 10px; border: none; width: 100%; cursor: pointer; box-shadow: 0 4px 12px rgba(163, 0, 0, 0.4); transition: background 0.3s ease, transform 0.2s ease;" onmouseover="this.style.background='#800000'; this.style.transform='translateY(-3px)';" onmouseout="this.style.background='#A30000'; this.style.transform='translateY(0)';">🛒 Add to Cart</button>
                            </form>
                        </div>
                    <?php } ?>
                <?php } else { ?>
                    <p style="color: #FFFFFF; text-align: center; font-size: 1.2rem;">No accessories available.</p>
                <?php } ?>
            </div>
        </div>
        <div class="notification <?php echo $notificationType; ?>" id="notification" style="<?php echo $notification ? 'display: block;' : ''; ?>"><?php echo htmlspecialchars($notification); ?></div>
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
            <a href="Admin.php">🛠️ Admin</a>
            <a href="Login.php">🔑 Login</a>
            <a href="Register.php">📝 Register</a>
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
            $("#accessorySearch").on("input", function() {
                var value = $(this).val().toLowerCase();
                $("#accessoryList .accessory-card").filter(function() {
                    $(this).toggle($(this).data("name").toLowerCase().indexOf(value) > -1 || $(this).data("carid").toString().indexOf(value) > -1);
                });
            });

            $(".add-to-cart-form").on("submit", function(e) {
                e.preventDefault();
                var form = $(this);
                $.ajax({
                    url: form.attr("action"),
                    type: "POST",
                    data: form.serialize(),
                    dataType: "json",
                    success: function(response) {
                        $("#notification").text(response.message).removeClass("success error").addClass(response.type).addClass("show");
                        if (response.success) {
                            $("#cart-count").text(response.cartcount);
                        }
                        setTimeout(() => { $("#notification").removeClass("show"); }, 3000);
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX error:", xhr.responseText);
                        $("#notification").text("Error adding to cart: " + error).removeClass("success").addClass("error").addClass("show");
                        setTimeout(() => { $("#notification").removeClass("show"); }, 3000);
                    }
                });
            });

            // Show notification if present on page load
            if ($("#notification").text().trim() !== "") {
                $("#notification").addClass("show");
                setTimeout(() => { $("#notification").removeClass("show"); }, 3000);
            }
        });
    </script>
</body>
</html>
<?php
if (isset($stmt)) {
    mysqli_stmt_close($stmt);
}
mysqli_close($conn);
?>
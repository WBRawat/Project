<?php
session_start();
require_once 'Conection.php'; // Corrected from Conection.php

$userid = isset($_SESSION['userid']) ? $_SESSION['userid'] : session_id();
$notification = '';
$notificationType = 'success';
$cartCount = 0;
$totalCartPrice = 0;
$showCart = true;
$showPlaceOrder = false;
$showThankYou = false;
$orderItems = [];

// Fetch profile image from users table
$profileImage = 'default.png';
if (isset($_SESSION['userid'])) {
    $sql = "SELECT image FROM User WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $userid);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $profileImage = $row['image'] ?? 'default.png';
        $_SESSION['userimage'] = $profileImage; // Update session for consistency
    }
    mysqli_stmt_close($stmt);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['userid'])) {
        $notification = 'Please log in to modify or place an order.';
        $notificationType = 'error';
    } else {
        $userid = $_SESSION['userid'];

        // Update quantity
        if (isset($_POST['update_quantity']) && isset($_POST['accessory_id']) && isset($_POST['quantity'])) {
            $accessory_id = (int)$_POST['accessory_id'];
            $quantity = (int)$_POST['quantity'];

            if ($accessory_id > 0 && $quantity >= 0) {
                if ($quantity == 0) {
                    $sql = "DELETE FROM Cart WHERE SessionID = ? AND AccessoryID = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "si", $userid, $accessory_id);
                } else {
                    $sql = "UPDATE Cart SET Quantity = ? WHERE SessionID = ? AND AccessoryID = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "isi", $quantity, $userid, $accessory_id);
                }

                if (mysqli_stmt_execute($stmt)) {
                    $notification = 'Cart updated successfully.';
                } else {
                    $notification = 'Failed to update cart: ' . mysqli_error($conn);
                    $notificationType = 'error';
                    error_log("Cart update error: " . mysqli_error($conn));
                }
                mysqli_stmt_close($stmt);
            } else {
                $notification = 'Invalid accessory or quantity.';
                $notificationType = 'error';
            }
        }
        // Remove item
        elseif (isset($_POST['remove_item']) && isset($_POST['accessory_id'])) {
            $accessory_id = (int)$_POST['accessory_id'];
            if ($accessory_id > 0) {
                $sql = "DELETE FROM Cart WHERE SessionID = ? AND AccessoryID = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "si", $userid, $accessory_id);
                if (mysqli_stmt_execute($stmt)) {
                    $notification = 'Item removed from cart.';
                } else {
                    $notification = 'Failed to remove item: ' . mysqli_error($conn);
                    $notificationType = 'error';
                    error_log("Cart remove error: " . mysqli_error($conn));
                }
                mysqli_stmt_close($stmt);
            } else {
                $notification = 'Invalid accessory ID.';
                $notificationType = 'error';
            }
        }
        // Initiate order
        elseif (isset($_POST['buy'])) {
            // Fetch cart items for confirmation
            $query = "SELECT c.AccessoryID, c.Quantity, c.AddedOnDATETIME, a.name, a.price, a.image 
                      FROM Cart c 
                      JOIN Accessories a ON c.AccessoryID = a.id 
                      WHERE c.SessionID = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $userid);
            $stmt->execute();
            $result = $stmt->get_result();

            $orderItems = [];
            $orderTotal = 0;
            while ($item = $result->fetch_assoc()) {
                $orderItems[] = $item;
                $orderTotal += $item['Quantity'] * $item['price'];
            }
            $stmt->close();

            if (empty($orderItems)) {
                $notification = 'Your cart is empty. Cannot place order.';
                $notificationType = 'error';
            } else {
                $showCart = false;
                $showPlaceOrder = true;
                $totalCartPrice = $orderTotal;
                $notification = 'Please confirm your order.';
            }
        }
        // Confirm order
        elseif (isset($_POST['place_order'])) {
            // Fetch cart items to place order
            $query = "SELECT c.AccessoryID, c.Quantity, a.price, a.name, a.image 
                      FROM Cart c 
                      JOIN Accessories a ON c.AccessoryID = a.id 
                      WHERE c.SessionID = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $userid);
            $stmt->execute();
            $result = $stmt->get_result();

            $orderItems = [];
            $orderTotal = 0;
            while ($item = $result->fetch_assoc()) {
                $orderItems[] = $item;
                $orderTotal += $item['Quantity'] * $item['price'];
            }
            $stmt->close();

            if (empty($orderItems)) {
                $notification = 'Your cart is empty. Cannot place order.';
                $notificationType = 'error';
                $showCart = true;
                $showPlaceOrder = false;
            } else {
                // Insert order
                $sql = "INSERT INTO Orders (userid, totalamount, orderdate, status) VALUES (?, ?, NOW(), 'Placed')";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sd", $userid, $orderTotal);
                if (mysqli_stmt_execute($stmt)) {
                    $orderid = mysqli_insert_id($conn);

                    // Insert order items
                    $sql = "INSERT INTO Orderitems (orderid, accessoryid, quantity, price) VALUES (?, ?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $sql);
                    $success = true;
                    foreach ($orderItems as $item) {
                        mysqli_stmt_bind_param($stmt, "iiid", $order_id, $item['AccessoryID'], $item['Quantity'], $item['price']);
                        if (!mysqli_stmt_execute($stmt)) {
                            $success = false;
                            error_log("Order item insert error: " . mysqli_error($conn));
                            break;
                        }
                    }

                    if ($success) {
                        // Clear cart
                        $sql = "DELETE FROM Cart WHERE SessionID = ?";
                        $stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param($stmt, "s", $userid);
                        mysqli_stmt_execute($stmt);

                        // Set thank you state
                        $showCart = false;
                        $showPlaceOrder = false;
                        $showThankYou = true;
                        $totalCartPrice = $orderTotal;
                        $cartCount = 0;
                        $notification = 'Order placed successfully!';
                    } else {
                        $notification = 'Failed to save order items: ' . mysqli_error($conn);
                        $notificationType = 'error';
                        $showCart = true;
                        $showPlaceOrder = false;
                    }
                } else {
                    $notification = 'Failed to place order: ' . mysqli_error($conn);
                    $notificationType = 'error';
                    $showCart = true;
                    $showPlaceOrder = false;
                    error_log("Order insert error: " . mysqli_error($conn));
                }
                mysqli_stmt_close($stmt);
            }
        }
        // Cancel order
        elseif (isset($_POST['cancel_order'])) {
            $showCart = true;
            $showPlaceOrder = false;
            $notification = 'Order cancelled.';
        }
    }
    error_log("Notification set: $notification, Type: $notificationType");
}

// Fetch cart items if showing cart
if ($showCart) {
    $query = "SELECT c.AccessoryID, c.Quantity, c.AddedOnDATETIME, a.name, a.price, a.image 
              FROM Cart c 
              JOIN Accessories a ON c.AccessoryID = a.id 
              WHERE c.SessionID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $userid);
    $stmt->execute();
    $result = $stmt->get_result();

    $cartItems = [];
    while ($item = $result->fetch_assoc()) {
        $cartItems[] = $item;
        $cartCount += $item['Quantity'];
        $totalCartPrice += $item['Quantity'] * $item['price'];
    }
    $stmt->close();

    error_log("Cart items fetched: " . count($cartItems) . ", Cart count: $cartCount, UserID: $userid");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lux Car Showroom - Cart Items</title>
    <link rel="stylesheet" href="Styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            position: relative;
        }

        .cart-items, .order-items {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .cart-item, .order-item {
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 15px;
            width: 300px;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .cart-item img, .order-item img {
            width: 150px;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 10px;
        }

        .cart-item p, .order-item p {
            margin: 5px 0;
        }

        .btn {
            padding: 8px 16px;
            background: black;
            color: yellow;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            margin: 5px;
            transition: background 0.3s;
        }

        .btn:hover {
            background: #333;
        }

        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .quantity-input {
            width: 60px;
            padding: 5px;
            margin: 5px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }

        .cart-icon {
            position: relative;
            display: inline-block;
            margin: 0 10px;
        }

        .cart-icon a {
            text-decoration: none;
            color: black;
            font-size: 24px;
        }

        .cart-count {
            position: absolute;
            top: -10px;
            right: -10px;
            background: red;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
        }

        .notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: <?php echo $notificationType === 'success' ? '#28a745' : '#dc3545'; ?>;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            display: block;
            opacity: <?php echo $notification ? '1' : '0'; ?>;
            transition: opacity 0.3s;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        .notification:empty {
            display: none;
        }

        .total-price {
            margin-top: 20px;
            font-size: 1.2em;
            font-weight: bold;
            text-align: right;
        }

        .back-to-products {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .place-order-container, .thank-you-container {
            text-align: center;
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="logo-nav">
            <img src="logo.jpeg" alt="Logo" />
        </div>
        <h1 class="glow-text">🚘 Lux Car Showroom</h1>
        <div class="nav-right">
            <div class="cart-icon" id="carticon">
                <a href="cartitem.php" title="Cart">🛒
                    <span class="cart-count" id="cartcount"><?php echo $cartCount; ?></span>
                </a>
            </div>
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
            <li><a href="Admin.php">🛠️ Admin</a></li>
            <li><a href="AboutUs.php">📄 About</a></li>
            <li><a href="Contact.php">📞 Contact</a></li>
            <li><a href="track.php">📦 Track Orders</a></li>
        </ul>
    </nav>

    <div class="container">
        <?php if ($showCart): ?>
            <h2>Cart Items</h2>
            <?php if (count($cartItems) > 0): ?>
                <div class="cart-items">
                    <?php foreach ($cartItems as $item): ?>
                        <div class="cart-item">
                            <img src="images/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p>Accessory ID: <?php echo $item['AccessoryID']; ?></p>
                            <p>Price: ₹<?php echo number_format($item['price'], 2); ?></p>
                            <p>Total: ₹<?php echo number_format($item['Quantity'] * $item['price'], 2); ?></p>
                            <p>Added On: <?php echo htmlspecialchars($item['AddedOnDATETIME']); ?></p>
                            <form method="POST" action="">
                                <input type="hidden" name="accessory_id" value="<?php echo $item['AccessoryID']; ?>">
                                <label>Quantity: 
                                    <input type="number" name="quantity" class="quantity-input" value="<?php echo $item['Quantity']; ?>" min="0">
                                </label>
                                <button type="submit" name="update_quantity" class="btn">Update</button>
                                <button type="submit" name="remove_item" class="btn" style="background: #dc3545;">Remove</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="total-price">
                    Total Cart Price: ₹<?php echo number_format($totalCartPrice, 2); ?>
                </div>
                <form method="POST" action="">
                    <div class="buy-button">
                        <button type="submit" name="buy" class="btn">Buy</button>
                    </div>
                </form>
                <div class="back-to-products">
                    <a href="index.php" class="btn">Back to Products</a>
                    <a href="track.php" class="btn" <?php echo isset($_SESSION['userid']) ? '' : 'disabled onclick="return false;"'; ?>>Track Orders</a>
                </div>
            <?php else: ?>
                <p>Your cart is empty.</p>
                <div class="back-to-products">
                    <a href="index.php" class="btn">Back to Products</a>
                    <a href="track.php" class="btn" <?php echo isset($_SESSION['userid']) ? '' : 'disabled onclick="return false;"'; ?>>Track Orders</a>
                </div>
            <?php endif; ?>
        <?php elseif ($showPlaceOrder): ?>
            <div class="place-order-container">
                <h2>Confirm Your Order</h2>
                <div class="order-items">
                    <?php foreach ($orderItems as $item): ?>
                        <div class="order-item">
                            <img src="images/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p>Accessory ID: <?php echo $item['AccessoryID']; ?></p>
                            <p>Price: ₹<?php echo number_format($item['price'], 2); ?></p>
                            <p>Quantity: <?php echo $item['Quantity']; ?></p>
                            <p>Total: ₹<?php echo number_format($item['Quantity'] * $item['price'], 2); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="total-price">
                    Total Order Amount: ₹<?php echo number_format($totalCartPrice, 2); ?>
                </div>
                <form method="POST" action="">
                    <button type="submit" name="place_order" class="btn">Place Order</button>
                    <button type="submit" name="cancel_order" class="btn" style="background: #dc3545;">Cancel</button>
                </form>
                <div class="back-to-products">
                    <a href="index.php" class="btn">Back to Products</a>
                    <a href="track.php" class="btn" <?php echo isset($_SESSION['userid']) ? '' : 'disabled onclick="return false;"'; ?>>Track Orders</a>
                </div>
            </div>
        <?php elseif ($showThankYou): ?>
            <div class="thank-you-container">
                <h2>Thank You!</h2>
                <p>Your order has been placed successfully.</p>
                <div class="order-items">
                    <?php foreach ($orderItems as $item): ?>
                        <div class="order-item">
                            <img src="images/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p>Accessory ID: <?php echo $item['AccessoryID']; ?></p>
                            <p>Price: ₹<?php echo number_format($item['price'], 2); ?></p>
                            <p>Quantity: <?php echo $item['Quantity']; ?></p>
                            <p>Total: ₹<?php echo number_format($item['Quantity'] * $item['price'], 2); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="total-price">
                    Total Order Amount: ₹<?php echo number_format($totalCartPrice, 2); ?>
                </div>
                <div class="back-to-products">
                    <a href="index.php" class="btn">Back to Products</a>
                    <a href="track.php" class="btn" <?php echo isset($_SESSION['userid']) ? '' : 'disabled onclick="return false;"'; ?>>Track Orders</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="notification" id="notification"><?php echo htmlspecialchars($notification); ?></div>

    <footer>
        <div class="footer-section">
            <h3>About Us</h3>
            <p>Luxury Car Showroom brings you the best and most exclusive cars worldwide.</p>
        </div>
        <div class="footer-section footer-links">
            <h3>Quick Links</h3>
            <a href="Index.php">Home</a>
            <a href="Inventory.php">Inventory</a>
            <a href="AboutUs.php">About</a>
            <a href="Contact.php">Contact</a>
            <a href="track.php">Track Orders</a>
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
            <p>© 2025 Lux Car Showroom | Designed by Nitin Rawat</p>
        </div>
    </footer>
</body>
</html>
<?php mysqli_close($conn); ?>
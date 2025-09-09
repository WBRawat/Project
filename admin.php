<?php
session_start();
include "Conection.php"; // Corrected

$cartCount = 0;
$profileImage = "default.png"; 

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
} else {
    header("location: Login.php"); // Fixed typo
    exit;
}

$sql = "SELECT * FROM user WHERE ID = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $userid);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$rowwell = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Lux Car Showroom</title>
    <link rel="stylesheet" href="Luxstyle.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <style>
        .user-table, .product-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            text-align: center;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .user-table th, .user-table td,
        .product-table th, .product-table td {
            border: 1px solid #ccc;
            padding: 12px;
        }

        .user-table th, .product-table th {
            background-color: #222;
            color: white;
        }

        .edit-btn {
            background-color: red;
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .edit-btn:hover {
            background-color: darkred;
        }

        .add-product-container {
            text-align: right;
            margin: 20px;
        }

        .add-product-btn {
            background-color: red;
            color: white;
            padding: 10px 20px;
            border: none;
            font-weight: bold;
            font-size: 16px;
            border-radius: 6px;
            cursor: pointer;
        }

        .add-product-btn:hover {
            background-color: darkred;
        }

        .search-box {
            width: 100%;
            padding: 10px;
            margin: 20px 0;
            font-size: 16px;
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
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            display: none;
            z-index: 1000;
        }

        .notification.show {
            display: block;
            animation: fadeInOut 2s;
        }

        @keyframes fadeInOut {
            0% { opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { opacity: 0; }
        }

        .car-image-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .car-image-preview div {
            border: 1px solid #ddd;
            padding: 15px;
            text-align: center;
        }

        .car-image-preview button {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px;
            cursor: pointer;
        }

        .car-image-preview button:hover {
            background: #218838;
        }




        
    </style>

    <header class="site-header">
        <div class="logo-nav">
            <img src="logo.jpeg" alt="Lux Car Showroom Logo" />
        </div>
        <h1 class="glow-text">🚘 Lux Car Showroom</h1>
        <div class="nav-right">
            <div class="cart-icon" id="carticon">
                <a href="cartitem.php" title="Cart">🛒
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count" id="cartcount"><?php echo $cartCount; ?></span>
                </a>
            </div>
            <a href="Login.php" title="Login">🔑</a>
            <a href="Register.php" title="Register">📝</a>
            <img src="images/<?php echo htmlspecialchars($profileImage); ?>" alt="Profile Icon" class="profile-icon" />
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
        </ul>
    </nav>

    <main>
        <input type="text" class="search-box" placeholder="Search users or products..." id="searchInput">
        <script>
            $(document).ready(function(){
                $('#searchInput').on('keyup', function(){
                    let value = $(this).val().toLowerCase();
                    $('.user-table tr, .product-table tr').filter(function(){
                        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                    });
                });
            });
        </script>

        <?php
        $sql = "SELECT * FROM user";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            echo "<table class='user-table'>";
            echo "<tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Image</th>
                    <th>Email</th>
                    <th>Phone Number</th>
                    <th>Date Of Birth</th>
                    <th>Favourite Car</th>
                    <th>Address</th>
                    <th>Action</th>
                  </tr>";

            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>
                        <td>" . htmlspecialchars($row['ID']) . "</td>
                        <td>" . htmlspecialchars($row['FullName']) . "</td>
                        <td><a href='profile.php?id=" . (int)$row['ID'] . "'><img src='images/" . htmlspecialchars($row['Image']) . "' width='100' height='100' alt='User Image'></a></td>
                        <td>" . htmlspecialchars($row['Email']) . "</td>
                        <td>" . htmlspecialchars($row['PhoneNumber']) . "</td>
                        <td>" . htmlspecialchars($row['DateOfBirth']) . "</td>
                        <td>" . htmlspecialchars($row['FavouriteCar']) . "</td>
                        <td>" . htmlspecialchars($row['Address']) . "</td>
                        <td><a href='edituser.php?id=" . (int)$row['ID'] . "'><button class='edit-btn'>Edit</button></a></td>
                      </tr>";
            }
            echo "</table>";
        } else {
            echo "No user data found.";
        }
        mysqli_stmt_close($stmt);

        // Products
        $sql = "SELECT ID, ProductName, Price, Image, Category, Stock, Description, Status FROM Product";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            echo "<table class='product-table'>";
            echo "<tr>
                    <th>ID</th>
                    <th>ProductName</th>
                    <th>Price</th>
                    <th>Image</th>
                    <th>Category</th>
                    <th>Stock</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>";

            while ($row = mysqli_fetch_assoc($result)) {
                $clean_price = str_replace(',', '', $row['Price']);
                $price = is_numeric($clean_price) ? (float) $clean_price : 0.0;
                echo "<tr>
                        <td>" . (int)$row['ID'] . "</td>
                        <td>" . htmlspecialchars($row['ProductName']) . "</td>
                        <td>" . ($price > 0 ? number_format($price, 2) : 'N/A') . "</td>
                        <td><a href='productprofile.php?id=" . (int)$row['ID'] . "'><img src='images/" . htmlspecialchars($row['Image']) . "' width='100' height='100' alt='Product Image'></a></td>
                        <td>" . htmlspecialchars($row['Category']) . "</td>
                        <td>" . (int)$row['Stock'] . "</td>
                        <td>" . htmlspecialchars($row['Description']) . "</td>
                        <td>" . htmlspecialchars($row['Status']) . "</td>
                        <td><a href='edit.php?id=" . (int)$row['ID'] . "'><button class='edit-btn'>Edit</button></a></td>
                      </tr>";
            }
            echo "</table>";
        } else {
            echo "No product data found.";
        }
        mysqli_stmt_close($stmt);
        ?>

        <div class="add-product-container">
            <a href="product.php"><button class="add-product-btn">➕ Add Product</button></a>
        </div>
    </main>

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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="script.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>
<?php
include 'conection.php';

if (isset($_POST['query'])) {
    $search = $conn->real_escape_string($_POST['query']);
    $sql = "SELECT ID, FullName, Email FROM User WHERE FullName LIKE '%$search%' || PhoneNumber LIKE '%$search%' LIMIT 10";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo "<a href= profile.php?id=" . $row['ID'] . "><div class='user'><strong>" . $row['FullName'] . "</strong><br>" . $row['Email'] . "</div></a>";
        }
    } else {
        echo "<div>No users found.</div>";
    }
}
?>

<!----------------------------------------------------------------------------------------------------------------------------------------------->


<?php
include 'conection.php';

if (isset($_POST['query'])) {
    $search = $conn->real_escape_string($_POST['query']);
    $sql = "SELECT ID, ProductName, Price FROM Product WHERE ProductName LIKE '%$search%' || Category LIKE '%$search%' LIMIT 10";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo "<a href= productprofile.php?id=" . $row['ID'] . "><div class='user'><strong>" . $row['ProductName'] . "</strong><br>" . $row['Price'] . "</div></a>";
        }
    } else {
        echo "<div>No Product found.</div>";
    }
}
?>
<?php
include 'db.php'; // Database connection

// Check if form data is posted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $name = $_POST['product_name']; // Use 'product_name' from the form
    $category = $_POST['category'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];

    // Insert data into the database using prepared statements (to prevent SQL injection)
    $sql = "INSERT INTO products (name, category, price, stock) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssdi', $name, $category, $price, $stock); // 'ssdi' means: string, string, decimal, integer

    if ($stmt->execute()) {
        echo "✅ Product added successfully! <a href='inventory.php'>View Inventory</a>";
    } else {
        echo "❌ Failed to add product: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>

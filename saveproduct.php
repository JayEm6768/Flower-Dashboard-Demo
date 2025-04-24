<?php
require 'db.php'; // Ensure $conn is correctly set up here

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Collect form data with safe fallbacks
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);
    $size = trim($_POST['size'] ?? '');
    $color = trim($_POST['color'] ?? '');
    $available = intval($_POST['available'] ?? 0);
    $image_url = trim($_POST['image_url'] ?? '');

    // Basic validation
    if (
        empty($name) || empty($price) || $price < 0 ||
        $quantity < 0 || empty($size) || empty($color) || !is_numeric($available)
    ) {
        $error_message = urlencode("Invalid input. Please check all required fields.");
        header("Location: add_product.php?status=error&message=$error_message");
        exit;
    }

    // Prepare SQL statement (flower_id auto-increments)
    $stmt = $conn->prepare("INSERT INTO product (name, description, price, quantity, size, color, available, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    if ($stmt) {
        $stmt->bind_param("ssdissis", $name, $description, $price, $quantity, $size, $color, $available, $image_url);

        if ($stmt->execute()) {
            $success_message = urlencode("Product '$name' added successfully!");
            header("Location: add_product.php?status=success&message=$success_message");
        } else {
            $error_message = urlencode("Failed to add product. Please try again.");
            header("Location: add_product.php?status=error&message=$error_message");
        }

        $stmt->close();
    } else {
        $error_message = urlencode("Database error. Please contact support.");
        header("Location: add_product.php?status=error&message=$error_message");
    }

    $conn->close();
} else {
    header("Location: add_product.php");
    exit;
}

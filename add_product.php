<?php
include 'db.php'; // Database connection

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_name = $_POST['product_name'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $category = $_POST['category'];

    // Insert new product into the database
    $query = "INSERT INTO products (product_name, price, stock, category) VALUES ('$product_name', '$price', '$stock', '$category')";
    if (mysqli_query($conn, $query)) {
        echo "<div class='success'>Product added successfully! <a href='inventory.php'>View Inventory</a></div>";
    } else {
        echo "<div class='error'>Error adding product: " . mysqli_error($conn) . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Product - Flower Shop</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
        }

        h1 {
            text-align: center;
            color: #333;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
            color: #555;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: #4CAF50;
        }

        .form-group input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }

        .form-group input[type="submit"]:hover {
            background-color: #45a049;
        }

        .success {
            color: #28a745;
            text-align: center;
            font-size: 18px;
            margin-top: 20px;
        }

        .error {
            color: #dc3545;
            text-align: center;
            font-size: 18px;
            margin-top: 20px;
        }

        .button {
            text-align: center;
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            font-size: 16px;
        }

        .button:hover {
            background-color: #0056b3;
        }

        a {
            text-decoration: none;
            color: inherit;
        }
    </style>
</head>
<body>

    <div class="container">
        <h1>🌸 Add New Product</h1>

        <!-- Product Add Form -->
        <form action="add_product.php" method="POST">
            <div class="form-group">
                <label for="product_name">Product Name:</label>
                <input type="text" id="product_name" name="product_name" required>
            </div>

            <div class="form-group">
                <label for="category">Category:</label>
                <input type="text" id="category" name="category" required>
            </div>

            <div class="form-group">
                <label for="price">Price (₱):</label>
                <input type="number" id="price" name="price" step="0.01" required>
            </div>

            <div class="form-group">
                <label for="stock">Stock:</label>
                <input type="number" id="stock" name="stock" required>
            </div>

            <div class="form-group">
                <input type="submit" value="Add Product">
            </div>
        </form>

        <!-- Success or Error message -->
        <?php if (isset($message)) { echo $message; } ?>
    </div>

</body>
</html>

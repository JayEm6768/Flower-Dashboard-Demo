<?php
// No need to include db.php here since saveproduct.php handles DB logic
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Product - Flower Shop</title>
    <style>
        :root {
            --primary: #4CAF50;
            --primary-hover: #45a049;
            --secondary: #007bff;
            --secondary-hover: #0056b3;
            --error-color: #dc3545;
            --success-color: #28a745;
            --background: #f4f6f9;
            --card-bg: #ffffff;
            --text-color: #333;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: var(--background);
            margin: 0;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            color: var(--text-color);
        }

        .container {
            background-color: var(--card-bg);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 600px;
        }

        h1 {
            text-align: center;
            margin-bottom: 25px;
            font-size: 26px;
            color: var(--primary);
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 6px;
            display: block;
            color: #555;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--primary);
            outline: none;
        }

        .form-group input[type="submit"] {
            background-color: var(--primary);
            color: white;
            border: none;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .form-group input[type="submit"]:hover {
            background-color: var(--primary-hover);
        }

        .success, .error {
            text-align: center;
            font-size: 16px;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .success {
            background-color: #eafaf1;
            color: var(--success-color);
            border: 1px solid var(--success-color);
        }

        .error {
            background-color: #fdeaea;
            color: var(--error-color);
            border: 1px solid var(--error-color);
        }

        .button {
            display: inline-block;
            text-align: center;
            background-color: var(--secondary);
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 16px;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }

        .button:hover {
            background-color: var(--secondary-hover);
        }

        .center {
            text-align: center;
            margin-top: 25px;
        }

        a {
            text-decoration: none;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>🌸 Add New Product</h1>

    <!-- Status Message -->
    <?php if (isset($_GET['status']) && isset($_GET['message'])): ?>
        <div class="<?= $_GET['status'] === 'success' ? 'success' : 'error' ?>">
            <?= htmlspecialchars($_GET['message']) ?>
        </div>
    <?php endif; ?>

    <!-- Product Add Form -->
    <form action="saveproduct.php" method="POST">
        <div class="form-group">
            <label for="name">Product Name:</label>
            <input type="text" id="name" name="name" required minlength="2" maxlength="100">
        </div>

        <div class="form-group">
            <label for="description">Description:</label>
            <input type="text" id="description" name="description" maxlength="255">
        </div>

        <div class="form-group">
            <label for="price">Price (₱):</label>
            <input type="number" id="price" name="price" step="0.01" min="0" required>
        </div>

        <div class="form-group">
            <label for="quantity">Stock Quantity:</label>
            <input type="number" id="quantity" name="quantity" min="0" required>
        </div>

        <div class="form-group">
            <label for="size">Size:</label>
            <select id="size" name="size" required>
                <option value="" disabled selected>Select size</option>
                <option value="Small">Small</option>
                <option value="Medium">Medium</option>
                <option value="Large">Large</option>
                <option value="Standard">Standard</option>
            </select>
        </div>

        <div class="form-group">
            <label for="color">Color:</label>
            <input type="text" id="color" name="color" required>
        </div>

        <div class="form-group">
            <label for="available">Available:</label>
            <select id="available" name="available" required>
                <option value="1">Yes</option>
                <option value="0">No</option>
            </select>
        </div>

        <div class="form-group">
            <label for="image_url">Image URL:</label>
            <input type="text" id="image_url" name="image_url" placeholder="e.g., rose.jpg">
        </div>

        <div class="form-group">
            <input type="submit" value="Add Product">
        </div>
    </form>

    <!-- Back to Dashboard Button -->
    <div class="center">
        <a href="dashboard.php" class="button">← Back to Dashboard</a>
    </div>
</div>

</body>
</html>

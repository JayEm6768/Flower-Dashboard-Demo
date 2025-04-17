<?php
include 'db.php';

$success = '';
$error = '';

// Handle POST submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    $sale_date = $_POST['sale_date'];

    $result = $conn->query("SELECT stock FROM products WHERE id = $product_id");
    if ($result->num_rows == 0) {
        $error = "❌ Product ID does not exist.";
    } else {
        $product = $result->fetch_assoc();
        if ($product['stock'] < $quantity) {
            $error = "❌ Not enough stock.";
        } else {
            // Insert sale and update stock
            $stmt = $conn->prepare("INSERT INTO sales (product_id, quantity, sale_date) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $product_id, $quantity, $sale_date);
            $stmt->execute();
            $conn->query("UPDATE products SET stock = stock - $quantity WHERE id = $product_id");
            $success = "✅ Sale recorded successfully!";
        }
    }
}

// Fetch product list
$products = $conn->query("SELECT id, name FROM products");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Sale</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
        }

        h2 {
            text-align: center;
            color: #333;
        }

        .message {
            text-align: center;
            font-size: 16px;
            margin-bottom: 20px;
        }

        .success {
            color: #28a745;
        }

        .error {
            color: #dc3545;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            font-size: 16px;
            margin-bottom: 5px;
            color: #333;
        }

        select, input[type="number"], input[type="date"] {
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        input[type="submit"] {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        input[type="submit"]:hover {
            background-color: #0056b3;
        }

        select:focus, input[type="number"]:focus, input[type="date"]:focus {
            outline: none;
            border-color: #007bff;
        }

    </style>
</head>
<body>

    <div class="container">
        <h2>💸 Record a Sale</h2>

        <?php if ($success): ?>
            <p class="message success"><?= $success ?></p>
        <?php elseif ($error): ?>
            <p class="message error"><?= $error ?></p>
        <?php endif; ?>

        <form method="post">
            <label for="product_id">Select Flower:</label>
            <select name="product_id" id="product_id" required>
                <?php while($row = $products->fetch_assoc()): ?>
                    <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
                <?php endwhile; ?>
            </select>

            <label for="quantity">Quantity Sold:</label>
            <input type="number" name="quantity" id="quantity" required>

            <label for="sale_date">Sale Date:</label>
            <input type="date" name="sale_date" id="sale_date" required>

            <input type="submit" value="Record Sale">
        </form>
    </div>

</body>
</html>

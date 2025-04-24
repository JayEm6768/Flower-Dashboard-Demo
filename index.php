<?php
include 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get flowers for dropdown
$flowerOptions = [];
$result = $conn->query("SELECT id, name FROM product ORDER BY name");
while ($row = $result->fetch_assoc()) {
    $flowerOptions[$row['id']] = $row['name'];
}

// Filters
$flowerFilter = $_GET['flower'] ?? '';
$fromDate = $_GET['from'] ?? '';
$toDate = $_GET['to'] ?? '';
$where = "1=1";
if ($flowerFilter !== '') $where .= " AND s.product_id = " . intval($flowerFilter);
if ($fromDate) $where .= " AND s.sale_date >= '$fromDate'";
if ($toDate) $where .= " AND s.sale_date <= '$toDate'";

// Sales query
$sql = "
    SELECT p.name, p.price, s.quantity, s.sale_date,
           (p.price * s.quantity) AS revenue
    FROM sales s
    JOIN products p ON s.product_id = p.id
    WHERE $where
    ORDER BY s.sale_date DESC
";
$result = $conn->query($sql);
$salesData = [];
$summary = [];
while ($row = $result->fetch_assoc()) {
    $salesData[] = $row;
    $summary[$row['name']]['quantity'] = ($summary[$row['name']]['quantity'] ?? 0) + $row['quantity'];
    $summary[$row['name']]['revenue'] = ($summary[$row['name']]['revenue'] ?? 0) + $row['revenue'];
}
$totalRevenue = array_sum(array_column($summary, 'revenue'));

// Monthly summary
$sql_monthly = "
    SELECT MONTH(s.sale_date) as month,
           SUM(s.quantity) as total
    FROM sales s
    WHERE $where
    GROUP BY MONTH(s.sale_date)
    ORDER BY month
";
$result_monthly = $conn->query($sql_monthly);
$monthLabels = [];
$monthData = [];
while ($row = $result_monthly->fetch_assoc()) {
    $monthLabels[] = date("F", mktime(0, 0, 0, $row['month'], 1));
    $monthData[] = $row['total'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Flower Shop Sales Report</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f9fafb;
            padding: 30px;
        }
        h2, h3 {
            color: #2c3e50;
        }
        form {
            margin-bottom: 30px;
        }
        form input, form select {
            padding: 5px;
            margin: 5px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        th, td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #3498db;
            color: white;
        }
        tr:hover {
            background: #f2f2f2;
        }
        canvas {
            margin-top: 30px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        button {
            padding: 8px 12px;
            border: none;
            background: #27ae60;
            color: white;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
        }
        button:hover {
            background: #2ecc71;
        }
    </style>
</head>
<body>

    <h2>🌸 Flower Shop Sales Report</h2>

    <!-- Filters -->
    <form method="get">
        <label>Flower:</label>
        <select name="flower">
            <option value="">-- All --</option>
            <?php foreach ($flowerOptions as $id => $name): ?>
                <option value="<?= $id ?>" <?= ($flowerFilter == $id) ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
            <?php endforeach; ?>
        </select>

        <label>From:</label>
        <input type="date" name="from" value="<?= htmlspecialchars($fromDate) ?>">
        <label>To:</label>
        <input type="date" name="to" value="<?= htmlspecialchars($toDate) ?>">
        <input type="submit" value="Apply Filters">
    </form>

    <!-- Total Revenue -->
    <h3>💰 Overall Total Revenue: ₱<?= number_format($totalRevenue, 2) ?></h3>

    <!-- Export Buttons -->
    <div style="margin: 20px 0;">
        <button onclick="exportToCSV()">📤 Export to CSV</button>
        <button onclick="exportToPDF()">🧾 Export to PDF</button>
    </div>

    <!-- Summary Table -->
    <h3>Summary by Flower</h3>
    <table>
        <tr>
            <th>Flower Name</th>
            <th>Quantity Sold</th>
            <th>Revenue (₱)</th>
        </tr>
        <?php foreach ($summary as $name => $data): ?>
        <tr>
            <td><?= htmlspecialchars($name) ?></td>
            <td><?= $data['quantity'] ?></td>
            <td><?= number_format($data['revenue'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <!-- Revenue Chart -->
    <h3>Total Revenue per Flower (₱)</h3>
    <canvas id="revenueChart" height="<?= count($summary) * 30 ?>"></canvas>

    <!-- Monthly Chart -->
    <h3>Sales Quantity per Month</h3>
    <canvas id="monthlyChart" height="150"></canvas>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <script>
    // Revenue Chart
    const ctxRev = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctxRev, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_keys($summary)) ?>,
            datasets: [{
                label: 'Revenue (₱)',
                data: <?= json_encode(array_map(fn($s) => $s['revenue'], $summary)) ?>,
                backgroundColor: 'rgba(255, 99, 132, 0.7)',
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: { y: { beginAtZero: true } }
        }
    });

    // Monthly Sales Chart
    const ctxMonth = document.getElementById('monthlyChart').getContext('2d');
    new Chart(ctxMonth, {
        type: 'line',
        data: {
            labels: <?= json_encode($monthLabels) ?>,
            datasets: [{
                label: 'Total Quantity Sold',
                data: <?= json_encode($monthData) ?>,
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                fill: false,
                tension: 0.3
            }]
        },
        options: {
            scales: { y: { beginAtZero: true } }
        }
    });

    // CSV Export
    function exportToCSV() {
        const rows = [["Flower Name", "Quantity Sold", "Revenue (₱)"]];
        <?php foreach ($summary as $name => $data): ?>
        rows.push(["<?= $name ?>", "<?= $data['quantity'] ?>", "<?= number_format($data['revenue'], 2) ?>"]);
        <?php endforeach; ?>
        let csv = "data:text/csv;charset=utf-8," + rows.map(e => e.join(",")).join("\n");
        const link = document.createElement("a");
        link.setAttribute("href", encodeURI(csv));
        link.setAttribute("download", "flower_sales_report.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // PDF Export
    async function exportToPDF() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        doc.setFontSize(16);
        doc.text("Flower Sales Report", 10, 10);
        doc.setFontSize(12);
        let y = 20;
        doc.text("Overall Revenue: ₱<?= number_format($totalRevenue, 2) ?>", 10, y);
        y += 10;
        doc.text("Flower | Quantity | Revenue", 10, y);
        <?php foreach ($summary as $name => $data): ?>
        y += 10;
        doc.text("<?= $name ?> | <?= $data['quantity'] ?> | ₱<?= number_format($data['revenue'], 2) ?>", 10, y);
        <?php endforeach; ?>
        doc.save("flower_sales_report.pdf");
    }
    </script>

</body>
</html>

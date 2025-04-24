<?php
// Configuration and security
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
require_once 'db.php';

class SalesReport {
    private $conn;
    private $flowerOptions = [];
    private $productPrices = [];
    private $salesData = [];
    private $summary = [];
    private $totalRevenue = 0;
    private $monthLabels = [];
    private $monthData = [];
    private $topSelling = [];
    private $whereClause = "1=1"; // Initialize whereClause as a class property

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function fetchFlowerData() {
        $flowerQuery = "SELECT flower_id, name, price FROM product ORDER BY name";
        $flowerResult = $this->conn->query($flowerQuery);
        
        if (!$flowerResult) {
            throw new Exception("Error fetching flower data: " . $this->conn->error);
        }

        while ($row = $flowerResult->fetch_assoc()) {
            $this->flowerOptions[$row['flower_id']] = $row['name'];
            $this->productPrices[$row['flower_id']] = $row['price'];
        }
    }

    public function generateReport($filters) {
        $this->buildWhereClause($filters);
        $this->fetchSalesData();
        $this->calculateSummary();
        $this->fetchMonthlyData();
        $this->determineTopSelling();
    }

    private function buildWhereClause($filters) {
        $conditions = ["1=1"];
        
        if (!empty($filters['flower'])) {
            $conditions[] = "s.product_id = " . intval($filters['flower']);
        }
        if (!empty($filters['from'])) {
            $conditions[] = "s.sale_date >= '" . $this->conn->real_escape_string($filters['from']) . "'";
        }
        if (!empty($filters['to'])) {
            $conditions[] = "s.sale_date <= '" . $this->conn->real_escape_string($filters['to']) . "'";
        }
        
        $this->whereClause = implode(" AND ", $conditions);
    }

    private function fetchSalesData() {
        $sql = "
            SELECT s.id, s.product_id, p.name, s.quantity, s.sale_date, p.price,
                   (s.quantity * p.price) AS revenue
            FROM sales s
            JOIN product p ON s.product_id = p.flower_id
            WHERE {$this->whereClause}
            ORDER BY s.sale_date DESC
        ";
        
        $result = $this->conn->query($sql);
        
        if (!$result) {
            throw new Exception("Error fetching sales data: " . $this->conn->error);
        }

        while ($row = $result->fetch_assoc()) {
            $this->salesData[] = $row;
        }
    }

    private function calculateSummary() {
        foreach ($this->salesData as $row) {
            $this->summary[$row['name']]['quantity'] = ($this->summary[$row['name']]['quantity'] ?? 0) + $row['quantity'];
            $this->summary[$row['name']]['revenue'] = ($this->summary[$row['name']]['revenue'] ?? 0) + $row['revenue'];
        }
        
        $this->totalRevenue = array_sum(array_column($this->summary, 'revenue'));
    }

    private function fetchMonthlyData() {
        $sql = "
            SELECT 
                DATE_FORMAT(s.sale_date, '%Y-%m') AS ym,
                SUM(s.quantity) AS total
            FROM sales s
            WHERE {$this->whereClause}
            GROUP BY DATE_FORMAT(s.sale_date, '%Y-%m')
            ORDER BY DATE_FORMAT(s.sale_date, '%Y-%m')
        ";

        $result = $this->conn->query($sql);
        
        if (!$result) {
            throw new Exception("Error fetching monthly data: " . $this->conn->error);
        }

        while ($row = $result->fetch_assoc()) {
            $this->monthLabels[] = date("M Y", strtotime($row['ym']));
            $this->monthData[] = $row['total'];
        }
    }

    private function determineTopSelling() {
        arsort($this->summary);
        $this->topSelling = array_slice($this->summary, 0, 1, true);
    }

    // Getters for the view
    public function getFlowerOptions() { return $this->flowerOptions; }
    public function getSummary() { return $this->summary; }
    public function getTotalRevenue() { return $this->totalRevenue; }
    public function getMonthLabels() { return $this->monthLabels; }
    public function getMonthData() { return $this->monthData; }
    public function getTopSelling() { return $this->topSelling; }
    public function getSalesData() { return $this->salesData; }
}

try {
    // Initialize and generate report
    $report = new SalesReport($conn);
    $report->fetchFlowerData();
    
    // Get filters from request
    $filters = [
        'flower' => $_GET['flower'] ?? '',
        'from' => $_GET['from'] ?? '',
        'to' => $_GET['to'] ?? ''
    ];
    
    $report->generateReport($filters);
    
    // Extract data for view
    $flowerOptions = $report->getFlowerOptions();
    $summary = $report->getSummary();
    $totalRevenue = $report->getTotalRevenue();
    $monthLabels = $report->getMonthLabels();
    $monthData = $report->getMonthData();
    $topSelling = $report->getTopSelling();
    $flowerFilter = $filters['flower'];
    $fromDate = $filters['from'];
    $toDate = $filters['to'];
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report | Flower Shop Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #f8f9fa;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --border-radius: 0.375rem;
            --box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        
        body {
            background-color: #f4f6f8;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: #212529;
        }
        
        .dashboard-container {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .card {
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 1.5rem;
            border: none;
            transition: transform 0.2s ease;
        }
        
        .card:hover {
            transform: translateY(-2px);
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
            padding: 1rem 1.25rem;
        }
        
        .card-body {
            padding: 1.25rem;
        }
        
        .chart-container {
            position: relative;
            height: 400px;
            margin-bottom: 2rem;
        }
        
        .chart-container canvas {
            width: 100% !important;
            height: 100% !important;
            transition: all 0.3s ease;
        }
        
        /* Hover effect for revenue chart bars */
        #revenueChart:hover {
            transform: scale(1.01);
        }
        
        .btn-export {
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            transition: background-color 0.2s ease;
        }
        
        .back-btn:hover {
            background-color: #2980b9;
            color: white;
        }
        
        .chart-header {
            margin-bottom: 1rem;
            font-weight: 600;
            color: #495057;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            background-color: var(--secondary-color);
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 1rem;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            .chart-container {
                height: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="container dashboard-container">
        <!-- Header and Back Button -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-graph-up"></i> Sales Report
            </h1>
            <a href="dashboard.php" class="back-btn">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-funnel"></i> Filters
            </div>
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-3">
                        <label for="flower" class="form-label">Flower</label>
                        <select name="flower" id="flower" class="form-select">
                            <option value="">All Flowers</option>
                            <?php foreach ($flowerOptions as $id => $name): ?>
                                <option value="<?= $id ?>" <?= ($flowerFilter == $id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="from" class="form-label">From Date</label>
                        <input type="date" name="from" id="from" value="<?= htmlspecialchars($fromDate) ?>" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label for="to" class="form-label">To Date</label>
                        <input type="date" name="to" id="to" value="<?= htmlspecialchars($toDate) ?>" class="form-control">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-filter"></i> Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Key Metrics -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <i class="bi bi-cash-stack"></i> Total Revenue
                    </div>
                    <div class="card-body">
                        <h3 class="card-title">₱<?= number_format($totalRevenue, 2) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <i class="bi bi-trophy"></i> Top-Selling Product
                    </div>
                    <div class="card-body">
                        <?php foreach ($topSelling as $name => $data): ?>
                            <h5 class="card-title"><?= htmlspecialchars($name) ?></h5>
                            <p class="card-text mb-1">
                                <span class="text-muted">Quantity:</span> 
                                <strong><?= $data['quantity'] ?></strong>
                            </p>
                            <p class="card-text">
                                <span class="text-muted">Revenue:</span> 
                                <strong>₱<?= number_format($data['revenue'], 2) ?></strong>
                            </p>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Export Buttons -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-download"></i> Export Report
            </div>
            <div class="card-body">
                <button onclick="exportToCSV()" class="btn btn-primary btn-export">
                    <i class="bi bi-file-earmark-excel"></i> Export to CSV
                </button>
                <button onclick="exportToPDF()" class="btn btn-danger btn-export">
                    <i class="bi bi-file-earmark-pdf"></i> Export to PDF
                </button>
            </div>
        </div>

        <!-- Summary Table -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-table"></i> Sales Summary by Flower
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Flower Name</th>
                                <th class="text-end">Quantity Sold</th>
                                <th class="text-end">Revenue (₱)</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($summary as $name => $data): ?>
                            <tr>
                                <td><?= htmlspecialchars($name) ?></td>
                                <td class="text-end"><?= number_format($data['quantity']) ?></td>
                                <td class="text-end"><?= number_format($data['revenue'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-active">
                                <th>Total</th>
                                <th class="text-end"><?= number_format(array_sum(array_column($summary, 'quantity'))) ?></th>
                                <th class="text-end">₱<?= number_format($totalRevenue, 2) ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="row">
            <div class="col-lg-6">
                <div class="chart-container">
                    <h5 class="chart-header">
                        <i class="bi bi-bar-chart"></i> Revenue per Flower
                    </h5>
                    <canvas id="revenueChart" height="<?= max(300, count($summary) * 30) ?>"></canvas>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="chart-container">
                    <h5 class="chart-header">
                        <i class="bi bi-graph-up"></i> Monthly Sales Trend
                    </h5>
                    <canvas id="monthlyChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Store chart instances so we can resize them later
        const charts = {
        revenue: null,
        monthly: null
    };

    // Function to initialize charts
    function initializeCharts() {
        // Destroy existing charts if they exist
        if (charts.revenue) charts.revenue.destroy();
        if (charts.monthly) charts.monthly.destroy();

        // Revenue Chart with enhanced hover effects
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        charts.revenue = new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_keys($summary)) ?>,
                datasets: [{
                    label: 'Revenue (₱)',
                    data: <?= json_encode(array_map(fn($s) => $s['revenue'], $summary)) ?>,
                    backgroundColor: function(context) {
                        // Hover effect - change color when hovered
                        return context.datasetIndex === 0 && context.dataIndex === context.active?.index ?
                            'rgba(231, 76, 60, 0.7)' : 'rgba(52, 152, 219, 0.7)';
                    },
                    borderColor: function(context) {
                        // Hover effect - change border when hovered
                        return context.datasetIndex === 0 && context.dataIndex === context.active?.index ?
                            'rgba(231, 76, 60, 1)' : 'rgba(52, 152, 219, 1)';
                    },
                    borderWidth: 1,
                    hoverBackgroundColor: 'rgba(231, 76, 60, 0.7)',
                    hoverBorderColor: 'rgba(231, 76, 60, 1)',
                    hoverBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '₱' + context.raw.toLocaleString('en-PH', { 
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2 
                                });
                            }
                        },
                        displayColors: true,
                        usePointStyle: true,
                        padding: 10,
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 12
                        }
                    },
                    // Add animation on hover
                    hover: {
                        animationDuration: 200
                    }
                },
                scales: { 
                    y: { 
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString('en-PH');
                            }
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                // Enhanced hover effects
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart',
                    onComplete: function() {
                        // Animation complete callback
                    }
                },
                elements: {
                    bar: {
                        borderRadius: 4,
                        borderSkipped: false
                    }
                }
            }
        });

        // Monthly Sales Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        charts.monthly = new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($monthLabels) ?>,
                datasets: [{
                    label: 'Total Quantity Sold',
                    data: <?= json_encode($monthData) ?>,
                    borderColor: 'rgba(40, 167, 69, 1)',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: 'rgba(40, 167, 69, 1)',
                    pointHoverRadius: 6,
                    pointHoverBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.raw.toLocaleString('en-PH') + ' units';
                            }
                        }
                    }
                },
                scales: { 
                    y: { 
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('en-PH');
                            }
                        }
                    } 
                }
            }
        });
    }

    // Initialize charts when page loads
    document.addEventListener('DOMContentLoaded', function() {
        initializeCharts();
        
        // Handle window resize
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                initializeCharts();
            }, 200);
        });
    });

    // Export to CSV
    function exportToCSV() {
        const rows = [
            ["Flower Name", "Quantity Sold", "Revenue (₱)"],
            <?php foreach ($summary as $name => $data): ?>
                ["<?= addslashes($name) ?>", "<?= $data['quantity'] ?>", "<?= number_format($data['revenue'], 2) ?>"],
            <?php endforeach; ?>
            ["Total", "<?= array_sum(array_column($summary, 'quantity')) ?>", "<?= number_format($totalRevenue, 2) ?>"]
        ];
        
        let csvContent = "data:text/csv;charset=utf-8,";
        rows.forEach(row => {
            csvContent += row.join(",") + "\r\n";
        });
        
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "flower_sales_report_<?= date('Ymd') ?>.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // Export to PDF
    async function exportToPDF() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({
            orientation: 'landscape',
            unit: 'mm'
        });
        
        // Title
        doc.setFontSize(18);
        doc.setTextColor(40);
        doc.text("Flower Shop Sales Report", 105, 15, { align: 'center' });
        
        // Date range if filtered
        <?php if (!empty($fromDate) || !empty($toDate)): ?>
            doc.setFontSize(12);
            let dateRange = "All Time";
            if ("<?= $fromDate ?>" && "<?= $toDate ?>") {
                dateRange = "From <?= date('M j, Y', strtotime($fromDate)) ?> to <?= date('M j, Y', strtotime($toDate)) ?>";
            } else if ("<?= $fromDate ?>") {
                dateRange = "From <?= date('M j, Y', strtotime($fromDate)) ?> to present";
            } else if ("<?= $toDate ?>") {
                dateRange = "Up to <?= date('M j, Y', strtotime($toDate)) ?>";
            }
            doc.text(dateRange, 105, 22, { align: 'center' });
        <?php endif; ?>
        
        // Summary
        doc.setFontSize(14);
        doc.text("Summary", 15, 32);
        
        doc.setFontSize(12);
        doc.text(`Total Revenue: ₱<?= number_format($totalRevenue, 2) ?>`, 15, 40);
        
        <?php foreach ($topSelling as $name => $data): ?>
            doc.text(`Top-Selling Product: <?= addslashes($name) ?> (<?= $data['quantity'] ?> units, ₱<?= number_format($data['revenue'], 2) ?>)`, 15, 48);
        <?php endforeach; ?>
        
        // Table
        doc.setFontSize(14);
        doc.text("Sales by Flower", 15, 60);
        
        // Table headers
        doc.setFontSize(12);
        doc.setTextColor(255);
        doc.setFillColor(52, 152, 219);
        doc.rect(15, 65, 180, 8, 'F');
        doc.text("Flower Name", 20, 70);
        doc.text("Quantity Sold", 100, 70, { align: 'right' });
        doc.text("Revenue", 180, 70, { align: 'right' });
        
        // Table rows
        doc.setTextColor(0);
        let y = 75;
        <?php foreach ($summary as $name => $data): ?>
            doc.text("<?= addslashes($name) ?>", 20, y);
            doc.text("<?= number_format($data['quantity']) ?>", 100, y, { align: 'right' });
            doc.text("₱<?= number_format($data['revenue'], 2) ?>", 180, y, { align: 'right' });
            y += 7;
        <?php endforeach; ?>
        
        // Table footer
        doc.setFontSize(12);
        doc.setDrawColor(52, 152, 219);
        doc.line(15, y, 195, y);
        y += 5;
        doc.setFont(undefined, 'bold');
        doc.text("Total", 20, y);
        doc.text("<?= number_format(array_sum(array_column($summary, 'quantity'))) ?>", 100, y, { align: 'right' });
        doc.text("₱<?= number_format($totalRevenue, 2) ?>", 180, y, { align: 'right' });
        
        // Save the PDF
        doc.save("flower_sales_report_<?= date('Ymd') ?>.pdf");
    }
    </script>
</body>
</html>
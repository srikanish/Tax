<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'connect.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: register.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$user_sql = "SELECT full_name, email, phone, pan_number, profile_image, dob, address, aadhar_number FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
if (!$user_stmt) {
    die("SQL Error: " . $conn->error);
}
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
if ($user_result->num_rows > 0) {
    $user = $user_result->fetch_assoc();
    $_SESSION['name'] = $user['full_name'];
    $_SESSION['pan'] = $user['pan_number'];
    $_SESSION['profile_image'] = $user['profile_image'] ?? 'default.png';
    $_SESSION['email'] = $user['email'];
}
$sql = "SELECT * FROM tax_calculations WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $total_income = $row['total_income'];
    $total_deductions = $row['total_deductions'];
    $taxable_income = $row['taxable_income'];
    $tax_payable_new = $row['tax_payable_new'];
} else {
    echo "No tax record found.";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tax Summary - FY 2024-2025</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            background-color: #eef2f7; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            margin: 0; 
        }
        .container { 
            background-color: #fff; 
            border-radius: 15px; 
            padding: 20px 40px; 
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); 
            width: 90%; 
            max-width: 1000px; 
        }
        .header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 20px; 
        }
        h1{
            color:rgb(90, 126, 138);
        }
        .profile-image img { 
            width: 150px; 
            height: 150px; 
            border-radius: 50%; 
        }
        .line { 
            height: 1px; 
            width: 100%; 
            background-color: black; 
        }
        .content {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }
        .chart-container {
            flex: 1;
        }
        .summary-card {
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 20px; 
            flex: 1;
        }
        .box { 
            background-color: #eef2f7; 
            border-radius: 10px; 
            padding: 15px; 
            text-align: center; 
        }
        .box h3 { 
           padding-top: 20%;
            margin: 0; 
            color: #333; 
        }
        .box p { 
            margin: 5px 0; 
            font-weight: bold; 
            font-size: 1.2rem; 
        }
        .dashboard-btn {
            margin-top: 50px;
            margin-bottom: 30px;
            text-align: center;
        }
        .dashboard-btn a {
            background-color:rgb(110, 120, 141);;
            color: #fff;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
        }
        .dashboard-btn a:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>SUMMARY - FY 2024-2025 (AY 2025-2026)</h1>
                <h3>Name: <strong><?php echo $_SESSION['name']; ?></strong></h3>
                <h3>Pan number: <strong><?php echo $_SESSION['pan']; ?></strong></h3>
                <h3>Email: <strong><?php echo $_SESSION['email']; ?></strong></h3>
            </div>
            <div class="profile-image">
                <img src="uploads/<?php echo htmlspecialchars($user['profile_image'] ?? 'default.png'); ?>" alt="Profile Picture">
            </div>
        </div>
        <div class="line"></div>
        <div class="content">
            <div class="chart-container">
                <canvas id="taxChart" width="400" height="300"></canvas>
            </div>
            <div class="summary-card">
                <div class="box">
                    <h3>Total Income</h3>
                    <p>₹<?php echo number_format($total_income, 2); ?></p>
                </div>
                <div class="box">
                    <h3>Taxable Income</h3>
                    <p>₹<?php echo number_format($taxable_income, 2); ?></p>
                </div>
                <div class="box">
                    <h3>Tax Payable</h3>
                    <p>₹<?php echo number_format($tax_payable_new, 2); ?></p>
                </div>
                <div class="box">
                    <h3>Total Deductions</h3>
                    <p>₹<?php echo number_format($total_deductions, 2); ?></p>
                </div>
            </div>
        </div>
        <div class="dashboard-btn">
            <a href="dashboard.php">Go to Dashboard</a>
        </div>
    </div>
    <script>
        const ctx = document.getElementById('taxChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Total Income', 'Taxable Income', 'Total Deductions', 'Tax Payable'],
                datasets: [{
                    label: 'Amount (₹)',
                    data: [
                        <?php echo $total_income; ?>,
                        <?php echo $taxable_income; ?>,
                        <?php echo $total_deductions; ?>,
                        <?php echo $tax_payable_new; ?>
                    ],
                    backgroundColor: ['#4CAF50', '#2196F3', '#FF9800', '#F44336']
                }]
            },
            options: {
                scales: {
                    y: { beginAtZero: true }
                    
                }
            }
        });
    </script>
</body>
</html>





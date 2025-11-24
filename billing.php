<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Get pending payments
$payments_query = "SELECT p.*, r.id as reservation_id, c.full_name, rm.room_number 
                  FROM payments p 
                  JOIN reservations r ON p.reservation_id = r.id 
                  JOIN customers c ON r.customer_id = c.id 
                  JOIN rooms rm ON r.room_id = rm.id 
                  ORDER BY p.payment_date DESC";
$payments_stmt = $db->prepare($payments_query);
$payments_stmt->execute();
$payments = $payments_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing - Hotel Management System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <a href="dashboard.php" class="navbar-brand">OHMS</a>
                <ul class="navbar-nav">
                    <li><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                    <li><a href="reservations.php" class="nav-link">Reservations</a></li>
                    <li><a href="rooms.php" class="nav-link">Rooms</a></li>
                    <li><a href="customers.php" class="nav-link">Customers</a></li>
                    <li><a href="billing.php" class="nav-link active">Billing</a></li>
                    <li><a href="reports.php" class="nav-link">Reports</a></li>
                    <li><a href="logout.php" class="nav-link">Logout (<?php echo $_SESSION['full_name']; ?>)</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h1>Billing & Payments</h1>
            <p>Manage customer payments and invoices - All amounts in Kenyan Shillings (KES)</p>
        </div>

        <!-- Payment Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number">KES 0</div>
                <div class="stat-label">Today's Revenue</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">0</div>
                <div class="stat-label">Pending Payments</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">KES 0</div>
                <div class="stat-label">Monthly Revenue</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">0</div>
                <div class="stat-label">Completed Payments</div>
            </div>
        </div>

        <!-- Payments List -->
        <div class="card">
            <div class="card-header">
                <h2>Payment History</h2>
            </div>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Payment ID</th>
                            <th>Customer</th>
                            <th>Room</th>
                            <th>Amount (KES)</th>
                            <th>Method</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($payments)): ?>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td>#<?php echo $payment['id']; ?></td>
                                    <td><?php echo $payment['full_name']; ?></td>
                                    <td><?php echo $payment['room_number']; ?></td>
                                    <td><strong>KES <?php echo number_format($payment['amount'], 2); ?></strong></td>
                                    <td><?php echo $payment['payment_method'] ?: 'Not specified'; ?></td>
                                    <td><?php echo date('M j, Y', strtotime($payment['payment_date'])); ?></td>
                                    <td>
                                        <span style="padding: 0.25rem 0.75rem; border-radius: 15px; font-size: 0.8rem; font-weight: 600;
                                            background: <?php 
                                                switch($payment['status']) {
                                                    case 'completed': echo '#e8f5e8'; break;
                                                    case 'pending': echo '#fff3e0'; break;
                                                    default: echo '#ffebee';
                                                }
                                            ?>; 
                                            color: <?php 
                                                switch($payment['status']) {
                                                    case 'completed': echo '#388e3c'; break;
                                                    case 'pending': echo '#f57c00'; break;
                                                    default: echo '#d32f2f';
                                                }
                                            ?>;">
                                            <?php echo ucfirst($payment['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: #7f8c8d; padding: 2rem;">
                                    No payment records found. Payments will appear here when reservations are created.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Billing Information -->
        <div class="card">
            <div class="card-header">
                <h2>Billing Information</h2>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Hotel Name</label>
                    <input type="text" class="form-control" value="Your Hotel Name" readonly>
                </div>
                <div class="form-group">
                    <label>VAT Number</label>
                    <input type="text" class="form-control" value="PENDING0001" readonly>
                </div>
                <div class="form-group">
                    <label>Currency</label>
                    <input type="text" class="form-control" value="Kenyan Shillings (KES)" readonly>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
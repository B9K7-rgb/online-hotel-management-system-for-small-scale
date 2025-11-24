<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Handle form submissions
if ($_POST) {
    if (isset($_POST['add_customer'])) {
        $full_name = $_POST['full_name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $id_number = $_POST['id_number'];
        $address = $_POST['address'];
        
        $insert_query = "INSERT INTO customers (full_name, email, phone, id_number, address) VALUES (?, ?, ?, ?, ?)";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->execute([$full_name, $email, $phone, $id_number, $address]);
        
        $success = "Customer added successfully!";
    }
}

// Get all customers
$customers_query = "SELECT * FROM customers ORDER BY full_name";
$customers_stmt = $db->prepare($customers_query);
$customers_stmt->execute();
$customers = $customers_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers - Hotel Management System</title>
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
                    <li><a href="customers.php" class="nav-link active">Customers</a></li>
                    <li><a href="billing.php" class="nav-link">Billing</a></li>
                    <li><a href="reports.php" class="nav-link">Reports</a></li>
                    <li><a href="logout.php" class="nav-link">Logout (<?php echo $_SESSION['full_name']; ?>)</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h1>Customer Management</h1>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Add Customer Form -->
        <div class="card">
            <div class="card-header">
                <h2>Add New Customer</h2>
            </div>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>ID Number</label>
                        <input type="text" name="id_number" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <button type="submit" name="add_customer" class="btn btn-primary">Add Customer</button>
            </form>
        </div>

        <!-- Customers List -->
        <div class="card">
            <div class="card-header">
                <h2>All Customers</h2>
            </div>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>ID Number</th>
                            <th>Registered</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td>#<?php echo $customer['id']; ?></td>
                                <td><?php echo $customer['full_name']; ?></td>
                                <td><?php echo $customer['email']; ?></td>
                                <td><?php echo $customer['phone']; ?></td>
                                <td><?php echo $customer['id_number']; ?></td>
                                <td><?php echo date('M j, Y', strtotime($customer['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
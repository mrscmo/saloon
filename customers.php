<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

require_once "config/database.php";

// Process delete operation
if(isset($_GET["delete"]) && !empty($_GET["delete"])){
    $sql = "DELETE FROM customers WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $_GET["delete"]);
        if(mysqli_stmt_execute($stmt)){
            header("location: customers.php");
            exit();
        }
    }
}

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(isset($_POST["action"])){
        if($_POST["action"] == "add"){
            $sql = "INSERT INTO customers (name, email, phone, address, date_of_birth, anniversary_date) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "ssssss", 
                    $_POST["name"], 
                    $_POST["email"], 
                    $_POST["phone"], 
                    $_POST["address"],
                    $_POST["date_of_birth"],
                    $_POST["anniversary_date"]
                );
                if(mysqli_stmt_execute($stmt)){
                    $customer_id = mysqli_insert_id($conn);
                    
                    // Add preferences if any
                    if(isset($_POST["preferences"]) && is_array($_POST["preferences"])){
                        foreach($_POST["preferences"] as $type => $value){
                            if(!empty($value)){
                                $sql = "INSERT INTO customer_preferences (customer_id, preference_type, preference_value) 
                                        VALUES (?, ?, ?)";
                                if($stmt = mysqli_prepare($conn, $sql)){
                                    mysqli_stmt_bind_param($stmt, "iss", $customer_id, $type, $value);
                                    mysqli_stmt_execute($stmt);
                                }
                            }
                        }
                    }
                    
                    header("location: customers.php");
                    exit();
                }
            }
        } elseif($_POST["action"] == "edit"){
            $sql = "UPDATE customers SET 
                    name=?, email=?, phone=?, address=?, 
                    date_of_birth=?, anniversary_date=? 
                    WHERE id=?";
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "ssssssi", 
                    $_POST["name"], 
                    $_POST["email"], 
                    $_POST["phone"], 
                    $_POST["address"],
                    $_POST["date_of_birth"],
                    $_POST["anniversary_date"],
                    $_POST["id"]
                );
                if(mysqli_stmt_execute($stmt)){
                    // Update preferences
                    if(isset($_POST["preferences"]) && is_array($_POST["preferences"])){
                        // Delete existing preferences
                        $sql = "DELETE FROM customer_preferences WHERE customer_id = ?";
                        if($stmt = mysqli_prepare($conn, $sql)){
                            mysqli_stmt_bind_param($stmt, "i", $_POST["id"]);
                            mysqli_stmt_execute($stmt);
                        }
                        
                        // Add new preferences
                        foreach($_POST["preferences"] as $type => $value){
                            if(!empty($value)){
                                $sql = "INSERT INTO customer_preferences (customer_id, preference_type, preference_value) 
                                        VALUES (?, ?, ?)";
                                if($stmt = mysqli_prepare($conn, $sql)){
                                    mysqli_stmt_bind_param($stmt, "iss", $_POST["id"], $type, $value);
                                    mysqli_stmt_execute($stmt);
                                }
                            }
                        }
                    }
                    
                    header("location: customers.php");
                    exit();
                }
            }
        }
    }
}

// Fetch all customers with their membership and loyalty information
$customers = array();
$sql = "SELECT c.*, m.name as membership_name, m.points_multiplier,
        (SELECT COUNT(*) FROM customer_history WHERE customer_id = c.id) as visit_count,
        (SELECT SUM(points) FROM loyalty_transactions WHERE customer_id = c.id AND transaction_type = 'earn') as total_points_earned,
        (SELECT SUM(points) FROM loyalty_transactions WHERE customer_id = c.id AND transaction_type = 'redeem') as total_points_redeemed
        FROM customers c
        LEFT JOIN memberships m ON c.membership_id = m.id
        ORDER BY c.name";
$result = mysqli_query($conn, $sql);
while($row = mysqli_fetch_assoc($result)){
    $customers[] = $row;
}

// Fetch all memberships for dropdown
$memberships = array();
$sql = "SELECT * FROM memberships ORDER BY price";
$result = mysqli_query($conn, $sql);
while($row = mysqli_fetch_assoc($result)){
    $memberships[] = $row;
}

// Fetch all services for preferences dropdown
$services = array();
$sql = "SELECT id, name FROM services ORDER BY name";
$result = mysqli_query($conn, $sql);
while($row = mysqli_fetch_assoc($result)){
    $services[] = $row;
}

// Fetch all staff for preferences dropdown
$staff = array();
$sql = "SELECT id, name, specialization FROM staff ORDER BY name";
$result = mysqli_query($conn, $sql);
while($row = mysqli_fetch_assoc($result)){
    $staff[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers - Salon Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 250px;
            --sidebar-bg: #2c3e50;
            --sidebar-hover: #34495e;
            --sidebar-active: #3498db;
            --text-color: #ecf0f1;
        }

        .sidebar {
            min-height: 100vh;
            background-color: var(--sidebar-bg);
            padding: 0;
            position: fixed;
            width: var(--sidebar-width);
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .sidebar-header {
            padding: 20px;
            background-color: rgba(0,0,0,0.1);
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-header h3 {
            color: var(--text-color);
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .sidebar nav {
            padding: 20px 0;
        }

        .sidebar a {
            color: var(--text-color);
            text-decoration: none;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .sidebar a i {
            width: 25px;
            font-size: 1.1rem;
            margin-right: 10px;
        }

        .sidebar a:hover {
            background-color: var(--sidebar-hover);
            border-left-color: var(--sidebar-active);
        }

        .sidebar a.active {
            background-color: var(--sidebar-hover);
            border-left-color: var(--sidebar-active);
            color: var(--sidebar-active);
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: all 0.3s ease;
        }

        @media (max-width: 768px) {
            .sidebar {
                margin-left: calc(-1 * var(--sidebar-width));
            }
            .sidebar.active {
                margin-left: 0;
            }
            .main-content {
                margin-left: 0;
            }
            .main-content.active {
                margin-left: var(--sidebar-width);
            }
        }
        .customer-card {
            margin-bottom: 20px;
        }
        .loyalty-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
        .preference-tag {
            font-size: 0.8rem;
            margin-right: 5px;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="sidebar-header">
                    <h3>Salon Admin</h3>
                </div>
                <nav>
                    <a href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="customers.php" class="active">
                        <i class="fas fa-users"></i>
                        <span>Customers</span>
                    </a>
                    <a href="appointments.php">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Appointments</span>
                    </a>
                    <a href="services.php">
                        <i class="fas fa-cut"></i>
                        <span>Services</span>
                    </a>
                    <a href="staff.php">
                        <i class="fas fa-user-tie"></i>
                        <span>Staff</span>
                    </a>
                    <a href="reports.php">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                    <a href="logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Customers</h2>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                        <i class="fas fa-plus"></i> Add Customer
                    </button>
                </div>

                <!-- Customer Cards -->
                <div class="row">
                    <?php foreach($customers as $customer): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card customer-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($customer['name']); ?></h5>
                                    <div>
                                        <?php if($customer['membership_name']): ?>
                                        <span class="badge bg-primary loyalty-badge">
                                            <?php echo htmlspecialchars($customer['membership_name']); ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <p class="card-text">
                                    <i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($customer['email']); ?><br>
                                    <i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($customer['phone']); ?><br>
                                    <i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($customer['address']); ?>
                                </p>
                                
                                <?php if($customer['date_of_birth']): ?>
                                <p class="card-text">
                                    <i class="fas fa-birthday-cake me-2"></i>
                                    Birthday: <?php echo date('F j, Y', strtotime($customer['date_of_birth'])); ?>
                                </p>
                                <?php endif; ?>
                                
                                <?php if($customer['anniversary_date']): ?>
                                <p class="card-text">
                                    <i class="fas fa-heart me-2"></i>
                                    Anniversary: <?php echo date('F j, Y', strtotime($customer['anniversary_date'])); ?>
                                </p>
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <strong>Loyalty Points:</strong> 
                                    <?php 
                                    $points = ($customer['total_points_earned'] ?? 0) - ($customer['total_points_redeemed'] ?? 0);
                                    echo $points;
                                    ?>
                                    <?php if($customer['points_multiplier'] > 1): ?>
                                    <span class="badge bg-success"><?php echo $customer['points_multiplier']; ?>x</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <strong>Visit Count:</strong> <?php echo $customer['visit_count']; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <strong>Preferences:</strong><br>
                                    <?php
                                    $sql = "SELECT * FROM customer_preferences WHERE customer_id = ?";
                                    if($stmt = mysqli_prepare($conn, $sql)){
                                        mysqli_stmt_bind_param($stmt, "i", $customer['id']);
                                        mysqli_stmt_execute($stmt);
                                        $result = mysqli_stmt_get_result($stmt);
                                        while($pref = mysqli_fetch_assoc($result)){
                                            echo '<span class="badge bg-info preference-tag">';
                                            echo htmlspecialchars($pref['preference_type'] . ': ' . $pref['preference_value']);
                                            echo '</span>';
                                        }
                                    }
                                    ?>
                                </div>
                                
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-primary edit-customer" 
                                            data-bs-toggle="modal" data-bs-target="#editCustomerModal"
                                            data-id="<?php echo $customer['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($customer['name']); ?>"
                                            data-email="<?php echo htmlspecialchars($customer['email']); ?>"
                                            data-phone="<?php echo htmlspecialchars($customer['phone']); ?>"
                                            data-address="<?php echo htmlspecialchars($customer['address']); ?>"
                                            data-dob="<?php echo $customer['date_of_birth']; ?>"
                                            data-anniversary="<?php echo $customer['anniversary_date']; ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button type="button" class="btn btn-sm btn-info view-history" 
                                            data-bs-toggle="modal" data-bs-target="#viewHistoryModal"
                                            data-id="<?php echo $customer['id']; ?>">
                                        <i class="fas fa-history"></i> History
                                    </button>
                                    <a href="customers.php?delete=<?php echo $customer['id']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Are you sure you want to delete this customer?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Customer Modal -->
    <div class="modal fade" id="addCustomerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="customers.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Name</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" class="form-control" name="phone">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Address</label>
                                    <textarea class="form-control" name="address" rows="3"></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" name="date_of_birth">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Anniversary Date</label>
                                    <input type="date" class="form-control" name="anniversary_date">
                                </div>
                                
                                <h6 class="mt-4">Preferences</h6>
                                <div class="mb-3">
                                    <label class="form-label">Preferred Services</label>
                                    <select class="form-select" name="preferences[service]">
                                        <option value="">Select Service</option>
                                        <?php foreach($services as $service): ?>
                                        <option value="<?php echo $service['id']; ?>">
                                            <?php echo htmlspecialchars($service['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Preferred Staff</label>
                                    <select class="form-select" name="preferences[staff]">
                                        <option value="">Select Staff</option>
                                        <?php foreach($staff as $staff_member): ?>
                                        <option value="<?php echo $staff_member['id']; ?>">
                                            <?php echo htmlspecialchars($staff_member['name']); ?> 
                                            (<?php echo htmlspecialchars($staff_member['specialization']); ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Preferred Style</label>
                                    <input type="text" class="form-control" name="preferences[style]" 
                                           placeholder="e.g., Modern, Classic, etc.">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Customer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Customer Modal -->
    <div class="modal fade" id="editCustomerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="customers.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Name</label>
                                    <input type="text" class="form-control" name="name" id="edit_name" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" id="edit_email">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" class="form-control" name="phone" id="edit_phone">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Address</label>
                                    <textarea class="form-control" name="address" id="edit_address" rows="3"></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" name="date_of_birth" id="edit_dob">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Anniversary Date</label>
                                    <input type="date" class="form-control" name="anniversary_date" id="edit_anniversary">
                                </div>
                                
                                <h6 class="mt-4">Preferences</h6>
                                <div class="mb-3">
                                    <label class="form-label">Preferred Services</label>
                                    <select class="form-select" name="preferences[service]" id="edit_pref_service">
                                        <option value="">Select Service</option>
                                        <?php foreach($services as $service): ?>
                                        <option value="<?php echo $service['id']; ?>">
                                            <?php echo htmlspecialchars($service['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Preferred Staff</label>
                                    <select class="form-select" name="preferences[staff]" id="edit_pref_staff">
                                        <option value="">Select Staff</option>
                                        <?php foreach($staff as $staff_member): ?>
                                        <option value="<?php echo $staff_member['id']; ?>">
                                            <?php echo htmlspecialchars($staff_member['name']); ?> 
                                            (<?php echo htmlspecialchars($staff_member['specialization']); ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Preferred Style</label>
                                    <input type="text" class="form-control" name="preferences[style]" id="edit_pref_style"
                                           placeholder="e.g., Modern, Classic, etc.">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View History Modal -->
    <div class="modal fade" id="viewHistoryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Customer History</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="historyContent">
                        <!-- Content will be loaded dynamically -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle edit customer modal
        document.querySelectorAll('.edit-customer').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const email = this.getAttribute('data-email');
                const phone = this.getAttribute('data-phone');
                const address = this.getAttribute('data-address');
                const dob = this.getAttribute('data-dob');
                const anniversary = this.getAttribute('data-anniversary');

                document.getElementById('edit_id').value = id;
                document.getElementById('edit_name').value = name;
                document.getElementById('edit_email').value = email;
                document.getElementById('edit_phone').value = phone;
                document.getElementById('edit_address').value = address;
                document.getElementById('edit_dob').value = dob;
                document.getElementById('edit_anniversary').value = anniversary;
            });
        });

        // Handle view history modal
        document.querySelectorAll('.view-history').forEach(button => {
            button.addEventListener('click', function() {
                const customerId = this.getAttribute('data-id');
                const historyContent = document.getElementById('historyContent');
                
                // Fetch customer history
                fetch(`get_customer_history.php?id=${customerId}`)
                    .then(response => response.text())
                    .then(html => {
                        historyContent.innerHTML = html;
                    })
                    .catch(error => {
                        historyContent.innerHTML = '<div class="alert alert-danger">Error loading history</div>';
                    });
            });
        });
    </script>
</body>
</html> 
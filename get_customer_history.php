<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

require_once "config/database.php";

if(!isset($_GET["id"]) || empty($_GET["id"])){
    echo '<div class="alert alert-danger">Invalid customer ID</div>';
    exit;
}

$customer_id = $_GET["id"];

// Get customer information
$sql = "SELECT * FROM customers WHERE id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $customer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $customer = mysqli_fetch_assoc($result);
}

if(!$customer){
    echo '<div class="alert alert-danger">Customer not found</div>';
    exit;
}

// Get customer service history
$sql = "SELECT h.*, s.name as service_name, st.name as staff_name 
        FROM customer_history h
        JOIN services s ON h.service_id = s.id
        JOIN staff st ON h.staff_id = st.id
        WHERE h.customer_id = ?
        ORDER BY h.appointment_date DESC";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $customer_id);
    mysqli_stmt_execute($stmt);
    $history_result = mysqli_stmt_get_result($stmt);
}

// Get loyalty transactions
$sql = "SELECT * FROM loyalty_transactions 
        WHERE customer_id = ? 
        ORDER BY created_at DESC";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $customer_id);
    mysqli_stmt_execute($stmt);
    $transactions_result = mysqli_stmt_get_result($stmt);
}

// Get membership history
$sql = "SELECT cm.*, m.name as membership_name, m.benefits
        FROM customer_memberships cm
        JOIN memberships m ON cm.membership_id = m.id
        WHERE cm.customer_id = ?
        ORDER BY cm.start_date DESC";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $customer_id);
    mysqli_stmt_execute($stmt);
    $memberships_result = mysqli_stmt_get_result($stmt);
}
?>

<div class="container-fluid p-0">
    <!-- Customer Info -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h6>Customer Information</h6>
            <p>
                <strong>Name:</strong> <?php echo htmlspecialchars($customer['name']); ?><br>
                <strong>Email:</strong> <?php echo htmlspecialchars($customer['email']); ?><br>
                <strong>Phone:</strong> <?php echo htmlspecialchars($customer['phone']); ?><br>
                <strong>Address:</strong> <?php echo htmlspecialchars($customer['address']); ?>
            </p>
        </div>
        <div class="col-md-6">
            <h6>Important Dates</h6>
            <?php if($customer['date_of_birth']): ?>
            <p>
                <strong>Birthday:</strong> <?php echo date('F j, Y', strtotime($customer['date_of_birth'])); ?>
            </p>
            <?php endif; ?>
            <?php if($customer['anniversary_date']): ?>
            <p>
                <strong>Anniversary:</strong> <?php echo date('F j, Y', strtotime($customer['anniversary_date'])); ?>
            </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Service History -->
    <div class="mb-4">
        <h6>Service History</h6>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Service</th>
                        <th>Staff</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($history = mysqli_fetch_assoc($history_result)): ?>
                    <tr>
                        <td><?php echo date('M d, Y', strtotime($history['appointment_date'])); ?></td>
                        <td><?php echo htmlspecialchars($history['service_name']); ?></td>
                        <td><?php echo htmlspecialchars($history['staff_name']); ?></td>
                        <td><?php echo htmlspecialchars($history['notes']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Loyalty Transactions -->
    <div class="mb-4">
        <h6>Loyalty Points History</h6>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Points</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($transaction = mysqli_fetch_assoc($transactions_result)): ?>
                    <tr>
                        <td><?php echo date('M d, Y', strtotime($transaction['created_at'])); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $transaction['transaction_type'] == 'earn' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst($transaction['transaction_type']); ?>
                            </span>
                        </td>
                        <td><?php echo $transaction['points']; ?></td>
                        <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Membership History -->
    <div class="mb-4">
        <h6>Membership History</h6>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Membership</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Benefits</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($membership = mysqli_fetch_assoc($memberships_result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($membership['membership_name']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($membership['start_date'])); ?></td>
                        <td><?php echo date('M d, Y', strtotime($membership['end_date'])); ?></td>
                        <td>
                            <span class="badge bg-<?php 
                                echo $membership['status'] == 'active' ? 'success' : 
                                    ($membership['status'] == 'expired' ? 'danger' : 'warning'); 
                            ?>">
                                <?php echo ucfirst($membership['status']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($membership['benefits']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div> 
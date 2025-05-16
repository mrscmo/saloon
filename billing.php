<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

require_once "config/database.php";

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(isset($_POST["action"])){
        if($_POST["action"] == "create_billing"){
            // Start transaction
            mysqli_begin_transaction($conn);
            
            try {
                // Insert billing record
                $sql = "INSERT INTO billing (appointment_id, customer_id, staff_id, subtotal, tax_rate, tax_amount, discount_amount, total_amount) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                if($stmt = mysqli_prepare($conn, $sql)){
                    mysqli_stmt_bind_param($stmt, "iiiddddd", 
                        $_POST["appointment_id"],
                        $_POST["customer_id"],
                        $_POST["staff_id"],
                        $_POST["subtotal"],
                        $_POST["tax_rate"],
                        $_POST["tax_amount"],
                        $_POST["discount_amount"],
                        $_POST["total_amount"]
                    );
                    mysqli_stmt_execute($stmt);
                    $billing_id = mysqli_insert_id($conn);
                    
                    // Insert billing items
                    foreach($_POST["services"] as $service){
                        $sql = "INSERT INTO billing_items (billing_id, service_id, quantity, unit_price, total_amount) 
                                VALUES (?, ?, ?, ?, ?)";
                        if($stmt = mysqli_prepare($conn, $sql)){
                            mysqli_stmt_bind_param($stmt, "iiidd", 
                                $billing_id,
                                $service["id"],
                                $service["quantity"],
                                $service["price"],
                                $service["total"]
                            );
                            mysqli_stmt_execute($stmt);
                        }
                    }
                    
                    // Generate invoice
                    $invoice_number = "INV-" . date("Ymd") . "-" . str_pad($billing_id, 4, "0", STR_PAD_LEFT);
                    $sql = "INSERT INTO invoices (billing_id, invoice_number, invoice_date, due_date, notes) 
                            VALUES (?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), ?)";
                    if($stmt = mysqli_prepare($conn, $sql)){
                        mysqli_stmt_bind_param($stmt, "iss", 
                            $billing_id,
                            $invoice_number,
                            $_POST["notes"]
                        );
                        mysqli_stmt_execute($stmt);
                    }
                    
                    // Process payment if provided
                    if(isset($_POST["payment_method"]) && !empty($_POST["payment_method"])){
                        $sql = "INSERT INTO payments (billing_id, payment_method, amount, payment_date, status) 
                                VALUES (?, ?, ?, NOW(), 'completed')";
                        if($stmt = mysqli_prepare($conn, $sql)){
                            mysqli_stmt_bind_param($stmt, "isd", 
                                $billing_id,
                                $_POST["payment_method"],
                                $_POST["total_amount"]
                            );
                            mysqli_stmt_execute($stmt);
                            
                            // Update billing status to paid
                            $sql = "UPDATE billing SET status = 'paid' WHERE id = ?";
                            if($stmt = mysqli_prepare($conn, $sql)){
                                mysqli_stmt_bind_param($stmt, "i", $billing_id);
                                mysqli_stmt_execute($stmt);
                            }
                        }
                    }
                    
                    // Commit transaction
                    mysqli_commit($conn);
                    header("location: billing.php?success=1");
                    exit();
                }
            } catch (Exception $e) {
                // Rollback transaction on error
                mysqli_rollback($conn);
                $error = "Error creating billing: " . $e->getMessage();
            }
        }
    }
}

// Fetch all billings with related information
$billings = array();
$sql = "SELECT b.*, 
        c.name as customer_name,
        s.name as staff_name,
        i.invoice_number,
        i.invoice_date,
        i.due_date,
        (SELECT SUM(amount) FROM payments WHERE billing_id = b.id AND status = 'completed') as paid_amount
        FROM billing b
        LEFT JOIN customers c ON b.customer_id = c.id
        LEFT JOIN staff s ON b.staff_id = s.id
        LEFT JOIN invoices i ON b.id = i.billing_id
        ORDER BY b.created_at DESC";
$result = mysqli_query($conn, $sql);
while($row = mysqli_fetch_assoc($result)){
    $billings[] = $row;
}

// Fetch active discounts
$discounts = array();
$sql = "SELECT * FROM discounts WHERE status = 'active' AND CURDATE() BETWEEN start_date AND end_date";
$result = mysqli_query($conn, $sql);
while($row = mysqli_fetch_assoc($result)){
    $discounts[] = $row;
}

// Fetch tax rates
$tax_rates = array();
$sql = "SELECT * FROM tax_rates WHERE status = 'active'";
$result = mysqli_query($conn, $sql);
while($row = mysqli_fetch_assoc($result)){
    $tax_rates[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing - Salon Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
            padding-top: 20px;
        }
        .sidebar a {
            color: #fff;
            text-decoration: none;
            padding: 10px 15px;
            display: block;
        }
        .sidebar a:hover {
            background-color: #495057;
        }
        .main-content {
            padding: 20px;
        }
        .billing-card {
            margin-bottom: 20px;
        }
        .invoice-preview {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <h3 class="text-white text-center mb-4">Salon Admin</h3>
                <nav>
                    <a href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
                    <a href="customers.php"><i class="fas fa-users me-2"></i> Customers</a>
                    <a href="appointments.php"><i class="fas fa-calendar-alt me-2"></i> Appointments</a>
                    <a href="services.php"><i class="fas fa-cut me-2"></i> Services</a>
                    <a href="staff.php"><i class="fas fa-user-tie me-2"></i> Staff</a>
                    <a href="billing.php" class="active"><i class="fas fa-file-invoice-dollar me-2"></i> Billing</a>
                    <a href="reports.php"><i class="fas fa-chart-bar me-2"></i> Reports</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Billing & Invoicing</h2>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBillingModal">
                        <i class="fas fa-plus"></i> Create New Billing
                    </button>
                </div>

                <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if(isset($_GET["success"])): ?>
                <div class="alert alert-success">Billing created successfully!</div>
                <?php endif; ?>

                <!-- Billing List -->
                <div class="row">
                    <?php foreach($billings as $billing): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card billing-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="card-title mb-0">
                                        Invoice #<?php echo htmlspecialchars($billing["invoice_number"]); ?>
                                    </h5>
                                    <span class="badge bg-<?php echo $billing["status"] == "paid" ? "success" : "warning"; ?>">
                                        <?php echo ucfirst($billing["status"]); ?>
                                    </span>
                                </div>
                                
                                <div class="mb-3">
                                    <p class="mb-1">
                                        <strong>Customer:</strong> <?php echo htmlspecialchars($billing["customer_name"]); ?>
                                    </p>
                                    <p class="mb-1">
                                        <strong>Staff:</strong> <?php echo htmlspecialchars($billing["staff_name"]); ?>
                                    </p>
                                    <p class="mb-1">
                                        <strong>Date:</strong> <?php echo date("M d, Y", strtotime($billing["invoice_date"])); ?>
                                    </p>
                                    <p class="mb-1">
                                        <strong>Due Date:</strong> <?php echo date("M d, Y", strtotime($billing["due_date"])); ?>
                                    </p>
                                </div>
                                
                                <div class="mb-3">
                                    <p class="mb-1">
                                        <strong>Subtotal:</strong> $<?php echo number_format($billing["subtotal"], 2); ?>
                                    </p>
                                    <p class="mb-1">
                                        <strong>Tax:</strong> $<?php echo number_format($billing["tax_amount"], 2); ?>
                                    </p>
                                    <?php if($billing["discount_amount"] > 0): ?>
                                    <p class="mb-1">
                                        <strong>Discount:</strong> $<?php echo number_format($billing["discount_amount"], 2); ?>
                                    </p>
                                    <?php endif; ?>
                                    <p class="mb-1">
                                        <strong>Total:</strong> $<?php echo number_format($billing["total_amount"], 2); ?>
                                    </p>
                                    <?php if($billing["paid_amount"]): ?>
                                    <p class="mb-1">
                                        <strong>Paid:</strong> $<?php echo number_format($billing["paid_amount"], 2); ?>
                                    </p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-primary view-invoice" 
                                            data-bs-toggle="modal" data-bs-target="#viewInvoiceModal"
                                            data-id="<?php echo $billing["id"]; ?>">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <button type="button" class="btn btn-sm btn-success add-payment" 
                                            data-bs-toggle="modal" data-bs-target="#addPaymentModal"
                                            data-id="<?php echo $billing["id"]; ?>"
                                            data-amount="<?php echo $billing["total_amount"] - $billing["paid_amount"]; ?>">
                                        <i class="fas fa-money-bill"></i> Payment
                                    </button>
                                    <a href="print_invoice.php?id=<?php echo $billing["id"]; ?>" 
                                       class="btn btn-sm btn-info" target="_blank">
                                        <i class="fas fa-print"></i> Print
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

    <!-- Create Billing Modal -->
    <div class="modal fade" id="createBillingModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Billing</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="billing.php" method="post" id="billingForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_billing">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Customer</label>
                                    <select class="form-select" name="customer_id" required>
                                        <option value="">Select Customer</option>
                                        <?php
                                        $sql = "SELECT id, name FROM customers ORDER BY name";
                                        $result = mysqli_query($conn, $sql);
                                        while($row = mysqli_fetch_assoc($result)){
                                            echo "<option value='" . $row["id"] . "'>" . htmlspecialchars($row["name"]) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Staff Member</label>
                                    <select class="form-select" name="staff_id" required>
                                        <option value="">Select Staff</option>
                                        <?php
                                        $sql = "SELECT id, name FROM staff WHERE status = 'active' ORDER BY name";
                                        $result = mysqli_query($conn, $sql);
                                        while($row = mysqli_fetch_assoc($result)){
                                            echo "<option value='" . $row["id"] . "'>" . htmlspecialchars($row["name"]) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Appointment</label>
                                    <select class="form-select" name="appointment_id" required>
                                        <option value="">Select Appointment</option>
                                        <?php
                                        $sql = "SELECT a.id, a.appointment_date, a.appointment_time, c.name as customer_name 
                                                FROM appointments a 
                                                JOIN customers c ON a.customer_id = c.id 
                                                WHERE a.status = 'completed' 
                                                ORDER BY a.appointment_date DESC, a.appointment_time DESC";
                                        $result = mysqli_query($conn, $sql);
                                        while($row = mysqli_fetch_assoc($result)){
                                            echo "<option value='" . $row["id"] . "'>" . 
                                                 date("M d, Y", strtotime($row["appointment_date"])) . " " . 
                                                 date("g:i A", strtotime($row["appointment_time"])) . " - " . 
                                                 htmlspecialchars($row["customer_name"]) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Discount Code</label>
                                    <select class="form-select" name="discount_code" id="discountCode">
                                        <option value="">No Discount</option>
                                        <?php foreach($discounts as $discount): ?>
                                        <option value="<?php echo $discount["code"]; ?>" 
                                                data-type="<?php echo $discount["discount_type"]; ?>"
                                                data-value="<?php echo $discount["discount_value"]; ?>"
                                                data-min="<?php echo $discount["minimum_purchase"]; ?>">
                                            <?php echo htmlspecialchars($discount["code"]); ?> - 
                                            <?php echo $discount["discount_type"] == "percentage" ? 
                                                  $discount["discount_value"] . "%" : 
                                                  "$" . $discount["discount_value"]; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <h6 class="mt-4">Services</h6>
                        <div class="table-responsive">
                            <table class="table table-sm" id="servicesTable">
                                <thead>
                                    <tr>
                                        <th>Service</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <select class="form-select form-select-sm service-select" required>
                                                <option value="">Select Service</option>
                                                <?php
                                                $sql = "SELECT id, name, price FROM services ORDER BY name";
                                                $result = mysqli_query($conn, $sql);
                                                while($row = mysqli_fetch_assoc($result)){
                                                    echo "<option value='" . $row["id"] . "' data-price='" . $row["price"] . "'>" . 
                                                         htmlspecialchars($row["name"]) . " ($" . number_format($row["price"], 2) . ")</option>";
                                                }
                                                ?>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm service-price" 
                                                   readonly>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm service-quantity" 
                                                   value="1" min="1" required>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm service-total" 
                                                   readonly>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-danger remove-service">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="5">
                                            <button type="button" class="btn btn-sm btn-secondary" id="addService">
                                                <i class="fas fa-plus"></i> Add Service
                                            </button>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Notes</label>
                                    <textarea class="form-control" name="notes" rows="3"></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="invoice-preview">
                                    <h6>Invoice Preview</h6>
                                    <div class="row">
                                        <div class="col-6">
                                            <p class="mb-1">Subtotal:</p>
                                            <p class="mb-1">Tax (<?php echo $tax_rates[0]["rate"]; ?>%):</p>
                                            <p class="mb-1">Discount:</p>
                                            <p class="mb-1"><strong>Total:</strong></p>
                                        </div>
                                        <div class="col-6 text-end">
                                            <p class="mb-1" id="previewSubtotal">$0.00</p>
                                            <p class="mb-1" id="previewTax">$0.00</p>
                                            <p class="mb-1" id="previewDiscount">$0.00</p>
                                            <p class="mb-1"><strong id="previewTotal">$0.00</strong></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Payment Method</label>
                                    <select class="form-select" name="payment_method">
                                        <option value="">No Payment</option>
                                        <option value="cash">Cash</option>
                                        <option value="credit_card">Credit Card</option>
                                        <option value="debit_card">Debit Card</option>
                                        <option value="mobile_payment">Mobile Payment</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <input type="hidden" name="subtotal" id="subtotal">
                        <input type="hidden" name="tax_rate" value="<?php echo $tax_rates[0]["rate"]; ?>">
                        <input type="hidden" name="tax_amount" id="taxAmount">
                        <input type="hidden" name="discount_amount" id="discountAmount">
                        <input type="hidden" name="total_amount" id="totalAmount">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Create Billing</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Invoice Modal -->
    <div class="modal fade" id="viewInvoiceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">View Invoice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="invoiceContent">
                        <!-- Content will be loaded dynamically -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="#" class="btn btn-primary" id="printInvoice" target="_blank">
                        <i class="fas fa-print"></i> Print Invoice
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Payment Modal -->
    <div class="modal fade" id="addPaymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="process_payment.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="billing_id" id="paymentBillingId">
                        <input type="hidden" name="amount" id="paymentAmount">
                        
                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <select class="form-select" name="payment_method" required>
                                <option value="cash">Cash</option>
                                <option value="credit_card">Credit Card</option>
                                <option value="debit_card">Debit Card</option>
                                <option value="mobile_payment">Mobile Payment</option>
                                <option value="bank_transfer">Bank Transfer</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Amount</label>
                            <input type="number" class="form-control" name="amount" id="paymentAmountInput" 
                                   step="0.01" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Transaction ID</label>
                            <input type="text" class="form-control" name="transaction_id">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle service selection and calculations
        function updateServiceTotal(row) {
            const price = parseFloat(row.querySelector('.service-price').value) || 0;
            const quantity = parseInt(row.querySelector('.service-quantity').value) || 0;
            const total = price * quantity;
            row.querySelector('.service-total').value = total.toFixed(2);
            updateInvoicePreview();
        }

        function updateInvoicePreview() {
            let subtotal = 0;
            document.querySelectorAll('.service-total').forEach(input => {
                subtotal += parseFloat(input.value) || 0;
            });
            
            const taxRate = <?php echo $tax_rates[0]["rate"]; ?>;
            const taxAmount = subtotal * (taxRate / 100);
            
            let discountAmount = 0;
            const discountSelect = document.getElementById('discountCode');
            if(discountSelect.value) {
                const option = discountSelect.options[discountSelect.selectedIndex];
                const discountType = option.dataset.type;
                const discountValue = parseFloat(option.dataset.value);
                const minPurchase = parseFloat(option.dataset.min);
                
                if(subtotal >= minPurchase) {
                    if(discountType === 'percentage') {
                        discountAmount = subtotal * (discountValue / 100);
                    } else {
                        discountAmount = discountValue;
                    }
                }
            }
            
            const total = subtotal + taxAmount - discountAmount;
            
            document.getElementById('previewSubtotal').textContent = '$' + subtotal.toFixed(2);
            document.getElementById('previewTax').textContent = '$' + taxAmount.toFixed(2);
            document.getElementById('previewDiscount').textContent = '$' + discountAmount.toFixed(2);
            document.getElementById('previewTotal').textContent = '$' + total.toFixed(2);
            
            // Update hidden inputs
            document.getElementById('subtotal').value = subtotal.toFixed(2);
            document.getElementById('taxAmount').value = taxAmount.toFixed(2);
            document.getElementById('discountAmount').value = discountAmount.toFixed(2);
            document.getElementById('totalAmount').value = total.toFixed(2);
        }

        // Add new service row
        document.getElementById('addService').addEventListener('click', function() {
            const tbody = document.querySelector('#servicesTable tbody');
            const newRow = tbody.rows[0].cloneNode(true);
            newRow.querySelectorAll('input').forEach(input => input.value = '');
            newRow.querySelector('select').value = '';
            tbody.appendChild(newRow);
        });

        // Remove service row
        document.addEventListener('click', function(e) {
            if(e.target.classList.contains('remove-service')) {
                const tbody = document.querySelector('#servicesTable tbody');
                if(tbody.rows.length > 1) {
                    e.target.closest('tr').remove();
                    updateInvoicePreview();
                }
            }
        });

        // Handle service selection
        document.addEventListener('change', function(e) {
            if(e.target.classList.contains('service-select')) {
                const row = e.target.closest('tr');
                const option = e.target.options[e.target.selectedIndex];
                const price = option.dataset.price || 0;
                row.querySelector('.service-price').value = price;
                updateServiceTotal(row);
            }
        });

        // Handle quantity change
        document.addEventListener('input', function(e) {
            if(e.target.classList.contains('service-quantity')) {
                updateServiceTotal(e.target.closest('tr'));
            }
        });

        // Handle discount code change
        document.getElementById('discountCode').addEventListener('change', updateInvoicePreview);

        // Handle view invoice
        document.querySelectorAll('.view-invoice').forEach(button => {
            button.addEventListener('click', function() {
                const billingId = this.dataset.id;
                const invoiceContent = document.getElementById('invoiceContent');
                const printButton = document.getElementById('printInvoice');
                
                fetch(`get_invoice.php?id=${billingId}`)
                    .then(response => response.text())
                    .then(html => {
                        invoiceContent.innerHTML = html;
                        printButton.href = `print_invoice.php?id=${billingId}`;
                    });
            });
        });

        // Handle add payment
        document.querySelectorAll('.add-payment').forEach(button => {
            button.addEventListener('click', function() {
                const billingId = this.dataset.id;
                const amount = this.dataset.amount;
                
                document.getElementById('paymentBillingId').value = billingId;
                document.getElementById('paymentAmount').value = amount;
                document.getElementById('paymentAmountInput').value = amount;
            });
        });
    </script>
</body>
</html> 
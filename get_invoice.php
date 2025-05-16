<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

require_once "config/database.php";

if(isset($_GET["id"])){
    $billing_id = $_GET["id"];
    
    // Fetch billing details
    $sql = "SELECT b.*, 
            c.name as customer_name, c.email as customer_email, c.phone as customer_phone, c.address as customer_address,
            s.name as staff_name,
            i.invoice_number, i.invoice_date, i.due_date, i.notes as invoice_notes
            FROM billing b
            LEFT JOIN customers c ON b.customer_id = c.id
            LEFT JOIN staff s ON b.staff_id = s.id
            LEFT JOIN invoices i ON b.id = i.billing_id
            WHERE b.id = ?";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $billing_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $billing = mysqli_fetch_assoc($result);
        
        if($billing){
            // Fetch billing items
            $sql = "SELECT bi.*, s.name as service_name 
                    FROM billing_items bi
                    LEFT JOIN services s ON bi.service_id = s.id
                    WHERE bi.billing_id = ?";
            
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "i", $billing_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $items = array();
                while($row = mysqli_fetch_assoc($result)){
                    $items[] = $row;
                }
                
                // Fetch payments
                $sql = "SELECT * FROM payments WHERE billing_id = ? AND status = 'completed'";
                if($stmt = mysqli_prepare($conn, $sql)){
                    mysqli_stmt_bind_param($stmt, "i", $billing_id);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    $payments = array();
                    while($row = mysqli_fetch_assoc($result)){
                        $payments[] = $row;
                    }
                    
                    // Calculate total paid amount
                    $total_paid = 0;
                    foreach($payments as $payment){
                        $total_paid += $payment["amount"];
                    }
                    
                    // Display invoice
                    ?>
                    <div class="invoice-header text-center mb-4">
                        <h4>INVOICE</h4>
                        <p class="mb-1">Invoice #: <?php echo htmlspecialchars($billing["invoice_number"]); ?></p>
                        <p class="mb-1">Date: <?php echo date("F d, Y", strtotime($billing["invoice_date"])); ?></p>
                        <p class="mb-1">Due Date: <?php echo date("F d, Y", strtotime($billing["due_date"])); ?></p>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Bill To:</h6>
                            <p class="mb-1"><?php echo htmlspecialchars($billing["customer_name"]); ?></p>
                            <p class="mb-1"><?php echo htmlspecialchars($billing["customer_email"]); ?></p>
                            <p class="mb-1"><?php echo htmlspecialchars($billing["customer_phone"]); ?></p>
                            <p class="mb-1"><?php echo htmlspecialchars($billing["customer_address"]); ?></p>
                        </div>
                        <div class="col-md-6 text-end">
                            <h6>Service Provider:</h6>
                            <p class="mb-1"><?php echo htmlspecialchars($billing["staff_name"]); ?></p>
                        </div>
                    </div>
                    
                    <div class="table-responsive mb-4">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Service</th>
                                    <th class="text-end">Price</th>
                                    <th class="text-center">Quantity</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item["service_name"]); ?></td>
                                    <td class="text-end">$<?php echo number_format($item["unit_price"], 2); ?></td>
                                    <td class="text-center"><?php echo $item["quantity"]; ?></td>
                                    <td class="text-end">$<?php echo number_format($item["total_amount"], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                    <td class="text-end">$<?php echo number_format($billing["subtotal"], 2); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end">Tax (<?php echo $billing["tax_rate"]; ?>%):</td>
                                    <td class="text-end">$<?php echo number_format($billing["tax_amount"], 2); ?></td>
                                </tr>
                                <?php if($billing["discount_amount"] > 0): ?>
                                <tr>
                                    <td colspan="3" class="text-end">Discount:</td>
                                    <td class="text-end">$<?php echo number_format($billing["discount_amount"], 2); ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                    <td class="text-end"><strong>$<?php echo number_format($billing["total_amount"], 2); ?></strong></td>
                                </tr>
                                <?php if($total_paid > 0): ?>
                                <tr>
                                    <td colspan="3" class="text-end">Paid Amount:</td>
                                    <td class="text-end">$<?php echo number_format($total_paid, 2); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Balance Due:</strong></td>
                                    <td class="text-end"><strong>$<?php echo number_format($billing["total_amount"] - $total_paid, 2); ?></strong></td>
                                </tr>
                                <?php endif; ?>
                            </tfoot>
                        </table>
                    </div>
                    
                    <?php if(!empty($payments)): ?>
                    <div class="mb-4">
                        <h6>Payment History:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Method</th>
                                        <th>Amount</th>
                                        <th>Transaction ID</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($payments as $payment): ?>
                                    <tr>
                                        <td><?php echo date("M d, Y", strtotime($payment["payment_date"])); ?></td>
                                        <td><?php echo ucfirst(str_replace("_", " ", $payment["payment_method"])); ?></td>
                                        <td>$<?php echo number_format($payment["amount"], 2); ?></td>
                                        <td><?php echo htmlspecialchars($payment["transaction_id"]); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if(!empty($billing["invoice_notes"])): ?>
                    <div class="mb-4">
                        <h6>Notes:</h6>
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($billing["invoice_notes"])); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="text-center mt-4">
                        <p class="mb-0">Thank you for your business!</p>
                    </div>
                    <?php
                }
            }
        }
    }
}
?> 
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
                    <!DOCTYPE html>
                    <html lang="en">
                    <head>
                        <meta charset="UTF-8">
                        <meta name="viewport" content="width=device-width, initial-scale=1.0">
                        <title>Invoice #<?php echo htmlspecialchars($billing["invoice_number"]); ?></title>
                        <style>
                            body {
                                font-family: Arial, sans-serif;
                                margin: 0;
                                padding: 20px;
                                color: #333;
                            }
                            .invoice-header {
                                text-align: center;
                                margin-bottom: 30px;
                            }
                            .invoice-header h1 {
                                margin: 0;
                                color: #2c3e50;
                            }
                            .invoice-details {
                                margin-bottom: 30px;
                            }
                            .invoice-details p {
                                margin: 5px 0;
                            }
                            .customer-details, .staff-details {
                                margin-bottom: 30px;
                            }
                            .customer-details h3, .staff-details h3 {
                                margin: 0 0 10px 0;
                                color: #2c3e50;
                            }
                            table {
                                width: 100%;
                                border-collapse: collapse;
                                margin-bottom: 30px;
                            }
                            th, td {
                                padding: 10px;
                                text-align: left;
                                border-bottom: 1px solid #ddd;
                            }
                            th {
                                background-color: #f8f9fa;
                            }
                            .text-end {
                                text-align: right;
                            }
                            .text-center {
                                text-align: center;
                            }
                            .total-row {
                                font-weight: bold;
                            }
                            .payment-history {
                                margin-top: 30px;
                            }
                            .payment-history h3 {
                                margin: 0 0 10px 0;
                                color: #2c3e50;
                            }
                            .notes {
                                margin-top: 30px;
                            }
                            .notes h3 {
                                margin: 0 0 10px 0;
                                color: #2c3e50;
                            }
                            .footer {
                                text-align: center;
                                margin-top: 50px;
                                padding-top: 20px;
                                border-top: 1px solid #ddd;
                            }
                            @media print {
                                body {
                                    padding: 0;
                                }
                                .no-print {
                                    display: none;
                                }
                            }
                        </style>
                    </head>
                    <body>
                        <div class="no-print" style="text-align: right; margin-bottom: 20px;">
                            <button onclick="window.print()">Print Invoice</button>
                        </div>
                        
                        <div class="invoice-header">
                            <h1>INVOICE</h1>
                            <p>Invoice #: <?php echo htmlspecialchars($billing["invoice_number"]); ?></p>
                            <p>Date: <?php echo date("F d, Y", strtotime($billing["invoice_date"])); ?></p>
                            <p>Due Date: <?php echo date("F d, Y", strtotime($billing["due_date"])); ?></p>
                        </div>
                        
                        <div class="invoice-details">
                            <div style="float: left; width: 50%;">
                                <div class="customer-details">
                                    <h3>Bill To:</h3>
                                    <p><?php echo htmlspecialchars($billing["customer_name"]); ?></p>
                                    <p><?php echo htmlspecialchars($billing["customer_email"]); ?></p>
                                    <p><?php echo htmlspecialchars($billing["customer_phone"]); ?></p>
                                    <p><?php echo htmlspecialchars($billing["customer_address"]); ?></p>
                                </div>
                            </div>
                            <div style="float: right; width: 50%; text-align: right;">
                                <div class="staff-details">
                                    <h3>Service Provider:</h3>
                                    <p><?php echo htmlspecialchars($billing["staff_name"]); ?></p>
                                </div>
                            </div>
                            <div style="clear: both;"></div>
                        </div>
                        
                        <table>
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
                                    <td colspan="3" class="text-end">Subtotal:</td>
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
                                <tr class="total-row">
                                    <td colspan="3" class="text-end">Total:</td>
                                    <td class="text-end">$<?php echo number_format($billing["total_amount"], 2); ?></td>
                                </tr>
                                <?php if($total_paid > 0): ?>
                                <tr>
                                    <td colspan="3" class="text-end">Paid Amount:</td>
                                    <td class="text-end">$<?php echo number_format($total_paid, 2); ?></td>
                                </tr>
                                <tr class="total-row">
                                    <td colspan="3" class="text-end">Balance Due:</td>
                                    <td class="text-end">$<?php echo number_format($billing["total_amount"] - $total_paid, 2); ?></td>
                                </tr>
                                <?php endif; ?>
                            </tfoot>
                        </table>
                        
                        <?php if(!empty($payments)): ?>
                        <div class="payment-history">
                            <h3>Payment History:</h3>
                            <table>
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
                        <?php endif; ?>
                        
                        <?php if(!empty($billing["invoice_notes"])): ?>
                        <div class="notes">
                            <h3>Notes:</h3>
                            <p><?php echo nl2br(htmlspecialchars($billing["invoice_notes"])); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="footer">
                            <p>Thank you for your business!</p>
                        </div>
                    </body>
                    </html>
                    <?php
                }
            }
        }
    }
}
?> 
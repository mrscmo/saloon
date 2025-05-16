<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

require_once "config/database.php";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $billing_id = $_POST["billing_id"];
    $payment_method = $_POST["payment_method"];
    $amount = $_POST["amount"];
    $transaction_id = $_POST["transaction_id"] ?? null;
    $notes = $_POST["notes"] ?? null;
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Insert payment record
        $sql = "INSERT INTO payments (billing_id, payment_method, amount, transaction_id, notes, payment_date, status) 
                VALUES (?, ?, ?, ?, ?, NOW(), 'completed')";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "isdss", 
                $billing_id,
                $payment_method,
                $amount,
                $transaction_id,
                $notes
            );
            mysqli_stmt_execute($stmt);
            
            // Get total paid amount
            $sql = "SELECT SUM(amount) as total_paid FROM payments WHERE billing_id = ? AND status = 'completed'";
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "i", $billing_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($result);
                $total_paid = $row["total_paid"];
                
                // Get billing total
                $sql = "SELECT total_amount FROM billing WHERE id = ?";
                if($stmt = mysqli_prepare($conn, $sql)){
                    mysqli_stmt_bind_param($stmt, "i", $billing_id);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    $row = mysqli_fetch_assoc($result);
                    $total_amount = $row["total_amount"];
                    
                    // Update billing status if fully paid
                    if($total_paid >= $total_amount){
                        $sql = "UPDATE billing SET status = 'paid' WHERE id = ?";
                        if($stmt = mysqli_prepare($conn, $sql)){
                            mysqli_stmt_bind_param($stmt, "i", $billing_id);
                            mysqli_stmt_execute($stmt);
                        }
                    }
                }
            }
        }
        
        // Commit transaction
        mysqli_commit($conn);
        header("location: billing.php?success=2");
        exit();
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        header("location: billing.php?error=" . urlencode("Error processing payment: " . $e->getMessage()));
        exit();
    }
}
?> 
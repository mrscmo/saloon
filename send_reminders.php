<?php
require_once "config/database.php";

// Function to send email reminder
function sendEmailReminder($customer_email, $customer_name, $appointment_date, $appointment_time, $service_name) {
    $subject = "Reminder: Your Upcoming Salon Appointment";
    $message = "Dear " . $customer_name . ",\n\n";
    $message .= "This is a reminder for your upcoming appointment:\n\n";
    $message .= "Service: " . $service_name . "\n";
    $message .= "Date: " . date('F j, Y', strtotime($appointment_date)) . "\n";
    $message .= "Time: " . date('g:i A', strtotime($appointment_time)) . "\n\n";
    $message .= "We look forward to seeing you!\n\n";
    $message .= "Best regards,\nSalon Management Team";
    
    $headers = "From: salon@example.com\r\n";
    $headers .= "Reply-To: salon@example.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    return mail($customer_email, $subject, $message, $headers);
}

// Function to send SMS reminder (placeholder - implement actual SMS gateway)
function sendSMSReminder($phone_number, $customer_name, $appointment_date, $appointment_time, $service_name) {
    $message = "Reminder: Your appointment for " . $service_name . " is scheduled for " . 
               date('F j, Y', strtotime($appointment_date)) . " at " . 
               date('g:i A', strtotime($appointment_time)) . ". We look forward to seeing you!";
    
    // TODO: Implement actual SMS gateway integration
    // For now, just log the message
    error_log("SMS to " . $phone_number . ": " . $message);
    return true;
}

// Get pending reminders
$sql = "SELECT r.*, a.appointment_date, a.appointment_time, 
               c.name as customer_name, c.email, c.phone,
               s.name as service_name
        FROM reminders r
        JOIN appointments a ON r.appointment_id = a.id
        JOIN customers c ON a.customer_id = c.id
        JOIN services s ON a.service_id = s.id
        WHERE r.status = 'pending' 
        AND r.reminder_time <= NOW()";
$result = mysqli_query($conn, $sql);

while($reminder = mysqli_fetch_assoc($result)) {
    $success = false;
    
    if($reminder['reminder_type'] == 'email') {
        $success = sendEmailReminder(
            $reminder['email'],
            $reminder['customer_name'],
            $reminder['appointment_date'],
            $reminder['appointment_time'],
            $reminder['service_name']
        );
    } else {
        $success = sendSMSReminder(
            $reminder['phone'],
            $reminder['customer_name'],
            $reminder['appointment_date'],
            $reminder['appointment_time'],
            $reminder['service_name']
        );
    }
    
    // Update reminder status
    $update_sql = "UPDATE reminders SET status = ? WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $update_sql)) {
        $status = $success ? 'sent' : 'failed';
        mysqli_stmt_bind_param($stmt, "si", $status, $reminder['id']);
        mysqli_stmt_execute($stmt);
    }
}

// Update appointment reminder_sent flag
$sql = "UPDATE appointments a 
        JOIN reminders r ON a.id = r.appointment_id 
        SET a.reminder_sent = TRUE 
        WHERE r.status = 'sent'";
mysqli_query($conn, $sql);
?> 
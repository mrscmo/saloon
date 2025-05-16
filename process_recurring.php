<?php
require_once "config/database.php";

// Function to get next appointment date based on pattern
function getNextAppointmentDate($current_date, $pattern) {
    switch($pattern) {
        case 'daily':
            return date('Y-m-d', strtotime($current_date . ' +1 day'));
        case 'weekly':
            return date('Y-m-d', strtotime($current_date . ' +1 week'));
        case 'biweekly':
            return date('Y-m-d', strtotime($current_date . ' +2 weeks'));
        case 'monthly':
            return date('Y-m-d', strtotime($current_date . ' +1 month'));
        default:
            return null;
    }
}

// Get recurring appointments that need to be processed
$sql = "SELECT a.*, c.name as customer_name, s.name as service_name, st.name as staff_name
        FROM appointments a
        JOIN customers c ON a.customer_id = c.id
        JOIN services s ON a.service_id = s.id
        JOIN staff st ON a.staff_id = st.id
        WHERE a.is_recurring = TRUE
        AND a.recurring_end_date >= CURDATE()
        AND NOT EXISTS (
            SELECT 1 FROM appointments a2
            WHERE a2.customer_id = a.customer_id
            AND a2.service_id = a.service_id
            AND a2.staff_id = a.staff_id
            AND a2.appointment_date = (
                CASE a.recurring_pattern
                    WHEN 'daily' THEN DATE_ADD(a.appointment_date, INTERVAL 1 DAY)
                    WHEN 'weekly' THEN DATE_ADD(a.appointment_date, INTERVAL 1 WEEK)
                    WHEN 'biweekly' THEN DATE_ADD(a.appointment_date, INTERVAL 2 WEEK)
                    WHEN 'monthly' THEN DATE_ADD(a.appointment_date, INTERVAL 1 MONTH)
                END
            )
        )";
$result = mysqli_query($conn, $sql);

while($appointment = mysqli_fetch_assoc($result)) {
    $next_date = getNextAppointmentDate($appointment['appointment_date'], $appointment['recurring_pattern']);
    
    if($next_date && $next_date <= $appointment['recurring_end_date']) {
        // Check if staff is available at the new time
        $check_sql = "SELECT COUNT(*) as count FROM appointments 
                     WHERE staff_id = ? AND appointment_date = ? AND appointment_time = ?";
        if($stmt = mysqli_prepare($conn, $check_sql)) {
            mysqli_stmt_bind_param($stmt, "iss", 
                $appointment['staff_id'], 
                $next_date, 
                $appointment['appointment_time']
            );
            mysqli_stmt_execute($stmt);
            $result_check = mysqli_stmt_get_result($stmt);
            $count = mysqli_fetch_assoc($result_check)['count'];
            
            if($count == 0) {
                // Create new appointment
                $insert_sql = "INSERT INTO appointments (
                                customer_id, service_id, staff_id, 
                                appointment_date, appointment_time, 
                                status, notes, is_recurring, 
                                recurring_pattern, recurring_end_date
                            ) VALUES (?, ?, ?, ?, ?, 'scheduled', ?, TRUE, ?, ?)";
                
                if($stmt = mysqli_prepare($conn, $insert_sql)) {
                    mysqli_stmt_bind_param($stmt, "iiisssss", 
                        $appointment['customer_id'],
                        $appointment['service_id'],
                        $appointment['staff_id'],
                        $next_date,
                        $appointment['appointment_time'],
                        $appointment['notes'],
                        $appointment['recurring_pattern'],
                        $appointment['recurring_end_date']
                    );
                    
                    if(mysqli_stmt_execute($stmt)) {
                        $new_appointment_id = mysqli_insert_id($conn);
                        
                        // Create reminder for the new appointment
                        $reminder_time = date('Y-m-d H:i:s', strtotime($next_date . ' ' . $appointment['appointment_time'] . ' -1 day'));
                        $reminder_sql = "INSERT INTO reminders (appointment_id, reminder_type, reminder_time) 
                                       VALUES (?, 'email', ?)";
                        if($stmt = mysqli_prepare($conn, $reminder_sql)) {
                            mysqli_stmt_bind_param($stmt, "is", $new_appointment_id, $reminder_time);
                            mysqli_stmt_execute($stmt);
                        }
                    }
                }
            }
        }
    }
}
?> 
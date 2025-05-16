<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

require_once "config/database.php";

// Process delete operation
if(isset($_GET["delete"]) && !empty($_GET["delete"])){
    $sql = "DELETE FROM appointments WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $_GET["delete"]);
        if(mysqli_stmt_execute($stmt)){
            header("location: appointments.php");
            exit();
        }
    }
}

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(isset($_POST["action"])){
        if($_POST["action"] == "add"){
            $sql = "INSERT INTO appointments (customer_id, service_id, staff_id, appointment_date, appointment_time, status, notes, is_recurring, recurring_pattern, recurring_end_date) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "iiisssssss", 
                    $_POST["customer_id"], 
                    $_POST["service_id"], 
                    $_POST["staff_id"], 
                    $_POST["appointment_date"], 
                    $_POST["appointment_time"], 
                    $_POST["status"], 
                    $_POST["notes"],
                    $_POST["is_recurring"],
                    $_POST["recurring_pattern"],
                    $_POST["recurring_end_date"]
                );
                if(mysqli_stmt_execute($stmt)){
                    $appointment_id = mysqli_insert_id($conn);
                    
                    // Create reminder if enabled
                    if(isset($_POST["send_reminder"]) && $_POST["send_reminder"] == "1"){
                        $reminder_time = date('Y-m-d H:i:s', strtotime($_POST["appointment_date"] . ' ' . $_POST["appointment_time"] . ' -1 day'));
                        $sql = "INSERT INTO reminders (appointment_id, reminder_type, reminder_time) VALUES (?, ?, ?)";
                        if($stmt = mysqli_prepare($conn, $sql)){
                            mysqli_stmt_bind_param($stmt, "iss", $appointment_id, $_POST["reminder_type"], $reminder_time);
                            mysqli_stmt_execute($stmt);
                        }
                    }
                    
                    header("location: appointments.php");
                    exit();
                }
            }
        } elseif($_POST["action"] == "edit"){
            $sql = "UPDATE appointments SET 
                    customer_id=?, service_id=?, staff_id=?, 
                    appointment_date=?, appointment_time=?, 
                    status=?, notes=?, is_recurring=?, 
                    recurring_pattern=?, recurring_end_date=? 
                    WHERE id=?";
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "iiisssssssi", 
                    $_POST["customer_id"], 
                    $_POST["service_id"], 
                    $_POST["staff_id"], 
                    $_POST["appointment_date"], 
                    $_POST["appointment_time"], 
                    $_POST["status"], 
                    $_POST["notes"],
                    $_POST["is_recurring"],
                    $_POST["recurring_pattern"],
                    $_POST["recurring_end_date"],
                    $_POST["id"]
                );
                if(mysqli_stmt_execute($stmt)){
                    header("location: appointments.php");
                    exit();
                }
            }
        }
    }
}

// Fetch all appointments with related information
$appointments = array();
$sql = "SELECT a.*, c.name as customer_name, s.name as service_name, st.name as staff_name 
        FROM appointments a 
        JOIN customers c ON a.customer_id = c.id 
        JOIN services s ON a.service_id = s.id 
        JOIN staff st ON a.staff_id = st.id 
        ORDER BY a.appointment_date DESC, a.appointment_time DESC";
$result = mysqli_query($conn, $sql);
while($row = mysqli_fetch_assoc($result)){
    $appointments[] = $row;
}

// Fetch customers for dropdown
$customers = array();
$sql = "SELECT id, name FROM customers ORDER BY name";
$result = mysqli_query($conn, $sql);
while($row = mysqli_fetch_assoc($result)){
    $customers[] = $row;
}

// Fetch services for dropdown
$services = array();
$sql = "SELECT id, name, price, duration FROM services ORDER BY name";
$result = mysqli_query($conn, $sql);
while($row = mysqli_fetch_assoc($result)){
    $services[] = $row;
}

// Fetch staff for dropdown
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
    <title>Appointments - Salon Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css" rel="stylesheet">
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
        .status-badge {
            min-width: 100px;
            text-align: center;
        }
        #calendar {
            margin-top: 20px;
        }
        .fc-event {
            cursor: pointer;
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
                    <a href="customers.php">
                        <i class="fas fa-users"></i>
                        <span>Customers</span>
                    </a>
                    <a href="appointments.php" class="active">
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
                    <h2>Appointments</h2>
                    <div>
                        <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#walkInModal">
                            <i class="fas fa-walking"></i> Walk-in
                        </button>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAppointmentModal">
                            <i class="fas fa-plus"></i> New Appointment
                        </button>
                    </div>
                </div>

                <!-- Calendar View -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div id="calendar"></div>
                    </div>
                </div>

                <!-- List View -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Service</th>
                                        <th>Staff</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                        <th>Recurring</th>
                                        <th>Notes</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($appointments as $appointment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($appointment['customer_name']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['service_name']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['staff_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></td>
                                        <td><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></td>
                                        <td>
                                            <span class="badge status-badge bg-<?php 
                                                echo $appointment['status'] == 'completed' ? 'success' : 
                                                    ($appointment['status'] == 'cancelled' ? 'danger' : 
                                                    ($appointment['status'] == 'walk-in' ? 'warning' : 'primary')); 
                                            ?>">
                                                <?php echo ucfirst($appointment['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if($appointment['is_recurring']): ?>
                                                <span class="badge bg-info">
                                                    <?php echo ucfirst($appointment['recurring_pattern']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($appointment['notes']); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary edit-appointment" 
                                                    data-bs-toggle="modal" data-bs-target="#editAppointmentModal"
                                                    data-id="<?php echo $appointment['id']; ?>"
                                                    data-customer="<?php echo $appointment['customer_id']; ?>"
                                                    data-service="<?php echo $appointment['service_id']; ?>"
                                                    data-staff="<?php echo $appointment['staff_id']; ?>"
                                                    data-date="<?php echo $appointment['appointment_date']; ?>"
                                                    data-time="<?php echo $appointment['appointment_time']; ?>"
                                                    data-status="<?php echo $appointment['status']; ?>"
                                                    data-notes="<?php echo htmlspecialchars($appointment['notes']); ?>"
                                                    data-recurring="<?php echo $appointment['is_recurring']; ?>"
                                                    data-recurring-pattern="<?php echo $appointment['recurring_pattern']; ?>"
                                                    data-recurring-end="<?php echo $appointment['recurring_end_date']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="appointments.php?delete=<?php echo $appointment['id']; ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Are you sure you want to delete this appointment?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Appointment Modal -->
    <div class="modal fade" id="addAppointmentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">New Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="appointments.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label class="form-label">Customer</label>
                            <select class="form-select" name="customer_id" required>
                                <option value="">Select Customer</option>
                                <?php foreach($customers as $customer): ?>
                                <option value="<?php echo $customer['id']; ?>">
                                    <?php echo htmlspecialchars($customer['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Service</label>
                            <select class="form-select" name="service_id" required>
                                <option value="">Select Service</option>
                                <?php foreach($services as $service): ?>
                                <option value="<?php echo $service['id']; ?>">
                                    <?php echo htmlspecialchars($service['name']); ?> 
                                    (<?php echo number_format($service['price'], 2); ?> - 
                                    <?php echo $service['duration']; ?> mins)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Staff</label>
                            <select class="form-select" name="staff_id" required>
                                <option value="">Select Staff</option>
                                <?php foreach($staff as $staff_member): ?>
                                <option value="<?php echo $staff_member['id']; ?>">
                                    <?php echo htmlspecialchars($staff_member['name']); ?> 
                                    (<?php echo htmlspecialchars($staff_member['specialization']); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date</label>
                                <input type="date" class="form-control" name="appointment_date" required 
                                       min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Time</label>
                                <input type="time" class="form-control" name="appointment_time" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" required>
                                <option value="scheduled">Scheduled</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_recurring" id="is_recurring">
                                <label class="form-check-label" for="is_recurring">
                                    Recurring Appointment
                                </label>
                            </div>
                        </div>

                        <div class="recurring-options" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Recurring Pattern</label>
                                <select class="form-select" name="recurring_pattern">
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="biweekly">Bi-weekly</option>
                                    <option value="monthly">Monthly</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">End Date</label>
                                <input type="date" class="form-control" name="recurring_end_date">
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="send_reminder" id="send_reminder" value="1">
                                <label class="form-check-label" for="send_reminder">
                                    Send Reminder
                                </label>
                            </div>
                        </div>

                        <div class="reminder-options" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Reminder Type</label>
                                <select class="form-select" name="reminder_type">
                                    <option value="email">Email</option>
                                    <option value="sms">SMS</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Appointment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Walk-in Modal -->
    <div class="modal fade" id="walkInModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">New Walk-in</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="appointments.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="status" value="walk-in">
                        
                        <div class="mb-3">
                            <label class="form-label">Customer</label>
                            <select class="form-select" name="customer_id" required>
                                <option value="">Select Customer</option>
                                <?php foreach($customers as $customer): ?>
                                <option value="<?php echo $customer['id']; ?>">
                                    <?php echo htmlspecialchars($customer['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Service</label>
                            <select class="form-select" name="service_id" required>
                                <option value="">Select Service</option>
                                <?php foreach($services as $service): ?>
                                <option value="<?php echo $service['id']; ?>">
                                    <?php echo htmlspecialchars($service['name']); ?> 
                                    (<?php echo number_format($service['price'], 2); ?> - 
                                    <?php echo $service['duration']; ?> mins)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Staff</label>
                            <select class="form-select" name="staff_id" required>
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
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success">Add Walk-in</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js"></script>
    <script>
        // Initialize FullCalendar
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'timeGridWeek',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: <?php echo json_encode(array_map(function($appointment) {
                    return [
                        'title' => $appointment['customer_name'] . ' - ' . $appointment['service_name'],
                        'start' => $appointment['appointment_date'] . 'T' . $appointment['appointment_time'],
                        'end' => date('Y-m-d H:i:s', strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time'] . ' +1 hour')),
                        'backgroundColor' => $appointment['status'] == 'completed' ? '#28a745' : 
                            ($appointment['status'] == 'cancelled' ? '#dc3545' : 
                            ($appointment['status'] == 'walk-in' ? '#ffc107' : '#007bff')),
                        'borderColor' => $appointment['status'] == 'completed' ? '#28a745' : 
                            ($appointment['status'] == 'cancelled' ? '#dc3545' : 
                            ($appointment['status'] == 'walk-in' ? '#ffc107' : '#007bff'))
                    ];
                }, $appointments)); ?>,
                eventClick: function(info) {
                    // Handle event click
                    const appointmentId = info.event.id;
                    // You can add logic to show appointment details or edit modal
                }
            });
            calendar.render();
        });

        // Handle recurring appointment options
        document.getElementById('is_recurring').addEventListener('change', function() {
            const recurringOptions = document.querySelector('.recurring-options');
            recurringOptions.style.display = this.checked ? 'block' : 'none';
        });

        // Handle reminder options
        document.getElementById('send_reminder').addEventListener('change', function() {
            const reminderOptions = document.querySelector('.reminder-options');
            reminderOptions.style.display = this.checked ? 'block' : 'none';
        });

        // Handle edit appointment modal
        document.querySelectorAll('.edit-appointment').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const customer = this.getAttribute('data-customer');
                const service = this.getAttribute('data-service');
                const staff = this.getAttribute('data-staff');
                const date = this.getAttribute('data-date');
                const time = this.getAttribute('data-time');
                const status = this.getAttribute('data-status');
                const notes = this.getAttribute('data-notes');
                const recurring = this.getAttribute('data-recurring');
                const recurringPattern = this.getAttribute('data-recurring-pattern');
                const recurringEnd = this.getAttribute('data-recurring-end');

                document.getElementById('edit_id').value = id;
                document.getElementById('edit_customer_id').value = customer;
                document.getElementById('edit_service_id').value = service;
                document.getElementById('edit_staff_id').value = staff;
                document.getElementById('edit_appointment_date').value = date;
                document.getElementById('edit_appointment_time').value = time;
                document.getElementById('edit_status').value = status;
                document.getElementById('edit_notes').value = notes;
                document.getElementById('edit_is_recurring').checked = recurring === '1';
                document.getElementById('edit_recurring_pattern').value = recurringPattern;
                document.getElementById('edit_recurring_end_date').value = recurringEnd;

                const recurringOptions = document.querySelector('.edit-recurring-options');
                recurringOptions.style.display = recurring === '1' ? 'block' : 'none';
            });
        });
    </script>
</body>
</html> 
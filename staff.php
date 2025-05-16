<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

require_once "config/database.php";

// Process delete operation
if(isset($_GET["delete"]) && !empty($_GET["delete"])){
    $sql = "DELETE FROM staff WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $_GET["delete"]);
        if(mysqli_stmt_execute($stmt)){
            header("location: staff.php");
            exit();
        }
    }
}

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(isset($_POST["action"])){
        if($_POST["action"] == "add"){
            $sql = "INSERT INTO staff (name, email, phone, specialization, role, commission_rate, hourly_rate) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "sssssdd", 
                    $_POST["name"], 
                    $_POST["email"], 
                    $_POST["phone"], 
                    $_POST["specialization"],
                    $_POST["role"],
                    $_POST["commission_rate"],
                    $_POST["hourly_rate"]
                );
                if(mysqli_stmt_execute($stmt)){
                    $staff_id = mysqli_insert_id($conn);
                    
                    // Add schedule if provided
                    if(isset($_POST["schedule"]) && is_array($_POST["schedule"])){
                        foreach($_POST["schedule"] as $day => $times){
                            if($times["is_working_day"]){
                                $sql = "INSERT INTO staff_schedules (staff_id, day_of_week, start_time, end_time) 
                                        VALUES (?, ?, ?, ?)";
                                if($stmt = mysqli_prepare($conn, $sql)){
                                    mysqli_stmt_bind_param($stmt, "isss", 
                                        $staff_id, 
                                        $day, 
                                        $times["start_time"], 
                                        $times["end_time"]
                                    );
                                    mysqli_stmt_execute($stmt);
                                }
                            }
                        }
                    }
                    
                    header("location: staff.php");
                    exit();
                }
            }
        } elseif($_POST["action"] == "edit"){
            $sql = "UPDATE staff SET 
                    name=?, email=?, phone=?, specialization=?, 
                    role=?, commission_rate=?, hourly_rate=?, status=? 
                    WHERE id=?";
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "sssssdsis", 
                    $_POST["name"], 
                    $_POST["email"], 
                    $_POST["phone"], 
                    $_POST["specialization"],
                    $_POST["role"],
                    $_POST["commission_rate"],
                    $_POST["hourly_rate"],
                    $_POST["status"],
                    $_POST["id"]
                );
                if(mysqli_stmt_execute($stmt)){
                    // Update schedule if provided
                    if(isset($_POST["schedule"]) && is_array($_POST["schedule"])){
                        // Delete existing schedule
                        $sql = "DELETE FROM staff_schedules WHERE staff_id = ?";
                        if($stmt = mysqli_prepare($conn, $sql)){
                            mysqli_stmt_bind_param($stmt, "i", $_POST["id"]);
                            mysqli_stmt_execute($stmt);
                        }
                        
                        // Add new schedule
                        foreach($_POST["schedule"] as $day => $times){
                            if($times["is_working_day"]){
                                $sql = "INSERT INTO staff_schedules (staff_id, day_of_week, start_time, end_time) 
                                        VALUES (?, ?, ?, ?)";
                                if($stmt = mysqli_prepare($conn, $sql)){
                                    mysqli_stmt_bind_param($stmt, "isss", 
                                        $_POST["id"], 
                                        $day, 
                                        $times["start_time"], 
                                        $times["end_time"]
                                    );
                                    mysqli_stmt_execute($stmt);
                                }
                            }
                        }
                    }
                    
                    header("location: staff.php");
                    exit();
                }
            }
        }
    }
}

// Fetch all staff members with their performance metrics
$staff = array();
$sql = "SELECT s.*, 
        (SELECT COUNT(*) FROM appointments WHERE staff_id = s.id AND status = 'completed') as completed_appointments,
        (SELECT COUNT(*) FROM appointments WHERE staff_id = s.id AND status = 'cancelled') as cancelled_appointments,
        (SELECT COUNT(*) FROM appointments WHERE staff_id = s.id AND status = 'no-show') as no_show_appointments,
        (SELECT SUM(sc.commission_amount) FROM staff_commissions sc WHERE sc.staff_id = s.id AND sc.status = 'pending') as pending_commissions
        FROM staff s 
        ORDER BY s.name";
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
    <title>Staff - Salon Management System</title>
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
        .staff-card {
            margin-bottom: 20px;
        }
        .staff-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-weight: bold;
            font-size: 1.5rem;
        }
        .performance-stats {
            font-size: 0.9rem;
        }
        .schedule-table th, .schedule-table td {
            padding: 0.5rem;
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
                    <a href="appointments.php">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Appointments</span>
                    </a>
                    <a href="services.php">
                        <i class="fas fa-cut"></i>
                        <span>Services</span>
                    </a>
                    <a href="staff.php" class="active">
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
                    <h2>Staff Members</h2>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStaffModal">
                        <i class="fas fa-plus"></i> Add Staff Member
                    </button>
                </div>

                <!-- Staff Cards -->
                <div class="row">
                    <?php foreach($staff as $member): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card staff-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="staff-avatar me-3">
                                        <?php echo strtoupper(substr($member['name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <h5 class="card-title mb-0"><?php echo htmlspecialchars($member['name']); ?></h5>
                                        <p class="text-muted mb-0">
                                            <?php echo htmlspecialchars($member['role']); ?> - 
                                            <?php echo htmlspecialchars($member['specialization']); ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <p class="mb-1">
                                        <i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($member['email']); ?>
                                    </p>
                                    <p class="mb-1">
                                        <i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($member['phone']); ?>
                                    </p>
                                </div>
                                
                                <div class="performance-stats mb-3">
                                    <div class="row">
                                        <div class="col-6">
                                            <p class="mb-1">
                                                <strong>Completed:</strong> <?php echo $member['completed_appointments']; ?>
                                            </p>
                                            <p class="mb-1">
                                                <strong>Cancelled:</strong> <?php echo $member['cancelled_appointments']; ?>
                                            </p>
                                        </div>
                                        <div class="col-6">
                                            <p class="mb-1">
                                                <strong>No Shows:</strong> <?php echo $member['no_show_appointments']; ?>
                                            </p>
                                            <p class="mb-1">
                                                <strong>Pending Commissions:</strong> 
                                                $<?php echo number_format($member['pending_commissions'], 2); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-primary edit-staff" 
                                            data-bs-toggle="modal" data-bs-target="#editStaffModal"
                                            data-id="<?php echo $member['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($member['name']); ?>"
                                            data-email="<?php echo htmlspecialchars($member['email']); ?>"
                                            data-phone="<?php echo htmlspecialchars($member['phone']); ?>"
                                            data-specialization="<?php echo htmlspecialchars($member['specialization']); ?>"
                                            data-role="<?php echo htmlspecialchars($member['role']); ?>"
                                            data-commission="<?php echo $member['commission_rate']; ?>"
                                            data-hourly="<?php echo $member['hourly_rate']; ?>"
                                            data-status="<?php echo $member['status']; ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button type="button" class="btn btn-sm btn-info view-schedule" 
                                            data-bs-toggle="modal" data-bs-target="#viewScheduleModal"
                                            data-id="<?php echo $member['id']; ?>">
                                        <i class="fas fa-calendar"></i> Schedule
                                    </button>
                                    <button type="button" class="btn btn-sm btn-success view-attendance" 
                                            data-bs-toggle="modal" data-bs-target="#viewAttendanceModal"
                                            data-id="<?php echo $member['id']; ?>">
                                        <i class="fas fa-clock"></i> Attendance
                                    </button>
                                    <a href="staff.php?delete=<?php echo $member['id']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Are you sure you want to delete this staff member?')">
                                        <i class="fas fa-trash"></i>
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

    <!-- Add Staff Modal -->
    <div class="modal fade" id="addStaffModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Staff Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="staff.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" class="form-control" name="phone" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Specialization</label>
                                    <select class="form-select" name="specialization" required>
                                        <option value="">Select Specialization</option>
                                        <option value="Hair Stylist">Hair Stylist</option>
                                        <option value="Nail Technician">Nail Technician</option>
                                        <option value="Facial Specialist">Facial Specialist</option>
                                        <option value="Massage Therapist">Massage Therapist</option>
                                        <option value="Makeup Artist">Makeup Artist</option>
                                        <option value="Colorist">Colorist</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Role</label>
                                    <select class="form-select" name="role" required>
                                        <option value="stylist">Stylist</option>
                                        <option value="technician">Technician</option>
                                        <option value="assistant">Assistant</option>
                                        <option value="manager">Manager</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Commission Rate (%)</label>
                                    <input type="number" class="form-control" name="commission_rate" 
                                           min="0" max="100" step="0.01" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Hourly Rate ($)</label>
                                    <input type="number" class="form-control" name="hourly_rate" 
                                           min="0" step="0.01" required>
                                </div>
                            </div>
                        </div>
                        
                        <h6 class="mt-4">Schedule</h6>
                        <div class="table-responsive">
                            <table class="table table-sm schedule-table">
                                <thead>
                                    <tr>
                                        <th>Day</th>
                                        <th>Working Day</th>
                                        <th>Start Time</th>
                                        <th>End Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                                    foreach($days as $day):
                                    ?>
                                    <tr>
                                        <td><?php echo ucfirst($day); ?></td>
                                        <td>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" 
                                                       name="schedule[<?php echo $day; ?>][is_working_day]" 
                                                       value="1" checked>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="time" class="form-control form-control-sm" 
                                                   name="schedule[<?php echo $day; ?>][start_time]" 
                                                   value="09:00">
                                        </td>
                                        <td>
                                            <input type="time" class="form-control form-control-sm" 
                                                   name="schedule[<?php echo $day; ?>][end_time]" 
                                                   value="17:00">
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Staff Member</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Staff Modal -->
    <div class="modal fade" id="editStaffModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Staff Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="staff.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" class="form-control" name="name" id="edit_name" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" id="edit_email" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" class="form-control" name="phone" id="edit_phone" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Specialization</label>
                                    <select class="form-select" name="specialization" id="edit_specialization" required>
                                        <option value="Hair Stylist">Hair Stylist</option>
                                        <option value="Nail Technician">Nail Technician</option>
                                        <option value="Facial Specialist">Facial Specialist</option>
                                        <option value="Massage Therapist">Massage Therapist</option>
                                        <option value="Makeup Artist">Makeup Artist</option>
                                        <option value="Colorist">Colorist</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Role</label>
                                    <select class="form-select" name="role" id="edit_role" required>
                                        <option value="stylist">Stylist</option>
                                        <option value="technician">Technician</option>
                                        <option value="assistant">Assistant</option>
                                        <option value="manager">Manager</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Commission Rate (%)</label>
                                    <input type="number" class="form-control" name="commission_rate" 
                                           id="edit_commission" min="0" max="100" step="0.01" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Hourly Rate ($)</label>
                                    <input type="number" class="form-control" name="hourly_rate" 
                                           id="edit_hourly" min="0" step="0.01" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status" id="edit_status" required>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="on_leave">On Leave</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <h6 class="mt-4">Schedule</h6>
                        <div class="table-responsive">
                            <table class="table table-sm schedule-table">
                                <thead>
                                    <tr>
                                        <th>Day</th>
                                        <th>Working Day</th>
                                        <th>Start Time</th>
                                        <th>End Time</th>
                                    </tr>
                                </thead>
                                <tbody id="edit_schedule_body">
                                    <!-- Schedule will be loaded dynamically -->
                                </tbody>
                            </table>
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

    <!-- View Schedule Modal -->
    <div class="modal fade" id="viewScheduleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Staff Schedule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="scheduleContent">
                        <!-- Content will be loaded dynamically -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Attendance Modal -->
    <div class="modal fade" id="viewAttendanceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Staff Attendance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="attendanceContent">
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
        // Handle edit staff modal
        document.querySelectorAll('.edit-staff').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const email = this.getAttribute('data-email');
                const phone = this.getAttribute('data-phone');
                const specialization = this.getAttribute('data-specialization');
                const role = this.getAttribute('data-role');
                const commission = this.getAttribute('data-commission');
                const hourly = this.getAttribute('data-hourly');
                const status = this.getAttribute('data-status');

                document.getElementById('edit_id').value = id;
                document.getElementById('edit_name').value = name;
                document.getElementById('edit_email').value = email;
                document.getElementById('edit_phone').value = phone;
                document.getElementById('edit_specialization').value = specialization;
                document.getElementById('edit_role').value = role;
                document.getElementById('edit_commission').value = commission;
                document.getElementById('edit_hourly').value = hourly;
                document.getElementById('edit_status').value = status;

                // Fetch and populate schedule
                fetch(`get_staff_schedule.php?id=${id}`)
                    .then(response => response.text())
                    .then(html => {
                        document.getElementById('edit_schedule_body').innerHTML = html;
                    });
            });
        });

        // Handle view schedule modal
        document.querySelectorAll('.view-schedule').forEach(button => {
            button.addEventListener('click', function() {
                const staffId = this.getAttribute('data-id');
                const scheduleContent = document.getElementById('scheduleContent');
                
                fetch(`get_staff_schedule.php?id=${staffId}&view=true`)
                    .then(response => response.text())
                    .then(html => {
                        scheduleContent.innerHTML = html;
                    });
            });
        });

        // Handle view attendance modal
        document.querySelectorAll('.view-attendance').forEach(button => {
            button.addEventListener('click', function() {
                const staffId = this.getAttribute('data-id');
                const attendanceContent = document.getElementById('attendanceContent');
                
                fetch(`get_staff_attendance.php?id=${staffId}`)
                    .then(response => response.text())
                    .then(html => {
                        attendanceContent.innerHTML = html;
                    });
            });
        });
    </script>
</body>
</html>
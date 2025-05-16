<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

require_once "config/database.php";

// Get date range from request or default to current month
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Get total revenue for the period
$sql = "SELECT SUM(s.price) as total_revenue 
        FROM appointments a 
        JOIN services s ON a.service_id = s.id 
        WHERE a.appointment_date BETWEEN ? AND ? 
        AND a.status = 'completed'";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($stmt);
$revenue_result = mysqli_stmt_get_result($stmt);
$total_revenue = mysqli_fetch_assoc($revenue_result)['total_revenue'] ?? 0;

// Get total appointments for the period
$sql = "SELECT COUNT(*) as total_appointments 
        FROM appointments 
        WHERE appointment_date BETWEEN ? AND ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($stmt);
$appointments_result = mysqli_stmt_get_result($stmt);
$total_appointments = mysqli_fetch_assoc($appointments_result)['total_appointments'] ?? 0;

// Get top services
$sql = "SELECT s.name, COUNT(*) as service_count, SUM(s.price) as total_revenue 
        FROM appointments a 
        JOIN services s ON a.service_id = s.id 
        WHERE a.appointment_date BETWEEN ? AND ? 
        AND a.status = 'completed'
        GROUP BY s.id 
        ORDER BY service_count DESC 
        LIMIT 5";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($stmt);
$top_services = mysqli_stmt_get_result($stmt);

// Get staff performance
$sql = "SELECT st.name, COUNT(*) as appointment_count, SUM(s.price) as total_revenue 
        FROM appointments a 
        JOIN staff st ON a.staff_id = st.id 
        JOIN services s ON a.service_id = s.id 
        WHERE a.appointment_date BETWEEN ? AND ? 
        AND a.status = 'completed'
        GROUP BY st.id 
        ORDER BY appointment_count DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($stmt);
$staff_performance = mysqli_stmt_get_result($stmt);

// Get daily appointments for the period
$sql = "SELECT appointment_date, COUNT(*) as appointment_count 
        FROM appointments 
        WHERE appointment_date BETWEEN ? AND ? 
        GROUP BY appointment_date 
        ORDER BY appointment_date";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($stmt);
$daily_appointments = mysqli_stmt_get_result($stmt);

// Prepare data for charts
$dates = [];
$appointment_counts = [];
while($row = mysqli_fetch_assoc($daily_appointments)) {
    $dates[] = date('M d', strtotime($row['appointment_date']));
    $appointment_counts[] = $row['appointment_count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Salon Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .stat-card {
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .stat-card i {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .stat-label {
            color: #6c757d;
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
                    <a href="staff.php">
                        <i class="fas fa-user-tie"></i>
                        <span>Staff</span>
                    </a>
                    <a href="reports.php" class="active">
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
                    <h2>Reports & Analytics</h2>
                    <form class="d-flex gap-2">
                        <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                        <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                        <button type="submit" class="btn btn-primary">Apply Filter</button>
                    </form>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="stat-card bg-primary text-white">
                            <i class="fas fa-dollar-sign"></i>
                            <div class="stat-value">$<?php echo number_format($total_revenue, 2); ?></div>
                            <div class="stat-label">Total Revenue</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card bg-success text-white">
                            <i class="fas fa-calendar-check"></i>
                            <div class="stat-value"><?php echo $total_appointments; ?></div>
                            <div class="stat-label">Total Appointments</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card bg-info text-white">
                            <i class="fas fa-users"></i>
                            <div class="stat-value"><?php echo number_format($total_revenue / max($total_appointments, 1), 2); ?></div>
                            <div class="stat-label">Average Revenue per Appointment</div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Daily Appointments Chart -->
                    <div class="col-md-8 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Daily Appointments</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="appointmentsChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Top Services -->
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Top Services</h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group">
                                    <?php while($service = mysqli_fetch_assoc($top_services)): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($service['name']); ?></h6>
                                                <small class="text-muted"><?php echo $service['service_count']; ?> appointments</small>
                                            </div>
                                            <span class="badge bg-primary rounded-pill">
                                                $<?php echo number_format($service['total_revenue'], 2); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Staff Performance -->
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Staff Performance</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Staff Member</th>
                                                <th>Appointments</th>
                                                <th>Revenue Generated</th>
                                                <th>Average per Appointment</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while($staff = mysqli_fetch_assoc($staff_performance)): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($staff['name']); ?></td>
                                                <td><?php echo $staff['appointment_count']; ?></td>
                                                <td>$<?php echo number_format($staff['total_revenue'], 2); ?></td>
                                                <td>$<?php echo number_format($staff['total_revenue'] / $staff['appointment_count'], 2); ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize appointments chart
        const ctx = document.getElementById('appointmentsChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dates); ?>,
                datasets: [{
                    label: 'Appointments',
                    data: <?php echo json_encode($appointment_counts); ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html> 
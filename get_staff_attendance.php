<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

require_once "config/database.php";

if(isset($_GET["id"])){
    $staff_id = $_GET["id"];
    
    // Fetch staff member's name
    $sql = "SELECT name FROM staff WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $staff_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $staff = mysqli_fetch_assoc($result);
    }
    
    // Fetch attendance records for the current month
    $sql = "SELECT a.*, 
            TIMESTAMPDIFF(HOUR, a.clock_in, IFNULL(a.clock_out, NOW())) as hours_worked
            FROM staff_attendance a 
            WHERE a.staff_id = ? 
            AND MONTH(a.date) = MONTH(CURRENT_DATE())
            AND YEAR(a.date) = YEAR(CURRENT_DATE())
            ORDER BY a.date DESC";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $staff_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $attendance = array();
        while($row = mysqli_fetch_assoc($result)){
            $attendance[] = $row;
        }
    }
    
    // Calculate attendance statistics
    $total_days = count($attendance);
    $present_days = 0;
    $late_days = 0;
    $absent_days = 0;
    $total_hours = 0;
    
    foreach($attendance as $record){
        if($record["status"] == "present"){
            $present_days++;
            $total_hours += $record["hours_worked"];
        } elseif($record["status"] == "late"){
            $late_days++;
            $total_hours += $record["hours_worked"];
        } elseif($record["status"] == "absent"){
            $absent_days++;
        }
    }
    
    // Display attendance information
    ?>
    <h6 class="mb-3"><?php echo htmlspecialchars($staff["name"]); ?>'s Attendance</h6>
    
    <!-- Attendance Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">Present Days</h6>
                    <h3 class="mb-0"><?php echo $present_days; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="card-title">Late Days</h6>
                    <h3 class="mb-0"><?php echo $late_days; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h6 class="card-title">Absent Days</h6>
                    <h3 class="mb-0"><?php echo $absent_days; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Hours</h6>
                    <h3 class="mb-0"><?php echo number_format($total_hours, 1); ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Attendance Records -->
    <div class="table-responsive">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Clock In</th>
                    <th>Clock Out</th>
                    <th>Hours</th>
                    <th>Status</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($attendance as $record): ?>
                <tr>
                    <td><?php echo date("M d, Y", strtotime($record["date"])); ?></td>
                    <td>
                        <?php 
                        if($record["clock_in"]){
                            echo date("g:i A", strtotime($record["clock_in"]));
                        } else {
                            echo "-";
                        }
                        ?>
                    </td>
                    <td>
                        <?php 
                        if($record["clock_out"]){
                            echo date("g:i A", strtotime($record["clock_out"]));
                        } else {
                            echo "-";
                        }
                        ?>
                    </td>
                    <td>
                        <?php 
                        if($record["hours_worked"]){
                            echo number_format($record["hours_worked"], 1);
                        } else {
                            echo "-";
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        $status_class = "";
                        switch($record["status"]){
                            case "present":
                                $status_class = "success";
                                break;
                            case "late":
                                $status_class = "warning";
                                break;
                            case "absent":
                                $status_class = "danger";
                                break;
                        }
                        ?>
                        <span class="badge bg-<?php echo $status_class; ?>">
                            <?php echo ucfirst($record["status"]); ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($record["notes"]); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}
?> 
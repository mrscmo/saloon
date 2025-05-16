<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

require_once "config/database.php";

if(isset($_GET["id"])){
    $staff_id = $_GET["id"];
    $view_mode = isset($_GET["view"]) && $_GET["view"] === "true";
    
    // Fetch staff member's name
    $sql = "SELECT name FROM staff WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $staff_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $staff = mysqli_fetch_assoc($result);
    }
    
    // Fetch schedule
    $sql = "SELECT * FROM staff_schedules WHERE staff_id = ? ORDER BY FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $staff_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $schedule = array();
        while($row = mysqli_fetch_assoc($result)){
            $schedule[$row["day_of_week"]] = $row;
        }
    }
    
    if($view_mode){
        // Display schedule in view mode
        ?>
        <h6 class="mb-3"><?php echo htmlspecialchars($staff["name"]); ?>'s Schedule</h6>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Day</th>
                        <th>Working Hours</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                    foreach($days as $day):
                        $day_schedule = isset($schedule[$day]) ? $schedule[$day] : null;
                    ?>
                    <tr>
                        <td><?php echo ucfirst($day); ?></td>
                        <td>
                            <?php if($day_schedule): ?>
                                <?php echo date("g:i A", strtotime($day_schedule["start_time"])); ?> - 
                                <?php echo date("g:i A", strtotime($day_schedule["end_time"])); ?>
                            <?php else: ?>
                                <span class="text-muted">Not scheduled</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    } else {
        // Display schedule in edit mode
        ?>
        <?php
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        foreach($days as $day):
            $day_schedule = isset($schedule[$day]) ? $schedule[$day] : null;
        ?>
        <tr>
            <td><?php echo ucfirst($day); ?></td>
            <td>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" 
                           name="schedule[<?php echo $day; ?>][is_working_day]" 
                           value="1" <?php echo $day_schedule ? 'checked' : ''; ?>>
                </div>
            </td>
            <td>
                <input type="time" class="form-control form-control-sm" 
                       name="schedule[<?php echo $day; ?>][start_time]" 
                       value="<?php echo $day_schedule ? $day_schedule["start_time"] : '09:00'; ?>">
            </td>
            <td>
                <input type="time" class="form-control form-control-sm" 
                       name="schedule[<?php echo $day; ?>][end_time]" 
                       value="<?php echo $day_schedule ? $day_schedule["end_time"] : '17:00'; ?>">
            </td>
        </tr>
        <?php endforeach; ?>
        <?php
    }
}
?> 
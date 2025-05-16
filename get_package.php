<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("HTTP/1.1 401 Unauthorized");
    exit;
}

require_once "config/database.php";

if(isset($_GET["id"])){
    $sql = "SELECT sp.*, GROUP_CONCAT(ps.service_id) as services 
            FROM service_packages sp 
            LEFT JOIN package_services ps ON sp.id = ps.package_id 
            WHERE sp.id = ? 
            GROUP BY sp.id";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $_GET["id"]);
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            if($row = mysqli_fetch_assoc($result)){
                // Convert services string to array
                $row["services"] = $row["services"] ? explode(",", $row["services"]) : [];
                header("Content-Type: application/json");
                echo json_encode($row);
            } else {
                header("HTTP/1.1 404 Not Found");
            }
        } else {
            header("HTTP/1.1 500 Internal Server Error");
        }
    }
} else {
    header("HTTP/1.1 400 Bad Request");
}
?> 
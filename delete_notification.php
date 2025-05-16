<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

require_once "config/database.php";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $data = json_decode(file_get_contents("php://input"), true);
    
    if(isset($data["id"])){
        // First, delete related notification recipients
        $sql = "DELETE FROM notification_recipients WHERE notification_id = ?";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "i", $data["id"]);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        
        // Then delete the notification
        $sql = "DELETE FROM push_notifications WHERE id = ?";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "i", $data["id"]);
            
            if(mysqli_stmt_execute($stmt)){
                echo json_encode(["success" => true, "message" => "Notification deleted successfully"]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Error deleting notification"]);
            }
            
            mysqli_stmt_close($stmt);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Error preparing statement"]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["error" => "Missing notification ID"]);
    }
} else {
    http_response_code(400);
    echo json_encode(["error" => "Invalid request method"]);
}
?> 
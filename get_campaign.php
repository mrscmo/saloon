<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

require_once "config/database.php";

if($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["id"])){
    $sql = "SELECT * FROM marketing_campaigns WHERE id = ?";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $_GET["id"]);
        
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            
            if($row = mysqli_fetch_assoc($result)){
                echo json_encode($row);
            } else {
                http_response_code(404);
                echo json_encode(["error" => "Campaign not found"]);
            }
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Error executing query"]);
        }
        
        mysqli_stmt_close($stmt);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Error preparing statement"]);
    }
} else {
    http_response_code(400);
    echo json_encode(["error" => "Invalid request"]);
}
?> 
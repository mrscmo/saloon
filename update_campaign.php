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
    
    if(isset($data["id"]) && isset($data["name"]) && isset($data["campaign_type"]) && 
       isset($data["start_date"]) && isset($data["end_date"]) && isset($data["content"])){
        
        $sql = "UPDATE marketing_campaigns SET 
                name = ?, 
                description = ?, 
                campaign_type = ?, 
                start_date = ?, 
                end_date = ?, 
                target_audience = ?, 
                content = ? 
                WHERE id = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "sssssssi", 
                $data["name"],
                $data["description"],
                $data["campaign_type"],
                $data["start_date"],
                $data["end_date"],
                $data["target_audience"],
                $data["content"],
                $data["id"]
            );
            
            if(mysqli_stmt_execute($stmt)){
                echo json_encode(["success" => true, "message" => "Campaign updated successfully"]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Error updating campaign"]);
            }
            
            mysqli_stmt_close($stmt);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Error preparing statement"]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["error" => "Missing required fields"]);
    }
} else {
    http_response_code(400);
    echo json_encode(["error" => "Invalid request method"]);
}
?> 
<?php

$conn = new mysqli("localhost", "root", "", "code_arena");

for ($i = 1; $i <= 1000; $i++) {

    $username = "user".$i;
    $email = "user".$i."@codearena.com";
    $password = password_hash("123456", PASSWORD_DEFAULT);
    
    $role = "student";
    $is_blocked = 0;
    $is_deleted = 0;

    $hardcore = rand(800, 2000);
    $learning = $hardcore + rand(-50, 50);
    $roadmap = rand(1, 50);

    $sql = "INSERT INTO users 
    (username, email, password, role, is_blocked, is_deleted, hardcore_rating, learning_rating, roadmap_day, created_at)
    VALUES
    ('$username','$email','$password','$role',$is_blocked,$is_deleted,$hardcore,$learning,$roadmap,NOW())";

    $conn->query($sql);
}

echo "1000 users generated successfully!";
?>
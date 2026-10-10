<?php

include "config/database.php";

$username = "admin";
$password = password_hash("1234", PASSWORD_DEFAULT);

$sql = "INSERT INTO users (username, password) VALUES (?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $username, $password);

if ($stmt->execute()) {
    echo "Admin user created successfully!";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();

?>

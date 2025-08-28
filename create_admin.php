<?php
// Script to create or reset admin user
require_once __DIR__ . '/config.php';

// Check if connection is established
if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection failed.");
}

// Create admin user with username 'admin' and password 'admin'
$username = 'admin';
$password = 'admin';
$email = 'admin@localhost';
$fullname = 'مدیر سیستم';
$role_id = 1; // Admin role

// Hash the password
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Check if admin user already exists
$stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Update existing admin user
    $row = $result->fetch_assoc();
    $user_id = $row['user_id'];
    
    $update = $conn->prepare("UPDATE users SET 
                             password_hash = ?, 
                             email = ?,
                             full_name = ?,
                             role_id = ?,
                             is_active = 1,
                             failed_login_attempts = 0,
                             locked_until = NULL
                             WHERE user_id = ?");
    
    $update->bind_param('sssii', $password_hash, $email, $fullname, $role_id, $user_id);
    
    if ($update->execute()) {
        echo "Admin user updated successfully!<br>";
        echo "Username: $username<br>";
        echo "Password: $password<br>";
    } else {
        echo "Error updating admin user: " . $update->error;
    }
    
    $update->close();
} else {
    // Create new admin user
    $insert = $conn->prepare("INSERT INTO users 
                             (username, password_hash, email, full_name, role_id, is_active) 
                             VALUES (?, ?, ?, ?, ?, 1)");
    
    $insert->bind_param('ssssi', $username, $password_hash, $email, $fullname, $role_id);
    
    if ($insert->execute()) {
        echo "Admin user created successfully!<br>";
        echo "Username: $username<br>";
        echo "Password: $password<br>";
    } else {
        echo "Error creating admin user: " . $insert->error;
    }
    
    $insert->close();
}

$stmt->close();
$conn->close();

echo "<p><a href='login.php'>برو به صفحه ورود</a></p>";
?>

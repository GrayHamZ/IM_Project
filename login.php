<?php
session_start();
require_once('db_connection.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Query the users table
    $query_users = "SELECT * FROM users WHERE email='$email' AND password='$password'";
    $result_users = mysqli_query($conn, $query_users);
    $count_users = mysqli_num_rows($result_users);

    // Query the staff table
    $query_staff = "SELECT * FROM staff WHERE email='$email' AND password='$password'";
    $result_staff = mysqli_query($conn, $query_staff);
    $count_staff = mysqli_num_rows($result_staff);

    if ($count_users == 1) {
        // User found in users table
        $row = mysqli_fetch_assoc($result_users);
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['user_type'] = 'user'; 
        header("Location: user_dashboard.php"); 
        exit();
    } elseif ($count_staff == 1) {
        // User found in staff table
        $row = mysqli_fetch_assoc($result_staff);
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['user_type'] = $row['staffType'];

        // Redirect based on staff type
        if ($row['staffType'] == 'doctor') {
            header("Location: doctordash.php"); 
            exit(); 
        } elseif ($row['staffType'] == 'secretary') {
            header("Location: secretarydash.php"); 
            exit();
        } elseif ($row['staffType'] == 'admin') {
            header("Location: admindash.php"); 
            exit();
        } else {
            // Handle unrecognized staff type
            $_SESSION['login_error'] = "Unrecognized staff type";
            header("Location: index.php"); 
            exit(); 
        }
    } else {
        // No user found with the provided credentials
        $_SESSION['login_error'] = "Invalid email or password";
        header("Location: index.php");
        exit();
    }
} else {
    header("Location: index.php"); // Redirect back to login (index) page if accessed directly
    exit(); // Ensure script stops execution after redirection
}
?>

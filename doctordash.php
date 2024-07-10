<?php
session_start();
require_once('db_connection.php'); 

// Check if user is logged in as a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header("Location: index.php");
    exit();
}

// Fetch doctor's first name
$doctorId = $_SESSION['user_id']; // Assuming user_id in session represents the doctor's ID
$queryDoctor = "SELECT fname FROM staff WHERE id = $doctorId";
$resultDoctor = mysqli_query($conn, $queryDoctor);
$rowDoctor = mysqli_fetch_assoc($resultDoctor);
$doctorFirstName = $rowDoctor['fname'];

// Default query to fetch all 'approved' appointments
$queryAppointments = "SELECT appointment.id, appointment.pref_schedule, appointment.reason, users.fname AS patient_fname, users.lname AS patient_lname 
                      FROM appointment 
                      INNER JOIN patients ON appointment.patientID = patients.id 
                      INNER JOIN users ON patients.usersID = users.id 
                      WHERE appointment.status = 'approved'";

// Handling filters and search
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Date filter
    $dateFilter = $_POST['dateFilter'];
    if (!empty($dateFilter)) {
        $dateFilter = date('Y-m-d', strtotime($dateFilter));
        $queryAppointments .= " AND DATE(appointment.pref_schedule) = '$dateFilter'";
    }

    // Patient name search
    $patientName = $_POST['patientName'];
    if (!empty($patientName)) {
        $queryAppointments .= " AND (users.fname LIKE '%$patientName%' OR users.lname LIKE '%$patientName%')";
    }
}

$queryAppointments .= " ORDER BY appointment.pref_schedule DESC";

$resultAppointments = mysqli_query($conn, $queryAppointments);

// Function to logout user
function logout() {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}

// Logout if logout button is clicked
if (isset($_POST['logout'])) {
    logout();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .navbar {
            justify-content: space-between;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-light bg-light">
        <a class="navbar-brand">Doctor Dashboard</a>
        <p class="mr-3 mt-2">Welcome, <?php echo $doctorFirstName; ?></p>
        <form method="post" class="form-inline">
            <a href="patientlist.php" class="nav-item nav-link">Patients</a>
            <button type="submit" class="btn btn-outline-danger my-2 my-sm-0" name="logout">Logout</button>
        </form>
    </nav>

    <div class="container mt-3">
        <div class="card">
            <div class="card-body">
                <form method="post" class="form-inline mb-3">
                    <div class="form-group mr-3">
                        <label for="dateFilter">Filter by Date:</label>
                        <input type="date" class="form-control mx-sm-2" id="dateFilter" name="dateFilter">
                    </div>
                    <div class="form-group mr-3">
                        <label for="patientName">Search by Patient Name:</label>
                        <input type="text" class="form-control mx-sm-2" id="patientName" name="patientName" placeholder="Enter patient name">
                    </div>
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </form>

                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Patient Name</th>
                            <th>Reason</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        while ($row = mysqli_fetch_assoc($resultAppointments)) {
                            $dateTime = strtotime($row['pref_schedule']);
                            $dateFormatted = date('F j, Y', $dateTime); // Format date as July 7, 2024
                            $timeFormatted = date('h:i A', $dateTime); // Format time as 12-hour clock with AM/PM

                            $patientName = $row['patient_fname'] . ' ' . $row['patient_lname'];
                            $reason = $row['reason'];

                            echo "<tr>";
                            echo "<td>$dateFormatted</td>";
                            echo "<td>$timeFormatted</td>";
                            echo "<td>$patientName</td>";
                            echo "<td>$reason</td>";
                            echo '<td><a href="appointment.php?id=' . $row['id'] . '" class="btn btn-primary">Open Appointment</a></td>';
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>

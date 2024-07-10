<?php
session_start();
require_once('db_connection.php');

// Check if user is logged in as a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header("Location: index.php");
    exit();
}

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Fetch doctor's first name
$doctorId = $_SESSION['user_id'];
$queryDoctor = "SELECT fname FROM staff WHERE id = ?";
$stmtDoctor = $conn->prepare($queryDoctor);
$stmtDoctor->bind_param("i", $doctorId);
$stmtDoctor->execute();
$resultDoctor = $stmtDoctor->get_result();
$rowDoctor = $resultDoctor->fetch_assoc();
$doctorFirstName = $rowDoctor['fname'];
$stmtDoctor->close();

// Initialize variables for form values and errors
$prescription = $doctorsNote = $diagnosis = $allergies = '';
$prescriptionErr = '';

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle appointment and patient updates together
    if (isset($_POST['finishAppointment'])) {
        $appointmentId = $_POST['appointment_id'];
        $prescription = sanitize_input($_POST['prescription']);
        $doctorsNote = sanitize_input($_POST['doctors_note']);
        $diagnosis = sanitize_input($_POST['diagnosis']);
        $allergies = sanitize_input($_POST['allergies']);
        $patientId = $_POST['patient_id'];

        // Validate prescription (optional based on your needs)
        if (empty($prescription)) {
            $prescriptionErr = 'Prescription is required';
        } else {
            // Update appointment table
            $updateAppointmentQuery = "UPDATE appointment SET prescription = ?, doctors_note = ?, diagnosis = ?, status = 'finished' WHERE id = ?";
            $stmtUpdateAppointment = $conn->prepare($updateAppointmentQuery);
            $stmtUpdateAppointment->bind_param("sssi", $prescription, $doctorsNote, $diagnosis, $appointmentId);
            $appointmentUpdated = $stmtUpdateAppointment->execute();
            $stmtUpdateAppointment->close();

            // Update patient allergies
            $updatePatientQuery = "UPDATE patients SET allergies = ? WHERE id = ?";
            $stmtUpdatePatient = $conn->prepare($updatePatientQuery);
            $stmtUpdatePatient->bind_param("si", $allergies, $patientId);
            $patientUpdated = $stmtUpdatePatient->execute();
            $stmtUpdatePatient->close();

            if ($appointmentUpdated && $patientUpdated) {
                // Success message or redirect to another page
                header("Location: doctordash.php");
                exit();
            } else {
                echo "Error updating appointment or patient information";
            }
        }
    }
}

// Fetch appointment and patient information
if (isset($_GET['id'])) {
    $appointmentId = $_GET['id'];

    // Fetch appointment details including patient information
    $queryAppointment = "SELECT appointment.id, appointment.pref_schedule, appointment.prescription, appointment.doctors_note, appointment.diagnosis,
                                users.fname AS patient_fname, users.lname AS patient_lname, appointment.patientID,
                                patients.height, patients.weight
                         FROM appointment
                         INNER JOIN patients ON appointment.patientID = patients.id
                         INNER JOIN users ON patients.usersID = users.id
                         WHERE appointment.id = ?";
    $stmt = $conn->prepare($queryAppointment);
    $stmt->bind_param("i", $appointmentId);
    $stmt->execute();
    $resultAppointment = $stmt->get_result();
    $appointment = $resultAppointment->fetch_assoc();
    $stmt->close();

    // Fetch patient allergies
    $queryPatient = "SELECT allergies FROM patients WHERE id = ?";
    $stmt = $conn->prepare($queryPatient);
    $stmt->bind_param("i", $appointment['patientID']);
    $stmt->execute();
    $resultPatient = $stmt->get_result();
    $patient = $resultPatient->fetch_assoc();
    $stmt->close();
} else {
    // Redirect if appointment ID is not provided
    header("Location: doctordash.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Details</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
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
        <a href="doctordash.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
    <div class="container mt-3">
        <div class="card">
            <div class="card-header">
                Appointment Details
            </div>
            <div class="card-body">
                <h5 class="card-title">Patient: <?php echo $appointment['patient_fname'] . ' ' . $appointment['patient_lname']; ?></h5>
                <p class="card-text">Preferred Schedule: <?php echo $appointment['pref_schedule']; ?></p>
                <p class="card-text">Height: <?php echo $appointment['height']; ?> cm</p>
                <p class="card-text">Weight: <?php echo $appointment['weight']; ?> kg</p>
                <hr>

                <!-- Update Form -->
                <form method="post">
                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                    <input type="hidden" name="patient_id" value="<?php echo $appointment['patientID']; ?>">
                    <div class="form-group">
                        <label for="prescription">Prescription:</label>
                        <textarea class="form-control" id="prescription" name="prescription" rows="3"><?php echo $appointment['prescription']; ?></textarea>
                        <small class="text-danger"><?php echo $prescriptionErr; ?></small>
                    </div>
                    <div class="form-group">
                        <label for="doctors_note">Doctor's Note:</label>
                        <textarea class="form-control" id="doctors_note" name="doctors_note" rows="3"><?php echo $appointment['doctors_note']; ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="diagnosis">Diagnosis:</label>
                        <input type="text" class="form-control" id="diagnosis" name="diagnosis" value="<?php echo $appointment['diagnosis']; ?>">
                    </div>
                    <div class="form-group">
                        <label for="allergies">Allergies:</label>
                        <textarea class="form-control" id="allergies" name="allergies" rows="3"><?php echo $patient['allergies']; ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" name="finishAppointment">Finish Appointment</button>
                </form>
            </div>
        </div>
    </div>

</body>
</html>

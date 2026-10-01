<?php

session_start();

if (!isset($_SESSION['uname'])) {
    header("Location: login.html");
    exit();
}

include "demo.php";

if (!isset($_GET['sid'])) {
    die("Student ID not found.");
}

$sid = mysqli_real_escape_string($conn, $_GET['sid']);

$sql = "SELECT
            s.*,
            a.ano,
            a.admit_year,
            a.addate,
            a.status,
            c.cname,
            c.duration,
            c.fees,

            p.total_paid AS amount,
            p.pmode,
            p.pdate,

            CASE
                WHEN COALESCE(p.total_paid, 0) >= c.fees
                    THEN 'Paid'

                WHEN COALESCE(p.total_paid, 0) > 0
                    THEN 'Partial Paid'

                ELSE 'Not Paid'
            END AS pstatus

        FROM student s

        INNER JOIN admission a
            ON s.sid = a.sid

        INNER JOIN course c
            ON a.cid = c.cid

        LEFT JOIN
        (
            SELECT
                ano,
                SUM(amount) AS total_paid,

                SUBSTRING_INDEX(
                    GROUP_CONCAT(
                        pmode
                        ORDER BY pid DESC
                    ),
                    ',',
                    1
                ) AS pmode,

                MAX(pdate) AS pdate

            FROM payment

            GROUP BY ano

        ) p
            ON a.ano = p.ano

        WHERE s.sid = '$sid'

        AND a.status = 'Approved'

        ORDER BY

            CASE
                WHEN LOWER(TRIM(a.admit_year))
                     IN ('third year','3rd year','3')
                    THEN 3

                WHEN LOWER(TRIM(a.admit_year))
                     IN ('second year','2nd year','2')
                    THEN 2

                WHEN LOWER(TRIM(a.admit_year))
                     IN ('first year','1st year','1')
                    THEN 1

                ELSE 0
            END DESC,

            a.addate DESC

        LIMIT 1";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Error: " . mysqli_error($conn));
}

if (mysqli_num_rows($result) == 0) {
    die("Approved student not found.");
}

$student = mysqli_fetch_assoc($result);

?>

<!DOCTYPE html>
<html>

<head>

<title>View Student</title>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial;
}

body {
    background: linear-gradient(135deg,#eaf2fb,#d6e5f5);
}

.header {
    height: 70px;
    background: linear-gradient(90deg,#0b3c7c,#1559a6);
    color: white;
    display: flex;
    align-items: center;
    padding: 0 30px;
    font-size: 22px;
    font-weight: bold;
}

.container {
    display: flex;
    min-height: calc(100vh - 70px);
}

.sidebar {
    width: 220px;
    background: white;
    padding: 25px 15px;
}

.sidebar a {
    display: block;
    padding: 14px 15px;
    margin-bottom: 10px;
    text-decoration: none;
    color: #333;
    font-weight: bold;
    border-radius: 7px;
}

.sidebar a:hover,
.sidebar .active {
    background: #eaf2fb;
    color: #0b3c7c;
}

.main {
    flex: 1;
    padding: 35px;
}

.title {
    font-size: 30px;
    color: #0b3c7c;
    font-weight: bold;
    margin-bottom: 30px;
}

.box {
    background: white;
    padding: 30px;
    border-radius: 12px;
}

.section {
    margin-bottom: 25px;
    padding-bottom: 25px;
    border-bottom: 2px solid #0b3c7c;
}

.section h3 {
    color: #0b3c7c;
    margin-bottom: 18px;
}

.row {
    display: flex;
    gap: 20px;
    margin-bottom: 15px;
}

.field {
    flex: 1;
}

.label {
    font-weight: bold;
    color: #555;
    margin-bottom: 5px;
}

.value {
    font-weight: bold;
    color: #222;
}

.back {
    display: inline-block;
    margin-top: 10px;
    padding: 12px 25px;
    background: #0b3c7c;
    color: white;
    text-decoration: none;
    border-radius: 7px;
    font-weight: bold;
}

</style>

</head>

<body>

<div class="header">
    STUDENT ADMISSION SYSTEM
</div>

<div class="container">

<div class="sidebar">

    <a href="dashboard.php">
        Dashboard
    </a>

    <a href="Students.php" class="active">
        Students
    </a>

    <a href="Courses.php">
        Courses
    </a>

    <a href="Admissions.php">
        Admissions
    </a>

    <a href="PayFees.php">
        Payments
    </a>

    <a href="logout.php">
        Logout
    </a>

</div>

<div class="main">

<div class="title">
    Student Details
</div>

<div class="box">


<!-- PERSONAL INFORMATION -->

<div class="section">

<h3>
    Personal Information
</h3>

<div class="row">

<div class="field">
<div class="label">Student ID</div>
<div class="value">
<?php echo htmlspecialchars($student['sid']); ?>
</div>
</div>

<div class="field">
<div class="label">Name</div>
<div class="value">
<?php echo htmlspecialchars($student['sname']); ?>
</div>
</div>

<div class="field">
<div class="label">DOB</div>
<div class="value">
<?php echo htmlspecialchars($student['dob']); ?>
</div>
</div>

</div>


<div class="row">

<div class="field">
<div class="label">Gender</div>
<div class="value">
<?php echo htmlspecialchars($student['gender']); ?>
</div>
</div>

<div class="field">
<div class="label">Phone</div>
<div class="value">
<?php echo htmlspecialchars($student['phone']); ?>
</div>
</div>

<div class="field">
<div class="label">Email</div>
<div class="value">
<?php echo htmlspecialchars($student['email']); ?>
</div>
</div>

</div>


<div class="row">

<div class="field">
<div class="label">Blood Group</div>
<div class="value">
<?php echo htmlspecialchars($student['blood_group']); ?>
</div>
</div>

</div>

</div>


<!-- ADDRESS -->

<div class="section">

<h3>
    Address
</h3>

<div class="row">

<div class="field">
<div class="label">House / Flat No.</div>
<div class="value">
<?php echo htmlspecialchars($student['house_no']); ?>
</div>
</div>

<div class="field">
<div class="label">Street</div>
<div class="value">
<?php echo htmlspecialchars($student['street']); ?>
</div>
</div>

<div class="field">
<div class="label">City</div>
<div class="value">
<?php echo htmlspecialchars($student['city']); ?>
</div>
</div>

</div>


<div class="row">

<div class="field">
<div class="label">State</div>
<div class="value">
<?php echo htmlspecialchars($student['state']); ?>
</div>
</div>

<div class="field">
<div class="label">Pincode</div>
<div class="value">
<?php echo htmlspecialchars($student['pincode']); ?>
</div>
</div>

</div>

</div>


<!-- ACADEMIC -->

<div class="section">

<h3>
    Previous Academic Details
</h3>

<div class="row">

<div class="field">
<div class="label">Qualification</div>
<div class="value">
<?php echo htmlspecialchars($student['qualification']); ?>
</div>
</div>

<div class="field">
<div class="label">Previous Institute</div>
<div class="value">
<?php echo htmlspecialchars($student['prev_institute']); ?>
</div>
</div>

</div>


<div class="row">

<div class="field">
<div class="label">Passing Year</div>
<div class="value">
<?php echo htmlspecialchars($student['pass_year']); ?>
</div>
</div>

<div class="field">
<div class="label">Percentage / CGPA</div>
<div class="value">
<?php echo htmlspecialchars($student['percent_cgpa']); ?>
</div>
</div>

</div>

</div>


<!-- ADMISSION -->

<div class="section">

<h3>
    Admission Details
</h3>

<div class="row">

<div class="field">
<div class="label">Admission No.</div>
<div class="value">
<?php echo htmlspecialchars($student['ano']); ?>
</div>
</div>

<div class="field">
<div class="label">Course</div>
<div class="value">
<?php echo htmlspecialchars($student['cname']); ?>
</div>
</div>

<div class="field">
<div class="label">Admission Year</div>
<div class="value">
<?php echo htmlspecialchars($student['admit_year']); ?>
</div>
</div>

</div>


<div class="row">

<div class="field">
<div class="label">Admission Date</div>
<div class="value">
<?php echo htmlspecialchars($student['addate']); ?>
</div>
</div>

<div class="field">
<div class="label">Status</div>
<div class="value">
<?php echo htmlspecialchars($student['status']); ?>
</div>
</div>

<div class="field">
<div class="label">Course Fees</div>
<div class="value">
<?php echo htmlspecialchars($student['fees']); ?>
</div>
</div>

</div>

</div>


<!-- PAYMENT -->

<div class="section">

<h3>
    Payment Details
</h3>

<div class="row">

<div class="field">
<div class="label">Amount</div>
<div class="value">
<?php echo htmlspecialchars($student['amount'] ?? ''); ?>
</div>
</div>

<div class="field">
<div class="label">Payment Mode</div>
<div class="value">
<?php echo htmlspecialchars($student['pmode'] ?? ''); ?>
</div>
</div>

<div class="field">
<div class="label">Payment Status</div>
<div class="value">
<?php echo htmlspecialchars($student['pstatus'] ?? ''); ?>
</div>
</div>

</div>


<div class="row">

<div class="field">
<div class="label">Payment Date</div>
<div class="value">
<?php echo htmlspecialchars($student['pdate'] ?? ''); ?>
</div>
</div>

</div>

</div>


<a href="Students.php" class="back">
    BACK
</a>


</div>

</div>

</div>

</body>

</html>
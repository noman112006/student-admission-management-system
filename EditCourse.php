<?php

session_start();

if (!isset($_SESSION['uname'])) {
    header("Location: login.html");
    exit();
}

include "demo.php";

$message = "";

$errors = array();


/* =========================================
   GET COURSE ID
========================================= */

if (!isset($_GET['cid'])) {

    header("Location: Courses.php");
    exit();

}

$cid = intval($_GET['cid']);


/* =========================================
   GET COURSE
========================================= */

$course_result = mysqli_query(
    $conn,
    "SELECT *
     FROM course
     WHERE cid=$cid
     LIMIT 1"
);

if (!$course_result) {
    die("Error: " . mysqli_error($conn));
}

if (mysqli_num_rows($course_result) == 0) {
    die("Course not found.");
}

$course = mysqli_fetch_assoc($course_result);


/* =========================================
   GET DEPARTMENTS
========================================= */

$departments = array();

$department_result = mysqli_query(
    $conn,
    "SELECT did, dname
     FROM department
     ORDER BY dname"
);

if (!$department_result) {
    die("Department Error: " . mysqli_error($conn));
}

while ($department = mysqli_fetch_assoc($department_result)) {

    $departments[] = $department;
}


/* =========================================
   UPDATE COURSE
========================================= */

if (
    $_SERVER["REQUEST_METHOD"] == "POST"
    && isset($_POST['update_course'])
) {

    $cname = trim($_POST['cname'] ?? "");
    $ctype = trim($_POST['ctype'] ?? "");
    $duration = trim($_POST['duration'] ?? "");
    $fees_input = trim($_POST['fees'] ?? "");
    $seats_input = trim($_POST['total_seats'] ?? "");
    $did = intval($_POST['did'] ?? 0);


    /* =========================================
       VALIDATION
    ========================================= */

    if ($cname == "") {

        $errors['cname'] = "Course Name is required.";

    }


    if ($ctype == "") {

        $errors['ctype'] = "Course Type is required.";

    }


    if ($duration == "") {

        $errors['duration'] = "Duration is required.";

    }


    if ($fees_input == "") {

        $errors['fees'] = "Course Fees is required.";

    }

    elseif (!is_numeric($fees_input)) {

        $errors['fees'] = "Course Fees must contain numbers only.";

    }

    elseif ($fees_input <= 0) {

        $errors['fees'] = "Course Fees must be greater than 0.";

    }


    if ($seats_input == "") {

        $errors['total_seats'] = "Total Seats is required.";

    }

    elseif (!ctype_digit($seats_input)) {

        $errors['total_seats'] = "Total Seats must contain numbers only.";

    }

    elseif (intval($seats_input) <= 0) {

        $errors['total_seats'] = "Total Seats must be greater than 0.";

    }


    if ($did <= 0) {

        $errors['did'] = "Department is required.";

    }


    /* =========================================
       IF NO VALIDATION ERROR
    ========================================= */

    if (count($errors) == 0) {

        $cname = mysqli_real_escape_string(
            $conn,
            $cname
        );

        $ctype = mysqli_real_escape_string(
            $conn,
            $ctype
        );

        $duration = mysqli_real_escape_string(
            $conn,
            $duration
        );

        $fees = floatval($fees_input);

        $total_seats = intval($seats_input);


        /* =========================================
           CHECK DUPLICATE COURSE NAME
        ========================================= */

        $check = mysqli_query(
            $conn,

            "SELECT cid
             FROM course
             WHERE cname='$cname'
             AND cid != $cid"
        );


        if (!$check) {

            die(
                "Error: "
                . mysqli_error($conn)
            );
        }


        if (mysqli_num_rows($check) > 0) {

            $errors['cname'] =
                "Course with this name already exists.";

        }


        /* =========================================
           CHECK APPROVED STUDENTS
        ========================================= */

        if (count($errors) == 0) {

            $approved_result = mysqli_query(
                $conn,

                "SELECT COUNT(*) AS total
                 FROM admission
                 WHERE cid=$cid
                 AND status='Approved'"
            );


            if (!$approved_result) {

                die(
                    "Error: "
                    . mysqli_error($conn)
                );
            }


            $approved_data =
                mysqli_fetch_assoc(
                    $approved_result
                );


            $approved_count =
                intval(
                    $approved_data['total']
                );


            if (
                $total_seats <
                $approved_count
            ) {

                $errors['total_seats'] =
                    "Total seats cannot be less than the number of already approved students ("
                    . $approved_count
                    . ").";

            }

        }


        /* =========================================
           UPDATE
        ========================================= */

        if (count($errors) == 0) {

            $update_sql = "UPDATE course SET

                            cname='$cname',
                            ctype='$ctype',
                            duration='$duration',
                            fees='$fees',
                            total_seats='$total_seats',
                            did='$did'

                           WHERE cid=$cid";


            if (
                mysqli_query(
                    $conn,
                    $update_sql
                )
            ) {

                header(
                    "Location: Courses.php"
                );

                exit();

            }

            else {

                $message =
                    "Course could not be updated: "
                    . mysqli_error($conn);

            }

        }

    }

}

?>

<!DOCTYPE html>
<html>

<head>

<title>Edit Course</title>

<style>

* {
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Arial;
}

body {
    background:
        linear-gradient(
            135deg,
            #eaf2fb,
            #d6e5f5
        );
}

.header {
    height:70px;

    background:
        linear-gradient(
            90deg,
            #0b3c7c,
            #1559a6
        );

    color:white;

    display:flex;
    align-items:center;

    padding:0 30px;

    font-size:22px;

    font-weight:bold;
}

.container {
    display:flex;

    min-height:
        calc(100vh - 70px);
}

.sidebar {
    width:220px;

    background:white;

    padding:25px 15px;
}

.sidebar a {
    display:block;

    padding:14px 15px;

    margin-bottom:10px;

    text-decoration:none;

    color:#333;

    font-weight:bold;

    border-radius:7px;
}

.sidebar a:hover,
.sidebar .active {
    background:#eaf2fb;

    color:#0b3c7c;
}

.main {
    flex:1;

    padding:35px;
}

.title {
    font-size:30px;

    color:#0b3c7c;

    font-weight:bold;

    margin-bottom:30px;
}

.box {
    background:white;

    padding:30px;

    border-radius:12px;

    max-width:900px;
}

.section {

    border-bottom:
        2px solid #0b3c7c;

    padding-bottom:25px;

    margin-bottom:25px;
}

.section h3 {

    color:#0b3c7c;

    margin-bottom:20px;
}

.row {

    display:flex;

    gap:20px;

    margin-bottom:20px;
}

.field {

    flex:1;
}

label {

    display:block;

    font-weight:bold;

    margin-bottom:8px;
}

.required {
    color:red;
}

input,
select {

    width:100%;

    padding:12px;

    border:
        1px solid #ccc;

    border-radius:7px;

    outline:none;
}

input:focus,
select:focus {
    border-color:#0b3c7c;
}

input.invalid,
select.invalid {
    border:2px solid red;
}

.field-error {
    color:red;
    font-size:13px;
    margin-top:5px;
    min-height:16px;
}

input[readonly] {

    background:#f2f2f2;
}

button,
.back {

    padding:
        12px 25px;

    background:#0b3c7c;

    color:white;

    border:none;

    border-radius:7px;

    font-weight:bold;

    text-decoration:none;

    cursor:pointer;
}

.cancel {

    background:#777;
}

.buttons {

    display:flex;

    gap:10px;

    margin-top:20px;
}

.message {

    padding:12px;

    margin-bottom:20px;

    background:#ffecec;

    color:red;

    border:1px solid #f5b5b5;

    font-weight:bold;

    border-radius:7px;
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

    <a href="Students.php">
        Students
    </a>

    <a href="Courses.php"
       class="active">
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
    Edit Course
</div>


<div class="box">


<?php

if ($message != "") {

    echo
        "<div class='message'>"
        . htmlspecialchars($message)
        . "</div>";
}

?>


<form
    method="POST"
    onsubmit="return validateForm()"
>


<div class="section">

<h3>
    Course Information
</h3>


<div class="row">


<div class="field">

<label>
    Course Name <span class="required">*</span>
</label>

<input
    type="text"
    id="cname"
    name="cname"
    value="<?php

    echo htmlspecialchars(
        $_POST['cname']
        ?? $course['cname']
    );

    ?>"
>

<div
    id="cnameError"
    class="field-error"
>

<?php
echo htmlspecialchars(
    $errors['cname'] ?? ""
);
?>

</div>

</div>


<div class="field">

<label>
    Course Type <span class="required">*</span>
</label>

<input
    type="text"
    id="ctype"
    name="ctype"
    value="<?php

    echo htmlspecialchars(
        $_POST['ctype']
        ?? $course['ctype']
    );

    ?>"
>

<div
    id="ctypeError"
    class="field-error"
>

<?php
echo htmlspecialchars(
    $errors['ctype'] ?? ""
);
?>

</div>

</div>

</div>


<div class="row">


<div class="field">

<label>
    Duration <span class="required">*</span>
</label>

<input
    type="text"
    id="duration"
    name="duration"
    value="<?php

    echo htmlspecialchars(
        $_POST['duration']
        ?? $course['duration']
    );

    ?>"
>

<div
    id="durationError"
    class="field-error"
>

<?php
echo htmlspecialchars(
    $errors['duration'] ?? ""
);
?>

</div>

</div>


<div class="field">

<label>
    Course Fees <span class="required">*</span>
</label>

<input
    type="text"
    id="fees"
    name="fees"

    value="<?php

    echo htmlspecialchars(
        $_POST['fees']
        ?? $course['fees']
    );

    ?>"

    oninput="
        this.value =
        this.value.replace(
            /[^0-9.]/g,
            ''
        )
    "
>

<div
    id="feesError"
    class="field-error"
>

<?php
echo htmlspecialchars(
    $errors['fees'] ?? ""
);
?>

</div>

</div>


<div class="field">

<label>
    Total Seats <span class="required">*</span>
</label>

<input
    type="text"
    id="total_seats"
    name="total_seats"

    value="<?php

    echo htmlspecialchars(
        $_POST['total_seats']
        ?? $course['total_seats']
    );

    ?>"

    oninput="
        this.value =
        this.value.replace(
            /[^0-9]/g,
            ''
        )
    "
>

<div
    id="totalSeatsError"
    class="field-error"
>

<?php
echo htmlspecialchars(
    $errors['total_seats'] ?? ""
);
?>

</div>

</div>

</div>


<div class="row">


<div class="field">

<label>
    Department <span class="required">*</span>
</label>

<select
    id="did"
    name="did"
>

<option value="">
    Select Department
</option>


<?php

foreach (
    $departments
    as $department
) {

?>

<option
    value="<?php
    echo $department['did'];
    ?>"

<?php

$current_did =
    $_POST['did']
    ?? $course['did'];

if (
    $current_did
    ==
    $department['did']
) {

    echo "selected";

}

?>

>

<?php

echo htmlspecialchars(
    $department['dname']
);

?>

</option>

<?php

}

?>

</select>

<div
    id="didError"
    class="field-error"
>

<?php
echo htmlspecialchars(
    $errors['did'] ?? ""
);
?>

</div>

</div>

</div>

</div>


<div class="buttons">


<button
    type="submit"
    name="update_course">

    Update Course

</button>


<a
    href="Courses.php"
    class="back">

    Cancel

</a>


</div>


</form>


</div>

</div>

</div>


<script>

function validateForm() {

    let valid = true;


    let cname =
        document.getElementById("cname");

    let ctype =
        document.getElementById("ctype");

    let duration =
        document.getElementById("duration");

    let fees =
        document.getElementById("fees");

    let total_seats =
        document.getElementById("total_seats");

    let did =
        document.getElementById("did");


    let cnameError =
        document.getElementById("cnameError");

    let ctypeError =
        document.getElementById("ctypeError");

    let durationError =
        document.getElementById("durationError");

    let feesError =
        document.getElementById("feesError");

    let totalSeatsError =
        document.getElementById("totalSeatsError");

    let didError =
        document.getElementById("didError");


    /* Clear old validation */

    cname.classList.remove("invalid");
    ctype.classList.remove("invalid");
    duration.classList.remove("invalid");
    fees.classList.remove("invalid");
    total_seats.classList.remove("invalid");
    did.classList.remove("invalid");


    cnameError.innerHTML = "";
    ctypeError.innerHTML = "";
    durationError.innerHTML = "";
    feesError.innerHTML = "";
    totalSeatsError.innerHTML = "";
    didError.innerHTML = "";


    /* Course Name */

    if (cname.value.trim() == "") {

        cname.classList.add("invalid");

        cnameError.innerHTML =
            "Course Name is required.";

        valid = false;
    }


    /* Course Type */

    if (ctype.value.trim() == "") {

        ctype.classList.add("invalid");

        ctypeError.innerHTML =
            "Course Type is required.";

        valid = false;
    }


    /* Duration */

    if (duration.value.trim() == "") {

        duration.classList.add("invalid");

        durationError.innerHTML =
            "Duration is required.";

        valid = false;
    }


    /* Fees */

    if (fees.value.trim() == "") {

        fees.classList.add("invalid");

        feesError.innerHTML =
            "Course Fees is required.";

        valid = false;

    }

    else if (
        !/^[0-9]+(\.[0-9]+)?$/.test(
            fees.value.trim()
        )
    ) {

        fees.classList.add("invalid");

        feesError.innerHTML =
            "Course Fees must contain numbers only.";

        valid = false;

    }

    else if (
        Number(fees.value) <= 0
    ) {

        fees.classList.add("invalid");

        feesError.innerHTML =
            "Course Fees must be greater than 0.";

        valid = false;
    }


    /* Total Seats */

    if (total_seats.value.trim() == "") {

        total_seats.classList.add("invalid");

        totalSeatsError.innerHTML =
            "Total Seats is required.";

        valid = false;

    }

    else if (
        !/^[0-9]+$/.test(
            total_seats.value.trim()
        )
    ) {

        total_seats.classList.add("invalid");

        totalSeatsError.innerHTML =
            "Total Seats must contain numbers only.";

        valid = false;

    }

    else if (
        Number(total_seats.value) <= 0
    ) {

        total_seats.classList.add("invalid");

        totalSeatsError.innerHTML =
            "Total Seats must be greater than 0.";

        valid = false;
    }


    /* Department */

    if (did.value == "") {

        did.classList.add("invalid");

        didError.innerHTML =
            "Department is required.";

        valid = false;
    }


    return valid;
}


/* Remove Course Name error */

document.getElementById("cname").addEventListener(
    "input",
    function() {

        this.classList.remove("invalid");

        document.getElementById(
            "cnameError"
        ).innerHTML = "";

    }
);


/* Remove Course Type error */

document.getElementById("ctype").addEventListener(
    "input",
    function() {

        this.classList.remove("invalid");

        document.getElementById(
            "ctypeError"
        ).innerHTML = "";

    }
);


/* Remove Duration error */

document.getElementById("duration").addEventListener(
    "input",
    function() {

        this.classList.remove("invalid");

        document.getElementById(
            "durationError"
        ).innerHTML = "";

    }
);


/* Remove Fees error */

document.getElementById("fees").addEventListener(
    "input",
    function() {

        this.classList.remove("invalid");

        document.getElementById(
            "feesError"
        ).innerHTML = "";

    }
);


/* Remove Seats error */

document.getElementById("total_seats").addEventListener(
    "input",
    function() {

        this.classList.remove("invalid");

        document.getElementById(
            "totalSeatsError"
        ).innerHTML = "";

    }
);


/* Remove Department error */

document.getElementById("did").addEventListener(
    "change",
    function() {

        this.classList.remove("invalid");

        document.getElementById(
            "didError"
        ).innerHTML = "";

    }
);

</script>


</body>

</html>
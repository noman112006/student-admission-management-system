<?php

session_start();

if (!isset($_SESSION['uname'])) {
    header("Location: login.html");
    exit();
}

include "demo.php";

$message = "";
$saved = false;
$ano = "";


/* =========================
   GET DEPARTMENTS
========================= */

$departments = [];

$department_query = mysqli_query(
    $conn,
    "SELECT did, dname
     FROM department
     ORDER BY dname"
);

if (!$department_query) {
    die("Department Error: " . mysqli_error($conn));
}

while ($department = mysqli_fetch_assoc($department_query)) {
    $departments[] = $department;
}


/* =========================
   GET ALL COURSES
   AVAILABLE SEATS:
   TOTAL SEATS - APPROVED
========================= */

$courses = [];

$course_query = mysqli_query(
    $conn,
    "SELECT
        c.cid,
        c.cname,
        c.duration,
        c.fees,
        c.total_seats,
        c.did,
        (
            c.total_seats -
            (
                SELECT COUNT(*)
                FROM admission a
                WHERE a.cid = c.cid
                AND a.status = 'Approved'
            )
        ) AS available_seats
     FROM course c
     ORDER BY c.cname"
);

if (!$course_query) {
    die("Course Error: " . mysqli_error($conn));
}

while ($course = mysqli_fetch_assoc($course_query)) {
    $courses[] = $course;
}


/* =========================
   SAVE ADMISSION
========================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    /* Student */

    $sname = mysqli_real_escape_string(
        $conn,
        trim($_POST['sname'] ?? "")
    );

    $dob = mysqli_real_escape_string(
        $conn,
        $_POST['dob'] ?? ""
    );

    $gender = mysqli_real_escape_string(
        $conn,
        $_POST['gender'] ?? ""
    );

    $phone = mysqli_real_escape_string(
        $conn,
        trim($_POST['phone'] ?? "")
    );

    $email = mysqli_real_escape_string(
        $conn,
        trim($_POST['email'] ?? "")
    );

    $blood_group = mysqli_real_escape_string(
        $conn,
        $_POST['blood_group'] ?? ""
    );


    /* Address */

    $house_no = mysqli_real_escape_string(
        $conn,
        trim($_POST['house_no'] ?? "")
    );

    $street = mysqli_real_escape_string(
        $conn,
        trim($_POST['street'] ?? "")
    );

    $city = mysqli_real_escape_string(
        $conn,
        trim($_POST['city'] ?? "")
    );

    $state = mysqli_real_escape_string(
        $conn,
        $_POST['state'] ?? ""
    );

    $pincode = mysqli_real_escape_string(
        $conn,
        trim($_POST['pincode'] ?? "")
    );


    /* Academic */

    $qualification = mysqli_real_escape_string(
        $conn,
        $_POST['qualification'] ?? ""
    );

    $prev_institute = mysqli_real_escape_string(
        $conn,
        trim($_POST['prev_institute'] ?? "")
    );

    $pass_year = mysqli_real_escape_string(
        $conn,
        $_POST['pass_year'] ?? ""
    );

    $percent_cgpa = mysqli_real_escape_string(
        $conn,
        trim($_POST['percent_cgpa'] ?? "")
    );


    /* Admission */

    $cid = intval($_POST['cid'] ?? 0);

    $admit_year = mysqli_real_escape_string(
        $conn,
        $_POST['admit_year'] ?? ""
    );

    $ad_date = mysqli_real_escape_string(
        $conn,
        $_POST['ad_date'] ?? ""
    );


    /* Payment */

    $amount = mysqli_real_escape_string(
        $conn,
        trim($_POST['amount'] ?? "")
    );

    $pmode = mysqli_real_escape_string(
        $conn,
        $_POST['pmode'] ?? ""
    );

    $pdate = mysqli_real_escape_string(
        $conn,
        $_POST['pdate'] ?? ""
    );

    $pstatus = "Paid";


    /* =========================
       SERVER SIDE VALIDATION
    ========================= */

    if ($sname == "") {

        $message = "Full Name is required.";

    } elseif ($dob == "") {

        $message = "DOB is required.";

    } elseif ($gender == "") {

        $message = "Gender is required.";

    } elseif ($phone == "") {

        $message = "Phone is required.";

    } elseif (!preg_match("/^[0-9]{10}$/", $phone)) {

        $message = "Phone must be exactly 10 digits.";

    } elseif ($email == "") {

        $message = "Email is required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Enter a valid email address.";

    } elseif ($house_no == "") {

        $message = "House / Flat No. is required.";

    } elseif ($street == "") {

        $message = "Street is required.";

    } elseif ($city == "") {

        $message = "City is required.";

    } elseif ($state == "") {

        $message = "State is required.";

    } elseif ($pincode == "") {

        $message = "Pincode is required.";

    } elseif (!preg_match("/^[0-9]{6}$/", $pincode)) {

        $message = "Pincode must be exactly 6 digits.";

    } elseif ($qualification == "") {

        $message = "Qualification is required.";

    } elseif ($prev_institute == "") {

        $message = "Previous College / School is required.";

    } elseif ($pass_year == "") {

        $message = "Passing Year is required.";

    } elseif ($percent_cgpa == "") {

        $message = "Percentage / CGPA is required.";

    } elseif ($cid <= 0) {

        $message = "Please select a course.";

    } elseif ($admit_year == "") {

        $message = "Admission Year is required.";

    } elseif ($ad_date == "") {

        $message = "Admission Date is required.";

    } elseif ($amount == "") {

        $message = "Amount is required.";

    } elseif (!preg_match("/^[0-9]+(\.[0-9]+)?$/", $amount)) {

        $message = "Amount must contain numbers only.";

    } elseif ($pmode == "") {

        $message = "Payment Mode is required.";

    } elseif ($pdate == "") {

        $message = "Payment Date is required.";

    } else {


        /* =========================
           CHECK COURSE
        ========================= */

        $seat_query = mysqli_query(
            $conn,
            "SELECT
                c.total_seats,
                (
                    c.total_seats -
                    (
                        SELECT COUNT(*)
                        FROM admission a
                        WHERE a.cid = c.cid
                        AND a.status = 'Approved'
                    )
                ) AS available_seats
             FROM course c
             WHERE c.cid = $cid
             LIMIT 1"
        );


        if (!$seat_query) {

            $message = "Unable to check course seats.";

        } else {

            $seat_data = mysqli_fetch_assoc($seat_query);

            if (!$seat_data) {

                $message = "Selected course not found.";

            } else {

                $available_seats = intval(
                    $seat_data['available_seats']
                );


                if ($available_seats <= 0) {

                    $message =
                        "No seats available for this course.";

                } else {


                    /* =========================
                       GENERATE ADMISSION ID
                    ========================= */

                    do {

                        $ano =
                            "ADM" .
                            rand(100000, 999999);

                        $check_ano = mysqli_query(
                            $conn,
                            "SELECT ano
                             FROM admission
                             WHERE ano='$ano'"
                        );

                    } while (
                        $check_ano &&
                        mysqli_num_rows($check_ano) > 0
                    );


                    /* Temporary SID */

                    $temporary_sid = $ano;


                    /* =========================
                       SAVE STUDENT DETAILS
                    ========================= */

                    $student_sql = "INSERT INTO student
                    (
                        sid,
                        sname,
                        dob,
                        gender,
                        phone,
                        email,
                        blood_group,
                        house_no,
                        street,
                        city,
                        state,
                        pincode,
                        qualification,
                        prev_institute,
                        pass_year,
                        percent_cgpa
                    )
                    VALUES
                    (
                        '$temporary_sid',
                        '$sname',
                        '$dob',
                        '$gender',
                        '$phone',
                        '$email',
                        '$blood_group',
                        '$house_no',
                        '$street',
                        '$city',
                        '$state',
                        '$pincode',
                        '$qualification',
                        '$prev_institute',
                        '$pass_year',
                        '$percent_cgpa'
                    )";


                    if (!mysqli_query($conn, $student_sql)) {

                        $message =
                            "Student data could not be saved: "
                            . mysqli_error($conn);

                    } else {


                        /* =========================
                           SAVE ADMISSION
                        ========================= */

                        $admission_sql = "INSERT INTO admission
                        (
                            ano,
                            sid,
                            cid,
                            admit_year,
                            addate,
                            status
                        )
                        VALUES
                        (
                            '$ano',
                            '$temporary_sid',
                            '$cid',
                            '$admit_year',
                            '$ad_date',
                            'Pending'
                        )";


                        if (!mysqli_query(
                            $conn,
                            $admission_sql
                        )) {

                            $message =
                                "Admission could not be saved: "
                                . mysqli_error($conn);

                            mysqli_query(
                                $conn,
                                "DELETE FROM student
                                 WHERE sid='$temporary_sid'"
                            );

                        } else {


                            /* =========================
                               SAVE FIRST PAYMENT
                            ========================= */

                            $payment_sql = "INSERT INTO payment
                            (
                                ano,
                                amount,
                                pmode,
                                pdate,
                                pstatus
                            )
                            VALUES
                            (
                                '$ano',
                                '$amount',
                                '$pmode',
                                '$pdate',
                                '$pstatus'
                            )";


                            if (!mysqli_query(
                                $conn,
                                $payment_sql
                            )) {

                                $message =
                                    "Payment could not be saved: "
                                    . mysqli_error($conn);

                                mysqli_query(
                                    $conn,
                                    "DELETE FROM admission
                                     WHERE ano='$ano'"
                                );

                                mysqli_query(
                                    $conn,
                                    "DELETE FROM student
                                     WHERE sid='$temporary_sid'"
                                );

                            } else {


                                /* =========================
                                   RECENT ACTIVITY
                                ========================= */

                                mysqli_query(
                                    $conn,
                                    "INSERT INTO activity
                                    (activity_text)
                                    VALUES
                                    ('New admission added: $sname')"
                                );


                                $message =
                                    "Admission saved successfully! "
                                    . "Admission No: "
                                    . $ano;

                                $saved = true;
                            }
                        }
                    }
                }
            }
        }
    }
}

?>

<!DOCTYPE html>
<html>

<head>

<title>New Admission</title>

<style>

* {
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Arial;
}

body {
    background:linear-gradient(
        135deg,
        #eaf2fb,
        #d6e5f5
    );
}

.header {
    height:70px;
    background:linear-gradient(
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
    min-height:calc(100vh - 70px);
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
    box-shadow:0 8px 20px rgba(0,0,0,0.08);
}

.section {
    margin-bottom:30px;
    padding-bottom:30px;
    border-bottom:2px solid #0b3c7c;
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
    border:1px solid #ccc;
    border-radius:7px;
    outline:none;
}

input[readonly] {
    background:#f2f2f2;
}


/* RED VALIDATION */

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


.message {
    padding:12px;
    margin-bottom:20px;
    background:#eaf2fb;
    color:#0b3c7c;
    font-weight:bold;
    border-radius:7px;
}

.buttons {
    margin-top:25px;
    display:flex;
    align-items:center;
}

button {
    padding:12px 25px;
    background:#0b3c7c;
    color:white;
    border:none;
    border-radius:7px;
    font-weight:bold;
    margin-right:10px;
    cursor:pointer;
}

button:hover {
    background:#1559a6;
}

button:disabled {
    background:#aaa;
    cursor:not-allowed;
}

.cancel {
    background:#777;
}

.cancel:hover {
    background:#555;
}

.back {
    margin-left:auto;
    background:#0b3c7c;
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

<a href="Courses.php">
    Courses
</a>

<a href="Admissions.php" class="active">
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
    + New Admission
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
    id="admissionForm"
    onsubmit="return validateForm()">


<!-- PERSONAL INFORMATION -->

<div class="section">

<h3>
    1. Personal Information
</h3>


<div class="row">


<div class="field">

<label>
    Full Name <span class="required">*</span>
</label>

<input
    type="text"
    name="sname"
    id="sname">

<div id="snameError" class="field-error"></div>

</div>


<div class="field">

<label>
    DOB <span class="required">*</span>
</label>

<input
    type="date"
    name="dob"
    id="dob">

<div id="dobError" class="field-error"></div>

</div>


<div class="field">

<label>
    Gender <span class="required">*</span>
</label>

<select
    name="gender"
    id="gender">

<option value="">
    Select Gender
</option>

<option value="Male">
    Male
</option>

<option value="Female">
    Female
</option>

<option value="Other">
    Other
</option>

</select>

<div id="genderError" class="field-error"></div>

</div>

</div>


<div class="row">


<div class="field">

<label>
    Phone <span class="required">*</span>
</label>

<input
    type="text"
    name="phone"
    id="phone"
    maxlength="10"
    oninput="this.value=this.value.replace(/[^0-9]/g,'')">

<div id="phoneError" class="field-error"></div>

</div>


<div class="field">

<label>
    Email <span class="required">*</span>
</label>

<input
    type="text"
    name="email"
    id="email">

<div id="emailError" class="field-error"></div>

</div>


<div class="field">

<label>
    Blood Group
</label>

<select name="blood_group">

<option value="">
    Select Blood Group
</option>

<option>A+</option>
<option>A-</option>
<option>B+</option>
<option>B-</option>
<option>AB+</option>
<option>AB-</option>
<option>O+</option>
<option>O-</option>

</select>

</div>

</div>

</div>


<!-- ADDRESS -->

<div class="section">

<h3>
    2. Address
</h3>


<div class="row">


<div class="field">

<label>
    House / Flat No. <span class="required">*</span>
</label>

<input
    type="text"
    name="house_no"
    id="house_no">

<div id="houseError" class="field-error"></div>

</div>


<div class="field">

<label>
    Street <span class="required">*</span>
</label>

<input
    type="text"
    name="street"
    id="street">

<div id="streetError" class="field-error"></div>

</div>

</div>


<div class="row">


<div class="field">

<label>
    City <span class="required">*</span>
</label>

<input
    type="text"
    name="city"
    id="city">

<div id="cityError" class="field-error"></div>

</div>


<div class="field">

<label>
    State <span class="required">*</span>
</label>

<select
    name="state"
    id="state">

<option value="">
    Select State
</option>

<option>
    Maharashtra
</option>

<option>
    Gujarat
</option>

<option>
    Goa
</option>

<option>
    Karnataka
</option>

</select>

<div id="stateError" class="field-error"></div>

</div>


<div class="field">

<label>
    Pincode <span class="required">*</span>
</label>

<input
    type="text"
    name="pincode"
    id="pincode"
    maxlength="6"
    oninput="this.value=this.value.replace(/[^0-9]/g,'')">

<div id="pincodeError" class="field-error"></div>

</div>

</div>

</div>


<!-- ACADEMIC -->

<div class="section">

<h3>
    3. Previous Academic Details
</h3>


<div class="row">


<div class="field">

<label>
    Qualification <span class="required">*</span>
</label>

<select
    name="qualification"
    id="qualification">

<option value="">
    Select Qualification
</option>

<option>
    12th
</option>

<option>
    Diploma
</option>

<option>
    Graduation
</option>

</select>

<div id="qualificationError" class="field-error"></div>

</div>


<div class="field">

<label>
    Previous College / School <span class="required">*</span>
</label>

<input
    type="text"
    name="prev_institute"
    id="prev_institute">

<div id="instituteError" class="field-error"></div>

</div>


<div class="field">

<label>
    Passing Year <span class="required">*</span>
</label>

<select
    name="pass_year"
    id="pass_year">

<option value="">
    Select Year
</option>

<option>2026</option>
<option>2025</option>
<option>2024</option>
<option>2023</option>

</select>

<div id="yearError" class="field-error"></div>

</div>

</div>


<div class="row">


<div class="field">

<label>
    Percentage / CGPA <span class="required">*</span>
</label>

<input
    type="text"
    name="percent_cgpa"
    id="percent_cgpa">

<div id="percentError" class="field-error"></div>

</div>

</div>

</div>


<!-- COURSE DETAILS -->

<div class="section">

<h3>
    4. Course Details
</h3>


<div class="row">


<div class="field">

<label>
    Department <span class="required">*</span>
</label>

<select
    id="department">

<option value="">
    Select Department
</option>

<?php

foreach ($departments as $department) {

?>

<option
    value="<?php echo $department['did']; ?>">

<?php
echo htmlspecialchars($department['dname']);
?>

</option>

<?php

}

?>

</select>

<div id="departmentError" class="field-error"></div>

</div>


<div class="field">

<label>
    Select Course <span class="required">*</span>
</label>

<select
    id="course"
    name="cid"
    disabled>

<option value="">
    Select Course
</option>

</select>

<div id="courseError" class="field-error"></div>

</div>

</div>


<div class="row">


<div class="field">

<label>
    Course Duration
</label>

<input
    type="text"
    id="duration"
    value="--"
    readonly>

</div>


<div class="field">

<label>
    Course Fees
</label>

<input
    type="text"
    id="fees"
    value="--"
    readonly>

</div>


<div class="field">

<label>
    Available Seats
</label>

<input
    type="text"
    id="seats"
    value="--"
    readonly>

</div>

</div>

</div>


<!-- ADMISSION DETAILS -->

<div class="section">

<h3>
    5. Admission Details
</h3>


<div class="row">


<div class="field">

<label>
    Admission Year <span class="required">*</span>
</label>

<select
    name="admit_year"
    id="admit_year">

<option value="">
    Select Year
</option>

<option>
    First Year
</option>

<option>
    Second Year
</option>

<option>
    Third Year
</option>

</select>

<div id="admitYearError" class="field-error"></div>

</div>


<div class="field">

<label>
    Admission Date <span class="required">*</span>
</label>

<input
    type="date"
    name="ad_date"
    id="ad_date">

<div id="adDateError" class="field-error"></div>

</div>


<div class="field">

<label>
    Selected Course
</label>

<input
    type="text"
    id="selectedCourse"
    value="--"
    readonly>

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

<label>
    Amount <span class="required">*</span>
</label>

<input
    type="text"
    name="amount"
    id="amount"
    placeholder="Enter Amount"
    oninput="this.value=this.value.replace(/[^0-9.]/g,'')">

<div id="amountError" class="field-error"></div>

</div>


<div class="field">

<label>
    Payment Mode <span class="required">*</span>
</label>

<select
    name="pmode"
    id="pmode">

<option value="">
    Select Payment Mode
</option>

<option value="Cash">
    Cash
</option>

<option value="UPI">
    UPI
</option>

<option value="Card">
    Card
</option>

<option value="Net Banking">
    Net Banking
</option>

</select>

<div id="pmodeError" class="field-error"></div>

</div>

</div>


<div class="row">

<div class="field">

<label>
    Payment Date <span class="required">*</span>
</label>

<input
    type="date"
    name="pdate"
    id="pdate">

<div id="pdateError" class="field-error"></div>

</div>

</div>

</div>


<!-- BUTTONS -->

<div class="buttons">

<button
    type="submit"
    id="saveButton">

Save

</button>


<?php if ($saved) { ?>

<button
    type="button"
    onclick="window.location.href='Receipt.php?ano=<?php echo urlencode($ano); ?>';">

Print

</button>

<?php } else { ?>

<button
    type="button"
    disabled>

Print

</button>

<?php } ?>


<button
    type="button"
    class="cancel"
    onclick="document.getElementById('admissionForm').reset();">

Cancel

</button>


<button
    type="button"
    class="back"
    onclick="window.location.href='Admissions.php';">

BACK

</button>

</div>


</form>


</div>

</div>

</div>


<script>

/* =========================
   COURSES FROM PHP
========================= */

let courses =
<?php echo json_encode($courses); ?>;


/* =========================
   ELEMENTS
========================= */

let department =
    document.getElementById("department");

let course =
    document.getElementById("course");

let duration =
    document.getElementById("duration");

let fees =
    document.getElementById("fees");

let seats =
    document.getElementById("seats");

let selectedCourse =
    document.getElementById("selectedCourse");


/* =========================
   DEPARTMENT CHANGE
========================= */

department.addEventListener(
    "change",
    function() {

        let did = this.value;

        course.innerHTML =
            '<option value="">Select Course</option>';

        duration.value = "--";
        fees.value = "--";
        seats.value = "--";
        selectedCourse.value = "--";

        course.classList.remove("invalid");
        document.getElementById("courseError").innerHTML = "";

        if (did == "") {

            course.disabled = true;

            return;
        }

        course.disabled = false;

        for (let i = 0; i < courses.length; i++) {

            if (
                String(courses[i].did) ===
                String(did)
            ) {

                let option =
                    document.createElement("option");

                option.value =
                    courses[i].cid;

                option.textContent =
                    courses[i].cname;

                course.appendChild(option);
            }
        }

    }
);


/* =========================
   COURSE CHANGE
========================= */

course.addEventListener(
    "change",
    function() {

        let cid = this.value;

        course.classList.remove("invalid");

        document.getElementById("courseError").innerHTML = "";

        if (cid == "") {

            duration.value = "--";
            fees.value = "--";
            seats.value = "--";
            selectedCourse.value = "--";

            return;
        }

        for (let i = 0; i < courses.length; i++) {

            if (
                String(courses[i].cid) ===
                String(cid)
            ) {

                duration.value =
                    courses[i].duration;

                fees.value =
                    courses[i].fees;

                seats.value =
                    courses[i].available_seats;

                selectedCourse.value =
                    courses[i].cname;

                break;
            }
        }

    }
);


/* =========================
   VALIDATION
========================= */

function validateForm() {

    let valid = true;


    let fields = [
        ["sname", "snameError", "Full Name is required."],
        ["dob", "dobError", "DOB is required."],
        ["gender", "genderError", "Gender is required."],
        ["phone", "phoneError", "Phone is required."],
        ["email", "emailError", "Email is required."],
        ["house_no", "houseError", "House / Flat No. is required."],
        ["street", "streetError", "Street is required."],
        ["city", "cityError", "City is required."],
        ["state", "stateError", "State is required."],
        ["pincode", "pincodeError", "Pincode is required."],
        ["qualification", "qualificationError", "Qualification is required."],
        ["prev_institute", "instituteError", "Previous College / School is required."],
        ["pass_year", "yearError", "Passing Year is required."],
        ["percent_cgpa", "percentError", "Percentage / CGPA is required."],
        ["department", "departmentError", "Department is required."],
        ["course", "courseError", "Course is required."],
        ["admit_year", "admitYearError", "Admission Year is required."],
        ["ad_date", "adDateError", "Admission Date is required."],
        ["amount", "amountError", "Amount is required."],
        ["pmode", "pmodeError", "Payment Mode is required."],
        ["pdate", "pdateError", "Payment Date is required."]
    ];


    /* Clear old errors */

    for (let i = 0; i < fields.length; i++) {

        let input =
            document.getElementById(fields[i][0]);

        let error =
            document.getElementById(fields[i][1]);

        input.classList.remove("invalid");

        error.innerHTML = "";
    }


    /* Required validation */

    for (let i = 0; i < fields.length; i++) {

        let input =
            document.getElementById(fields[i][0]);

        if (input.value.trim() == "") {

            input.classList.add("invalid");

            document.getElementById(
                fields[i][1]
            ).innerHTML = fields[i][2];

            valid = false;
        }
    }


    /* Phone */

    let phone =
        document.getElementById("phone");

    if (
        phone.value != "" &&
        !/^[0-9]{10}$/.test(phone.value)
    ) {

        phone.classList.add("invalid");

        document.getElementById(
            "phoneError"
        ).innerHTML =
            "Phone must be exactly 10 digits.";

        valid = false;
    }


    /* Email */

    let email =
        document.getElementById("email");

    if (
        email.value != "" &&
        !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)
    ) {

        email.classList.add("invalid");

        document.getElementById(
            "emailError"
        ).innerHTML =
            "Enter a valid email address.";

        valid = false;
    }


    /* Pincode */

    let pincode =
        document.getElementById("pincode");

    if (
        pincode.value != "" &&
        !/^[0-9]{6}$/.test(pincode.value)
    ) {

        pincode.classList.add("invalid");

        document.getElementById(
            "pincodeError"
        ).innerHTML =
            "Pincode must be exactly 6 digits.";

        valid = false;
    }


    /* Amount */

    let amount =
        document.getElementById("amount");

    if (
        amount.value != "" &&
        !/^[0-9]+(\.[0-9]+)?$/.test(amount.value)
    ) {

        amount.classList.add("invalid");

        document.getElementById(
            "amountError"
        ).innerHTML =
            "Amount must contain numbers only.";

        valid = false;
    }


    return valid;
}


/* =========================
   REMOVE ERROR WHILE TYPING
========================= */

let fieldsToClear = [
    ["sname", "snameError"],
    ["dob", "dobError"],
    ["phone", "phoneError"],
    ["email", "emailError"],
    ["house_no", "houseError"],
    ["street", "streetError"],
    ["city", "cityError"],
    ["pincode", "pincodeError"],
    ["prev_institute", "instituteError"],
    ["percent_cgpa", "percentError"],
    ["amount", "amountError"],
    ["ad_date", "adDateError"],
    ["pdate", "pdateError"]
];


for (let i = 0; i < fieldsToClear.length; i++) {

    document
        .getElementById(fieldsToClear[i][0])
        .addEventListener(
            "input",
            function() {

                this.classList.remove("invalid");

                document.getElementById(
                    fieldsToClear[i][1]
                ).innerHTML = "";

            }
        );
}


/* =========================
   SELECT ERROR REMOVE
========================= */

let selectFields = [
    ["gender", "genderError"],
    ["state", "stateError"],
    ["qualification", "qualificationError"],
    ["pass_year", "yearError"],
    ["department", "departmentError"],
    ["course", "courseError"],
    ["admit_year", "admitYearError"],
    ["pmode", "pmodeError"]
];


for (let i = 0; i < selectFields.length; i++) {

    document
        .getElementById(selectFields[i][0])
        .addEventListener(
            "change",
            function() {

                this.classList.remove("invalid");

                document.getElementById(
                    selectFields[i][1]
                ).innerHTML = "";

            }
        );
}

</script>

</body>

</html>
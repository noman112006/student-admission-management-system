<?php

session_start();

if (!isset($_SESSION['uname'])) {
    header("Location: login.html");
    exit();
}

include "demo.php";

if (!isset($_GET['sid'])) {
    header("Location: Students.php");
    exit();
}

$sid = mysqli_real_escape_string($conn, $_GET['sid']);

$message = "";

/* Update */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $sname = mysqli_real_escape_string($conn, trim($_POST['sname'] ?? ""));
    $dob = mysqli_real_escape_string($conn, $_POST['dob'] ?? "");
    $gender = mysqli_real_escape_string($conn, $_POST['gender'] ?? "");
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone'] ?? ""));
    $email = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ""));
    $blood_group = mysqli_real_escape_string($conn, $_POST['blood_group'] ?? "");
    $house_no = mysqli_real_escape_string($conn, trim($_POST['house_no'] ?? ""));
    $street = mysqli_real_escape_string($conn, trim($_POST['street'] ?? ""));
    $city = mysqli_real_escape_string($conn, trim($_POST['city'] ?? ""));
    $state = mysqli_real_escape_string($conn, $_POST['state'] ?? "");
    $pincode = mysqli_real_escape_string($conn, trim($_POST['pincode'] ?? ""));
    $qualification = mysqli_real_escape_string($conn, $_POST['qualification'] ?? "");
    $prev_institute = mysqli_real_escape_string($conn, trim($_POST['prev_institute'] ?? ""));
    $pass_year = mysqli_real_escape_string($conn, $_POST['pass_year'] ?? "");
    $percent_cgpa = mysqli_real_escape_string($conn, trim($_POST['percent_cgpa'] ?? ""));

    /* Server-side validation */

    if ($sname == "") {
        $message = "Full Name is required.";
    }
    elseif ($dob == "") {
        $message = "DOB is required.";
    }
    elseif ($gender == "") {
        $message = "Gender is required.";
    }
    elseif ($phone == "") {
        $message = "Phone is required.";
    }
    elseif (!preg_match("/^[0-9]{10}$/", $phone)) {
        $message = "Phone must be exactly 10 digits.";
    }
    elseif ($email == "") {
        $message = "Email is required.";
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Enter a valid email address.";
    }
    elseif ($house_no == "") {
        $message = "House / Flat No. is required.";
    }
    elseif ($street == "") {
        $message = "Street is required.";
    }
    elseif ($city == "") {
        $message = "City is required.";
    }
    elseif ($state == "") {
        $message = "State is required.";
    }
    elseif ($pincode == "") {
        $message = "Pincode is required.";
    }
    elseif (!preg_match("/^[0-9]{6}$/", $pincode)) {
        $message = "Pincode must be exactly 6 digits.";
    }
    elseif ($qualification == "") {
        $message = "Qualification is required.";
    }
    elseif ($prev_institute == "") {
        $message = "Previous Institute is required.";
    }
    elseif ($pass_year == "") {
        $message = "Passing Year is required.";
    }
    elseif (!preg_match("/^[0-9]{4}$/", $pass_year)) {
        $message = "Passing Year must be 4 digits.";
    }
    elseif ($percent_cgpa == "") {
        $message = "Percentage / CGPA is required.";
    }
    else {

        $sql = "UPDATE student SET

                sname='$sname',
                dob='$dob',
                gender='$gender',
                phone='$phone',
                email='$email',
                blood_group='$blood_group',
                house_no='$house_no',
                street='$street',
                city='$city',
                state='$state',
                pincode='$pincode',
                qualification='$qualification',
                prev_institute='$prev_institute',
                pass_year='$pass_year',
                percent_cgpa='$percent_cgpa'

                WHERE sid='$sid'";

        if (mysqli_query($conn, $sql)) {

            header(
                "Location: ViewStudent.php?sid="
                . urlencode($sid)
            );

            exit();

        } else {

            $message =
                "Update failed: "
                . mysqli_error($conn);
        }
    }
}


/* Get student */

$result = mysqli_query(
    $conn,
    "SELECT *
     FROM student
     WHERE sid='$sid'
     LIMIT 1"
);

if (!$result || mysqli_num_rows($result) == 0) {
    die("Student not found.");
}

$student = mysqli_fetch_assoc($result);

?>

<!DOCTYPE html>
<html>

<head>

<title>Edit Student</title>

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

/* Red border for invalid field */

input.invalid,
select.invalid {
    border:2px solid red;
}

/* Error text */

.field-error {
    color:red;
    font-size:13px;
    margin-top:5px;
    min-height:16px;
}

button,
.back,
.next-year {
    padding:12px 25px;
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
    background:#ffe6e6;
    color:red;
    font-weight:bold;
    border-radius:7px;
    margin-bottom:20px;
}

.next-year {
    background:#198754;
}

.next-year:hover {
    background:#146c43;
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
    Edit Student
</div>

<div class="box">

<?php

if ($message != "") {

    echo "<div class='message'>"
        . htmlspecialchars($message)
        . "</div>";
}

?>

<form method="POST" onsubmit="return validateForm()">


<!-- PERSONAL INFORMATION -->

<div class="section">

<h3>
    Personal Information
</h3>

<div class="row">

<div class="field">

<label>
    Student ID
</label>

<input
    type="text"
    value="<?php echo htmlspecialchars($student['sid']); ?>"
    readonly>

</div>


<div class="field">

<label>
    Full Name <span class="required">*</span>
</label>

<input
    type="text"
    name="sname"
    id="sname"
    value="<?php echo htmlspecialchars($student['sname']); ?>">

<div id="snameError" class="field-error"></div>

</div>


<div class="field">

<label>
    DOB <span class="required">*</span>
</label>

<input
    type="date"
    name="dob"
    id="dob"
    value="<?php echo htmlspecialchars($student['dob']); ?>">

<div id="dobError" class="field-error"></div>

</div>

</div>


<div class="row">

<div class="field">

<label>
    Gender <span class="required">*</span>
</label>

<select name="gender" id="gender">

<option value="">Select Gender</option>

<option value="Male"
<?php
if ($student['gender'] == 'Male') echo 'selected';
?>>
Male
</option>

<option value="Female"
<?php
if ($student['gender'] == 'Female') echo 'selected';
?>>
Female
</option>

<option value="Other"
<?php
if ($student['gender'] == 'Other') echo 'selected';
?>>
Other
</option>

</select>

<div id="genderError" class="field-error"></div>

</div>


<div class="field">

<label>
    Phone <span class="required">*</span>
</label>

<input
    type="text"
    name="phone"
    id="phone"
    maxlength="10"
    value="<?php echo htmlspecialchars($student['phone']); ?>"
    oninput="this.value=this.value.replace(/[^0-9]/g,'')">

<div id="phoneError" class="field-error"></div>

</div>


<div class="field">

<label>
    Email <span class="required">*</span>
</label>

<input
    type="email"
    name="email"
    id="email"
    value="<?php echo htmlspecialchars($student['email']); ?>">

<div id="emailError" class="field-error"></div>

</div>

</div>


<div class="row">

<div class="field">

<label>
    Blood Group
</label>

<input
    type="text"
    name="blood_group"
    value="<?php echo htmlspecialchars($student['blood_group']); ?>">

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

<label>
    House / Flat No. <span class="required">*</span>
</label>

<input
    type="text"
    name="house_no"
    id="house_no"
    value="<?php echo htmlspecialchars($student['house_no']); ?>">

<div id="houseError" class="field-error"></div>

</div>


<div class="field">

<label>
    Street <span class="required">*</span>
</label>

<input
    type="text"
    name="street"
    id="street"
    value="<?php echo htmlspecialchars($student['street']); ?>">

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
    id="city"
    value="<?php echo htmlspecialchars($student['city']); ?>">

<div id="cityError" class="field-error"></div>

</div>


<div class="field">

<label>
    State <span class="required">*</span>
</label>

<input
    type="text"
    name="state"
    id="state"
    value="<?php echo htmlspecialchars($student['state']); ?>">

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
    value="<?php echo htmlspecialchars($student['pincode']); ?>"
    oninput="this.value=this.value.replace(/[^0-9]/g,'')">

<div id="pincodeError" class="field-error"></div>

</div>

</div>

</div>


<!-- ACADEMIC DETAILS -->

<div class="section">

<h3>
    Academic Details
</h3>

<div class="row">

<div class="field">

<label>
    Qualification <span class="required">*</span>
</label>

<input
    type="text"
    name="qualification"
    id="qualification"
    value="<?php echo htmlspecialchars($student['qualification']); ?>">

<div id="qualificationError" class="field-error"></div>

</div>


<div class="field">

<label>
    Previous Institute <span class="required">*</span>
</label>

<input
    type="text"
    name="prev_institute"
    id="prev_institute"
    value="<?php echo htmlspecialchars($student['prev_institute']); ?>">

<div id="instituteError" class="field-error"></div>

</div>

</div>


<div class="row">

<div class="field">

<label>
    Passing Year <span class="required">*</span>
</label>

<input
    type="text"
    name="pass_year"
    id="pass_year"
    maxlength="4"
    value="<?php echo htmlspecialchars($student['pass_year']); ?>"
    oninput="this.value=this.value.replace(/[^0-9]/g,'')">

<div id="yearError" class="field-error"></div>

</div>


<div class="field">

<label>
    Percentage / CGPA <span class="required">*</span>
</label>

<input
    type="text"
    name="percent_cgpa"
    id="percent_cgpa"
    value="<?php echo htmlspecialchars($student['percent_cgpa']); ?>">

<div id="percentError" class="field-error"></div>

</div>

</div>

</div>


<div class="buttons">

<button type="submit">
    Update Student
</button>

<a
    href="NextYearAdmission.php?sid=<?php
    echo urlencode($student['sid']);
    ?>"
    class="next-year">

    Next Year Admission

</a>

<a
    href="ViewStudent.php?sid=<?php
    echo urlencode($student['sid']);
    ?>"
    class="back cancel">

    Cancel

</a>

</div>


</form>

</div>

</div>

</div>


<script>

function validateForm() {

    var valid = true;

    var fields = [
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
        ["prev_institute", "instituteError", "Previous Institute is required."],
        ["pass_year", "yearError", "Passing Year is required."],
        ["percent_cgpa", "percentError", "Percentage / CGPA is required."]
    ];

    /* Clear previous errors */

    for (var i = 0; i < fields.length; i++) {

        document.getElementById(fields[i][0]).classList.remove("invalid");

        document.getElementById(fields[i][1]).innerHTML = "";
    }


    /* Required validation */

    for (var i = 0; i < fields.length; i++) {

        var input = document.getElementById(fields[i][0]);

        if (input.value.trim() == "") {

            input.classList.add("invalid");

            document.getElementById(fields[i][1]).innerHTML =
                fields[i][2];

            valid = false;
        }
    }


    /* Phone validation */

    var phone = document.getElementById("phone");

    if (phone.value != "" &&
        !/^[0-9]{10}$/.test(phone.value)) {

        phone.classList.add("invalid");

        document.getElementById("phoneError").innerHTML =
            "Phone must be exactly 10 digits.";

        valid = false;
    }


    /* Email validation */

    var email = document.getElementById("email");

    if (email.value != "" &&
        !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {

        email.classList.add("invalid");

        document.getElementById("emailError").innerHTML =
            "Enter a valid email address.";

        valid = false;
    }


    /* Pincode validation */

    var pincode = document.getElementById("pincode");

    if (pincode.value != "" &&
        !/^[0-9]{6}$/.test(pincode.value)) {

        pincode.classList.add("invalid");

        document.getElementById("pincodeError").innerHTML =
            "Pincode must be exactly 6 digits.";

        valid = false;
    }


    /* Passing year validation */

    var year = document.getElementById("pass_year");

    if (year.value != "" &&
        !/^[0-9]{4}$/.test(year.value)) {

        year.classList.add("invalid");

        document.getElementById("yearError").innerHTML =
            "Passing Year must be 4 digits.";

        valid = false;
    }


    return valid;
}


/* Remove error while typing */

var allFields = [

    ["sname", "snameError"],
    ["dob", "dobError"],
    ["gender", "genderError"],
    ["phone", "phoneError"],
    ["email", "emailError"],
    ["house_no", "houseError"],
    ["street", "streetError"],
    ["city", "cityError"],
    ["state", "stateError"],
    ["pincode", "pincodeError"],
    ["qualification", "qualificationError"],
    ["prev_institute", "instituteError"],
    ["pass_year", "yearError"],
    ["percent_cgpa", "percentError"]

];


for (var i = 0; i < allFields.length; i++) {

    document.getElementById(allFields[i][0]).addEventListener(
        "input",
        function() {

            this.classList.remove("invalid");

            var errorId = "";

            for (var j = 0; j < allFields.length; j++) {

                if (allFields[j][0] == this.id) {
                    errorId = allFields[j][1];
                    break;
                }
            }

            if (errorId != "") {
                document.getElementById(errorId).innerHTML = "";
            }

        }
    );

}


/* Select fields */

document.getElementById("gender").addEventListener(
    "change",
    function() {

        this.classList.remove("invalid");

        document.getElementById("genderError").innerHTML = "";

    }
);

</script>

</body>

</html>
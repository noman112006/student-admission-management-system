<?php

session_start();

if (!isset($_SESSION['uname'])) {
    header("Location: login.html");
    exit();
}

include "demo.php";

$message = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $dname = trim($_POST['dname'] ?? "");

    /* Empty check */

    if ($dname == "") {

        $message = "Department Name is required.";

    } else {

        $dname_safe = mysqli_real_escape_string($conn, $dname);

        /* Check duplicate */

        $check = mysqli_query(
            $conn,
            "SELECT did
             FROM department
             WHERE dname='$dname_safe'"
        );

        if (!$check) {

            $message = "Database error.";

        } else {

            if (mysqli_num_rows($check) > 0) {

                $message = "Department already exists.";

            } else {

                /* Insert department */

                $sql = "INSERT INTO department
                        (dname)
                        VALUES
                        ('$dname_safe')";

                if (mysqli_query($conn, $sql)) {

                    /*
                       Department added successfully
                       Go back to Add Course
                    */

                    header("Location: AddCourse.php");
                    exit();

                } else {

                    $message = "Department could not be added.";
                }
            }
        }
    }
}

?>

<!DOCTYPE html>
<html>

<head>

<title>Add Department</title>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial;
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
    height: 70px;

    background:
        linear-gradient(
            90deg,
            #0b3c7c,
            #1559a6
        );

    color: white;

    display: flex;

    align-items: center;

    padding: 0 30px;

    font-size: 22px;

    font-weight: bold;
}

.container {
    display: flex;

    min-height:
        calc(100vh - 70px);
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

    max-width: 600px;

    padding: 30px;

    border-radius: 12px;

    box-shadow:
        0 5px 15px rgba(0,0,0,0.10);
}

label {
    display: block;

    font-weight: bold;

    margin-bottom: 8px;
}

.required {
    color: red;
}

input {
    width: 100%;

    padding: 12px;

    border: 1px solid #ccc;

    border-radius: 7px;

    margin-bottom: 5px;

    font-size: 15px;
}

/* Red border */

input.invalid {
    border: 2px solid red;
}

/* Error */

.field-error {
    color: red;

    font-size: 13px;

    min-height: 18px;

    margin-bottom: 15px;
}

button,
.back {
    padding: 12px 22px;

    background: #0b3c7c;

    color: white;

    border: none;

    border-radius: 7px;

    font-weight: bold;

    text-decoration: none;

    cursor: pointer;
}

button:hover {
    background: #1559a6;
}

.cancel {
    background: #777;
}

.message {
    padding: 12px;

    margin-bottom: 20px;

    background: #ffe6e6;

    color: red;

    font-weight: bold;

    border-radius: 7px;
}

.buttons {
    display: flex;

    gap: 10px;

    margin-top: 10px;
}

</style>

</head>

<body>

<div class="header">

    STUDENT ADMISSION SYSTEM

</div>


<div class="container">


<!-- SIDEBAR -->

<div class="sidebar">

    <a href="dashboard.php">
        Dashboard
    </a>

    <a href="Students.php">
        Students
    </a>

    <a href="Courses.php" class="active">
        Courses
    </a>

    <a href="Admissions.php">
        Admissions
    </a>

    <a href="logout.php">
        Logout
    </a>

</div>


<!-- MAIN -->

<div class="main">

    <div class="title">
        Add Department
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


        <form method="POST" onsubmit="return validateForm()">


            <label>
                Department Name <span class="required">*</span>
            </label>


            <input
                type="text"
                name="dname"
                id="dname"
                placeholder="Example: Law"
                value="<?php echo htmlspecialchars($_POST['dname'] ?? ''); ?>"
            >


            <div
                id="dnameError"
                class="field-error">
            </div>


            <div class="buttons">


                <button type="submit">
                    Add Department
                </button>


                <a
                    href="AddCourse.php"
                    class="back">

                    Cancel

                </a>


            </div>


        </form>


    </div>

</div>

</div>


<script>

/* Validation */

function validateForm() {

    var dname =
        document.getElementById("dname");

    var error =
        document.getElementById("dnameError");

    var valid = true;


    /* Clear old error */

    dname.classList.remove("invalid");

    error.innerHTML = "";


    /* Check empty */

    if (dname.value.trim() == "") {

        dname.classList.add("invalid");

        error.innerHTML =
            "Department Name is required.";

        valid = false;
    }


    return valid;
}


/* Remove error while typing */

document.getElementById("dname").addEventListener(
    "input",
    function() {

        this.classList.remove("invalid");

        document.getElementById("dnameError").innerHTML = "";

    }
);

</script>


</body>

</html>
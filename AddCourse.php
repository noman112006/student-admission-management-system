<?php

include "demo.php";

$message = "";
$error = "";

/* Add Course */

if (isset($_POST['add_course'])) {

    $cname = trim($_POST['cname'] ?? "");
    $ctype = trim($_POST['ctype'] ?? "");
    $duration = trim($_POST['duration'] ?? "");
    $fees = trim($_POST['fees'] ?? "");
    $total_seats = trim($_POST['total_seats'] ?? "");
    $did = trim($_POST['did'] ?? "");

    if ($cname == "" || $ctype == "" || $duration == "" || $fees == "" || $total_seats == "" || $did == "") {

        $error = "Please fill all fields.";

    } else {

        $check = mysqli_query(
            $conn,
            "SELECT * FROM course WHERE cname='$cname'"
        );

        if (mysqli_num_rows($check) > 0) {

            $error = "Course already exists.";

        } else {

            $sql = "INSERT INTO course
                    (cname, ctype, duration, fees, total_seats, did)
                    VALUES
                    ('$cname', '$ctype', '$duration', '$fees', '$total_seats', '$did')";

            if (mysqli_query($conn, $sql)) {

                mysqli_query(
                    $conn,
                    "INSERT INTO activity (activity_text)
                     VALUES ('New course added: $cname')"
                );

                $message = "Course added successfully.";

            } else {

                $error = "Unable to add course.";
            }
        }
    }
}


/* Get Departments */

$departments = mysqli_query(
    $conn,
    "SELECT * FROM department ORDER BY dname"
);

?>

<!DOCTYPE html>
<html>

<head>

    <title>Add Course</title>

    <style>

        body {
            margin: 0;
            font-family: Arial;
            background: linear-gradient(135deg, #eaf2fb, #d6e5f5);
        }

        .header {
            background: linear-gradient(90deg, #0b3c7c, #1559a6);
            color: white;
            padding: 18px;
            font-size: 25px;
            font-weight: bold;
        }

        .container {
            width: 600px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }

        h2 {
            margin-top: 0;
            color: #0b3c7c;
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }

        .required {
            color: red;
        }

        input,
        select {
            width: 100%;
            padding: 10px;
            margin-top: 7px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        /* Red border */

        input.invalid,
        select.invalid {
            border: 2px solid red;
        }

        /* Error message */

        .field-error {
            color: red;
            font-size: 13px;
            margin-top: 4px;
            min-height: 16px;
        }

        .department-row {
            display: flex;
            gap: 10px;
            align-items: end;
        }

        .department-row select {
            flex: 1;
        }

        .new-dept {
            background: #28a745;
            color: white;
            text-decoration: none;
            padding: 10px 14px;
            border-radius: 5px;
            white-space: nowrap;
        }

        .buttons {
            margin-top: 25px;
            display: flex;
            gap: 10px;
        }

        button {
            background: #0b3c7c;
            color: white;
            border: none;
            padding: 11px 20px;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background: #1559a6;
        }

        .back {
            background: #777;
            color: white;
            text-decoration: none;
            padding: 11px 20px;
            border-radius: 5px;
        }

        .success {
            color: green;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .error {
            color: red;
            font-weight: bold;
            margin-bottom: 15px;
        }

    </style>

</head>


<body>

<div class="header">
    Student Admission Management System
</div>


<div class="container">

    <h2>Add New Course</h2>


    <?php

    if ($message != "") {
        echo "<div class='success'>$message</div>";
    }

    if ($error != "") {
        echo "<div class='error'>$error</div>";
    }

    ?>


    <form method="post" onsubmit="return validateForm()">


        <!-- Course Name -->

        <label>
            Course Name <span class="required">*</span>
        </label>

        <input
            type="text"
            name="cname"
            id="cname"
            placeholder="Enter course name"
            value="<?php echo htmlspecialchars($_POST['cname'] ?? ''); ?>"
        >

        <div id="cnameError" class="field-error"></div>


        <!-- Course Type -->

        <label>
            Course Type <span class="required">*</span>
        </label>

        <select name="ctype" id="ctype">

            <option value="">Select Course Type</option>

            <option value="UG"
                <?php if (($_POST['ctype'] ?? '') == "UG") echo "selected"; ?>>
                UG
            </option>

            <option value="PG"
                <?php if (($_POST['ctype'] ?? '') == "PG") echo "selected"; ?>>
                PG
            </option>

            <option value="Diploma"
                <?php if (($_POST['ctype'] ?? '') == "Diploma") echo "selected"; ?>>
                Diploma
            </option>

        </select>

        <div id="ctypeError" class="field-error"></div>


        <!-- Duration -->

        <label>
            Duration <span class="required">*</span>
        </label>

        <input
            type="text"
            name="duration"
            id="duration"
            placeholder="Example: 3 Years"
            value="<?php echo htmlspecialchars($_POST['duration'] ?? ''); ?>"
        >

        <div id="durationError" class="field-error"></div>


        <!-- Fees -->

        <label>
            Course Fees <span class="required">*</span>
        </label>

        <input
            type="text"
            name="fees"
            id="fees"
            placeholder="Enter course fees"
            value="<?php echo htmlspecialchars($_POST['fees'] ?? ''); ?>"
            oninput="this.value=this.value.replace(/[^0-9]/g,'')"
        >

        <div id="feesError" class="field-error"></div>


        <!-- Total Seats -->

        <label>
            Total Seats <span class="required">*</span>
        </label>

        <input
            type="text"
            name="total_seats"
            id="total_seats"
            placeholder="Enter total seats"
            value="<?php echo htmlspecialchars($_POST['total_seats'] ?? ''); ?>"
            oninput="this.value=this.value.replace(/[^0-9]/g,'')"
        >

        <div id="seatsError" class="field-error"></div>


        <!-- Department -->

        <label>
            Department <span class="required">*</span>
        </label>

        <div class="department-row">

            <select name="did" id="did">

                <option value="">Select Department</option>

                <?php

                while ($dept = mysqli_fetch_assoc($departments)) {

                    $selected = "";

                    if (($_POST['did'] ?? '') == $dept['did']) {
                        $selected = "selected";
                    }

                    echo "<option value='".$dept['did']."' $selected>"
                         .htmlspecialchars($dept['dname']).
                         "</option>";
                }

                ?>

            </select>


            <a href="AddDepartment.php" class="new-dept">
                + Add New Department
            </a>

        </div>

        <div id="didError" class="field-error"></div>


        <!-- Buttons -->

        <div class="buttons">

            <button type="submit" name="add_course">
                Add Course
            </button>

            <a href="Courses.php" class="back">
                BACK
            </a>

        </div>


    </form>

</div>


<script>

function validateForm() {

    var cname = document.getElementById("cname");
    var ctype = document.getElementById("ctype");
    var duration = document.getElementById("duration");
    var fees = document.getElementById("fees");
    var seats = document.getElementById("total_seats");
    var did = document.getElementById("did");

    var valid = true;


    /* Remove old errors */

    cname.classList.remove("invalid");
    ctype.classList.remove("invalid");
    duration.classList.remove("invalid");
    fees.classList.remove("invalid");
    seats.classList.remove("invalid");
    did.classList.remove("invalid");

    document.getElementById("cnameError").innerHTML = "";
    document.getElementById("ctypeError").innerHTML = "";
    document.getElementById("durationError").innerHTML = "";
    document.getElementById("feesError").innerHTML = "";
    document.getElementById("seatsError").innerHTML = "";
    document.getElementById("didError").innerHTML = "";


    /* Course Name */

    if (cname.value.trim() == "") {

        cname.classList.add("invalid");

        document.getElementById("cnameError").innerHTML =
            "Course Name is required.";

        valid = false;
    }


    /* Course Type */

    if (ctype.value == "") {

        ctype.classList.add("invalid");

        document.getElementById("ctypeError").innerHTML =
            "Course Type is required.";

        valid = false;
    }


    /* Duration */

    if (duration.value.trim() == "") {

        duration.classList.add("invalid");

        document.getElementById("durationError").innerHTML =
            "Duration is required.";

        valid = false;
    }


    /* Fees */

    if (fees.value.trim() == "") {

        fees.classList.add("invalid");

        document.getElementById("feesError").innerHTML =
            "Course Fees is required.";

        valid = false;
    }


    /* Total Seats */

    if (seats.value.trim() == "") {

        seats.classList.add("invalid");

        document.getElementById("seatsError").innerHTML =
            "Total Seats is required.";

        valid = false;
    }


    /* Department */

    if (did.value == "") {

        did.classList.add("invalid");

        document.getElementById("didError").innerHTML =
            "Department is required.";

        valid = false;
    }


    return valid;
}


/* Remove Course Name error while typing */

document.getElementById("cname").addEventListener(
    "input",
    function() {

        this.classList.remove("invalid");

        document.getElementById("cnameError").innerHTML = "";

    }
);


/* Remove Course Type error */

document.getElementById("ctype").addEventListener(
    "change",
    function() {

        this.classList.remove("invalid");

        document.getElementById("ctypeError").innerHTML = "";

    }
);


/* Remove Duration error */

document.getElementById("duration").addEventListener(
    "input",
    function() {

        this.classList.remove("invalid");

        document.getElementById("durationError").innerHTML = "";

    }
);


/* Remove Fees error */

document.getElementById("fees").addEventListener(
    "input",
    function() {

        this.classList.remove("invalid");

        document.getElementById("feesError").innerHTML = "";

    }
);


/* Remove Seats error */

document.getElementById("total_seats").addEventListener(
    "input",
    function() {

        this.classList.remove("invalid");

        document.getElementById("seatsError").innerHTML = "";

    }
);


/* Remove Department error */

document.getElementById("did").addEventListener(
    "change",
    function() {

        this.classList.remove("invalid");

        document.getElementById("didError").innerHTML = "";

    }
);

</script>


</body>
</html>
<?php

session_start();

if (!isset($_SESSION['uname'])) {
    header("Location: login.html");
    exit();
}

include "demo.php";

$message = "";
$delete_course = null;


/* =========================================
   DELETE CONFIRMATION
========================================= */

if (isset($_GET['delete'])) {

    $cid = intval($_GET['delete']);

    $delete_check = mysqli_query(
        $conn,
        "SELECT cid, cname
         FROM course
         WHERE cid=$cid
         LIMIT 1"
    );

    if ($delete_check && mysqli_num_rows($delete_check) == 1) {

        $delete_course = mysqli_fetch_assoc(
            $delete_check
        );
    }
}


/* =========================================
   ACTUAL DELETE
========================================= */

if (
    $_SERVER["REQUEST_METHOD"] == "POST"
    && isset($_POST['confirm_delete'])
) {

    $cid = intval($_POST['cid']);


    /* Check whether course is already used */
    $check_admission = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM admission
         WHERE cid=$cid"
    );

    if (!$check_admission) {

        die(
            "Error checking admission: "
            . mysqli_error($conn)
        );
    }

    $count_data = mysqli_fetch_assoc(
        $check_admission
    );

    $admission_count = intval(
        $count_data['total']
    );


    if ($admission_count > 0) {

        $message =
            "This course cannot be deleted because it is already used in an admission record.";

    } else {

        $delete_result = mysqli_query(
            $conn,
            "DELETE FROM course
             WHERE cid=$cid"
        );

        if ($delete_result) {

            $message =
                "Course deleted successfully.";

        } else {

            $message =
                "Course could not be deleted: "
                . mysqli_error($conn);
        }
    }
}


/* =========================================
   SEARCH
========================================= */

$search = "";

if (isset($_GET['search'])) {

    $search = trim(
        $_GET['search']
    );
}


/* =========================================
   COURSE LIST
========================================= */

if ($search != "") {

    $search_safe =
        mysqli_real_escape_string(
            $conn,
            $search
        );

    $course_sql = "SELECT

                    c.cid,
                    c.cname,
                    c.ctype,
                    c.duration,
                    c.fees,
                    c.total_seats,
                    d.dname,

                    (
                        c.total_seats -
                        (
                            SELECT COUNT(*)
                            FROM admission a
                            WHERE a.cid=c.cid
                            AND a.status='Approved'
                        )
                    ) AS available_seats

                  FROM course c

                  LEFT JOIN department d
                  ON c.did=d.did

                  WHERE
                    c.cname LIKE '%$search_safe%'
                    OR c.ctype LIKE '%$search_safe%'
                    OR d.dname LIKE '%$search_safe%'

                  ORDER BY c.cid DESC";

} else {

    $course_sql = "SELECT

                    c.cid,
                    c.cname,
                    c.ctype,
                    c.duration,
                    c.fees,
                    c.total_seats,
                    d.dname,

                    (
                        c.total_seats -
                        (
                            SELECT COUNT(*)
                            FROM admission a
                            WHERE a.cid=c.cid
                            AND a.status='Approved'
                        )
                    ) AS available_seats

                  FROM course c

                  LEFT JOIN department d
                  ON c.did=d.did

                  ORDER BY c.cid DESC";
}


$result = mysqli_query(
    $conn,
    $course_sql
);

if (!$result) {

    die(
        "Course Error: "
        . mysqli_error($conn)
    );
}

?>

<!DOCTYPE html>
<html>

<head>

<title>Courses</title>

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

    box-shadow:
        0 5px 15px
        rgba(0,0,0,0.08);

    margin-bottom:30px;
}

.top {
    display:flex;

    justify-content:
        space-between;

    align-items:center;

    margin-bottom:25px;
}

.search::placeholder
{
    font-weight: bold;
    color:black;
}
.search {
    width:320px;

    padding:12px;

    border:1px solid #ccc;

    border-radius:7px;
}

.add {
    padding:
        12px 20px;

    background:#0b3c7c;

    color:white;

    text-decoration:none;

    border-radius:7px;

    font-weight:bold;
}

table {
    width:100%;

    border-collapse:
        collapse;
}

th {
    background:#0b3c7c;

    color:white;

    padding:13px;
}

td {
    padding:13px;

    text-align:center;

    border-bottom:
        1px solid #ddd;

    font-weight:bold;
}

.action {
    display:inline-block;

    padding:
        7px 10px;

    margin:2px;

    border-radius:5px;

    text-decoration:none;

    color:white;

    font-size:13px;

    font-weight:bold;
}

.edit {
    background:#1559a6;
}

.delete {
    background:#c62828;
}

.empty {
    padding:35px;

    color:#777;
}

.back {
    display:inline-block;

    margin-top:25px;

    padding:
        12px 25px;

    background:#0b3c7c;

    color:white;

    text-decoration:none;

    border-radius:7px;

    font-weight:bold;
}


/* Confirmation */

.confirm-box {
    width:500px;

    max-width:90%;

    margin:
        80px auto;

    padding:35px;

    background:white;

    border-radius:12px;

    text-align:center;

    box-shadow:
        0 10px 30px
        rgba(0,0,0,0.2);
}

.confirm-box h2 {
    color:#c62828;

    margin-bottom:20px;
}

.confirm-box p {
    line-height:1.6;

    margin-bottom:15px;
}

.confirm-buttons {
    margin-top:25px;

    display:flex;

    justify-content:center;

    gap:10px;
}

.yes-delete {
    background:#c62828;

    color:white;

    border:none;

    padding:
        12px 22px;

    border-radius:7px;

    font-weight:bold;

    cursor:pointer;
}

.cancel-delete {
    padding:
        12px 22px;

    background:#777;

    color:white;

    text-decoration:none;

    border-radius:7px;

    font-weight:bold;
}

.message {
    padding:12px;

    margin-bottom:20px;

    background:#eaf2fb;

    color:#0b3c7c;

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


<?php

if ($delete_course) {

?>


<!-- DELETE CONFIRMATION -->

<div class="confirm-box">

    <h2>
        Confirm Delete
    </h2>

    <p>
        Are you sure you want to delete
        <strong>
            <?php
            echo htmlspecialchars(
                $delete_course['cname']
            );
            ?>
        </strong>
        ?
    </p>

    <p>
        This action cannot be undone.
    </p>


    <div class="confirm-buttons">

        <form method="POST">

            <input
                type="hidden"
                name="cid"
                value="<?php
                echo $delete_course['cid'];
                ?>">

            <button
                type="submit"
                name="confirm_delete"
                class="yes-delete">

                Yes, Delete

            </button>

        </form>


        <a
            href="Courses.php"
            class="cancel-delete">

            Cancel

        </a>

    </div>

</div>


<?php

} else {

?>


<div class="title">
    Courses
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


<div class="top">

    <form method="GET">

        <input
            type="text"
            name="search"
            class="search"
            placeholder="Search course..."
            value="<?php
            echo htmlspecialchars(
                $search
            );
            ?>">

    </form>


    <a
        href="AddCourse.php"
        class="add">

        + Add Course

    </a>

</div>


<table>


<tr>

<th>Sr.No</th>

<th>Course Name</th>

<th>Type</th>

<th>Duration</th>

<th>Fees</th>

<th>Department</th>

<th>Total Seats</th>

<th>Available Seats</th>

<th>Action</th>

</tr>


<?php

$sr = 1;


if (mysqli_num_rows($result) > 0) {

    while (
        $row =
        mysqli_fetch_assoc($result)
    ) {

?>


<tr>


<td>
    <?php echo $sr; ?>
</td>


<td>
    <?php
    echo htmlspecialchars(
        $row['cname']
    );
    ?>
</td>


<td>
    <?php
    echo htmlspecialchars(
        $row['ctype']
    );
    ?>
</td>


<td>
    <?php
    echo htmlspecialchars(
        $row['duration']
    );
    ?>
</td>


<td>
    <?php
    echo htmlspecialchars(
        $row['fees']
    );
    ?>
</td>


<td>
    <?php
    echo htmlspecialchars(
        $row['dname']
    );
    ?>
</td>


<td>
    <?php
    echo htmlspecialchars(
        $row['total_seats']
    );
    ?>
</td>


<td>
    <?php
    echo htmlspecialchars(
        $row['available_seats']
    );
    ?>
</td>


<td>

<a
    href="EditCourse.php?cid=<?php
    echo $row['cid'];
    ?>"
    class="action edit">

    Edit

</a>


<a
    href="Courses.php?delete=<?php
    echo $row['cid'];
    ?>"
    class="action delete">

    Delete

</a>

</td>


</tr>


<?php

        $sr++;

    }

} else {

?>


<tr>

<td
    colspan="9"
    class="empty">

    No courses found.

</td>

</tr>


<?php

}

?>


</table>


<a
    href="dashboard.php"
    class="back">

    BACK

</a>


</div>


<?php

}

?>


</div>

</div>


</body>

</html>
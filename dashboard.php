<?php

session_start();

if (!isset($_SESSION['uname'])) {
    header("Location: login.html");
    exit();
}

include "demo.php";


/* =====================================================
   TOTAL STUDENTS
===================================================== */

$student_result = mysqli_query(
    $conn,
    "SELECT COUNT(DISTINCT sid) AS total
     FROM admission
     WHERE status='Approved'"
);

$student_data = mysqli_fetch_assoc($student_result);
$total_students = $student_data['total'];


/* =====================================================
   TOTAL COURSES
===================================================== */

$course_count_result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM course"
);

$course_data = mysqli_fetch_assoc($course_count_result);
$total_courses = $course_data['total'];


/* =====================================================
   TOTAL ADMISSIONS
===================================================== */

$admission_result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM admission
     WHERE status != 'Rejected'"
);

$admission_data = mysqli_fetch_assoc($admission_result);
$total_admissions = $admission_data['total'];


/* =====================================================
   PENDING ADMISSIONS
===================================================== */

$pending_result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM admission
     WHERE status='Pending'"
);

$pending_data = mysqli_fetch_assoc($pending_result);
$pending_admissions = $pending_data['total'];


/* =====================================================
   RECENT ACTIVITY
===================================================== */

$recent_result = mysqli_query(
    $conn,
    "SELECT activity_text, activity_date
     FROM activity
     ORDER BY activity_date DESC
     LIMIT 5"
);


/* =====================================================
   COURSE-WISE STUDENTS
   SAME STUDENT = ONE COUNT
===================================================== */

$course_result = mysqli_query(
    $conn,
    "SELECT
        c.cname,
        COUNT(x.sid) AS total_students

     FROM course c

     LEFT JOIN
     (
        SELECT
            a.sid,
            a.cid

        FROM admission a

        WHERE a.status='Approved'

        AND NOT EXISTS
        (
            SELECT 1

            FROM admission a2

            WHERE a2.sid = a.sid
            AND a2.status = 'Approved'

            AND
            (
                CASE
                    WHEN LOWER(TRIM(a2.admit_year))
                        IN ('fourth year','4th year','4')
                    THEN 4

                    WHEN LOWER(TRIM(a2.admit_year))
                        IN ('third year','3rd year','3')
                    THEN 3

                    WHEN LOWER(TRIM(a2.admit_year))
                        IN ('second year','2nd year','2')
                    THEN 2

                    WHEN LOWER(TRIM(a2.admit_year))
                        IN ('first year','1st year','1')
                    THEN 1

                    ELSE 0
                END

                >

                CASE
                    WHEN LOWER(TRIM(a.admit_year))
                        IN ('fourth year','4th year','4')
                    THEN 4

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
                END

                OR

                (
                    CASE
                        WHEN LOWER(TRIM(a2.admit_year))
                            IN ('fourth year','4th year','4')
                        THEN 4

                        WHEN LOWER(TRIM(a2.admit_year))
                            IN ('third year','3rd year','3')
                        THEN 3

                        WHEN LOWER(TRIM(a2.admit_year))
                            IN ('second year','2nd year','2')
                        THEN 2

                        WHEN LOWER(TRIM(a2.admit_year))
                            IN ('first year','1st year','1')
                        THEN 1

                        ELSE 0
                    END

                    =

                    CASE
                        WHEN LOWER(TRIM(a.admit_year))
                            IN ('fourth year','4th year','4')
                        THEN 4

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
                    END

                    AND

                    (
                        a2.addate > a.addate

                        OR

                        (
                            a2.addate = a.addate
                            AND a2.ano > a.ano
                        )
                    )
                )
            )
        )
     ) x

        ON c.cid = x.cid

     GROUP BY c.cid, c.cname

     ORDER BY c.cname"
);

?>

<!DOCTYPE html>
<html>

<head>

<title>Student Admission System</title>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {
    background: linear-gradient(
        135deg,
        #eaf2fb,
        #d6e5f5
    );
}


/* Header */

.header {
    height: 70px;

    background: linear-gradient(
        90deg,
        #0b3c7c,
        #1559a6
    );

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 0 30px;

    color: white;

    box-shadow:
        0 4px 10px
        rgba(0,0,0,0.2);
}

.logo {
    font-size: 22px;
    font-weight: bold;
    letter-spacing: 0.5px;
}

.welcome {
    font-size: 15px;
}


/* Main Container */

.container {
    display: flex;
    min-height: calc(100vh - 70px);
}


/* Sidebar */

.sidebar {
    width: 220px;

    background: white;

    padding: 25px 15px;

    box-shadow:
        2px 0 10px
        rgba(0,0,0,0.08);
}

.sidebar a {
    display: block;

    text-decoration: none;

    color: #333;

    padding: 14px 15px;

    margin-bottom: 10px;

    border-radius: 7px;

    font-weight: bold;
}

.sidebar a:hover {
    background: #eaf2fb;
    color: #0b3c7c;
}


/* Main */

.main {
    flex: 1;
    padding: 35px;
}

.title {
    font-size: 30px;

    font-weight: bold;

    color: #0b3c7c;

    margin-bottom: 30px;
}


/* Cards */

.cards {
    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 25px;
}

.card {
    background: white;

    min-height: 180px;

    padding: 30px;

    border-radius: 12px;

    box-shadow:
        0 8px 20px
        rgba(0,0,0,0.1);

    display: flex;

    flex-direction: column;

    justify-content: center;
}

.card h3 {
    font-size: 19px;

    margin-bottom: 20px;

    color: #333;
}

.number {
    font-size: 45px;

    font-weight: bold;

    color: #0b3c7c;
}


/* Course-wise Students */

.course-section {
    background: white;

    margin-top: 30px;

    padding: 25px;

    border-radius: 12px;

    box-shadow:
        0 8px 20px
        rgba(0,0,0,0.1);
}

.course-section h2 {
    font-size: 22px;

    color: #0b3c7c;

    margin-bottom: 20px;
}

.course-table {
    width: 100%;

    border-collapse: collapse;
}

.course-table th,
.course-table td {
    padding: 14px;

    text-align: left;

    border-bottom:
        1px solid #e0e0e0;
}

.course-table th {
    background: #f0f5fa;

    color: #333;
}

.course-table td:last-child,
.course-table th:last-child {
    text-align: center;
}


/* Recent Activity */

.activity {
    background: white;

    margin-top: 30px;

    padding: 25px;

    border-radius: 12px;

    box-shadow:
        0 8px 20px
        rgba(0,0,0,0.1);
}

.activity h2 {
    font-size: 22px;

    color: #0b3c7c;

    margin-bottom: 15px;
}

.activity-item {
    padding: 15px 10px;

    border-bottom:
        1px solid #e0e0e0;
}

.activity-item:last-child {
    border-bottom: none;
}

.time {
    display: block;

    font-size: 13px;

    color: #777;

    margin-top: 5px;
}

.empty {
    color: #777;

    padding: 15px 10px;
}


/* Logout Popup */

.popup {
    display: none;

    position: fixed;

    top: 0;

    left: 0;

    width: 100%;

    height: 100%;

    background:
        rgba(0,0,0,0.45);

    align-items: center;

    justify-content: center;

    z-index: 999;
}

.popup-box {
    width: 350px;

    background: white;

    padding: 30px;

    border-radius: 12px;

    text-align: center;

    box-shadow:
        0 10px 30px
        rgba(0,0,0,0.25);
}

.popup-box h3 {
    color: #0b3c7c;

    font-size: 22px;

    margin-bottom: 12px;
}

.popup-box p {
    color: #555;

    margin-bottom: 25px;
}

.popup-buttons {
    display: flex;

    justify-content: center;

    gap: 12px;
}

.popup-cancel {
    background: #777;

    color: white;

    border: none;

    padding: 10px 20px;

    border-radius: 6px;

    cursor: pointer;
}

.popup-logout {
    background: #0b3c7c;

    color: white;

    text-decoration: none;

    padding: 10px 20px;

    border-radius: 6px;
}

.popup-cancel:hover {
    background: #555;
}

.popup-logout:hover {
    background: #1559a6;
}


/* Mobile */

@media (max-width: 800px) {

    .sidebar {
        width: 180px;
    }

    .cards {
        grid-template-columns: 1fr;
    }

    .main {
        padding: 20px;
    }

}

</style>

</head>

<body>


<!-- Header -->

<div class="header">

    <div class="logo">
        STUDENT ADMISSION SYSTEM
    </div>

    <div class="welcome">
        Welcome,
        <?php echo htmlspecialchars($_SESSION['uname']); ?>
    </div>

</div>


<!-- Main Container -->

<div class="container">


    <!-- Sidebar -->

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

        <a href="Admissions.php">
            Admissions
        </a>

        <a href="PayFees.php">
            Payments
        </a>

        <a
            href="#"
            onclick="showLogoutPopup(); return false;"
        >
            Logout
        </a>

    </div>


    <!-- Main Content -->

    <div class="main">

        <div class="title">
            Dashboard
        </div>


        <!-- Cards -->

        <div class="cards">


            <div class="card">

                <h3>
                    Total Students
                </h3>

                <div class="number">
                    <?php echo $total_students; ?>
                </div>

            </div>


            <div class="card">

                <h3>
                    Total Courses
                </h3>

                <div class="number">
                    <?php echo $total_courses; ?>
                </div>

            </div>


            <div class="card">

                <h3>
                    Total Admissions
                </h3>

                <div class="number">
                    <?php echo $total_admissions; ?>
                </div>

            </div>


            <div class="card">

                <h3>
                    Pending Admissions
                </h3>

                <div class="number">
                    <?php echo $pending_admissions; ?>
                </div>

            </div>


        </div>


        <!-- Course-wise Students -->

        <div class="course-section">

            <h2>
                Course-wise Students
            </h2>


            <?php
            if (
                $course_result
                &&
                mysqli_num_rows($course_result) > 0
            ) {
            ?>


            <table class="course-table">

                <tr>

                    <th>
                        Course
                    </th>

                    <th>
                        Students
                    </th>

                </tr>


                <?php
                while (
                    $row =
                    mysqli_fetch_assoc(
                        $course_result
                    )
                ) {
                ?>


                <tr>

                    <td>
                        <?php
                        echo htmlspecialchars(
                            $row['cname']
                        );
                        ?>
                    </td>

                    <td>
                        <?php
                        echo $row['total_students'];
                        ?>
                    </td>

                </tr>


                <?php
                }
                ?>


            </table>


            <?php
            } else {
            ?>


            <div class="empty">
                No course data available.
            </div>


            <?php
            }
            ?>


        </div>


        <!-- Recent Activity -->

        <div class="activity">

            <h2>
                Recent Activity
            </h2>


            <?php
            if (
                $recent_result
                &&
                mysqli_num_rows($recent_result) > 0
            ) {

                while (
                    $row =
                    mysqli_fetch_assoc(
                        $recent_result
                    )
                ) {
            ?>


            <div class="activity-item">

                <strong>

                    <?php
                    echo htmlspecialchars(
                        $row['activity_text']
                    );
                    ?>

                </strong>

                <span class="time">

                    <?php
                    echo htmlspecialchars(
                        $row['activity_date']
                    );
                    ?>

                </span>

            </div>


            <?php
                }

            } else {
            ?>


            <div class="empty">
                No recent activity.
            </div>


            <?php
            }
            ?>


        </div>


    </div>

</div>


<!-- Logout Popup -->

<div
    id="logoutPopup"
    class="popup"
>

    <div class="popup-box">

        <h3>
            Logout
        </h3>

        <p>
            Are you sure you want to logout?
        </p>


        <div class="popup-buttons">

            <button
                type="button"
                class="popup-cancel"
                onclick="closeLogoutPopup()"
            >
                Cancel
            </button>


            <a
                href="logout.php"
                class="popup-logout"
            >
                Logout
            </a>

        </div>

    </div>

</div>


<script>

function showLogoutPopup() {

    document.getElementById(
        "logoutPopup"
    ).style.display = "flex";

}


function closeLogoutPopup() {

    document.getElementById(
        "logoutPopup"
    ).style.display = "none";

}

</script>


</body>

</html>
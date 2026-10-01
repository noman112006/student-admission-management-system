<?php

session_start();

if (!isset($_SESSION['uname'])) {
    header("Location: login.html");
    exit();
}

include "demo.php";


/* =====================================================
   DELETE CONFIRMATION
===================================================== */

$confirm_student = null;

if (isset($_GET['delete_sid'])) {

    $sid = mysqli_real_escape_string(
        $conn,
        $_GET['delete_sid']
    );

    $check_student = mysqli_query(
        $conn,
        "SELECT sid, sname
         FROM student
         WHERE sid='$sid'
         LIMIT 1"
    );

    if (!$check_student) {
        die("Error: " . mysqli_error($conn));
    }

    if (mysqli_num_rows($check_student) == 1) {

        $confirm_student = mysqli_fetch_assoc(
            $check_student
        );
    }
}


/* =====================================================
   ACTUAL DELETE
===================================================== */

if (
    $_SERVER["REQUEST_METHOD"] == "POST"
    &&
    isset($_POST['confirm_delete'])
) {

    $sid = mysqli_real_escape_string(
        $conn,
        $_POST['delete_sid']
    );


    /* Find admission numbers */

    $admission_result = mysqli_query(
        $conn,
        "SELECT ano
         FROM admission
         WHERE sid='$sid'"
    );

    if (!$admission_result) {
        die(
            "Admission Error: "
            . mysqli_error($conn)
        );
    }


    /* Delete payment records */

    while (
        $row = mysqli_fetch_assoc(
            $admission_result
        )
    ) {

        $ano = mysqli_real_escape_string(
            $conn,
            $row['ano']
        );

        $delete_payment = mysqli_query(
            $conn,
            "DELETE FROM payment
             WHERE ano='$ano'"
        );

        if (!$delete_payment) {

            die(
                "Payment Delete Error: "
                . mysqli_error($conn)
            );
        }
    }


    /* Delete admissions */

    $delete_admission = mysqli_query(
        $conn,
        "DELETE FROM admission
         WHERE sid='$sid'"
    );

    if (!$delete_admission) {

        die(
            "Admission Delete Error: "
            . mysqli_error($conn)
        );
    }


    /* Delete student */

    $delete_student = mysqli_query(
        $conn,
        "DELETE FROM student
         WHERE sid='$sid'"
    );

    if (!$delete_student) {

        die(
            "Student Delete Error: "
            . mysqli_error($conn)
        );
    }


    header("Location: Students.php");
    exit();
}


/* =====================================================
   SELECTED COURSE
===================================================== */

$selected_course = "";

if (isset($_GET['course'])) {

    $selected_course = trim(
        $_GET['course']
    );
}


/* =====================================================
   SEARCH
===================================================== */

$search = "";

if (isset($_GET['search'])) {

    $search = trim(
        $_GET['search']
    );
}


/* =====================================================
   COURSE CARDS
   AUTOMATICALLY LOAD ALL COURSES
===================================================== */

$course_cards = mysqli_query(
    $conn,

    "SELECT
        c.cid,
        c.cname,

        (
            SELECT COUNT(DISTINCT a.sid)

            FROM admission a

            WHERE a.cid = c.cid
            AND a.status = 'Approved'

            AND NOT EXISTS (

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
                )

                >

                (
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
                )

                OR

                (

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
                    )

                    =

                    (
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
                    )

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

        ) AS student_count

    FROM course c

    ORDER BY c.cname"
);


if (!$course_cards) {

    die(
        "Course Card Error: "
        . mysqli_error($conn)
    );
}


/* =====================================================
   ALL STUDENT COUNT
===================================================== */

$all_count_result = mysqli_query(
    $conn,

    "SELECT COUNT(DISTINCT a.sid) AS total

     FROM admission a

     WHERE a.status='Approved'

     AND NOT EXISTS (

        SELECT 1

        FROM admission a2

        WHERE a2.sid=a.sid
        AND a2.status='Approved'

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
        )

        >

        (
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
        )

        OR

        (

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
            )

            =

            (
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
            )

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

    )"
);


$all_count = 0;

if ($all_count_result) {

    $all_count_data =
        mysqli_fetch_assoc(
            $all_count_result
        );

    $all_count =
        intval(
            $all_count_data['total']
        );
}


/* =====================================================
   STUDENT SEARCH QUERY
===================================================== */

$search_condition = "";

if ($search != "") {

    $search_safe =
        mysqli_real_escape_string(
            $conn,
            $search
        );

    $search_condition = "

        AND (

            s.sname LIKE '%$search_safe%'

            OR s.sid LIKE '%$search_safe%'

            OR c.cname LIKE '%$search_safe%'

            OR a.ano LIKE '%$search_safe%'

        )

    ";
}


/* =====================================================
   COURSE FILTER
===================================================== */

$course_condition = "";

if ($selected_course != "") {

    $course_safe =
        mysqli_real_escape_string(
            $conn,
            $selected_course
        );

    $course_condition = "

        AND c.cname='$course_safe'

    ";
}


/* =====================================================
   STUDENT QUERY
===================================================== */

$sql = "

    SELECT
        a.ano,
        a.sid,
        s.sname,
        c.cname,
        a.addate,
        a.admit_year

    FROM admission a

    INNER JOIN student s
        ON a.sid=s.sid

    INNER JOIN course c
        ON a.cid=c.cid

    WHERE a.status='Approved'

    $course_condition

    $search_condition

    AND NOT EXISTS (

        SELECT 1

        FROM admission a2

        WHERE a2.sid=a.sid
        AND a2.status='Approved'

        AND (

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
            )

            >

            (
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
            )

            OR

            (

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
                )

                =

                (
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
                )

                AND

                (
                    a2.addate>a.addate

                    OR

                    (
                        a2.addate=a.addate
                        AND a2.ano>a.ano
                    )
                )

            )

        )

    )

    ORDER BY a.addate DESC

";


$result = mysqli_query(
    $conn,
    $sql
);


if (!$result) {

    die(
        "Student Error: "
        . mysqli_error($conn)
    );
}

?>

<!DOCTYPE html>

<html>

<head>

<title>Students</title>

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

    margin-bottom:25px;
}


.box {

    background:white;

    padding:30px;

    border-radius:12px;

    box-shadow:
        0 5px 15px
        rgba(0,0,0,0.08);
}


.description {

    font-weight:bold;

    margin-bottom:20px;
}


/* =====================================================
   COURSE CARDS
===================================================== */

.course-cards {

    display:grid;

    grid-template-columns:
        repeat(
            auto-fit,
            minmax(160px, 1fr)
        );

    gap:15px;

    margin-bottom:30px;
}


.course-card {

    background:white;

    border:
        2px solid #d7e4f2;

    border-radius:10px;

    padding:20px;

    text-decoration:none;

    color:#333;

    transition:0.2s;

    box-shadow:
        0 3px 10px
        rgba(0,0,0,0.06);
}


.course-card:hover {

    border-color:#0b3c7c;

    transform:translateY(-2px);
}


.course-card.active {

    background:#0b3c7c;

    color:white;

    border-color:#0b3c7c;
}


.course-name {

    font-size:18px;

    font-weight:bold;

    margin-bottom:10px;
}


.student-number {

    font-size:28px;

    font-weight:bold;

    color:#0b3c7c;
}


.course-card.active
.student-number {

    color:white;
}


.student-text {

    font-size:13px;

    margin-top:3px;
}


/* Search */

.search {

    width:320px;

    padding:12px;

    margin-bottom:25px;

    border:
        1px solid #ccc;

    border-radius:7px;

    outline:none;
}


.search:focus {

    border-color:#0b3c7c;
}


.search::placeholder {

    font-weight:bold;

    color:black;
}


/* Table */

table {

    width:100%;

    border-collapse:collapse;
}


th {

    background:#0b3c7c;

    color:white;

    padding:14px;

    font-weight:bold;
}


td {

    padding:14px;

    text-align:center;

    border-bottom:
        1px solid #ddd;

    font-weight:bold;
}


/* Actions */

.action {

    display:inline-block;

    padding:8px 12px;

    margin:2px;

    border-radius:5px;

    text-decoration:none;

    color:white;

    font-size:13px;

    font-weight:bold;
}


.view {

    background:#0b3c7c;
}


.edit {

    background:#1559a6;
}


.delete {

    background:#c62828;
}


.view:hover {

    background:#1559a6;
}


.edit:hover {

    background:#084fac;
}


.delete:hover {

    background:#a82020;
}


/* Empty */

.empty {

    padding:35px;

    color:#777;
}


/* Back */

.back {

    display:inline-block;

    margin-top:25px;

    padding:12px 25px;

    background:#0b3c7c;

    color:white;

    text-decoration:none;

    border-radius:7px;

    font-weight:bold;
}


.back:hover {

    background:#1559a6;
}


/* Delete */

.confirm-box {

    background:white;

    width:500px;

    max-width:90%;

    margin:100px auto;

    padding:35px;

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

    margin-bottom:15px;

    line-height:1.6;
}


.confirm-buttons {

    margin-top:25px;

    display:flex;

    justify-content:center;

    gap:10px;
}


.yes-delete {

    padding:12px 22px;

    background:#c62828;

    color:white;

    border:none;

    border-radius:7px;

    font-weight:bold;

    cursor:pointer;
}


.no-delete {

    padding:12px 22px;

    background:#777;

    color:white;

    text-decoration:none;

    border-radius:7px;

    font-weight:bold;
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

    <a
        href="Students.php"
        class="active"
    >
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


<?php if ($confirm_student) { ?>


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
                $confirm_student['sname']
            );

            ?>

        </strong>

        ?

    </p>


    <p>

        This will also delete the student's
        related admission and payment records.

    </p>


    <div class="confirm-buttons">


        <form method="POST">

            <input
                type="hidden"
                name="delete_sid"
                value="<?php

                echo htmlspecialchars(
                    $confirm_student['sid']
                );

                ?>"
            >


            <button
                type="submit"
                name="confirm_delete"
                class="yes-delete"
            >

                Yes, Delete

            </button>

        </form>


        <a
            href="Students.php"
            class="no-delete"
        >

            Cancel

        </a>


    </div>

</div>


<?php } else { ?>


<!-- STUDENTS PAGE -->


<div class="title">

    Students

</div>


<div class="box">


<p class="description">

    View and manage all approved students.

</p>


<!-- =====================================================
     COURSE CARDS
===================================================== -->

<div class="course-cards">


<!-- ALL STUDENTS -->

<a
    href="Students.php"
    class="course-card
    <?php

    if ($selected_course == "") {

        echo "active";

    }

    ?>"
>

    <div class="course-name">
        All Students
    </div>

    <div class="student-number">

        <?php
        echo $all_count;
        ?>

    </div>

    <div class="student-text">
        Students
    </div>

</a>


<?php

if (mysqli_num_rows($course_cards) > 0) {

    while (
        $course_row =
        mysqli_fetch_assoc(
            $course_cards
        )
    ) {

?>


<a
    href="Students.php?course=<?php

    echo urlencode(
        $course_row['cname']
    );

    ?>"
    class="course-card

    <?php

    if (
        $selected_course
        ==
        $course_row['cname']
    ) {

        echo "active";

    }

    ?>"
>


<div class="course-name">

    <?php

    echo htmlspecialchars(
        $course_row['cname']
    );

    ?>

</div>


<div class="student-number">

    <?php

    echo intval(
        $course_row['student_count']
    );

    ?>

</div>


<div class="student-text">

    Students

</div>


</a>


<?php

    }

}

?>


</div>


<!-- SEARCH -->

<form method="GET">


<?php if ($selected_course != "") { ?>

<input
    type="hidden"
    name="course"
    value="<?php

    echo htmlspecialchars(
        $selected_course
    );

    ?>"
>

<?php } ?>


<input

    type="text"

    name="search"

    class="search"

    placeholder="Search student..."

    value="<?php

    echo htmlspecialchars(
        $search
    );

    ?>"

>


</form>


<table>


<tr>

<th>
    Sr.No
</th>

<th>
    Admission No.
</th>

<th>
    Student ID
</th>

<th>
    Student Name
</th>

<th>
    Course
</th>

<th>
    Admission Date
</th>

<th>
    Action
</th>

</tr>


<?php

$sr = 1;


if (
    mysqli_num_rows($result)
    > 0
) {

    while (
        $row =
        mysqli_fetch_assoc(
            $result
        )
    ) {

?>


<tr>


<td>

<?php

echo $sr;

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $row['ano']
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $row['sid']
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $row['sname']
);

?>

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
    $row['addate']
);

?>

</td>


<td>


<!-- VIEW -->

<a

    href="ViewStudent.php?sid=<?php

    echo urlencode(
        $row['sid']
    );

    ?>"

    class="action view"

>

    View

</a>


<!-- EDIT -->

<a

    href="EditStudent.php?sid=<?php

    echo urlencode(
        $row['sid']
    );

    ?>"

    class="action edit"

>

    Edit

</a>


<!-- DELETE -->

<a

    href="Students.php?delete_sid=<?php

    echo urlencode(
        $row['sid']
    );

    ?>"

    class="action delete"

>

    Delete

</a>


</td>


</tr>


<?php

        $sr++;

    }

}

else {

?>


<tr>

<td
    colspan="7"
    class="empty"
>

    No approved students found.

</td>

</tr>


<?php

}

?>


</table>


<a
    href="dashboard.php"
    class="back"
>

    BACK

</a>


</div>


<?php } ?>


</div>

</div>


</body>

</html>
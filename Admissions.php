<?php 
 
session_start(); 
 
if (!isset($_SESSION['uname'])) { 
    header("Location: login.html"); 
    exit(); 
} 
 
include "demo.php"; 
 
 
/* ===================================================== 
   FUNCTION - DISPLAY ADMISSION NUMBER 
===================================================== */ 
 
function formatAdmissionNo($ano) 
{ 
    $ano = strtoupper(trim($ano)); 
 
    if (substr($ano, 0, 3) == "ADM") { 
 
        $number = substr($ano, 3); 
 
        if (ctype_digit($number)) { 
 
            return "ADM" . str_pad( 
                $number, 
                6, 
                "0", 
                STR_PAD_LEFT 
            ); 
        } 
    } 
 
    return $ano; 
} 
 
 
/* ===================================================== 
   APPROVE ADMISSION 
===================================================== */ 
 
if ( 
    $_SERVER["REQUEST_METHOD"] == "POST" 
    && 
    isset($_POST['approve']) 
) { 
 
    $ano = mysqli_real_escape_string( 
        $conn, 
        $_POST['ano'] 
    ); 
 
 
    /* Get pending student details */ 
 
    $student_query = mysqli_query( 
        $conn, 
 
        "SELECT 
            s.sname, 
            s.dob, 
            s.gender, 
            s.phone, 
            s.email, 
            s.blood_group, 
            s.house_no, 
            s.street, 
            s.city, 
            s.state, 
            s.pincode, 
            s.qualification, 
            s.prev_institute, 
            s.pass_year, 
            s.percent_cgpa 
 
         FROM student s 
 
         INNER JOIN admission a 
             ON s.sid = a.sid 
 
         WHERE a.ano='$ano' 
         AND a.status='Pending' 
 
         LIMIT 1" 
    ); 
 
 
    if (!$student_query) { 
 
        die( 
            "Student Error: " 
            . mysqli_error($conn) 
        ); 
    } 
 
 
    if (mysqli_num_rows($student_query) == 0) { 
 
        $error = 
            "Pending admission not found."; 
 
    } 
 
    else { 
 
        $student = 
            mysqli_fetch_assoc( 
                $student_query 
            ); 
 
 
        /* Generate official Student ID */ 
 
        do { 
 
            $new_sid = 
                "S" 
                . rand( 
                    100000000, 
                    999999999 
                ); 
 
 
            $check_sid = mysqli_query( 
                $conn, 
 
                "SELECT sid 
                 FROM student 
                 WHERE sid='$new_sid'" 
            ); 
 
        } while ( 
            $check_sid 
            && 
            mysqli_num_rows( 
                $check_sid 
            ) > 0 
        ); 
 
 
        /* Insert official student */ 
 
        $insert_student = mysqli_query( 
            $conn, 
 
            "INSERT INTO student 
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
                '$new_sid', 
                '{$student['sname']}', 
                '{$student['dob']}', 
                '{$student['gender']}', 
                '{$student['phone']}', 
                '{$student['email']}', 
                '{$student['blood_group']}', 
                '{$student['house_no']}', 
                '{$student['street']}', 
                '{$student['city']}', 
                '{$student['state']}', 
                '{$student['pincode']}', 
                '{$student['qualification']}', 
                '{$student['prev_institute']}', 
                '{$student['pass_year']}', 
                '{$student['percent_cgpa']}' 
            )" 
        ); 
 
 
        if (!$insert_student) { 
 
            $error = 
                "Student creation failed: " 
                . mysqli_error($conn); 
 
        } 
 
        else { 
 
 
            /* Update admission */ 
 
            $update_admission = mysqli_query( 
                $conn, 
 
                "UPDATE admission 
 
                 SET 
                    sid='$new_sid', 
                    status='Approved' 
 
                 WHERE ano='$ano' 
                 AND status='Pending'" 
            ); 
 
 
            if (!$update_admission) { 
 
                mysqli_query( 
                    $conn, 
 
                    "DELETE FROM student 
                     WHERE sid='$new_sid'" 
                ); 
 
                $error = 
                    "Admission approval failed: " 
                    . mysqli_error($conn); 
 
            } 
 
            else { 
 
 
                /* Delete temporary student record */ 
 
                mysqli_query( 
                    $conn, 
 
                    "DELETE FROM student 
 
                     WHERE sid='$ano'" 
                ); 
 
 
                /* Activity */ 
 
                mysqli_query( 
                    $conn, 
 
                    "INSERT INTO activity 
                    ( 
                        activity_text 
                    ) 
 
                    VALUES 
                    ( 
                        'Admission approved: $ano - Student ID $new_sid' 
                    )" 
                ); 
 
 
                header( 
                    "Location: Admissions.php" 
                ); 
 
                exit(); 
            } 
        } 
    } 
} 
 
 
/* ===================================================== 
   REJECT ADMISSION 
===================================================== */ 
 
if ( 
    $_SERVER["REQUEST_METHOD"] == "POST" 
    && 
    isset($_POST['reject']) 
) { 
 
    $ano = mysqli_real_escape_string( 
        $conn, 
        $_POST['ano'] 
    ); 
 
 
    /* Find temporary student */ 
 
    $temp_query = mysqli_query( 
        $conn, 
 
        "SELECT sid 
 
         FROM admission 
 
         WHERE ano='$ano' 
         AND status='Pending' 
 
         LIMIT 1" 
    ); 
 
 
    $temp_sid = $ano; 
 
 
    if ( 
        $temp_query 
        && 
        mysqli_num_rows($temp_query) > 0 
    ) { 
 
        $temp_data = 
            mysqli_fetch_assoc( 
                $temp_query 
            ); 
 
        $temp_sid = 
            $temp_data['sid']; 
    } 
 
 
    /* Reject */ 
 
    $reject_query = mysqli_query( 
        $conn, 
 
        "UPDATE admission 
 
         SET status='Rejected' 
 
         WHERE ano='$ano' 
         AND status='Pending'" 
    ); 
 
 
    if (!$reject_query) { 
 
        $error = 
            "Rejection failed: " 
            . mysqli_error($conn); 
 
    } 
 
    else { 
 
 
        /* Delete payment records */ 
 
        mysqli_query( 
            $conn, 
 
            "DELETE FROM payment 
             WHERE ano='$ano'" 
        ); 
 
 
        /* Delete temporary student */ 
 
        mysqli_query( 
            $conn, 
 
            "DELETE FROM student 
 
             WHERE sid='$temp_sid'" 
        ); 
 
 
        /* Activity */ 
 
        mysqli_query( 
            $conn, 
 
            "INSERT INTO activity 
            ( 
                activity_text 
            ) 
 
            VALUES 
            ( 
                'Admission rejected: $ano' 
            )" 
        ); 
 
 
        header( 
            "Location: Admissions.php" 
        ); 
 
        exit(); 
    } 
} 
 
 
/* ===================================================== 
   SEARCH 
===================================================== */ 
 
$search = ""; 
 
if (isset($_GET['search'])) { 
 
    $search = 
        trim($_GET['search']); 
} 
 
 
/* ===================================================== 
   SORT 
===================================================== */ 
 
$sort = "year_desc"; 
 
if (isset($_GET['sort'])) { 
 
    $sort = 
        $_GET['sort']; 
} 
 
 
/* ===================================================== 
   SORT OPTIONS 
===================================================== */ 
 
if ($sort == "year_asc") { 
 
    $order_by = " 
 
        CASE 
 
            WHEN LOWER(TRIM(a.admit_year)) 
                IN ( 
                    'first year', 
                    '1st year', 
                    '1' 
                ) 
            THEN 1 
 
            WHEN LOWER(TRIM(a.admit_year)) 
                IN ( 
                    'second year', 
                    '2nd year', 
                    '2' 
                ) 
            THEN 2 
 
            WHEN LOWER(TRIM(a.admit_year)) 
                IN ( 
                    'third year', 
                    '3rd year', 
                    '3' 
                ) 
            THEN 3 
 
            WHEN LOWER(TRIM(a.admit_year)) 
                IN ( 
                    'fourth year', 
                    '4th year', 
                    '4' 
                ) 
            THEN 4 
 
            ELSE 0 
 
        END ASC, 
 
        a.addate ASC, 
 
        a.ano ASC 
 
    "; 
 
} 
elseif ($sort == "date_desc") { 
 
    $order_by = " 
 
        a.addate DESC, 
 
        a.ano DESC 
 
    "; 
 
} 
elseif ($sort == "date_asc") { 
 
    $order_by = " 
 
        a.addate ASC, 
 
        a.ano ASC 
 
    "; 
 
} 
elseif ($sort == "name_asc") { 
 
    $order_by = " 
 
        s.sname ASC, 
 
        a.addate DESC 
 
    "; 
 
} 
elseif ($sort == "name_desc") { 
 
    $order_by = " 
 
        s.sname DESC, 
 
        a.addate DESC 
 
    "; 
 
} 
elseif ($sort == "course_asc") { 
 
    $order_by = " 
 
        c.cname ASC, 
 
        a.addate DESC 
 
    "; 
 
} 
elseif ($sort == "course_desc") { 
 
    $order_by = " 
 
        c.cname DESC, 
 
        a.addate DESC 
 
    "; 
 
} 
else { 
 
    /* Default = Latest Year First */ 
 
    $order_by = " 
 
        CASE 
 
            WHEN LOWER(TRIM(a.admit_year)) 
                IN ( 
                    'fourth year', 
                    '4th year', 
                    '4' 
                ) 
            THEN 4 
 
            WHEN LOWER(TRIM(a.admit_year)) 
                IN ( 
                    'third year', 
                    '3rd year', 
                    '3' 
                ) 
            THEN 3 
 
            WHEN LOWER(TRIM(a.admit_year)) 
                IN ( 
                    'second year', 
                    '2nd year', 
                    '2' 
                ) 
            THEN 2 
 
            WHEN LOWER(TRIM(a.admit_year)) 
                IN ( 
                    'first year', 
                    '1st year', 
                    '1' 
                ) 
            THEN 1 
 
            ELSE 0 
 
        END DESC, 
 
        a.addate DESC, 
 
        a.ano DESC 
 
    "; 
} 
 
 
/* ===================================================== 
   GET ADMISSIONS 
===================================================== */ 
 
$search_condition = ""; 
 
if ($search != "") { 
 
    $search_safe = 
        mysqli_real_escape_string( 
            $conn, 
            $search 
        ); 
 
 
    $search_condition = " 
 
        AND 
        ( 
            a.ano LIKE '%$search_safe%' 
 
            OR s.sname LIKE '%$search_safe%' 
 
            OR s.sid LIKE '%$search_safe%' 
 
            OR c.cname LIKE '%$search_safe%' 
 
            OR a.admit_year LIKE '%$search_safe%' 
 
            OR a.status LIKE '%$search_safe%' 
        ) 
 
    "; 
} 
 
 
/* ===================================================== 
   ADMISSION QUERY 
===================================================== */ 
 
$sql = " 
 
    SELECT 
 
        a.ano, 
        a.sid, 
        a.admit_year, 
        a.addate, 
        a.status, 
 
        s.sname, 
 
        c.cname 
 
    FROM admission a 
 
    LEFT JOIN student s 
        ON a.sid = s.sid 
 
    LEFT JOIN course c 
        ON a.cid = c.cid 
 
    WHERE 1=1 
 
    $search_condition 
 
    ORDER BY $order_by 
 
"; 
 
 
$result = mysqli_query( 
    $conn, 
    $sql 
); 
 
 
if (!$result) { 
 
    die( 
        "Admission Error: " 
        . mysqli_error($conn) 
    ); 
} 
 
?> 
 
<!DOCTYPE html> 
<html> 
 
<head> 
 
<title>Admissions</title> 
 
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
 
    background: 
        #eaf2fb; 
 
    color: 
        #0b3c7c; 
} 
 
.main { 
 
    flex: 1; 
 
    padding: 35px; 
} 
 
.title { 
 
    font-size: 30px; 
 
    color: 
        #0b3c7c; 
 
    font-weight: bold; 
 
    margin-bottom: 20px; 
} 
 
.top-area { 
 
    display: flex; 
 
    justify-content: 
        space-between; 
 
    align-items: center; 
 
    margin-bottom: 20px; 
} 
 
.new-admission { 
 
    display: inline-block; 
 
    padding: 12px 20px; 
 
    background: 
        #0b3c7c; 
 
    color: white; 
 
    text-decoration: none; 
 
    border-radius: 7px; 
 
    font-weight: bold; 
} 
 
.new-admission:hover { 
 
    background: 
        #1559a6; 
} 
 
.box { 
 
    background: white; 
 
    padding: 25px; 
 
    border-radius: 12px; 
 
    box-shadow: 
        0 5px 15px 
        rgba(0,0,0,0.08); 
 
    overflow-x: auto; 
} 
 
.filter-area { 
 
    margin-bottom: 20px; 
} 
 
.filter-area form { 
 
    display: flex; 
 
    align-items: center; 
 
    gap: 10px; 
 
    flex-wrap: wrap; 
} 
 
.search::placeholder { 
    font-weight: bold; 
    color: #555; 
} 
 
.search { 
 
    width: 320px; 
 
    padding: 12px; 
 
    border: 
        1px solid #ccc; 
 
    border-radius: 7px; 
 
    outline: none; 
} 
 
.search:focus { 
 
    border-color: 
        #0b3c7c; 
} 
 
.sort { 
 
    width: 220px; 
 
    padding: 12px; 
 
    border: 
        1px solid #ccc; 
 
    border-radius: 7px; 
 
    outline: none; 
 
    background: white; 
 
    font-weight: bold; 
} 
 
.sort:focus { 
 
    border-color: 
        #0b3c7c; 
} 
 
table { 
 
    width: 100%; 
 
    border-collapse: 
        collapse; 
 
    min-width: 850px; 
} 
 
th { 
 
    background: 
        #0b3c7c; 
 
    color: white; 
 
    padding: 13px; 
 
    text-align: center; 
} 
 
td { 
 
    padding: 12px; 
 
    text-align: center; 
 
    border-bottom: 
        1px solid #ddd; 
 
    font-weight: bold; 
} 
 
.extra-row { 
 
    display: none; 
} 
 
.status { 
 
    display: inline-block; 
 
    padding: 5px 10px; 
 
    border-radius: 15px; 
 
    font-size: 12px; 
} 
 
.approved { 
 
    background: #eaf8ee; 
 
    color: green; 
} 
 
.pending { 
 
    background: #fff7e6; 
 
    color: #9a6700; 
} 
 
.rejected { 
 
    background: #ffecec; 
 
    color: #c62828; 
} 
 
.action-form { 
 
    display: inline-block; 
 
    margin: 2px; 
} 
 
.approve-btn { 
 
    padding: 7px 12px; 
 
    background: 
        #198754; 
 
    color: white; 
 
    border: none; 
 
    border-radius: 5px; 
 
    font-weight: bold; 
 
    cursor: pointer; 
} 
 
.approve-btn:hover { 
 
    background: 
        #146c43; 
} 
 
.reject-btn { 
 
    padding: 7px 12px; 
 
    background: 
        #c62828; 
 
    color: white; 
 
    border: none; 
 
    border-radius: 5px; 
 
    font-weight: bold; 
 
    cursor: pointer; 
} 
 
.reject-btn:hover { 
 
    background: 
        #a82020; 
} 
 
.view-btn { 
 
    display: inline-block; 
 
    padding: 7px 12px; 
 
    background: 
        #1559a6; 
 
    color: white; 
 
    text-decoration: none; 
 
    border-radius: 5px; 
 
    font-size: 13px; 
 
    font-weight: bold; 
} 
 
.view-btn:hover { 
 
    background: 
        #084fac; 
} 
 
.see-more-area { 
 
    text-align: center; 
 
    margin-top: 20px; 
} 
 
.see-more-btn { 
 
    padding: 11px 25px; 
 
    background: 
        #0b3c7c; 
 
    color: white; 
 
    border: none; 
 
    border-radius: 7px; 
 
    font-weight: bold; 
 
    cursor: pointer; 
} 
 
.see-more-btn:hover { 
 
    background: 
        #1559a6; 
} 
 
.back { 
 
    display: inline-block; 
 
    margin-top: 25px; 
 
    padding: 12px 25px; 
 
    background: 
        #777; 
 
    color: white; 
 
    text-decoration: none; 
 
    border-radius: 7px; 
 
    font-weight: bold; 
} 
 
.back:hover { 
 
    background: 
        #555; 
} 
 
.empty { 
 
    padding: 35px; 
 
    color: #777; 
} 
 
@media (max-width: 900px) { 
 
    .filter-area form { 
 
        flex-direction: column; 
 
        align-items: stretch; 
    } 
 
    .search, 
    .sort { 
 
        width: 100%; 
    } 
 
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
 
<div class="top-area"> 
 
<div class="title"> 
    Admissions 
</div> 
 
<a href="NewAdmission.php" class="new-admission"> 
    + New Admission 
</a> 
 
</div> 
 
<div class="box"> 
 
<div class="filter-area"> 
 
<form method="GET"> 
 
<input 
    type="text" 
    name="search" 
    class="search" 
    placeholder="Search admission, student or course" 
    value="<?php echo htmlspecialchars($search); ?>" 
> 
 
<select 
    name="sort" 
    class="sort" 
    onchange="this.form.submit()" 
> 
 
<option 
    value="year_desc" 
    <?php if ($sort == "year_desc") echo "selected"; ?> 
> 
    Latest Year First 
</option> 
 
<option 
    value="year_asc" 
    <?php if ($sort == "year_asc") echo "selected"; ?> 
> 
    Oldest Year First 
</option> 
 
<option 
    value="date_desc" 
    <?php if ($sort == "date_desc") echo "selected"; ?> 
> 
    Latest Admission Date 
</option> 
 
<option 
    value="date_asc" 
    <?php if ($sort == "date_asc") echo "selected"; ?> 
> 
    Oldest Admission Date 
</option> 
 
<option 
    value="name_asc" 
    <?php if ($sort == "name_asc") echo "selected"; ?> 
> 
    Student Name A-Z 
</option> 
 
<option 
    value="name_desc" 
    <?php if ($sort == "name_desc") echo "selected"; ?> 
> 
    Student Name Z-A 
</option> 
 
<option 
    value="course_asc" 
    <?php if ($sort == "course_asc") echo "selected"; ?> 
> 
    Course A-Z 
</option> 
 
<option 
    value="course_desc" 
    <?php if ($sort == "course_desc") echo "selected"; ?> 
> 
    Course Z-A 
</option> 
 
</select> 
 
</form> 
 
</div> 
 
<table> 
 
<tr> 
 
<th>Admission No.</th> 
<th>Student</th> 
<th>Student ID</th> 
<th>Course</th> 
<th>Admission Year</th> 
<th>Admission Date</th> 
<th>Status</th> 
<th>Action</th> 
 
</tr> 
 
<?php 
 
$sr = 1; 
 
$total_rows = 
    mysqli_num_rows( 
        $result 
    ); 
 
if ($total_rows > 0) { 
 
    while ( 
        $row = 
        mysqli_fetch_assoc( 
            $result 
        ) 
    ) { 
 
        $row_class = ""; 
 
        if ($sr > 10) { 
 
            $row_class = "extra-row"; 
        } 
 
?> 
 
<tr class="<?php echo $row_class; ?>"> 
 
<td> 
 
<?php 
 
echo htmlspecialchars( 
    formatAdmissionNo( 
        $row['ano'] 
    ) 
); 
 
?> 
 
</td> 
 
<td> 
 
<?php 
 
echo htmlspecialchars( 
    $row['sname'] ?? 'Pending Student' 
); 
 
?> 
 
</td> 
 
<td> 
 
<?php 
 
if ($row['status'] == 'Pending') { 
 
    echo "-"; 
 
} 
else { 
 
    echo htmlspecialchars( 
        $row['sid'] 
    ); 
} 
 
?> 
 
</td> 
 
<td> 
 
<?php 
 
echo htmlspecialchars( 
    $row['cname'] ?? '-' 
); 
 
?> 
 
</td> 
 
<td> 
 
<?php 
 
echo htmlspecialchars( 
    $row['admit_year'] ?? '-' 
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
 
<?php 
 
if ($row['status'] == 'Approved') { 
 
?> 
 
<span class="status approved"> 
    Approved 
</span> 
 
<?php 
 
} 
elseif ($row['status'] == 'Pending') { 
 
?> 
 
<span class="status pending"> 
    Pending 
</span> 
 
<?php 
 
} 
else { 
 
?> 
 
<span class="status rejected"> 
    Rejected 
</span> 
 
<?php 
 
} 
 
?> 
 
</td> 
 
<td> 
 
<?php 
 
/* Pending = Approve + Reject */ 
 
if ($row['status'] == 'Pending') { 
 
?> 
 
<form 
    method="POST" 
    class="action-form" 
> 
 
<input 
    type="hidden" 
    name="ano" 
    value="<?php echo htmlspecialchars($row['ano']); ?>" 
> 
 
<button 
    type="submit" 
    name="approve" 
    class="approve-btn" 
> 
 
    Approve 
 
</button> 
 
</form> 
 
<form 
    method="POST" 
    class="action-form" 
> 
 
<input 
    type="hidden" 
    name="ano" 
    value="<?php echo htmlspecialchars($row['ano']); ?>" 
> 
 
<button 
    type="submit" 
    name="reject" 
    class="reject-btn" 
> 
 
    Reject 
 
</button> 
 
</form> 
 
<?php 
 
} 
 
/* Approved = View */ 
 
elseif ($row['status'] == 'Approved') { 
 
?> 
 
<a 
    href="Receipt.php?ano=<?php echo urlencode($row['ano']); ?>" 
    class="view-btn" 
> 
 
    View 
 
</a> 
 
<?php 
 
} 
 
/* Rejected = No View */ 
 
else { 
 
    echo "-"; 
 
} 
 
?> 
 
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
    colspan="8" 
    class="empty" 
> 
 
    No admissions found. 
 
</td> 
 
</tr> 
 
<?php 
 
} 
 
?> 
 
</table> 
 
<?php 
 
if ($total_rows > 10) { 
 
?> 
 
<div class="see-more-area"> 
 
<button 
    type="button" 
    class="see-more-btn" 
    id="seeMoreBtn" 
    onclick="showMoreAdmissions()" 
> 
 
    See More Admissions 
 
</button> 
 
</div> 
 
<?php 
 
} 
 
?> 
 
<a 
    href="dashboard.php" 
    class="back" 
> 
 
    BACK 
 
</a> 
 
</div> 
 
</div> 
 
</div> 
 
<script> 
 
function showMoreAdmissions() { 
 
    var rows = 
        document.querySelectorAll( 
            ".extra-row" 
        ); 
 
    for ( 
        var i = 0; 
        i < rows.length; 
        i++ 
    ) { 
 
        rows[i].style.display = 
            "table-row"; 
    } 
 
    document.getElementById( 
        "seeMoreBtn" 
    ).style.display = 
        "none"; 
} 
 
</script> 
 
</body> 
 
</html>
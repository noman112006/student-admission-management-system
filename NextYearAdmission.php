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
$error = "";
$saved = false;
$saved_year = "";
$ano = "";


/* =====================================================
   GET HIGHEST APPROVED ADMISSION
===================================================== */

$sql = "SELECT
            s.sid,
            s.sname,
            s.phone,
            s.email,
            a.ano,
            a.admit_year,
            a.cid,
            a.addate,
            c.cname,
            c.fees,
            c.duration
        FROM student s

        INNER JOIN admission a
            ON s.sid = a.sid

        INNER JOIN course c
            ON a.cid = c.cid

        WHERE s.sid = '$sid'
        AND a.status = 'Approved'

        ORDER BY

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

        END DESC,

        a.addate DESC

        LIMIT 1";


$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Database Error: " . mysqli_error($conn));
}

if (mysqli_num_rows($result) == 0) {
    die("Approved student not found.");
}

$student = mysqli_fetch_assoc($result);


/* =====================================================
   COURSE DURATION
===================================================== */

$duration = trim($student['duration']);

$total_years = 3;

if (preg_match('/\d+/', $duration, $matches)) {
    $total_years = (int)$matches[0];
}

if ($total_years != 3 && $total_years != 4) {
    $total_years = 3;
}


/* =====================================================
   CURRENT ADMISSION
===================================================== */

$current_ano = $student['ano'];

$current_year = trim(
    $student['admit_year']
);

$current_fees = (float)$student['fees'];


/* =====================================================
   CURRENT YEAR NUMBER
===================================================== */

$year_text = strtolower(
    trim($current_year)
);

if (
    $year_text == "first year" ||
    $year_text == "1st year" ||
    $year_text == "1"
) {

    $current_year_number = 1;

}
elseif (
    $year_text == "second year" ||
    $year_text == "2nd year" ||
    $year_text == "2"
) {

    $current_year_number = 2;

}
elseif (
    $year_text == "third year" ||
    $year_text == "3rd year" ||
    $year_text == "3"
) {

    $current_year_number = 3;

}
elseif (
    $year_text == "fourth year" ||
    $year_text == "4th year" ||
    $year_text == "4"
) {

    $current_year_number = 4;

}
else {

    $current_year_number = 1;
}


/* =====================================================
   FIND NEXT YEAR
===================================================== */

if ($current_year_number < $total_years) {

    $next_year_number =
        $current_year_number + 1;

}
else {

    $next_year_number = 0;
}


if ($next_year_number == 1) {

    $next_year = "First Year";

}
elseif ($next_year_number == 2) {

    $next_year = "Second Year";

}
elseif ($next_year_number == 3) {

    $next_year = "Third Year";

}
elseif ($next_year_number == 4) {

    $next_year = "Fourth Year";

}
else {

    $next_year = "";
}


/* =====================================================
   CHECK CURRENT YEAR FEES
===================================================== */

$paid_query = mysqli_query(
    $conn,
    "SELECT
        COALESCE(SUM(amount),0) AS paid

     FROM payment

     WHERE ano='$current_ano'"
);

if (!$paid_query) {
    die("Payment Error: " . mysqli_error($conn));
}

$paid_data = mysqli_fetch_assoc(
    $paid_query
);

$already_paid =
    (float)$paid_data['paid'];

$remaining_fees =
    $current_fees - $already_paid;


/* =====================================================
   SAVE NEXT YEAR ADMISSION
===================================================== */

if (
    $_SERVER["REQUEST_METHOD"] == "POST"
    &&
    isset($_POST['save_admission'])
) {

    $admit_year =
        mysqli_real_escape_string(
            $conn,
            trim($_POST['admit_year'] ?? "")
        );

    $addate =
        mysqli_real_escape_string(
            $conn,
            trim($_POST['addate'] ?? "")
        );

    $amount =
        mysqli_real_escape_string(
            $conn,
            trim($_POST['amount'] ?? "")
        );

    $pmode =
        mysqli_real_escape_string(
            $conn,
            trim($_POST['pmode'] ?? "")
        );

    $pdate =
        mysqli_real_escape_string(
            $conn,
            trim($_POST['pdate'] ?? "")
        );


    /* =================================================
       VALIDATION
    ================================================= */

    if ($next_year == "") {

        $error =
            "Student has completed "
            . $total_years
            . " years.";

    }

    elseif ($remaining_fees > 0) {

        $error =
            "Please clear "
            . $current_year
            . " pending fees first. "
            . "Remaining fees: ₹"
            . number_format(
                $remaining_fees,
                2
            );

    }

    elseif ($admit_year != $next_year) {

        $error =
            "Invalid admission year.";

    }

    elseif ($addate == "") {

        $error =
            "Admission Date is required.";

    }

    elseif ($amount == "") {

        $error =
            "Payment Amount is required.";

    }

    elseif (!is_numeric($amount)) {

        $error =
            "Payment Amount must contain numbers only.";

    }

    elseif ((float)$amount < 0) {

        $error =
            "Payment Amount cannot be negative.";

    }

    elseif ((float)$amount > $current_fees) {

        $error =
            "Payment amount cannot be more than course fees.";

    }

    elseif ($pmode == "") {

        $error =
            "Payment Mode is required.";

    }

    elseif ($pdate == "") {

        $error =
            "Payment Date is required.";

    }

    else {


        /* =============================================
           CHECK DUPLICATE
        ============================================= */

        $check = mysqli_query(
            $conn,

            "SELECT ano

             FROM admission

             WHERE sid='$sid'

             AND LOWER(TRIM(admit_year))
                 =
                 LOWER(TRIM('$admit_year'))

             AND status='Approved'

             LIMIT 1"
        );


        if (!$check) {

            $error =
                "Error: "
                . mysqli_error($conn);

        }

        elseif (
            mysqli_num_rows($check) > 0
        ) {

            $error =
                "This student already has "
                . $admit_year
                . " admission.";

        }

        else {


            /* =============================================
               GENERATE NEW ADMISSION NUMBER
            ============================================= */

            do {

                $ano =
                    "ADM"
                    . rand(
                        100000,
                        999999
                    );

                $check_ano = mysqli_query(
                    $conn,

                    "SELECT ano

                     FROM admission

                     WHERE ano='$ano'"
                );

            } while (
                $check_ano
                &&
                mysqli_num_rows(
                    $check_ano
                ) > 0
            );


            /* =============================================
               INSERT ADMISSION
            ============================================= */

            $admission_sql =
                "INSERT INTO admission

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
                    '$sid',
                    '{$student['cid']}',
                    '$admit_year',
                    '$addate',
                    'Approved'
                )";


            if (
                !mysqli_query(
                    $conn,
                    $admission_sql
                )
            ) {

                $error =
                    "Admission failed: "
                    . mysqli_error($conn);

            }

            else {

                $payment_success = true;


                /* =========================================
                   FIRST PAYMENT FOR NEW YEAR
                ========================================= */

                if (
                    $amount != ""
                    &&
                    $amount > 0
                ) {


                    if (
                        $pmode == ""
                        ||
                        $pdate == ""
                    ) {

                        mysqli_query(
                            $conn,

                            "DELETE FROM admission

                             WHERE ano='$ano'"
                        );


                        $error =
                            "Please fill payment mode "
                            . "and payment date.";


                        $payment_success =
                            false;

                    }

                    elseif (
                        $amount > $current_fees
                    ) {

                        mysqli_query(
                            $conn,

                            "DELETE FROM admission

                             WHERE ano='$ano'"
                        );


                        $error =
                            "Payment amount cannot be "
                            . "more than course fees.";


                        $payment_success =
                            false;

                    }

                    else {


                        if (
                            $amount >= $current_fees
                        ) {

                            $pstatus =
                                "Paid";

                        }

                        else {

                            $pstatus =
                                "Partial Paid";
                        }


                        $payment_sql =
                            "INSERT INTO payment

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


                        if (
                            !mysqli_query(
                                $conn,
                                $payment_sql
                            )
                        ) {

                            mysqli_query(
                                $conn,

                                "DELETE FROM admission

                                 WHERE ano='$ano'"
                            );


                            $error =
                                "Admission saved but "
                                . "payment failed.";


                            $payment_success =
                                false;
                        }
                    }
                }


                /* =========================================
                   ACTIVITY
                ========================================= */

                if ($payment_success) {

                    $activity_name =
                        mysqli_real_escape_string(
                            $conn,
                            $student['sname']
                        );


                    mysqli_query(
                        $conn,

                        "INSERT INTO activity
                        (activity_text)

                        VALUES
                        (
                            '$admit_year admission added: $activity_name'
                        )"
                    );


                    /* =====================================
                       REDIRECT AFTER SAVE
                    ===================================== */

                    header(
                        "Location: NextYearAdmission.php"
                        . "?sid="
                        . urlencode($sid)
                        . "&saved=1"
                        . "&ano="
                        . urlencode($ano)
                        . "&year="
                        . urlencode($admit_year)
                    );

                    exit();
                }
            }
        }
    }
}


/* =====================================================
   SUCCESS MESSAGE
===================================================== */

if (
    isset($_GET['saved'])
    &&
    $_GET['saved'] == "1"
) {

    $saved = true;

    if (isset($_GET['ano'])) {

        $ano =
            mysqli_real_escape_string(
                $conn,
                $_GET['ano']
            );
    }

    if (isset($_GET['year'])) {

        $saved_year =
            $_GET['year'];
    }
}

?>

<!DOCTYPE html>
<html>

<head>

<title>Next Year Admission</title>

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

.header {
    height: 70px;

    background: linear-gradient(
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
    width: 800px;

    margin: 40px auto 60px;
}

.card {
    background: white;

    padding: 30px;

    border-radius: 12px;

    box-shadow:
        0 8px 20px
        rgba(0,0,0,0.1);
}

h2 {
    color: #0b3c7c;

    margin-bottom: 25px;
}

.section {
    margin-bottom: 25px;

    padding-bottom: 22px;

    border-bottom:
        1px solid #ddd;
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

    margin-bottom: 6px;
}

.value {
    font-weight: bold;

    color: #222;
}

input,
select {

    width: 100%;

    padding: 11px;

    margin-top: 7px;

    border:
        1px solid #ccc;

    border-radius: 6px;

    outline: none;
}

input[readonly] {
    background: #f3f3f3;
}


/* =========================
   VALIDATION
========================= */

input.invalid,
select.invalid {
    border: 2px solid red;
}

.field-error {
    color: red;
    font-size: 13px;
    margin-top: 5px;
    min-height: 17px;
}


/* =========================
   MESSAGES
========================= */

.message {

    margin-bottom: 20px;

    padding: 12px;

    border-radius: 6px;

    font-weight: bold;
}

.success {

    background: #eaf8ee;

    color: green;

    border:
        1px solid #b7e4c7;
}

.error {

    background: #ffecec;

    color: red;

    border:
        1px solid #f5b5b5;
}

.info {

    background: #f0f5fa;

    padding: 12px;

    border-radius: 6px;

    margin-bottom: 20px;

    color: #555;
}

.pending {

    background: #fff7e6;

    color: #9a6700;

    border:
        1px solid #f0d58a;

    padding: 15px;

    border-radius: 6px;

    margin-bottom: 20px;

    font-weight: bold;
}

.pending a {

    display: inline-block;

    margin-top: 10px;

    color: #0b3c7c;

    font-weight: bold;

    text-decoration: underline;
}

.buttons {

    margin-top: 25px;

    display: flex;

    gap: 10px;
}

button,
.back,
.print {

    padding: 11px 22px;

    border: none;

    border-radius: 6px;

    cursor: pointer;

    font-weight: bold;

    text-decoration: none;

    display: inline-block;
}

.save {

    background: #0b3c7c;

    color: white;
}

.save:hover {

    background: #1559a6;
}

.print {

    background: #198754;

    color: white;
}

.back {

    background: #777;

    color: white;
}

.back:hover {

    background: #555;
}

</style>

</head>

<body>


<div class="header">

    STUDENT ADMISSION SYSTEM

</div>


<div class="container">

<div class="card">


<h2>

    Next Year Admission

</h2>


<?php if ($error != "") { ?>

<div class="message error">

    ✕

    <?php

    echo htmlspecialchars(
        $error
    );

    ?>

</div>

<?php } ?>


<?php if ($saved) { ?>


<div class="message success">

    ✓

    <?php

    echo htmlspecialchars(
        $saved_year
    );

    ?>

    admission added successfully.

</div>


<div class="buttons">


<?php if ($ano != "") { ?>

<a
    href="Receipt.php?ano=<?php
    echo urlencode(
        $ano
    );
    ?>"
    class="print"
>

    Print Receipt

</a>

<?php } ?>


<a
    href="Students.php"
    class="back"
>

    BACK

</a>


</div>


<?php } elseif ($next_year == "") { ?>


<div class="message error">

    This student has completed

    <?php
    echo $total_years;
    ?>

    years.

</div>


<div class="buttons">

<a
    href="Students.php"
    class="back"
>

    BACK

</a>

</div>


<?php } elseif ($remaining_fees > 0) { ?>


<div class="pending">

    Please clear previous year
    pending fees first.

    <br><br>

    Current Year:

    <?php
    echo htmlspecialchars(
        $current_year
    );
    ?>

    <br>

    Total Fees:

    ₹<?php
    echo number_format(
        $current_fees,
        2
    );
    ?>

    <br>

    Already Paid:

    ₹<?php
    echo number_format(
        $already_paid,
        2
    );
    ?>

    <br>

    Remaining Fees:

    ₹<?php
    echo number_format(
        $remaining_fees,
        2
    );
    ?>

    <br>

    <a
        href="PayFees.php?search=<?php
        echo urlencode(
            $current_ano
        );
        ?>"
    >

        Pay Pending Fees

    </a>

</div>


<div class="buttons">

<a
    href="Students.php"
    class="back"
>

    BACK

</a>

</div>


<?php } else { ?>


<div class="info">

    Previous year fees are fully paid.

    You can now take admission for

    <strong>

        <?php
        echo htmlspecialchars(
            $next_year
        );
        ?>

    </strong>.

</div>


<!-- EXISTING STUDENT -->

<div class="section">

<h3>

    Existing Student

</h3>


<div class="row">


<div class="field">

<div class="label">

    Student ID

</div>

<div class="value">

<?php

echo htmlspecialchars(
    $student['sid']
);

?>

</div>

</div>


<div class="field">

<div class="label">

    Student Name

</div>

<div class="value">

<?php

echo htmlspecialchars(
    $student['sname']
);

?>

</div>

</div>


<div class="field">

<div class="label">

    Course

</div>

<div class="value">

<?php

echo htmlspecialchars(
    $student['cname']
);

?>

</div>

</div>


</div>


<div class="row">


<div class="field">

<div class="label">

    Course Duration

</div>

<div class="value">

<?php

echo htmlspecialchars(
    $student['duration']
);

?>

</div>

</div>


<div class="field">

<div class="label">

    Current Year

</div>

<div class="value">

<?php

echo htmlspecialchars(
    $current_year
);

?>

</div>

</div>


<div class="field">

<div class="label">

    Next Year

</div>

<div class="value">

<?php

echo htmlspecialchars(
    $next_year
);

?>

</div>

</div>


</div>

</div>


<!-- NEW ADMISSION -->

<div class="section">

<h3>

    New Admission Details

</h3>


<form
    method="POST"
    id="nextYearForm"
    onsubmit="return validateForm()">


<div class="row">


<div class="field">

<div class="label">

    Admission Year

</div>

<input
    type="text"

    name="admit_year"

    value="<?php

    echo htmlspecialchars(
        $next_year
    );

    ?>"

    readonly
>

</div>


<div class="field">

<div class="label">

    Course

</div>

<input
    type="text"

    value="<?php

    echo htmlspecialchars(
        $student['cname']
    );

    ?>"

    readonly
>

</div>


<div class="field">

<div class="label">

    Course Fees

</div>

<input
    type="text"

    value="₹<?php

    echo number_format(
        $current_fees,
        2
    );

    ?>"

    readonly
>

</div>


</div>


<div class="row">


<div class="field">

<div class="label">

    Admission Date <span style="color:red;">*</span>

</div>

<input
    type="date"

    name="addate"

    id="addate"

    value="<?php

    echo date(
        'Y-m-d'
    );

    ?>"
>

<div
    id="addateError"
    class="field-error">
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

<div class="label">

    Payment Amount <span style="color:red;">*</span>

</div>

<input
    type="number"

    name="amount"

    id="amount"

    min="0"

    max="<?php

    echo $current_fees;

    ?>"

    step="0.01"

    placeholder="Enter amount"
>

<div
    id="amountError"
    class="field-error">
</div>

</div>


<div class="field">

<div class="label">

    Payment Mode <span style="color:red;">*</span>

</div>


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

<option value="Bank Transfer">

    Bank Transfer

</option>

<option value="Net Banking">

    Net Banking

</option>

</select>

<div
    id="pmodeError"
    class="field-error">
</div>

</div>


<div class="field">

<div class="label">

    Payment Date <span style="color:red;">*</span>

</div>

<input
    type="date"

    name="pdate"

    id="pdate"

    value="<?php

    echo date(
        'Y-m-d'
    );

    ?>"
>

<div
    id="pdateError"
    class="field-error">
</div>

</div>


</div>

</div>


<div class="buttons">


<button
    type="submit"

    name="save_admission"

    class="save"
>

    Save Admission

</button>


<a
    href="Students.php"

    class="back"
>

    BACK

</a>


</div>


</form>


<?php } ?>


</div>

</div>


<script>

/* =================================
   VALIDATION
================================= */

function validateForm() {

    let valid = true;


    let addate =
        document.getElementById("addate");

    let amount =
        document.getElementById("amount");

    let pmode =
        document.getElementById("pmode");

    let pdate =
        document.getElementById("pdate");


    /* Clear old errors */

    addate.classList.remove("invalid");
    amount.classList.remove("invalid");
    pmode.classList.remove("invalid");
    pdate.classList.remove("invalid");

    document.getElementById(
        "addateError"
    ).innerHTML = "";

    document.getElementById(
        "amountError"
    ).innerHTML = "";

    document.getElementById(
        "pmodeError"
    ).innerHTML = "";

    document.getElementById(
        "pdateError"
    ).innerHTML = "";


    /* =========================
       ADMISSION DATE
    ========================= */

    if (addate.value.trim() == "") {

        addate.classList.add("invalid");

        document.getElementById(
            "addateError"
        ).innerHTML =
            "Admission Date is required.";

        valid = false;
    }


    /* =========================
       PAYMENT AMOUNT
    ========================= */

    if (amount.value.trim() == "") {

        amount.classList.add("invalid");

        document.getElementById(
            "amountError"
        ).innerHTML =
            "Payment Amount is required.";

        valid = false;

    } else if (
        isNaN(amount.value) ||
        Number(amount.value) < 0
    ) {

        amount.classList.add("invalid");

        document.getElementById(
            "amountError"
        ).innerHTML =
            "Enter a valid payment amount.";

        valid = false;

    } else if (
        Number(amount.value)
        >
        Number(<?php echo $current_fees; ?>)
    ) {

        amount.classList.add("invalid");

        document.getElementById(
            "amountError"
        ).innerHTML =
            "Payment amount cannot be more than course fees.";

        valid = false;
    }


    /* =========================
       PAYMENT MODE
    ========================= */

    if (pmode.value == "") {

        pmode.classList.add("invalid");

        document.getElementById(
            "pmodeError"
        ).innerHTML =
            "Payment Mode is required.";

        valid = false;
    }


    /* =========================
       PAYMENT DATE
    ========================= */

    if (pdate.value.trim() == "") {

        pdate.classList.add("invalid");

        document.getElementById(
            "pdateError"
        ).innerHTML =
            "Payment Date is required.";

        valid = false;
    }


    return valid;
}


/* =================================
   REMOVE ERROR WHILE TYPING
================================= */

document
    .getElementById("addate")
    .addEventListener(
        "input",
        function() {

            this.classList.remove("invalid");

            document.getElementById(
                "addateError"
            ).innerHTML = "";

        }
    );


document
    .getElementById("amount")
    .addEventListener(
        "input",
        function() {

            this.classList.remove("invalid");

            document.getElementById(
                "amountError"
            ).innerHTML = "";

        }
    );


document
    .getElementById("pdate")
    .addEventListener(
        "input",
        function() {

            this.classList.remove("invalid");

            document.getElementById(
                "pdateError"
            ).innerHTML = "";

        }
    );


/* =================================
   REMOVE ERROR ON SELECT
================================= */

document
    .getElementById("pmode")
    .addEventListener(
        "change",
        function() {

            this.classList.remove("invalid");

            document.getElementById(
                "pmodeError"
            ).innerHTML = "";

        }
    );

</script>


</body>

</html>
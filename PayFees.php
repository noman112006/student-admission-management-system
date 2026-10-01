<?php

session_start();

if (!isset($_SESSION['uname'])) {
    header("Location: login.html");
    exit();
}

include "demo.php";


/* =====================================================
   VARIABLES
===================================================== */

$student_data = null;
$search_results = array();

$message = "";
$error = "";

$payment_id = null;

$search = "";

$selected_ano = "";


/* =====================================================
   SEARCH TEXT
===================================================== */

if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}


/* =====================================================
   SELECTED ADMISSION
===================================================== */

if (isset($_GET['ano'])) {

    $selected_ano = trim($_GET['ano']);

    $selected_ano = mysqli_real_escape_string(
        $conn,
        $selected_ano
    );
}


/* =====================================================
   SAVE PAYMENT
===================================================== */

if (
    $_SERVER["REQUEST_METHOD"] == "POST"
    &&
    isset($_POST['pay_fee'])
) {

    $ano = mysqli_real_escape_string(
        $conn,
        $_POST['ano'] ?? ""
    );

    $amount = trim($_POST['amount'] ?? "");

    $pmode = trim($_POST['pmode'] ?? "");

    $pdate = trim($_POST['pdate'] ?? "");


    /* =================================================
       GET TOTAL FEES
    ================================================= */

    $fee_query = mysqli_query(
        $conn,

        "SELECT c.fees

         FROM admission a

         LEFT JOIN course c
             ON a.cid = c.cid

         WHERE a.ano='$ano'
         AND a.status='Approved'"
    );


    if (!$fee_query) {

        die(
            "Fee Error: "
            . mysqli_error($conn)
        );
    }


    if (mysqli_num_rows($fee_query) == 0) {

        $error = "Admission not found.";

    } else {

        $fee_data = mysqli_fetch_assoc(
            $fee_query
        );

        $total_fees = $fee_data['fees'];


        /* =================================================
           ALREADY PAID
        ================================================= */

        $paid_query = mysqli_query(
            $conn,

            "SELECT
                COALESCE(SUM(amount),0) AS paid

             FROM payment

             WHERE ano='$ano'"
        );


        if (!$paid_query) {

            die(
                "Payment Error: "
                . mysqli_error($conn)
            );
        }


        $paid_data = mysqli_fetch_assoc(
            $paid_query
        );

        $already_paid = $paid_data['paid'];


        $remaining =
            $total_fees - $already_paid;


        if ($remaining < 0) {
            $remaining = 0;
        }


        /* =================================================
           VALIDATION
        ================================================= */

        if ($amount == "") {

            $error = "Payment Amount is required.";

        }

        elseif (!is_numeric($amount)) {

            $error = "Payment Amount must contain numbers only.";

        }

        elseif ($amount <= 0) {

            $error = "Enter a valid amount.";

        }

        elseif ($amount > $remaining) {

            $error =
                "Amount cannot be more than remaining fees.";

        }

        elseif ($pmode == "") {

            $error = "Payment Mode is required.";

        }

        elseif ($pdate == "") {

            $error = "Payment Date is required.";

        }

        else {

            /* =================================================
               PAYMENT STATUS
            ================================================= */

            $new_total_paid =
                $already_paid + $amount;


            if ($new_total_paid >= $total_fees) {

                $pstatus = "Paid";

            } else {

                $pstatus = "Partial Paid";

            }


            /* =================================================
               INSERT PAYMENT
            ================================================= */

            $amount = mysqli_real_escape_string(
                $conn,
                $amount
            );

            $pmode = mysqli_real_escape_string(
                $conn,
                $pmode
            );

            $pdate = mysqli_real_escape_string(
                $conn,
                $pdate
            );


            $sql = "INSERT INTO payment

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


            if (mysqli_query($conn, $sql)) {

                $payment_id =
                    mysqli_insert_id($conn);


                /* =================================================
                   ACTIVITY
                ================================================= */

                mysqli_query(
                    $conn,

                    "INSERT INTO activity
                    (
                        activity_text
                    )

                    VALUES
                    (
                        'Fee payment added for admission: $ano'
                    )"
                );


                $message =
                    "Payment added successfully.";

                $selected_ano =
                    $ano;

            }

            else {

                $error =
                    "Payment failed: "
                    . mysqli_error($conn);
            }
        }
    }
}


/* =====================================================
   LOAD SELECTED STUDENT
===================================================== */

if ($selected_ano != "") {

    $selected_query = mysqli_query(
        $conn,

        "SELECT
            a.ano,
            a.status,
            a.admit_year,

            s.sname,
            s.sid,
            s.phone,

            c.cname,
            c.fees

         FROM admission a

         LEFT JOIN student s
             ON a.sid = s.sid

         LEFT JOIN course c
             ON a.cid = c.cid

         WHERE a.ano='$selected_ano'
         AND a.status='Approved'

         LIMIT 1"
    );


    if (
        $selected_query
        &&
        mysqli_num_rows($selected_query) > 0
    ) {

        $student_data =
            mysqli_fetch_assoc(
                $selected_query
            );
    }

}


/* =====================================================
   SEARCH ALL STUDENTS
   ONLY LATEST APPROVED ADMISSION PER STUDENT
===================================================== */

elseif ($search != "") {

    $search_safe = mysqli_real_escape_string(
        $conn,
        $search
    );


    $search_query = mysqli_query(
        $conn,

        "SELECT
            a.ano,
            a.status,
            a.admit_year,

            s.sname,
            s.sid,
            s.phone,

            c.cname,
            c.fees

         FROM admission a

         LEFT JOIN student s
             ON a.sid = s.sid

         LEFT JOIN course c
             ON a.cid = c.cid

         WHERE a.status='Approved'

         AND
         (
            s.sname LIKE '%$search_safe%'

            OR s.sid LIKE '%$search_safe%'

            OR a.ano LIKE '%$search_safe%'

            OR c.cname LIKE '%$search_safe%'
         )

         AND NOT EXISTS
         (
            SELECT 1

            FROM admission a2

            WHERE a2.sid = a.sid
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
                            AND
                            a2.ano > a.ano
                        )
                    )

                )

            )
         )

         ORDER BY
            s.sname ASC"
    );


    if (!$search_query) {

        die(
            "Search Error: "
            . mysqli_error($conn)
        );
    }


    while (
        $row = mysqli_fetch_assoc(
            $search_query
        )
    ) {

        $search_results[] = $row;
    }


    if (count($search_results) == 1) {

        $student_data =
            $search_results[0];
    }

}

?>


<!DOCTYPE html>
<html>

<head>

<title>Payments</title>


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

    color: white;

    display: flex;

    align-items: center;

    padding: 0 30px;

    box-shadow:
        0 4px 10px
        rgba(0,0,0,0.15);
}


.logo {

    font-size: 22px;
    font-weight: bold;
}


/* Container */

.container {

    width: 800px;

    margin: 40px auto 60px;
}


/* Card */

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


/* Search */

.search-box {

    display: flex;

    gap: 10px;

    margin-bottom: 25px;
}


.search-box input {

    flex: 1;

    padding: 12px;

    border:
        1px solid #ccc;

    border-radius: 6px;

    outline: none;
}


.search-box input:focus {

    border-color: #0b3c7c;
}


.search-box button {

    padding: 12px 22px;

    background: #0b3c7c;

    color: white;

    border: none;

    border-radius: 6px;

    cursor: pointer;

    font-weight: bold;
}


.search-box button:hover {

    background: #1559a6;
}


/* Messages */

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


/* Search Results */

.result-box {

    margin-top: 20px;

    border:
        1px solid #ddd;

    border-radius: 8px;

    overflow: hidden;
}


.result-heading {

    background: #f0f5fa;

    padding: 15px;

    color: #0b3c7c;

    font-weight: bold;

    font-size: 18px;
}


.result-table {

    width: 100%;

    border-collapse: collapse;
}


.result-table th {

    background: #0b3c7c;

    color: white;

    padding: 12px;

    text-align: center;
}


.result-table td {

    padding: 12px;

    border-bottom:
        1px solid #ddd;

    text-align: center;

    font-weight: bold;
}


.select-btn {

    display: inline-block;

    padding: 7px 14px;

    background: #198754;

    color: white;

    text-decoration: none;

    border-radius: 5px;

    font-size: 13px;

    font-weight: bold;
}


.select-btn:hover {

    background: #146c43;
}


/* Student */

.student-box {

    margin-top: 25px;

    border:
        1px solid #ddd;

    border-radius: 8px;

    overflow: hidden;
}


.student-heading {

    background: #f0f5fa;

    padding: 16px;

    color: #0b3c7c;

    font-weight: bold;

    font-size: 18px;
}


.student-details {

    padding: 20px;
}


.student-details p {

    margin: 12px 0;

    font-weight: bold;
}


/* Fees */

.fee-box {

    margin-top: 20px;

    border-top:
        1px solid #ddd;

    padding-top: 20px;
}


.fee-row {

    display: flex;

    justify-content: space-between;

    padding: 12px 15px;

    margin-bottom: 8px;

    background: #f7f9fc;

    border-radius: 6px;
}


.total {

    color: #0b3c7c;

    font-weight: bold;
}


.paid {

    color: green;

    font-weight: bold;
}


.remaining {

    color: #d97706;

    font-weight: bold;
}


/* Payment Form */

.payment-form {

    margin-top: 25px;

    padding-top: 20px;

    border-top:
        1px solid #ddd;
}


.payment-form h3 {

    color: #0b3c7c;

    margin-bottom: 15px;
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

    padding: 11px;

    margin-top: 7px;

    border:
        1px solid #ccc;

    border-radius: 6px;

    outline: none;
}


/* Validation */

input.invalid,
select.invalid {

    border: 2px solid red;
}


.field-error {

    color: red;

    font-size: 13px;

    margin-top: 5px;

    min-height: 16px;
}


/* Buttons */

.pay-button {

    margin-top: 25px;
}


.pay-btn {

    padding: 11px 20px;

    background: #0b3c7c;

    color: white;

    border: none;

    border-radius: 6px;

    cursor: pointer;

    font-size: 15px;

    font-weight: bold;
}


.pay-btn:hover {

    background: #1559a6;
}


/* Receipt */

.print-payment {

    display: inline-block;

    margin-bottom: 20px;

    padding: 10px 18px;

    background: #0b3c7c;

    color: white;

    text-decoration: none;

    border-radius: 6px;

    font-weight: bold;
}


.print-payment:hover {

    background: #1559a6;
}


/* Fully Paid */

.full-paid {

    margin-top: 20px;

    padding: 14px;

    background: #eaf8ee;

    color: green;

    border:
        1px solid #b7e4c7;

    border-radius: 6px;

    text-align: center;

    font-weight: bold;
}


/* Back */

.back-area {

    margin-top: 30px;

    text-align: center;
}


.back-btn {

    display: inline-block;

    padding: 11px 25px;

    background: #777;

    color: white;

    text-decoration: none;

    border-radius: 6px;

    font-size: 15px;
}


.back-btn:hover {

    background: #555;
}

</style>

</head>


<body>


<div class="header">

    <div class="logo">
        STUDENT ADMISSION SYSTEM
    </div>

</div>


<div class="container">

<div class="card">


<h2>
    Payments
</h2>


<?php if ($message != "") { ?>

<div class="message success">

    ✓

    <?php
    echo htmlspecialchars(
        $message
    );
    ?>

</div>


<?php if (isset($payment_id)) { ?>

<a
    href="PaymentReceipt.php?pid=<?php
    echo $payment_id;
    ?>"
    class="print-payment"
>

    Print Payment Receipt

</a>

<?php } ?>


<?php } ?>


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


<!-- SEARCH -->

<form method="GET">

<div class="search-box">

<input
    type="text"
    name="search"

    placeholder="Search Student Name, Student ID or Admission No"

    value="<?php
        echo htmlspecialchars(
            $search
        );
    ?>"

    required
>

<button type="submit">

    Search

</button>

</div>

</form>



<!-- =================================================
     MULTIPLE SEARCH RESULTS
================================================= -->

<?php

if (
    $search != ""
    &&
    count($search_results) > 1
    &&
    $selected_ano == ""
) {

?>

<div class="result-box">

<div class="result-heading">

    Multiple Students Found

</div>


<table class="result-table">


<tr>

<th>
    Student Name
</th>

<th>
    Student ID
</th>

<th>
    Admission No.
</th>

<th>
    Admission Year
</th>

<th>
    Course
</th>

<th>
    Action
</th>

</tr>



<?php

foreach (
    $search_results as $row
) {

?>


<tr>


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
    $row['sid']
);

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
    $row['admit_year']
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

<a
    href="PayFees.php?ano=<?php
    echo urlencode(
        $row['ano']
    );
    ?>"
    class="select-btn"
>

    Select

</a>

</td>


</tr>


<?php

}

?>


</table>

</div>


<?php

}

?>



<!-- =================================================
     NO SEARCH RESULT
================================================= -->

<?php

if (
    $search != ""
    &&
    count($search_results) == 0
    &&
    $selected_ano == ""
) {

?>

<div class="message error">

    Student not found.

</div>

<?php

}

?>



<!-- =================================================
     STUDENT PAYMENT DETAILS
================================================= -->

<?php if ($student_data) { ?>


<?php

$ano =
    $student_data['ano'];


/* Paid Amount */

$paid_query = mysqli_query(

    $conn,

    "SELECT
        COALESCE(SUM(amount),0) AS paid

     FROM payment

     WHERE ano='$ano'"
);


$paid_data =
    mysqli_fetch_assoc(
        $paid_query
    );


$already_paid =
    $paid_data['paid'];


$total_fees =
    $student_data['fees'];


$remaining =
    $total_fees
    -
    $already_paid;


if ($remaining < 0) {
    $remaining = 0;
}

?>


<div class="student-box">


<div class="student-heading">

    Student Details

</div>


<div class="student-details">


<p>

    <strong>
        Student Name:
    </strong>

    <?php

    echo htmlspecialchars(
        $student_data['sname']
    );

    ?>

</p>


<p>

    <strong>
        Student ID:
    </strong>

    <?php

    echo htmlspecialchars(
        $student_data['sid']
    );

    ?>

</p>


<p>

    <strong>
        Admission No:
    </strong>

    <?php

    echo htmlspecialchars(
        $student_data['ano']
    );

    ?>

</p>


<p>

    <strong>
        Admission Year:
    </strong>

    <?php

    echo htmlspecialchars(
        $student_data['admit_year']
    );

    ?>

</p>


<p>

    <strong>
        Course:
    </strong>

    <?php

    echo htmlspecialchars(
        $student_data['cname']
    );

    ?>

</p>



<!-- FEE DETAILS -->

<div class="fee-box">


<div class="fee-row">

<span>

    Total Fees

</span>


<span class="total">

    ₹<?php

    echo number_format(
        $total_fees,
        2
    );

    ?>

</span>

</div>



<div class="fee-row">

<span>

    Already Paid

</span>


<span class="paid">

    ₹<?php

    echo number_format(
        $already_paid,
        2
    );

    ?>

</span>

</div>



<div class="fee-row">

<span>

    Remaining

</span>


<span class="remaining">

    ₹<?php

    echo number_format(
        $remaining,
        2
    );

    ?>

</span>

</div>


</div>



<!-- PAYMENT -->

<?php if ($remaining > 0) { ?>


<div class="payment-form">


<h3>

    Make Payment

</h3>


<form
    method="POST"
    onsubmit="return validatePayment()"
>


<input
    type="hidden"
    name="ano"
    value="<?php
    echo htmlspecialchars(
        $ano
    );
    ?>"
>


<label>

    Payment Amount <span class="required">*</span>

</label>


<input
    type="text"
    id="amount"
    name="amount"

    placeholder="Enter amount"

    oninput="this.value=this.value.replace(/[^0-9]/g,'')"
>

<div
    id="amountError"
    class="field-error"
></div>



<label>

    Payment Mode <span class="required">*</span>

</label>


<select
    id="pmode"
    name="pmode"
>

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
    class="field-error"
></div>



<label>

    Payment Date <span class="required">*</span>

</label>


<input
    type="date"
    id="pdate"
    name="pdate"

    value="<?php
    echo date('Y-m-d');
    ?>"
>

<div
    id="pdateError"
    class="field-error"
></div>



<div class="pay-button">


<button
    type="submit"

    name="pay_fee"

    class="pay-btn"
>

    Pay Fees

</button>


</div>


</form>

</div>


<?php } else { ?>


<div class="full-paid">

    ✓ Fees Fully Paid

</div>


<?php } ?>


</div>

</div>


<?php } ?>



<!-- BACK TO DASHBOARD -->

<div class="back-area">

<a
    href="./dashboard.php"
    class="back-btn"
>

    Back

</a>

</div>


</div>

</div>


<script>

function validatePayment() {

    let amount =
        document.getElementById("amount");

    let pmode =
        document.getElementById("pmode");

    let pdate =
        document.getElementById("pdate");

    let amountError =
        document.getElementById("amountError");

    let pmodeError =
        document.getElementById("pmodeError");

    let pdateError =
        document.getElementById("pdateError");


    let valid = true;


    /* Clear old errors */

    amount.classList.remove("invalid");
    pmode.classList.remove("invalid");
    pdate.classList.remove("invalid");

    amountError.innerHTML = "";
    pmodeError.innerHTML = "";
    pdateError.innerHTML = "";


    /* Amount */

    if (amount.value.trim() == "") {

        amount.classList.add("invalid");

        amountError.innerHTML =
            "Payment Amount is required.";

        valid = false;

    }

    else if (
        !/^[0-9]+$/.test(
            amount.value
        )
    ) {

        amount.classList.add("invalid");

        amountError.innerHTML =
            "Payment Amount must contain numbers only.";

        valid = false;

    }

    else if (
        Number(amount.value) <= 0
    ) {

        amount.classList.add("invalid");

        amountError.innerHTML =
            "Enter a valid amount.";

        valid = false;

    }

    else if (
        Number(amount.value) >
        <?php echo (float)$remaining; ?>
    ) {

        amount.classList.add("invalid");

        amountError.innerHTML =
            "Amount cannot be more than remaining fees.";

        valid = false;

    }


    /* Payment Mode */

    if (pmode.value == "") {

        pmode.classList.add("invalid");

        pmodeError.innerHTML =
            "Payment Mode is required.";

        valid = false;
    }


    /* Payment Date */

    if (pdate.value == "") {

        pdate.classList.add("invalid");

        pdateError.innerHTML =
            "Payment Date is required.";

        valid = false;
    }


    return valid;
}


/* Remove amount error while typing */

document.getElementById("amount").addEventListener(
    "input",
    function () {

        this.classList.remove("invalid");

        document.getElementById(
            "amountError"
        ).innerHTML = "";

    }
);


/* Remove mode error */

document.getElementById("pmode").addEventListener(
    "change",
    function () {

        this.classList.remove("invalid");

        document.getElementById(
            "pmodeError"
        ).innerHTML = "";

    }
);


/* Remove date error */

document.getElementById("pdate").addEventListener(
    "change",
    function () {

        this.classList.remove("invalid");

        document.getElementById(
            "pdateError"
        ).innerHTML = "";

    }
);

</script>


</body>

</html>
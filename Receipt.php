<?php

include "demo.php";

if (!isset($_GET['ano'])) {
    die("Admission number not found.");
}

$ano = mysqli_real_escape_string($conn, $_GET['ano']);


/* =====================================================
   GET ADMISSION + STUDENT + COURSE + TOTAL PAYMENT
===================================================== */

$sql = "SELECT
            a.ano,
            a.addate,
            a.status,
            s.sname,
            c.cname,
            c.fees,
            p.total_paid,
            p.pmode,
            p.pdate

        FROM admission a

        LEFT JOIN student s
            ON a.sid = s.sid

        LEFT JOIN course c
            ON a.cid = c.cid

        LEFT JOIN
        (
            SELECT
                ano,
                SUM(amount) AS total_paid,

                SUBSTRING_INDEX(
                    GROUP_CONCAT(
                        pmode
                        ORDER BY pid DESC
                    ),
                    ',',
                    1
                ) AS pmode,

                MAX(pdate) AS pdate

            FROM payment

            GROUP BY ano

        ) p
            ON a.ano = p.ano

        WHERE a.ano='$ano'";

$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    die("Receipt not found.");
}

$row = mysqli_fetch_assoc($result);


/* =====================================================
   FEE CALCULATION
===================================================== */

$total_fees = $row['fees'] ?? 0;

$paid_amount = $row['total_paid'] ?? 0;

$remaining_amount = $total_fees - $paid_amount;

if ($remaining_amount < 0) {
    $remaining_amount = 0;
}


/* =====================================================
   STATUS
===================================================== */

if ($row['status'] == 'Approved') {
    $status_text = "✓ Admission Successful";
    $status_class = "success";
}
elseif ($row['status'] == 'Rejected') {
    $status_text = "✕ Admission Rejected";
    $status_class = "rejected-message";
}
else {
    $status_text = "⏳ Admission Pending";
    $status_class = "pending-message";
}

?>

<!DOCTYPE html>
<html>

<head>

<title>Admission Receipt</title>

<style>

* {
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {
    margin: 0;
    background: linear-gradient(135deg, #eaf2fb, #d6e5f5);
}

.receipt {
    width: 650px;
    margin: 50px auto;
    background: white;
    border-radius: 14px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    overflow: hidden;
}


/* Header */

.receipt-header {
    background: linear-gradient(90deg, #0b3c7c, #1559a6);
    color: white;
    text-align: center;
    padding: 25px;
}

.receipt-header h1 {
    margin: 0;
    font-size: 28px;
}

.receipt-header p {
    margin: 8px 0 0;
    font-size: 14px;
}


/* Body */

.receipt-body {
    padding: 30px;
}

.receipt-title {
    text-align: center;
    color: #0b3c7c;
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 25px;
}

.details {
    border: 1px solid #ddd;
    border-radius: 10px;
    overflow: hidden;
}

.row {
    display: flex;
    padding: 15px 18px;
    border-bottom: 1px solid #eee;
}

.row:last-child {
    border-bottom: none;
}

.label {
    width: 190px;
    font-weight: bold;
    color: #444;
}

.value {
    flex: 1;
    color: #222;
}


/* Fee Section */

.fee-box {
    margin-top: 25px;
    border: 1px solid #ddd;
    border-radius: 10px;
    overflow: hidden;
}

.fee-row {
    display: flex;
    justify-content: space-between;
    padding: 14px 18px;
    border-bottom: 1px solid #eee;
}

.fee-row:last-child {
    border-bottom: none;
}

.fee-label {
    font-weight: bold;
}

.fee-value {
    font-weight: bold;
}

.paid {
    color: green;
}

.remaining {
    color: #d97706;
}


/* Admission Status */

.success {
    margin-top: 25px;
    padding: 14px;
    background: #eaf8ee;
    color: #198754;
    border: 1px solid #b7e4c7;
    border-radius: 8px;
    text-align: center;
    font-size: 18px;
    font-weight: bold;
}

.rejected-message {
    margin-top: 25px;
    padding: 14px;
    background: #ffecec;
    color: #c62828;
    border: 1px solid #f5b5b5;
    border-radius: 8px;
    text-align: center;
    font-size: 18px;
    font-weight: bold;
}

.pending-message {
    margin-top: 25px;
    padding: 14px;
    background: #fff7e6;
    color: #9a6700;
    border: 1px solid #f1d48a;
    border-radius: 8px;
    text-align: center;
    font-size: 18px;
    font-weight: bold;
}


/* Buttons */

.buttons {
    text-align: center;
    margin-top: 25px;
}

button,
.back {
    display: inline-block;
    padding: 11px 25px;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    cursor: pointer;
    text-decoration: none;
}

button {
    background: #0b3c7c;
    color: white;
}

button:hover {
    background: #1559a6;
}

.back {
    background: #777;
    color: white;
    margin-left: 8px;
}

.back:hover {
    background: #555;
}


/* Footer */

.footer {
    text-align: center;
    color: #777;
    font-size: 13px;
    margin-top: 20px;
}


/* Print */

@media print {

    body {
        background: white;
    }

    .receipt {
        width: 100%;
        margin: 0;
        box-shadow: none;
        border-radius: 0;
    }

    .buttons,
    .footer {
        display: none;
    }

}

</style>

</head>

<body>


<div class="receipt">


    <!-- Header -->

    <div class="receipt-header">

        <h1>
            STUDENT ADMISSION SYSTEM
        </h1>

        <p>
            Admission Receipt
        </p>

    </div>


    <!-- Body -->

    <div class="receipt-body">


        <div class="receipt-title">
            ADMISSION RECEIPT
        </div>


        <!-- Student Details -->

        <div class="details">


            <div class="row">

                <div class="label">
                    Admission No
                </div>

                <div class="value">
                    <?php
                    echo htmlspecialchars($row['ano']);
                    ?>
                </div>

            </div>


            <div class="row">

                <div class="label">
                    Student Name
                </div>

                <div class="value">
                    <?php
                    echo htmlspecialchars($row['sname']);
                    ?>
                </div>

            </div>


            <div class="row">

                <div class="label">
                    Course
                </div>

                <div class="value">
                    <?php
                    echo htmlspecialchars($row['cname']);
                    ?>
                </div>

            </div>


            <div class="row">

                <div class="label">
                    Admission Date
                </div>

                <div class="value">

                    <?php

                    if (!empty($row['addate'])) {

                        echo date(
                            "d/m/Y",
                            strtotime($row['addate'])
                        );

                    }

                    ?>

                </div>

            </div>


        </div>


        <!-- Fee Details -->

        <div class="fee-box">


            <div class="fee-row">

                <div class="fee-label">
                    Total Fees
                </div>

                <div class="fee-value">

                    ₹<?php
                    echo number_format(
                        $total_fees,
                        2
                    );
                    ?>

                </div>

            </div>


            <div class="fee-row">

                <div class="fee-label">
                    Paid Amount
                </div>

                <div class="fee-value paid">

                    ₹<?php
                    echo number_format(
                        $paid_amount,
                        2
                    );
                    ?>

                </div>

            </div>


            <div class="fee-row">

                <div class="fee-label">
                    Remaining Amount
                </div>

                <div class="fee-value remaining">

                    ₹<?php
                    echo number_format(
                        $remaining_amount,
                        2
                    );
                    ?>

                </div>

            </div>


            <div class="fee-row">

                <div class="fee-label">
                    Payment Mode
                </div>

                <div class="fee-value">

                    <?php

                    echo htmlspecialchars(
                        $row['pmode'] ?? '-'
                    );

                    ?>

                </div>

            </div>


            <div class="fee-row">

                <div class="fee-label">
                    Payment Date
                </div>

                <div class="fee-value">

                    <?php

                    if (!empty($row['pdate'])) {

                        echo date(
                            "d/m/Y",
                            strtotime($row['pdate'])
                        );

                    }
                    else {

                        echo "-";

                    }

                    ?>

                </div>

            </div>


        </div>


        <!-- Admission Status -->

        <div class="<?php echo $status_class; ?>">

            <?php
            echo $status_text;
            ?>

        </div>


        <!-- Buttons -->

        <div class="buttons">


            <button onclick="window.print()">
                Print
            </button>


            <a
                href="Admissions.php"
                class="back"
            >
                ← Back
            </a>


        </div>


        <div class="footer">

            Thank you for using Student Admission System

        </div>


    </div>

</div>


</body>

</html>
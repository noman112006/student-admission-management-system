<?php

include "demo.php";

if (!isset($_GET['pid'])) {
    die("Payment receipt not found.");
}

$pid = $_GET['pid'];

$sql = "SELECT
            p.pid,
            p.ano,
            p.amount,
            p.pmode,
            p.pdate,
            p.pstatus,
            a.addate,
            s.sid,
            s.sname,
            c.cname,
            c.fees
        FROM payment p
        LEFT JOIN admission a ON p.ano = a.ano
        LEFT JOIN student s ON a.sid = s.sid
        LEFT JOIN course c ON a.cid = c.cid
        WHERE p.pid='$pid'";

$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    die("Payment receipt not found.");
}

$row = mysqli_fetch_assoc($result);


/* Total Paid Till This Payment */

$paid_query = mysqli_query(
    $conn,
    "SELECT COALESCE(SUM(amount),0) AS total_paid
     FROM payment
     WHERE ano='".$row['ano']."'
     AND pid <= '$pid'"
);

$paid_data = mysqli_fetch_assoc($paid_query);

$paid_till_now = $paid_data['total_paid'];

$total_fees = $row['fees'];

$remaining = $total_fees - $paid_till_now;

if ($remaining < 0) {
    $remaining = 0;
}

?>

<!DOCTYPE html>
<html>

<head>

<title>Payment Receipt</title>

<style>

* {
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {
    margin: 0;
    background: #eef3f8;
    color: #222;
}

.receipt {
    width: 700px;
    margin: 40px auto;
    background: white;
    border: 1px solid #d4dbe3;
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
}


/* Header */

.header {
    background: #0b3c7c;
    color: white;
    text-align: center;
    padding: 24px;
}

.header h1 {
    margin: 0;
    font-size: 26px;
}

.header p {
    margin: 7px 0 0;
    font-size: 14px;
}


/* Receipt Title */

.receipt-title {
    text-align: center;
    padding: 22px 20px 12px;
}

.receipt-title h2 {
    margin: 0;
    color: #0b3c7c;
    font-size: 24px;
}

.receipt-title p {
    margin-top: 6px;
    color: #777;
    font-size: 13px;
}


/* Receipt Info */

.receipt-info {
    display: flex;
    justify-content: space-between;
    padding: 0 30px 20px;
    font-size: 14px;
}

.receipt-info strong {
    color: #333;
}


/* Section */

.section {
    margin: 0 30px 25px;
    border: 1px solid #dce2e8;
}

.section-title {
    background: #f1f5f9;
    color: #0b3c7c;
    font-weight: bold;
    padding: 12px 15px;
    border-bottom: 1px solid #dce2e8;
}


/* Rows */

.row {
    display: flex;
    padding: 12px 15px;
    border-bottom: 1px solid #edf0f3;
}

.row:last-child {
    border-bottom: none;
}

.label {
    width: 190px;
    font-weight: bold;
    color: #555;
}

.value {
    flex: 1;
}


/* Payment Highlight */

.payment-highlight {
    background: #edf6ff;
    border: 1px solid #b8d9f5;
}

.payment-highlight .value {
    color: #0b3c7c;
    font-size: 19px;
    font-weight: bold;
}


/* Fee Summary */

.fee-summary {
    margin: 0 30px 25px;
    border: 1px solid #dce2e8;
}

.fee-row {
    display: flex;
    justify-content: space-between;
    padding: 13px 15px;
    border-bottom: 1px solid #edf0f3;
}

.fee-row:last-child {
    border-bottom: none;
}

.fee-label {
    font-weight: bold;
}

.total {
    color: #0b3c7c;
    font-weight: bold;
}

.paid {
    color: green;
    font-weight: bold;
}

.balance {
    color: #d97706;
    font-weight: bold;
}


/* Status */

.status {
    margin: 0 30px 25px;
    padding: 13px;
    text-align: center;
    background: #eaf8ee;
    border: 1px solid #b7e4c7;
    color: #198754;
    font-weight: bold;
}


/* Signature */

.signature {
    display: flex;
    justify-content: space-between;
    padding: 30px;
    margin-top: 5px;
}

.signature-box {
    width: 180px;
    text-align: center;
    padding-top: 30px;
    border-top: 1px solid #777;
    font-size: 13px;
    color: #555;
}


/* Buttons */

.buttons {
    text-align: center;
    padding: 0 30px 30px;
}

button,
.back {
    display: inline-block;
    padding: 11px 24px;
    border-radius: 6px;
    font-size: 15px;
    text-decoration: none;
    border: none;
    cursor: pointer;
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
    font-size: 12px;
    color: #777;
    padding: 0 30px 25px;
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
        border: 1px solid #333;
    }

    .buttons {
        display: none;
    }

}

</style>

</head>

<body>

<div class="receipt">


    <!-- Header -->

    <div class="header">

        <h1>
            STUDENT ADMISSION SYSTEM
        </h1>

        <p>
            Official Fee Payment Receipt
        </p>

    </div>


    <!-- Title -->

    <div class="receipt-title">

        <h2>
            ADMISSION FEE RECEIPT
        </h2>

        <p>
            Payment Confirmation
        </p>

    </div>


    <!-- Receipt Information -->

    <div class="receipt-info">

        <div>

            <strong>
                Receipt No:
            </strong>

            REC<?php echo str_pad($row['pid'], 5, '0', STR_PAD_LEFT); ?>

        </div>

        <div>

            <strong>
                Date:
            </strong>

            <?php echo date("d/m/Y", strtotime($row['pdate'])); ?>

        </div>

    </div>


    <!-- Student Information -->

    <div class="section">

        <div class="section-title">
            Student Information
        </div>


        <div class="row">

            <div class="label">
                Student Name
            </div>

            <div class="value">
                <?php echo htmlspecialchars($row['sname']); ?>
            </div>

        </div>


        <div class="row">

            <div class="label">
                Student ID
            </div>

            <div class="value">
                <?php echo htmlspecialchars($row['sid']); ?>
            </div>

        </div>


        <div class="row">

            <div class="label">
                Admission No
            </div>

            <div class="value">
                <?php echo htmlspecialchars($row['ano']); ?>
            </div>

        </div>


        <div class="row">

            <div class="label">
                Course
            </div>

            <div class="value">
                <?php echo htmlspecialchars($row['cname']); ?>
            </div>

        </div>

    </div>


    <!-- Payment Information -->

    <div class="section">

        <div class="section-title">
            Payment Information
        </div>


        <div class="row payment-highlight">

            <div class="label">
                Amount Paid
            </div>

            <div class="value">
                ₹<?php echo number_format($row['amount'], 2); ?>
            </div>

        </div>


        <div class="row">

            <div class="label">
                Payment Mode
            </div>

            <div class="value">
                <?php echo htmlspecialchars($row['pmode']); ?>
            </div>

        </div>


        <div class="row">

            <div class="label">
                Payment Date
            </div>

            <div class="value">
                <?php echo date("d/m/Y", strtotime($row['pdate'])); ?>
            </div>

        </div>


        <div class="row">

            <div class="label">
                Payment Status
            </div>

            <div class="value">
                <?php echo htmlspecialchars($row['pstatus']); ?>
            </div>

        </div>

    </div>


    <!-- Fee Summary -->

    <div class="fee-summary">

        <div class="fee-row">

            <span class="fee-label">
                Total Course Fees
            </span>

            <span class="total">
                ₹<?php echo number_format($total_fees, 2); ?>
            </span>

        </div>


        <div class="fee-row">

            <span class="fee-label">
                Paid Till Date
            </span>

            <span class="paid">
                ₹<?php echo number_format($paid_till_now, 2); ?>
            </span>

        </div>


        <div class="fee-row">

            <span class="fee-label">
                Remaining Fees
            </span>

            <span class="balance">
                ₹<?php echo number_format($remaining, 2); ?>
            </span>

        </div>

    </div>


    <!-- Status -->

    <div class="status">

        ✓ PAYMENT RECEIVED SUCCESSFULLY

    </div>


    <!-- Signature -->

    <div class="signature">

        <div class="signature-box">
            Student Signature
        </div>

        <div class="signature-box">
            Authorized Signature
        </div>

    </div>


    <!-- Buttons -->

    <div class="buttons">

        <button onclick="window.print()">
            Print Receipt
        </button>

        <a href="PayFees.php" class="back">
            Back
        </a>

    </div>


    <div class="footer">

        This is a computer-generated payment receipt.

    </div>


</div>

</body>

</html>
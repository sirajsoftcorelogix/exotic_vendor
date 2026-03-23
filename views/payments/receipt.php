<?php
// $payment already coming from controller
?>

<!DOCTYPE html>
<html>

<head>
    <title>Payment Receipt</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #fff;
            padding: 20px;
        }

        .receipt-box {
            max-width: 700px;
            margin: auto;
            border: 1px solid #eee;
            padding: 25px;
        }

        h2 {
            margin: 0;
        }

        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .section {
            margin-bottom: 20px;
        }

        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
        }

        .title {
            font-weight: bold;
            color: #555;
        }

        .amount {
            font-size: 22px;
            font-weight: bold;
            color: green;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #888;
        }

        @media print {
            body {
                padding: 0;
            }

            .receipt-box {
                border: none;
            }
        }
    </style>

</head>

<body>

    <div class="receipt-box">

        <!-- HEADER -->
        <div class="header">
            <div>
                <h2>PAYMENT RECEIPT</h2>
                <div>Receipt No: #<?= $payment['id'] ?></div>
            </div>

            <div style="text-align:right;">
                <div><?= $payment['warehouse'] ?? '' ?></div>
                <div>Date: <?= $payment['payment_date'] ?></div>
            </div>
        </div>

        <!-- CUSTOMER -->
        <div class="section">
            <div class="title">Order Number</div>
            <div><?= $payment['order_number'] ?></div>
        </div>

        <!-- PAYMENT INFO -->
        <div class="section">

            <div class="row">
                <div class="title">Payment Mode</div>
                <div><?= strtoupper($payment['payment_mode']) ?></div>
            </div>

            <div class="row">
                <div class="title">Payment Stage</div>
                <div><?= strtoupper($payment['payment_stage']) ?></div>
            </div>

            <div class="row">
                <div class="title">Transaction ID</div>
                <div><?= $payment['transaction_id'] ?></div>
            </div>

            <div class="row">
                <div class="title">Collected By</div>
                <div><?= $payment['user_name'] ?></div>
            </div>

        </div>

        <!-- AMOUNT -->
        <div class="section" style="text-align:center;">
            <div class="title">Amount Paid</div>
            <div class="amount">₹ <?= number_format($payment['amount'], 2) ?></div>
        </div>

        <!-- NOTE -->
        <?php if (!empty($payment['note'])): ?>
            <div class="section">
                <div class="title">Note</div>
                <div><?= $payment['note'] ?></div>
            </div>
        <?php endif; ?>

        <!-- FOOTER -->
        <div class="footer">
            This is system generated receipt.<br>
            Thank you for your business.
        </div>

    </div>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>

</body>

</html>
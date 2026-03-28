<?php
require_once 'hr_bootstrap.php';

$payslipId = (int) ($_GET['id'] ?? 0);
$payslip = hrGetPayslip($pdo, $payslipId);

if (!$payslip) {
    http_response_code(404);
    echo 'Payslip not found.';
    exit;
}

echo hrBuildPayslipHtml($payslip);

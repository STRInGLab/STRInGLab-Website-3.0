<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['redirect_back'] = $_SERVER['REQUEST_URI'];
    header('Location: signin.php');
    exit;
}

require_once 'config.php';
require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

date_default_timezone_set('Asia/Kolkata');

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int) $e->getCode());
}

function hrSanitize($value)
{
    if (is_array($value)) {
        return array_map('hrSanitize', $value);
    }

    return trim((string) $value);
}

function hrMoney($value)
{
    if ($value === null || $value === '') {
        return 0;
    }

    return round((float) str_replace(',', '', (string) $value), 2);
}

function hrEnsureTables(PDO $pdo)
{
    $queries = [
        "CREATE TABLE IF NOT EXISTS hr_employees (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            employee_code VARCHAR(50) NOT NULL,
            first_name VARCHAR(120) NOT NULL,
            last_name VARCHAR(120) DEFAULT NULL,
            email VARCHAR(190) NOT NULL,
            phone VARCHAR(40) DEFAULT NULL,
            emergency_contact_name VARCHAR(150) DEFAULT NULL,
            emergency_contact_phone VARCHAR(40) DEFAULT NULL,
            designation VARCHAR(150) DEFAULT NULL,
            department VARCHAR(150) DEFAULT NULL,
            employment_type VARCHAR(80) DEFAULT NULL,
            joining_date DATE DEFAULT NULL,
            date_of_birth DATE DEFAULT NULL,
            gender VARCHAR(30) DEFAULT NULL,
            blood_group VARCHAR(10) DEFAULT NULL,
            pan_number VARCHAR(30) DEFAULT NULL,
            aadhaar_number VARCHAR(30) DEFAULT NULL,
            uan_number VARCHAR(30) DEFAULT NULL,
            esi_number VARCHAR(30) DEFAULT NULL,
            tax_regime VARCHAR(30) DEFAULT NULL,
            manager_name VARCHAR(150) DEFAULT NULL,
            work_location VARCHAR(150) DEFAULT NULL,
            bank_name VARCHAR(150) DEFAULT NULL,
            bank_account_number VARCHAR(80) DEFAULT NULL,
            bank_ifsc VARCHAR(30) DEFAULT NULL,
            address_line_1 VARCHAR(255) DEFAULT NULL,
            address_line_2 VARCHAR(255) DEFAULT NULL,
            city VARCHAR(120) DEFAULT NULL,
            state VARCHAR(120) DEFAULT NULL,
            country VARCHAR(120) DEFAULT NULL,
            postal_code VARCHAR(20) DEFAULT NULL,
            ctc DECIMAL(12,2) DEFAULT 0.00,
            basic_salary DECIMAL(12,2) DEFAULT 0.00,
            hra DECIMAL(12,2) DEFAULT 0.00,
            special_allowance DECIMAL(12,2) DEFAULT 0.00,
            conveyance DECIMAL(12,2) DEFAULT 0.00,
            medical_allowance DECIMAL(12,2) DEFAULT 0.00,
            bonus DECIMAL(12,2) DEFAULT 0.00,
            other_earnings DECIMAL(12,2) DEFAULT 0.00,
            pf_deduction DECIMAL(12,2) DEFAULT 0.00,
            professional_tax DECIMAL(12,2) DEFAULT 0.00,
            tds DECIMAL(12,2) DEFAULT 0.00,
            esi_deduction DECIMAL(12,2) DEFAULT 0.00,
            loan_deduction DECIMAL(12,2) DEFAULT 0.00,
            other_deductions DECIMAL(12,2) DEFAULT 0.00,
            auto_send_payslip TINYINT(1) NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            notes TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_hr_employee_code (employee_code),
            UNIQUE KEY uniq_hr_employee_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS hr_payslips (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            employee_id INT UNSIGNED NOT NULL,
            pay_period_month TINYINT UNSIGNED NOT NULL,
            pay_period_year SMALLINT UNSIGNED NOT NULL,
            pay_date DATE DEFAULT NULL,
            working_days DECIMAL(6,2) DEFAULT 0.00,
            payable_days DECIMAL(6,2) DEFAULT 0.00,
            lop_days DECIMAL(6,2) DEFAULT 0.00,
            reimbursement DECIMAL(12,2) DEFAULT 0.00,
            incentives DECIMAL(12,2) DEFAULT 0.00,
            arrears DECIMAL(12,2) DEFAULT 0.00,
            additional_deductions DECIMAL(12,2) DEFAULT 0.00,
            gross_earnings DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            gross_deductions DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            net_pay DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            net_pay_words VARCHAR(255) DEFAULT NULL,
            email_status VARCHAR(20) NOT NULL DEFAULT 'pending',
            last_sent_at DATETIME DEFAULT NULL,
            sent_to_email VARCHAR(190) DEFAULT NULL,
            remarks TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_hr_payslip_employee FOREIGN KEY (employee_id) REFERENCES hr_employees(id) ON DELETE CASCADE,
            UNIQUE KEY uniq_hr_payslip_period (employee_id, pay_period_month, pay_period_year)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS hr_payslip_components (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            payslip_id INT UNSIGNED NOT NULL,
            component_type ENUM('earning', 'deduction') NOT NULL,
            component_name VARCHAR(150) NOT NULL,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            display_order INT NOT NULL DEFAULT 0,
            CONSTRAINT fk_hr_component_payslip FOREIGN KEY (payslip_id) REFERENCES hr_payslips(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS hr_payslip_email_logs (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            payslip_id INT UNSIGNED NOT NULL,
            employee_id INT UNSIGNED NOT NULL,
            recipient_email VARCHAR(190) NOT NULL,
            delivery_mode VARCHAR(20) NOT NULL DEFAULT 'manual',
            delivery_status VARCHAR(20) NOT NULL DEFAULT 'sent',
            error_message TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_hr_email_log_payslip FOREIGN KEY (payslip_id) REFERENCES hr_payslips(id) ON DELETE CASCADE,
            CONSTRAINT fk_hr_email_log_employee FOREIGN KEY (employee_id) REFERENCES hr_employees(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ];

    foreach ($queries as $query) {
        $pdo->exec($query);
    }
}

function hrGetEmployee(PDO $pdo, $employeeId)
{
    $stmt = $pdo->prepare("SELECT * FROM hr_employees WHERE id = ?");
    $stmt->execute([$employeeId]);
    return $stmt->fetch();
}

function hrGetEmployees(PDO $pdo)
{
    $stmt = $pdo->query("SELECT * FROM hr_employees ORDER BY created_at DESC");
    return $stmt->fetchAll();
}

function hrGetPayslip(PDO $pdo, $payslipId)
{
    $stmt = $pdo->prepare("
        SELECT p.*, e.employee_code, e.first_name, e.last_name, e.email, e.designation, e.department,
               e.bank_name, e.bank_account_number, e.bank_ifsc, e.pan_number, e.uan_number,
               e.joining_date, e.work_location
        FROM hr_payslips p
        INNER JOIN hr_employees e ON e.id = p.employee_id
        WHERE p.id = ?
    ");
    $stmt->execute([$payslipId]);
    $payslip = $stmt->fetch();

    if (!$payslip) {
        return null;
    }

    $componentStmt = $pdo->prepare("
        SELECT component_type, component_name, amount
        FROM hr_payslip_components
        WHERE payslip_id = ?
        ORDER BY component_type, display_order, id
    ");
    $componentStmt->execute([$payslipId]);

    $payslip['components'] = [
        'earning' => [],
        'deduction' => [],
    ];

    foreach ($componentStmt->fetchAll() as $component) {
        $payslip['components'][$component['component_type']][] = $component;
    }

    return $payslip;
}

function hrGetPayslips(PDO $pdo)
{
    $stmt = $pdo->query("
        SELECT p.*, e.employee_code, e.first_name, e.last_name, e.email, e.auto_send_payslip
        FROM hr_payslips p
        INNER JOIN hr_employees e ON e.id = p.employee_id
        ORDER BY p.pay_period_year DESC, p.pay_period_month DESC, p.created_at DESC
    ");
    return $stmt->fetchAll();
}

function hrFormatCurrency($amount)
{
    return number_format((float) $amount, 2);
}

function hrMonthName($month, $year)
{
    return date('F Y', strtotime(sprintf('%04d-%02d-01', (int) $year, (int) $month)));
}

function hrNumberToWords($number)
{
    $rounded = (int) round($number);

    if (class_exists('NumberFormatter')) {
        $formatter = new NumberFormatter('en_IN', NumberFormatter::SPELLOUT);
        $words = $formatter->format($rounded);
        if ($words !== false) {
            return ucwords((string) $words) . ' Only';
        }
    }

    return 'INR ' . number_format($rounded, 0) . ' Only';
}

function hrGetDefaultComponents(array $employee)
{
    return [
        'earning' => [
            ['name' => 'Basic Salary', 'amount' => hrMoney($employee['basic_salary'] ?? 0)],
            ['name' => 'House Rent Allowance', 'amount' => hrMoney($employee['hra'] ?? 0)],
            ['name' => 'Special Allowance', 'amount' => hrMoney($employee['special_allowance'] ?? 0)],
            ['name' => 'Conveyance Allowance', 'amount' => hrMoney($employee['conveyance'] ?? 0)],
            ['name' => 'Medical Allowance', 'amount' => hrMoney($employee['medical_allowance'] ?? 0)],
            ['name' => 'Bonus', 'amount' => hrMoney($employee['bonus'] ?? 0)],
            ['name' => 'Other Earnings', 'amount' => hrMoney($employee['other_earnings'] ?? 0)],
        ],
        'deduction' => [
            ['name' => 'Provident Fund', 'amount' => hrMoney($employee['pf_deduction'] ?? 0)],
            ['name' => 'Professional Tax', 'amount' => hrMoney($employee['professional_tax'] ?? 0)],
            ['name' => 'TDS', 'amount' => hrMoney($employee['tds'] ?? 0)],
            ['name' => 'ESI', 'amount' => hrMoney($employee['esi_deduction'] ?? 0)],
            ['name' => 'Loan Deduction', 'amount' => hrMoney($employee['loan_deduction'] ?? 0)],
            ['name' => 'Other Deductions', 'amount' => hrMoney($employee['other_deductions'] ?? 0)],
        ],
    ];
}

function hrBuildPayslipHtml(array $payslip)
{
    $employeeName = trim($payslip['first_name'] . ' ' . $payslip['last_name']);
    $period = hrMonthName($payslip['pay_period_month'], $payslip['pay_period_year']);
    $earnings = $payslip['components']['earning'];
    $deductions = $payslip['components']['deduction'];
    $maxRows = max(count($earnings), count($deductions));

    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Payslip - <?php echo htmlspecialchars($employeeName); ?></title>
        <style>
            @font-face {
                font-family: 'MarkCustom';
                src: url('assets/fonts/Mark-Light.ttf') format('truetype');
                font-weight: 300;
                font-style: normal;
            }

            @font-face {
                font-family: 'MarkCustom';
                src: url('assets/fonts/Mark-Regular.ttf') format('truetype');
                font-weight: 400;
                font-style: normal;
            }

            @font-face {
                font-family: 'MarkCustom';
                src: url('assets/fonts/Mark-Medium.ttf') format('truetype');
                font-weight: 700;
                font-style: normal;
            }

            body {
                font-family: 'MarkCustom', Arial, sans-serif;
                background: #eef2f7;
                margin: 0;
                padding: 24px;
                color: #2f2f2f;
                font-weight: 300;
            }

            .sheet {
                max-width: 940px;
                margin: 0 auto;
                background: #ffffff;
                padding: 0;
                box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            }

            .inner {
                padding: 28px 28px 24px;
            }

            .top {
                display: flex;
                justify-content: space-between;
                align-items: stretch;
                gap: 24px;
            }

            .brand {
                display: flex;
                gap: 18px;
                align-items: center;
                flex: 1;
                padding: 12px 16px 12px 12px;
                border: 1px solid #e5e7eb;
                border-radius: 18px;
                background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            }

            .brand-mark {
                width: 180px;
                min-width: 180px;
                display: flex;
                align-items: center;
                justify-content: center;
                padding-right: 14px;
                border-right: 1px solid #dbe3ea;
            }

            .brand img {
                width: 160px;
                height: auto;
                object-fit: contain;
                display: block;
            }

            .brand-copy {
                display: flex;
                flex-direction: column;
                justify-content: center;
            }

            .brand-kicker {
                margin: 0 0 6px;
                color: #7c8a99;
                font-size: 12px;
                letter-spacing: 0.18em;
                font-weight: 400;
                text-transform: uppercase;
            }

            .brand h1 {
                margin: 0;
                font-size: 22px;
                line-height: 1.02;
                font-weight: 800;
                color: #262626;
                letter-spacing: -0.03em;
            }

            .brand p {
                margin: 8px 0 0;
                color: #6f6f6f;
                font-size: 15px;
                font-weight: 300;
            }

            .period-box {
                text-align: center;
                min-width: 220px;
                padding: 16px 18px;
                border-radius: 18px;
                background: #f8fafc;
                border: 1px solid #e5e7eb;
                display: flex;
                flex-direction: column;
                justify-content: center;
            }

            .period-box .eyebrow {
                color: #6b7280;
                font-size: 14px;
                margin-bottom: 4px;
                font-weight: 300;
            }

            .period-box .period {
                color: #111827;
                font-size: 22px;
                font-weight: 800;
                line-height: 1.15;
            }

            .divider {
                border-top: 2px solid #d8dde6;
                margin: 14px 0 22px;
            }

            .summary-row {
                display: grid;
                grid-template-columns: 1.4fr 0.9fr;
                gap: 22px;
                align-items: start;
            }

            .section-title {
                font-size: 14px;
                font-weight: 700;
                color: #5b5b5b;
                margin-bottom: 10px;
            }

            .employee-summary table {
                width: 100%;
                border-collapse: collapse;
            }

            .employee-summary td {
                padding: 7px 0;
                font-size: 16px;
                font-weight: 300;
            }

            .employee-summary td.label {
                width: 170px;
                color: #6b6b6b;
            }

            .employee-summary td.sep {
                width: 18px;
                color: #8a8a8a;
            }

            .employee-summary td.value {
                font-weight: 400;
                color: #222;
            }

            .netpay-card {
                background: #edf9ef;
                border: 1px solid #cbe7d1;
                border-radius: 14px;
                padding: 24px;
                margin-top: 24px;
            }

            .netpay-card .line {
                width: 4px;
                background: #57c66b;
                border-radius: 999px;
                margin-right: 14px;
            }

            .netpay-card .wrap {
                display: flex;
                align-items: center;
            }

            .netpay-card .amount {
                font-size: 32px;
                font-weight: 700;
                color: #111827;
                margin: 0;
            }

            .netpay-card .caption {
                margin-top: 4px;
                color: #68a276;
                font-size: 16px;
                font-weight: 300;
            }

            .breakup-box {
                border: 1px solid #d9dee8;
                border-radius: 16px;
                overflow: hidden;
                margin-top: 30px;
            }

            .breakup-box table {
                width: 100%;
                border-collapse: collapse;
            }

            .breakup-box th,
            .breakup-box td {
                padding: 12px 16px;
                font-size: 15px;
            }

            .breakup-box th {
                text-align: left;
                font-size: 14px;
                color: #3f3f46;
                font-weight: 700;
            }

            .breakup-box .header th {
                border-bottom: 2px dotted #cbd5e1;
            }

            .breakup-box .amount {
                text-align: right;
                font-weight: 700;
                white-space: nowrap;
            }

            .breakup-box .totals td {
                background: #f5f7fb;
                font-weight: 700;
                color: #3f3f46;
            }

            .net-row {
                border: 1px solid #d9dee8;
                border-radius: 14px;
                overflow: hidden;
                margin-top: 18px;
            }

            .net-row table {
                width: 100%;
                border-collapse: collapse;
            }

            .net-row td {
                padding: 12px 18px;
            }

            .net-row .label strong {
                display: block;
                font-size: 16px;
                color: #111827;
                font-weight: 700;
            }

            .net-row .label span {
                color: #6b7280;
                font-weight: 300;
            }

            .net-row .amount {
                width: 180px;
                background: #edf9ef;
                text-align: center;
                font-size: 18px;
                font-weight: 700;
            }

            .words {
                text-align: center;
                margin: 15px 0 1px;
                color: #6b7280;
                font-size: 14px;
                font-weight: 300;
            }

            .words strong {
                color: #2f2f2f;
                font-weight: 400;
            }

            .footer-divider {
                border-top: 2px solid #d8dde6;
                margin: 16px 0 0;
            }

            .system-note {
                text-align: center;
                padding: 18px 20px 12px;
                color: #7b7b7b;
                font-size: 13px;
                font-weight: 300;
            }

            .powered {
                text-align: center;
                padding: 14px 20px 2px;
                color: #4b5563;
                font-size: 14px;
                font-weight: 300;
            }

            .powered strong {
                color: #111827;
                font-weight: 400;
            }

            @media print {
                body {
                    background: #fff;
                    padding: 0;
                }

                .sheet {
                    box-shadow: none;
                    max-width: none;
                }
            }
        </style>
    </head>

    <body>
        <div class="sheet">
            <div class="inner">
                <div class="top">
                    <div class="brand">
                        <div class="brand-mark">
                            <img src="https://stringspace.blob.core.windows.net/stringspace/string_with_fullform_black.png"
                                alt="StringLab Logo">
                        </div>
                        <div class="brand-copy">
                            <div class="brand-kicker">Salary Statement</div>
                            <h1>STRINGLAB TECHNOLOGY SOLUTIONS Pvt. Ltd.</h1>
                            <p>Mumbai, 400091 India</p>
                        </div>
                    </div>
                    <div class="period-box">
                        <div class="eyebrow">Payslip For the Month</div>
                        <div class="period"><?php echo htmlspecialchars($period); ?></div>
                    </div>
                </div>
                <div class="divider"></div>

                <div class="summary-row">
                    <div class="employee-summary">
                        <div class="section-title">EMPLOYEE SUMMARY</div>
                        <table>
                            <tr>
                                <td class="label">Employee Name</td>
                                <td class="sep">:</td>
                                <td class="value"><?php echo htmlspecialchars($employeeName); ?></td>
                            </tr>
                            <tr>
                                <td class="label">Employee ID</td>
                                <td class="sep">:</td>
                                <td class="value"><?php echo htmlspecialchars($payslip['employee_code']); ?></td>
                            </tr>
                            <tr>
                                <td class="label">Pay Period</td>
                                <td class="sep">:</td>
                                <td class="value"><?php echo htmlspecialchars($period); ?></td>
                            </tr>
                            <tr>
                                <td class="label">Pay Date</td>
                                <td class="sep">:</td>
                                <td class="value"><?php echo htmlspecialchars($payslip['pay_date'] ?: '-'); ?></td>
                            </tr>
                            <tr>
                                <td class="label">Designation</td>
                                <td class="sep">:</td>
                                <td class="value"><?php echo htmlspecialchars($payslip['designation'] ?: '-'); ?></td>
                            </tr>
                            <tr>
                                <td class="label">Department</td>
                                <td class="sep">:</td>
                                <td class="value"><?php echo htmlspecialchars($payslip['department'] ?: '-'); ?></td>
                            </tr>
                        </table>
                    </div>

                    <div class="netpay-card">
                        <div class="wrap">
                            <div class="line"></div>
                            <div>
                                <div class="amount">Rs.<?php echo hrFormatCurrency($payslip['net_pay']); ?></div>
                                <div class="caption">Total Net Pay</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="breakup-box">
                    <table>
                        <tr class="header">
                            <th>EARNINGS</th>
                            <th class="amount">AMOUNT</th>
                            <th>DEDUCTIONS</th>
                            <th class="amount">AMOUNT</th>
                        </tr>
                        <?php for ($i = 0; $i < $maxRows; $i++):
                            $earning = $earnings[$i] ?? null;
                            $deduction = $deductions[$i] ?? null;
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($earning['component_name'] ?? ''); ?></td>
                                <td class="amount"><?php echo $earning ? 'Rs.' . hrFormatCurrency($earning['amount']) : ''; ?>
                                </td>
                                <td><?php echo htmlspecialchars($deduction['component_name'] ?? ''); ?></td>
                                <td class="amount">
                                    <?php echo $deduction ? 'Rs.' . hrFormatCurrency($deduction['amount']) : ''; ?>
                                </td>
                            </tr>
                        <?php endfor; ?>
                        <tr class="totals">
                            <td>Gross Earnings</td>
                            <td class="amount">Rs.<?php echo hrFormatCurrency($payslip['gross_earnings']); ?></td>
                            <td>Total Deductions</td>
                            <td class="amount">Rs.<?php echo hrFormatCurrency($payslip['gross_deductions']); ?></td>
                        </tr>
                    </table>
                </div>

                <div class="net-row">
                    <table>
                        <tr>
                            <td class="label">
                                <strong>TOTAL NET PAYABLE</strong>
                                <span>Gross Earnings - Total Deductions</span>
                            </td>
                            <td class="amount">Rs.<?php echo hrFormatCurrency($payslip['net_pay']); ?></td>
                        </tr>
                    </table>
                </div>

                <div class="words">
                    Amount In Words :
                    <strong><?php echo htmlspecialchars($payslip['net_pay_words'] ?: hrNumberToWords($payslip['net_pay'])); ?></strong>
                </div>

                <div class="footer-divider"></div>
                <div class="system-note">-- This is a system-generated document. --</div>
                <div class="powered">Powered by <strong>StringLab Payroll</strong> | Simplify payroll and compliance with
                    StringLab</div>
            </div>
        </div>
    </body>

    </html>
    <?php
    return (string) ob_get_clean();
}

function hrBuildPayslipPdf(array $payslip)
{
    $html = hrBuildPayslipHtml($payslip);
    $fontBasePath = __DIR__ . '/assets/fonts/';

    $pdfHtml = str_replace(
        [
            "url('assets/fonts/Mark-Thin.ttf')",
            "url('assets/fonts/Mark-Light.ttf')",
            "url('assets/fonts/Mark-Regular.ttf')",
        ],
        [
            "url('file://" . $fontBasePath . "Mark-Thin.ttf')",
            "url('file://" . $fontBasePath . "Mark-Light.ttf')",
            "url('file://" . $fontBasePath . "Mark-Regular.ttf')",
        ],
        $html
    );

    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans');

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($pdfHtml, 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    return $dompdf->output();
}

function hrPayslipAttachmentName(array $payslip)
{
    $employeeCode = preg_replace('/[^A-Za-z0-9_-]/', '-', (string) $payslip['employee_code']);
    return sprintf('payslip-%s-%04d-%02d.pdf', $employeeCode, (int) $payslip['pay_period_year'], (int) $payslip['pay_period_month']);
}

function hrSendPayslipEmail(PDO $pdo, $payslipId, $deliveryMode = 'manual')
{
    $payslip = hrGetPayslip($pdo, $payslipId);
    if (!$payslip) {
        return ['success' => false, 'message' => 'Payslip not found.'];
    }

    $recipient = $payslip['sent_to_email'] ?: $payslip['email'];
    if (!$recipient || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Employee email is missing or invalid.'];
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'careers@stringlab.org';
        $mail->Password = '$tringLab@2025';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        $mail->setFrom('careers@stringlab.org', 'S.T.R.In.G Lab HR');
        $mail->addAddress($recipient, trim($payslip['first_name'] . ' ' . $payslip['last_name']));
        $mail->isHTML(true);
        $mail->Subject = 'Payslip for ' . hrMonthName($payslip['pay_period_month'], $payslip['pay_period_year']);
        $html = hrBuildPayslipHtml($payslip);
        $pdf = hrBuildPayslipPdf($payslip);
        $mail->Body = $html;
        $mail->AltBody = 'Your payslip for ' . hrMonthName($payslip['pay_period_month'], $payslip['pay_period_year']) .
            ' is ready. Net pay: INR ' . hrFormatCurrency($payslip['net_pay']);
        $mail->addStringAttachment($pdf, hrPayslipAttachmentName($payslip), PHPMailer::ENCODING_BASE64, 'application/pdf');

        $mail->send();

        $updateStmt = $pdo->prepare("
            UPDATE hr_payslips
            SET email_status = 'sent', last_sent_at = NOW(), sent_to_email = ?
            WHERE id = ?
        ");
        $updateStmt->execute([$recipient, $payslipId]);

        $logStmt = $pdo->prepare("
            INSERT INTO hr_payslip_email_logs (payslip_id, employee_id, recipient_email, delivery_mode, delivery_status)
            VALUES (?, ?, ?, ?, 'sent')
        ");
        $logStmt->execute([$payslipId, $payslip['employee_id'], $recipient, $deliveryMode]);

        return ['success' => true, 'message' => 'Payslip emailed successfully.'];
    } catch (Exception $e) {
        $failStmt = $pdo->prepare("UPDATE hr_payslips SET email_status = 'failed' WHERE id = ?");
        $failStmt->execute([$payslipId]);

        $logStmt = $pdo->prepare("
            INSERT INTO hr_payslip_email_logs (payslip_id, employee_id, recipient_email, delivery_mode, delivery_status, error_message)
            VALUES (?, ?, ?, ?, 'failed', ?)
        ");
        $logStmt->execute([$payslipId, $payslip['employee_id'], $recipient, $deliveryMode, $mail->ErrorInfo ?: $e->getMessage()]);

        return ['success' => false, 'message' => 'Email send failed: ' . ($mail->ErrorInfo ?: $e->getMessage())];
    }
}

hrEnsureTables($pdo);

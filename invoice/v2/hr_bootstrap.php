<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['redirect_back'] = $_SERVER['REQUEST_URI'];
    header('Location: signin.php');
    exit;
}

require_once 'config.php';
require_once __DIR__ . '/vendor/autoload.php';

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

    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Payslip - <?php echo htmlspecialchars($employeeName); ?></title>
        <style>
            body { font-family: Arial, sans-serif; background: #f4f6fb; margin: 0; padding: 24px; color: #1e293b; }
            .sheet { max-width: 980px; margin: 0 auto; background: #fff; border-radius: 16px; padding: 32px; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08); }
            .top { display: flex; justify-content: space-between; gap: 24px; flex-wrap: wrap; margin-bottom: 24px; }
            .brand h1 { margin: 0 0 6px; font-size: 28px; }
            .muted { color: #64748b; }
            .badge { display: inline-block; padding: 8px 12px; border-radius: 999px; background: #e0f2fe; color: #0369a1; font-weight: 700; }
            .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px 24px; margin: 24px 0; }
            .cell { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; }
            .cell strong { display: block; color: #0f172a; margin-bottom: 4px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { padding: 12px; border-bottom: 1px solid #e2e8f0; text-align: left; }
            th { background: #eff6ff; color: #1d4ed8; }
            .right { text-align: right; }
            .summary { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; margin-top: 24px; }
            .summary .card { background: #0f172a; color: #fff; padding: 18px; border-radius: 14px; }
            .summary .card small { display: block; color: #cbd5e1; margin-bottom: 8px; }
            @media print {
                body { background: #fff; padding: 0; }
                .sheet { box-shadow: none; border-radius: 0; max-width: none; }
                .print-hidden { display: none; }
            }
        </style>
    </head>
    <body>
        <div class="sheet">
            <div class="top">
                <div class="brand">
                    <h1>S.T.R.In.G Lab Payslip</h1>
                    <div class="muted">Salary statement for <?php echo htmlspecialchars($period); ?></div>
                </div>
                <div>
                    <div class="badge"><?php echo htmlspecialchars($period); ?></div>
                    <div class="muted" style="margin-top:8px;">Pay Date: <?php echo htmlspecialchars($payslip['pay_date'] ?: '-'); ?></div>
                </div>
            </div>

            <div class="grid">
                <div class="cell"><strong>Employee</strong><?php echo htmlspecialchars($employeeName); ?></div>
                <div class="cell"><strong>Employee Code</strong><?php echo htmlspecialchars($payslip['employee_code']); ?></div>
                <div class="cell"><strong>Designation</strong><?php echo htmlspecialchars($payslip['designation'] ?: '-'); ?></div>
                <div class="cell"><strong>Department</strong><?php echo htmlspecialchars($payslip['department'] ?: '-'); ?></div>
                <div class="cell"><strong>Location</strong><?php echo htmlspecialchars($payslip['work_location'] ?: '-'); ?></div>
                <div class="cell"><strong>Joining Date</strong><?php echo htmlspecialchars($payslip['joining_date'] ?: '-'); ?></div>
                <div class="cell"><strong>Bank</strong><?php echo htmlspecialchars($payslip['bank_name'] ?: '-'); ?></div>
                <div class="cell"><strong>Account / IFSC</strong><?php echo htmlspecialchars(trim(($payslip['bank_account_number'] ?: '-') . ' / ' . ($payslip['bank_ifsc'] ?: '-'))); ?></div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Earnings</th>
                        <th class="right">Amount (INR)</th>
                        <th>Deductions</th>
                        <th class="right">Amount (INR)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $earnings = $payslip['components']['earning'];
                    $deductions = $payslip['components']['deduction'];
                    $maxRows = max(count($earnings), count($deductions));
                    for ($i = 0; $i < $maxRows; $i++):
                        $earning = $earnings[$i] ?? null;
                        $deduction = $deductions[$i] ?? null;
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($earning['component_name'] ?? ''); ?></td>
                        <td class="right"><?php echo $earning ? hrFormatCurrency($earning['amount']) : ''; ?></td>
                        <td><?php echo htmlspecialchars($deduction['component_name'] ?? ''); ?></td>
                        <td class="right"><?php echo $deduction ? hrFormatCurrency($deduction['amount']) : ''; ?></td>
                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>

            <div class="summary">
                <div class="card"><small>Gross Earnings</small><strong>INR <?php echo hrFormatCurrency($payslip['gross_earnings']); ?></strong></div>
                <div class="card"><small>Gross Deductions</small><strong>INR <?php echo hrFormatCurrency($payslip['gross_deductions']); ?></strong></div>
                <div class="card"><small>Net Pay</small><strong>INR <?php echo hrFormatCurrency($payslip['net_pay']); ?></strong></div>
            </div>

            <div style="margin-top:24px;" class="cell">
                <strong>Net Pay In Words</strong>
                <?php echo htmlspecialchars($payslip['net_pay_words'] ?: hrNumberToWords($payslip['net_pay'])); ?>
            </div>

            <div style="margin-top:16px;" class="grid">
                <div class="cell"><strong>Working Days</strong><?php echo htmlspecialchars((string) $payslip['working_days']); ?></div>
                <div class="cell"><strong>Payable Days</strong><?php echo htmlspecialchars((string) $payslip['payable_days']); ?></div>
                <div class="cell"><strong>LOP Days</strong><?php echo htmlspecialchars((string) $payslip['lop_days']); ?></div>
                <div class="cell"><strong>Remarks</strong><?php echo nl2br(htmlspecialchars($payslip['remarks'] ?: '-')); ?></div>
            </div>
        </div>
    </body>
    </html>
    <?php
    return (string) ob_get_clean();
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
        $mail->Body = hrBuildPayslipHtml($payslip);
        $mail->AltBody = 'Your payslip for ' . hrMonthName($payslip['pay_period_month'], $payslip['pay_period_year']) .
            ' is ready. Net pay: INR ' . hrFormatCurrency($payslip['net_pay']);

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

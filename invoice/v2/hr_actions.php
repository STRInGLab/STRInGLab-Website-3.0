<?php
require_once 'hr_bootstrap.php';

function hrRedirect($message, $type = 'success', $location = 'hr.php')
{
    $_SESSION['hr_flash_message'] = $message;
    $_SESSION['hr_flash_type'] = $type;
    header('Location: ' . $location);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['action'])) {
    hrRedirect('Invalid request.', 'error');
}

$action = hrSanitize($_POST['action']);

if ($action === 'save_employee') {
    $employeeId = (int) ($_POST['employee_id'] ?? 0);
    $checkbox = !empty($_POST['auto_send_payslip']) ? 1 : 0;

    $data = [
        'employee_code' => hrSanitize($_POST['employee_code'] ?? ''),
        'first_name' => hrSanitize($_POST['first_name'] ?? ''),
        'last_name' => hrSanitize($_POST['last_name'] ?? ''),
        'email' => hrSanitize($_POST['email'] ?? ''),
        'phone' => hrSanitize($_POST['phone'] ?? ''),
        'emergency_contact_name' => hrSanitize($_POST['emergency_contact_name'] ?? ''),
        'emergency_contact_phone' => hrSanitize($_POST['emergency_contact_phone'] ?? ''),
        'designation' => hrSanitize($_POST['designation'] ?? ''),
        'department' => hrSanitize($_POST['department'] ?? ''),
        'employment_type' => hrSanitize($_POST['employment_type'] ?? ''),
        'joining_date' => hrSanitize($_POST['joining_date'] ?? '') ?: null,
        'date_of_birth' => hrSanitize($_POST['date_of_birth'] ?? '') ?: null,
        'gender' => hrSanitize($_POST['gender'] ?? ''),
        'blood_group' => hrSanitize($_POST['blood_group'] ?? ''),
        'pan_number' => hrSanitize($_POST['pan_number'] ?? ''),
        'aadhaar_number' => hrSanitize($_POST['aadhaar_number'] ?? ''),
        'uan_number' => hrSanitize($_POST['uan_number'] ?? ''),
        'esi_number' => hrSanitize($_POST['esi_number'] ?? ''),
        'tax_regime' => hrSanitize($_POST['tax_regime'] ?? ''),
        'manager_name' => hrSanitize($_POST['manager_name'] ?? ''),
        'work_location' => hrSanitize($_POST['work_location'] ?? ''),
        'bank_name' => hrSanitize($_POST['bank_name'] ?? ''),
        'bank_account_number' => hrSanitize($_POST['bank_account_number'] ?? ''),
        'bank_ifsc' => hrSanitize($_POST['bank_ifsc'] ?? ''),
        'address_line_1' => hrSanitize($_POST['address_line_1'] ?? ''),
        'address_line_2' => hrSanitize($_POST['address_line_2'] ?? ''),
        'city' => hrSanitize($_POST['city'] ?? ''),
        'state' => hrSanitize($_POST['state'] ?? ''),
        'country' => hrSanitize($_POST['country'] ?? ''),
        'postal_code' => hrSanitize($_POST['postal_code'] ?? ''),
        'ctc' => hrMoney($_POST['ctc'] ?? 0),
        'basic_salary' => hrMoney($_POST['basic_salary'] ?? 0),
        'hra' => hrMoney($_POST['hra'] ?? 0),
        'special_allowance' => hrMoney($_POST['special_allowance'] ?? 0),
        'conveyance' => hrMoney($_POST['conveyance'] ?? 0),
        'medical_allowance' => hrMoney($_POST['medical_allowance'] ?? 0),
        'bonus' => hrMoney($_POST['bonus'] ?? 0),
        'other_earnings' => hrMoney($_POST['other_earnings'] ?? 0),
        'pf_deduction' => hrMoney($_POST['pf_deduction'] ?? 0),
        'professional_tax' => hrMoney($_POST['professional_tax'] ?? 0),
        'tds' => hrMoney($_POST['tds'] ?? 0),
        'esi_deduction' => hrMoney($_POST['esi_deduction'] ?? 0),
        'loan_deduction' => hrMoney($_POST['loan_deduction'] ?? 0),
        'other_deductions' => hrMoney($_POST['other_deductions'] ?? 0),
        'auto_send_payslip' => $checkbox,
        'status' => hrSanitize($_POST['status'] ?? 'active'),
        'notes' => hrSanitize($_POST['notes'] ?? ''),
    ];

    if (!$data['employee_code'] || !$data['first_name'] || !$data['email']) {
        hrRedirect('Employee code, first name and email are required.', 'error');
    }

    $columns = array_keys($data);

    if ($employeeId > 0) {
        try {
            $assignments = implode(', ', array_map(static function ($column) {
                return $column . ' = ?';
            }, $columns));
            $stmt = $pdo->prepare("UPDATE hr_employees SET $assignments WHERE id = ?");
            $stmt->execute(array_merge(array_values($data), [$employeeId]));
            hrRedirect('Employee updated successfully.');
        } catch (\PDOException $e) {
            hrRedirect('Could not update employee. Please check duplicate code/email values.', 'error');
        }
    }

    try {
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $stmt = $pdo->prepare("INSERT INTO hr_employees (" . implode(', ', $columns) . ") VALUES ($placeholders)");
        $stmt->execute(array_values($data));
        hrRedirect('Employee added successfully.');
    } catch (\PDOException $e) {
        hrRedirect('Could not add employee. Please check duplicate code/email values.', 'error');
    }
}

if ($action === 'generate_payslip') {
    $employeeId = (int) ($_POST['employee_id'] ?? 0);
    $employee = hrGetEmployee($pdo, $employeeId);

    if (!$employee) {
        hrRedirect('Please select a valid employee.', 'error');
    }

    $month = (int) ($_POST['pay_period_month'] ?? 0);
    $year = (int) ($_POST['pay_period_year'] ?? 0);
    if ($month < 1 || $month > 12 || $year < 2000) {
        hrRedirect('Invalid pay period.', 'error', 'hr.php?employee_id=' . $employeeId . '#generate-payslip');
    }

    $earningNames = $_POST['earning_name'] ?? [];
    $earningAmounts = $_POST['earning_amount'] ?? [];
    $deductionNames = $_POST['deduction_name'] ?? [];
    $deductionAmounts = $_POST['deduction_amount'] ?? [];

    $components = ['earning' => [], 'deduction' => []];
    $grossEarnings = 0;
    $grossDeductions = 0;

    foreach ($earningNames as $index => $name) {
        $componentName = hrSanitize($name);
        $amount = hrMoney($earningAmounts[$index] ?? 0);
        if ($componentName === '' && $amount == 0) {
            continue;
        }
        $components['earning'][] = ['name' => $componentName ?: 'Earning', 'amount' => $amount];
        $grossEarnings += $amount;
    }

    foreach ($deductionNames as $index => $name) {
        $componentName = hrSanitize($name);
        $amount = hrMoney($deductionAmounts[$index] ?? 0);
        if ($componentName === '' && $amount == 0) {
            continue;
        }
        $components['deduction'][] = ['name' => $componentName ?: 'Deduction', 'amount' => $amount];
        $grossDeductions += $amount;
    }

    $reimbursement = hrMoney($_POST['reimbursement'] ?? 0);
    $incentives = hrMoney($_POST['incentives'] ?? 0);
    $arrears = hrMoney($_POST['arrears'] ?? 0);
    $additionalDeductions = hrMoney($_POST['additional_deductions'] ?? 0);

    if ($reimbursement > 0) {
        $components['earning'][] = ['name' => 'Reimbursement', 'amount' => $reimbursement];
        $grossEarnings += $reimbursement;
    }
    if ($incentives > 0) {
        $components['earning'][] = ['name' => 'Incentives', 'amount' => $incentives];
        $grossEarnings += $incentives;
    }
    if ($arrears > 0) {
        $components['earning'][] = ['name' => 'Arrears', 'amount' => $arrears];
        $grossEarnings += $arrears;
    }
    if ($additionalDeductions > 0) {
        $components['deduction'][] = ['name' => 'Additional Deductions', 'amount' => $additionalDeductions];
        $grossDeductions += $additionalDeductions;
    }

    $netPay = $grossEarnings - $grossDeductions;
    $remarks = hrSanitize($_POST['remarks'] ?? '');

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO hr_payslips (
                employee_id, pay_period_month, pay_period_year, pay_date, working_days, payable_days, lop_days,
                reimbursement, incentives, arrears, additional_deductions, gross_earnings, gross_deductions,
                net_pay, net_pay_words, sent_to_email, remarks
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                pay_date = VALUES(pay_date),
                working_days = VALUES(working_days),
                payable_days = VALUES(payable_days),
                lop_days = VALUES(lop_days),
                reimbursement = VALUES(reimbursement),
                incentives = VALUES(incentives),
                arrears = VALUES(arrears),
                additional_deductions = VALUES(additional_deductions),
                gross_earnings = VALUES(gross_earnings),
                gross_deductions = VALUES(gross_deductions),
                net_pay = VALUES(net_pay),
                net_pay_words = VALUES(net_pay_words),
                sent_to_email = VALUES(sent_to_email),
                remarks = VALUES(remarks)
        ");
        $stmt->execute([
            $employeeId,
            $month,
            $year,
            hrSanitize($_POST['pay_date'] ?? '') ?: null,
            hrMoney($_POST['working_days'] ?? 0),
            hrMoney($_POST['payable_days'] ?? 0),
            hrMoney($_POST['lop_days'] ?? 0),
            $reimbursement,
            $incentives,
            $arrears,
            $additionalDeductions,
            $grossEarnings,
            $grossDeductions,
            $netPay,
            hrNumberToWords($netPay),
            $employee['email'],
            $remarks,
        ]);

        $payslipId = (int) $pdo->lastInsertId();
        if ($payslipId === 0) {
            $lookupStmt = $pdo->prepare("SELECT id FROM hr_payslips WHERE employee_id = ? AND pay_period_month = ? AND pay_period_year = ?");
            $lookupStmt->execute([$employeeId, $month, $year]);
            $payslipId = (int) $lookupStmt->fetchColumn();
        }

        $deleteStmt = $pdo->prepare("DELETE FROM hr_payslip_components WHERE payslip_id = ?");
        $deleteStmt->execute([$payslipId]);

        $componentStmt = $pdo->prepare("
            INSERT INTO hr_payslip_components (payslip_id, component_type, component_name, amount, display_order)
            VALUES (?, ?, ?, ?, ?)
        ");

        foreach (['earning', 'deduction'] as $type) {
            foreach ($components[$type] as $index => $component) {
                $componentStmt->execute([$payslipId, $type, $component['name'], $component['amount'], $index + 1]);
            }
        }

        $pdo->commit();
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        hrRedirect('Could not generate payslip: ' . $e->getMessage(), 'error', 'hr.php?employee_id=' . $employeeId . '#generate-payslip');
    }

    if (!empty($_POST['send_now']) || !empty($employee['auto_send_payslip'])) {
        $result = hrSendPayslipEmail($pdo, $payslipId, !empty($_POST['send_now']) ? 'manual' : 'auto');
        hrRedirect(
            $result['success']
                ? 'Payslip generated and emailed successfully.'
                : 'Payslip generated but email failed. ' . $result['message'],
            $result['success'] ? 'success' : 'error'
        );
    }

    hrRedirect('Payslip generated successfully.');
}

if ($action === 'send_payslip') {
    $payslipId = (int) ($_POST['payslip_id'] ?? 0);
    $result = hrSendPayslipEmail($pdo, $payslipId, 'manual');
    hrRedirect($result['message'], $result['success'] ? 'success' : 'error');
}

hrRedirect('Unknown action.', 'error');

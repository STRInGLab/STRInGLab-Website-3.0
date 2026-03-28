<?php
require_once 'hr_bootstrap.php';

$message = $_SESSION['hr_flash_message'] ?? null;
$messageType = $_SESSION['hr_flash_type'] ?? 'success';
unset($_SESSION['hr_flash_message'], $_SESSION['hr_flash_type']);

$editingEmployee = null;
if (isset($_GET['edit_employee'])) {
    $editingEmployee = hrGetEmployee($pdo, (int) $_GET['edit_employee']);
}

$employees = hrGetEmployees($pdo);
$payslips = hrGetPayslips($pdo);
$selectedEmployeeId = isset($_GET['employee_id']) ? (int) $_GET['employee_id'] : 0;
$selectedEmployee = $selectedEmployeeId ? hrGetEmployee($pdo, $selectedEmployeeId) : null;

$thisMonthStmt = $pdo->prepare("SELECT COUNT(*) FROM hr_payslips WHERE pay_period_month = ? AND pay_period_year = ?");
$thisMonthStmt->execute([(int) date('m'), (int) date('Y')]);

$stats = [
    'employees' => (int) $pdo->query("SELECT COUNT(*) FROM hr_employees WHERE status = 'active'")->fetchColumn(),
    'auto_send' => (int) $pdo->query("SELECT COUNT(*) FROM hr_employees WHERE auto_send_payslip = 1")->fetchColumn(),
    'payslips' => (int) $pdo->query("SELECT COUNT(*) FROM hr_payslips")->fetchColumn(),
    'this_month' => (int) $thisMonthStmt->fetchColumn(),
];

function field($source, $key, $default = '')
{
    return htmlspecialchars((string) ($source[$key] ?? $default));
}

$defaultComponents = $selectedEmployee ? hrGetDefaultComponents($selectedEmployee) : ['earning' => [], 'deduction' => []];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <base href="./" />
    <title>S.T.R.In.G - HR Module</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <link href="assets/plugins/custom/datatables/datatables.bundle.css" rel="stylesheet" type="text/css" />
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css" />
    <style>
        .hr-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .hr-pay-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; }
        .hr-block { border: 1px dashed var(--bs-gray-300); border-radius: 0.75rem; padding: 1rem; background: var(--bs-gray-100); }
        @media (max-width: 991px) {
            .hr-grid, .hr-pay-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-hoverable="true" data-kt-app-header-fixed="true" class="app-default">
<script>
var defaultThemeMode = "light"; var themeMode;
if (document.documentElement) {
    if (document.documentElement.hasAttribute("data-bs-theme-mode")) {
        themeMode = document.documentElement.getAttribute("data-bs-theme-mode");
    } else if (localStorage.getItem("data-bs-theme") !== null) {
        themeMode = localStorage.getItem("data-bs-theme");
    } else {
        themeMode = defaultThemeMode;
    }
    if (themeMode === "system") {
        themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
    }
    document.documentElement.setAttribute("data-bs-theme", themeMode);
}
</script>
<div class="d-flex flex-column flex-root app-root" id="kt_app_root">
    <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
        <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
            <?php include 'sidebar.html'; ?>
            <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
                <div class="d-flex flex-column flex-column-fluid">
                    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                        <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                                <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">HR Module</h1>
                                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                    <li class="breadcrumb-item text-muted"><a href="dashboard.php" class="text-muted text-hover-primary">Dashboard</a></li>
                                    <li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
                                    <li class="breadcrumb-item text-muted">Employees, salary breakup and payslips</li>
                                </ul>
                            </div>
                            <div class="d-flex gap-3">
                                <a href="hr.php#employee-form" class="btn btn-primary">Add Employee</a>
                                <a href="hr.php#generate-payslip" class="btn btn-light-primary">Generate Payslip</a>
                            </div>
                        </div>
                    </div>

                    <div id="kt_app_content" class="app-content flex-column-fluid">
                        <div id="kt_app_content_container" class="app-container container-xxl">
                            <?php if ($message): ?>
                                <div class="alert alert-<?php echo $messageType === 'error' ? 'danger' : 'success'; ?> d-flex align-items-center p-5 mb-10">
                                    <span><?php echo htmlspecialchars($message); ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="row g-5 g-xl-10 mb-10">
                                <div class="col-md-3">
                                    <div class="card card-flush h-md-100">
                                        <div class="card-body d-flex flex-column justify-content-center">
                                            <div class="fs-6 fw-semibold text-gray-500 mb-1">Active Employees</div>
                                            <div class="fs-2hx fw-bold text-gray-900"><?php echo $stats['employees']; ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card card-flush h-md-100">
                                        <div class="card-body d-flex flex-column justify-content-center">
                                            <div class="fs-6 fw-semibold text-gray-500 mb-1">Auto-send Enabled</div>
                                            <div class="fs-2hx fw-bold text-gray-900"><?php echo $stats['auto_send']; ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card card-flush h-md-100">
                                        <div class="card-body d-flex flex-column justify-content-center">
                                            <div class="fs-6 fw-semibold text-gray-500 mb-1">Total Payslips</div>
                                            <div class="fs-2hx fw-bold text-gray-900"><?php echo $stats['payslips']; ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card card-flush h-md-100">
                                        <div class="card-body d-flex flex-column justify-content-center">
                                            <div class="fs-6 fw-semibold text-gray-500 mb-1">This Month</div>
                                            <div class="fs-2hx fw-bold text-gray-900"><?php echo $stats['this_month']; ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card card-flush mb-10" id="employee-form">
                                <div class="card-header">
                                    <div class="card-title">
                                        <h2><?php echo $editingEmployee ? 'Update Employee' : 'Add Employee'; ?></h2>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <form method="post" action="hr_actions.php">
                                        <input type="hidden" name="action" value="save_employee" />
                                        <input type="hidden" name="employee_id" value="<?php echo (int) ($editingEmployee['id'] ?? 0); ?>" />

                                        <div class="hr-grid mb-8">
                                            <div class="hr-block">
                                                <h4 class="mb-5">Basic Details</h4>
                                                <div class="row g-5">
                                                    <div class="col-md-6"><label class="form-label">Employee Code</label><input class="form-control" name="employee_code" required value="<?php echo field($editingEmployee, 'employee_code'); ?>"></div>
                                                    <div class="col-md-6"><label class="form-label">Work Email</label><input type="email" class="form-control" name="email" required value="<?php echo field($editingEmployee, 'email'); ?>"></div>
                                                    <div class="col-md-6"><label class="form-label">First Name</label><input class="form-control" name="first_name" required value="<?php echo field($editingEmployee, 'first_name'); ?>"></div>
                                                    <div class="col-md-6"><label class="form-label">Last Name</label><input class="form-control" name="last_name" value="<?php echo field($editingEmployee, 'last_name'); ?>"></div>
                                                    <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="phone" value="<?php echo field($editingEmployee, 'phone'); ?>"></div>
                                                    <div class="col-md-6"><label class="form-label">Department</label><input class="form-control" name="department" value="<?php echo field($editingEmployee, 'department'); ?>"></div>
                                                    <div class="col-md-6"><label class="form-label">Designation</label><input class="form-control" name="designation" value="<?php echo field($editingEmployee, 'designation'); ?>"></div>
                                                    <div class="col-md-6"><label class="form-label">Employment Type</label><input class="form-control" name="employment_type" placeholder="Full Time / Contract / Intern" value="<?php echo field($editingEmployee, 'employment_type'); ?>"></div>
                                                    <div class="col-md-6"><label class="form-label">Joining Date</label><input type="date" class="form-control" name="joining_date" value="<?php echo field($editingEmployee, 'joining_date'); ?>"></div>
                                                    <div class="col-md-6"><label class="form-label">Date of Birth</label><input type="date" class="form-control" name="date_of_birth" value="<?php echo field($editingEmployee, 'date_of_birth'); ?>"></div>
                                                    <div class="col-md-6"><label class="form-label">Manager Name</label><input class="form-control" name="manager_name" value="<?php echo field($editingEmployee, 'manager_name'); ?>"></div>
                                                    <div class="col-md-6"><label class="form-label">Work Location</label><input class="form-control" name="work_location" value="<?php echo field($editingEmployee, 'work_location'); ?>"></div>
                                                    <div class="col-md-4"><label class="form-label">Gender</label><input class="form-control" name="gender" value="<?php echo field($editingEmployee, 'gender'); ?>"></div>
                                                    <div class="col-md-4"><label class="form-label">Blood Group</label><input class="form-control" name="blood_group" value="<?php echo field($editingEmployee, 'blood_group'); ?>"></div>
                                                    <div class="col-md-4"><label class="form-label">Status</label><select class="form-select" name="status"><option value="active" <?php echo (($editingEmployee['status'] ?? 'active') === 'active') ? 'selected' : ''; ?>>Active</option><option value="inactive" <?php echo (($editingEmployee['status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive</option></select></div>
                                                </div>
                                            </div>

                                            <div class="hr-block">
                                                <h4 class="mb-5">Compliance, Banking & Contact</h4>
                                                <div class="row g-5">
                                                    <div class="col-md-6"><label class="form-label">PAN Number</label><input class="form-control" name="pan_number" value="<?php echo field($editingEmployee, 'pan_number'); ?>"></div>
                                                    <div class="col-md-6"><label class="form-label">Aadhaar Number</label><input class="form-control" name="aadhaar_number" value="<?php echo field($editingEmployee, 'aadhaar_number'); ?>"></div>
                                                    <div class="col-md-6"><label class="form-label">UAN Number</label><input class="form-control" name="uan_number" value="<?php echo field($editingEmployee, 'uan_number'); ?>"></div>
                                                    <div class="col-md-6"><label class="form-label">ESI Number</label><input class="form-control" name="esi_number" value="<?php echo field($editingEmployee, 'esi_number'); ?>"></div>
                                                    <div class="col-md-6"><label class="form-label">Tax Regime</label><input class="form-control" name="tax_regime" placeholder="Old / New" value="<?php echo field($editingEmployee, 'tax_regime'); ?>"></div>
                                                    <div class="col-md-6"><label class="form-label">Bank Name</label><input class="form-control" name="bank_name" value="<?php echo field($editingEmployee, 'bank_name'); ?>"></div>
                                                    <div class="col-md-6"><label class="form-label">Bank Account Number</label><input class="form-control" name="bank_account_number" value="<?php echo field($editingEmployee, 'bank_account_number'); ?>"></div>
                                                    <div class="col-md-6"><label class="form-label">IFSC Code</label><input class="form-control" name="bank_ifsc" value="<?php echo field($editingEmployee, 'bank_ifsc'); ?>"></div>
                                                    <div class="col-md-6"><label class="form-label">Emergency Contact Name</label><input class="form-control" name="emergency_contact_name" value="<?php echo field($editingEmployee, 'emergency_contact_name'); ?>"></div>
                                                    <div class="col-md-6"><label class="form-label">Emergency Contact Phone</label><input class="form-control" name="emergency_contact_phone" value="<?php echo field($editingEmployee, 'emergency_contact_phone'); ?>"></div>
                                                    <div class="col-12"><label class="form-label">Address Line 1</label><input class="form-control" name="address_line_1" value="<?php echo field($editingEmployee, 'address_line_1'); ?>"></div>
                                                    <div class="col-12"><label class="form-label">Address Line 2</label><input class="form-control" name="address_line_2" value="<?php echo field($editingEmployee, 'address_line_2'); ?>"></div>
                                                    <div class="col-md-3"><label class="form-label">City</label><input class="form-control" name="city" value="<?php echo field($editingEmployee, 'city'); ?>"></div>
                                                    <div class="col-md-3"><label class="form-label">State</label><input class="form-control" name="state" value="<?php echo field($editingEmployee, 'state'); ?>"></div>
                                                    <div class="col-md-3"><label class="form-label">Country</label><input class="form-control" name="country" value="<?php echo field($editingEmployee, 'country'); ?>"></div>
                                                    <div class="col-md-3"><label class="form-label">Postal Code</label><input class="form-control" name="postal_code" value="<?php echo field($editingEmployee, 'postal_code'); ?>"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="hr-block mb-8">
                                            <h4 class="mb-5">Salary Breakup Defaults</h4>
                                            <div class="row g-5">
                                                <div class="col-md-3"><label class="form-label">Annual CTC</label><input class="form-control" name="ctc" value="<?php echo field($editingEmployee, 'ctc', '0'); ?>"></div>
                                                <div class="col-md-3"><label class="form-label">Basic Salary</label><input class="form-control" name="basic_salary" value="<?php echo field($editingEmployee, 'basic_salary', '0'); ?>"></div>
                                                <div class="col-md-3"><label class="form-label">HRA</label><input class="form-control" name="hra" value="<?php echo field($editingEmployee, 'hra', '0'); ?>"></div>
                                                <div class="col-md-3"><label class="form-label">Special Allowance</label><input class="form-control" name="special_allowance" value="<?php echo field($editingEmployee, 'special_allowance', '0'); ?>"></div>
                                                <div class="col-md-3"><label class="form-label">Conveyance</label><input class="form-control" name="conveyance" value="<?php echo field($editingEmployee, 'conveyance', '0'); ?>"></div>
                                                <div class="col-md-3"><label class="form-label">Medical Allowance</label><input class="form-control" name="medical_allowance" value="<?php echo field($editingEmployee, 'medical_allowance', '0'); ?>"></div>
                                                <div class="col-md-3"><label class="form-label">Bonus</label><input class="form-control" name="bonus" value="<?php echo field($editingEmployee, 'bonus', '0'); ?>"></div>
                                                <div class="col-md-3"><label class="form-label">Other Earnings</label><input class="form-control" name="other_earnings" value="<?php echo field($editingEmployee, 'other_earnings', '0'); ?>"></div>
                                                <div class="col-md-3"><label class="form-label">PF Deduction</label><input class="form-control" name="pf_deduction" value="<?php echo field($editingEmployee, 'pf_deduction', '0'); ?>"></div>
                                                <div class="col-md-3"><label class="form-label">Professional Tax</label><input class="form-control" name="professional_tax" value="<?php echo field($editingEmployee, 'professional_tax', '0'); ?>"></div>
                                                <div class="col-md-3"><label class="form-label">TDS</label><input class="form-control" name="tds" value="<?php echo field($editingEmployee, 'tds', '0'); ?>"></div>
                                                <div class="col-md-3"><label class="form-label">ESI Deduction</label><input class="form-control" name="esi_deduction" value="<?php echo field($editingEmployee, 'esi_deduction', '0'); ?>"></div>
                                                <div class="col-md-3"><label class="form-label">Loan Deduction</label><input class="form-control" name="loan_deduction" value="<?php echo field($editingEmployee, 'loan_deduction', '0'); ?>"></div>
                                                <div class="col-md-3"><label class="form-label">Other Deductions</label><input class="form-control" name="other_deductions" value="<?php echo field($editingEmployee, 'other_deductions', '0'); ?>"></div>
                                                <div class="col-md-3 d-flex align-items-center">
                                                    <div class="form-check form-switch form-check-custom form-check-solid mt-8">
                                                        <input class="form-check-input" type="checkbox" value="1" name="auto_send_payslip" <?php echo !empty($editingEmployee['auto_send_payslip']) ? 'checked' : ''; ?> />
                                                        <label class="form-check-label ms-3">Auto-send new payslips</label>
                                                    </div>
                                                </div>
                                                <div class="col-12"><label class="form-label">Notes</label><textarea class="form-control" rows="3" name="notes"><?php echo field($editingEmployee, 'notes'); ?></textarea></div>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-end gap-3">
                                            <?php if ($editingEmployee): ?><a href="hr.php" class="btn btn-light">Cancel</a><?php endif; ?>
                                            <button class="btn btn-primary" type="submit"><?php echo $editingEmployee ? 'Update Employee' : 'Save Employee'; ?></button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="card card-flush mb-10">
                                <div class="card-header"><div class="card-title"><h2>Employee Directory</h2></div></div>
                                <div class="card-body table-responsive">
                                    <table class="table align-middle table-row-dashed fs-6 gy-5">
                                        <thead>
                                            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                                <th>Employee</th>
                                                <th>Department</th>
                                                <th>Contact</th>
                                                <th>Salary</th>
                                                <th>Auto Send</th>
                                                <th>Status</th>
                                                <th class="text-end">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="fw-semibold text-gray-600">
                                        <?php foreach ($employees as $employee): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <span class="text-gray-900 fw-bold"><?php echo htmlspecialchars(trim($employee['first_name'] . ' ' . $employee['last_name'])); ?></span>
                                                        <span class="text-muted fs-7"><?php echo htmlspecialchars($employee['employee_code']); ?> · <?php echo htmlspecialchars($employee['designation'] ?: 'No designation'); ?></span>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($employee['department'] ?: '-'); ?></td>
                                                <td><?php echo htmlspecialchars($employee['email']); ?><br><span class="text-muted fs-7"><?php echo htmlspecialchars($employee['phone'] ?: '-'); ?></span></td>
                                                <td>₹<?php echo hrFormatCurrency($employee['basic_salary'] + $employee['hra'] + $employee['special_allowance'] + $employee['conveyance'] + $employee['medical_allowance'] + $employee['bonus'] + $employee['other_earnings']); ?></td>
                                                <td><?php echo !empty($employee['auto_send_payslip']) ? 'Enabled' : 'Manual'; ?></td>
                                                <td><span class="badge badge-light-<?php echo $employee['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo htmlspecialchars(ucfirst($employee['status'])); ?></span></td>
                                                <td class="text-end">
                                                    <a href="hr.php?edit_employee=<?php echo (int) $employee['id']; ?>#employee-form" class="btn btn-sm btn-light-primary">Edit</a>
                                                    <a href="hr.php?employee_id=<?php echo (int) $employee['id']; ?>#generate-payslip" class="btn btn-sm btn-light-success">Generate Payslip</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (!$employees): ?>
                                            <tr><td colspan="7" class="text-center text-muted py-10">No employees added yet.</td></tr>
                                        <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="card card-flush mb-10" id="generate-payslip">
                                <div class="card-header">
                                    <div class="card-title"><h2>Generate Payslip</h2></div>
                                </div>
                                <div class="card-body">
                                    <form method="post" action="hr_actions.php">
                                        <input type="hidden" name="action" value="generate_payslip" />
                                        <div class="row g-5 mb-8">
                                            <div class="col-md-4">
                                                <label class="form-label">Employee</label>
                                                <select class="form-select" name="employee_id" required onchange="if(this.value){window.location='hr.php?employee_id=' + this.value + '#generate-payslip';}">
                                                    <option value="">Select employee</option>
                                                    <?php foreach ($employees as $employee): ?>
                                                        <option value="<?php echo (int) $employee['id']; ?>" <?php echo $selectedEmployeeId === (int) $employee['id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($employee['employee_code'] . ' - ' . trim($employee['first_name'] . ' ' . $employee['last_name'])); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-2"><label class="form-label">Month</label><input type="number" min="1" max="12" class="form-control" name="pay_period_month" value="<?php echo date('n'); ?>" required></div>
                                            <div class="col-md-2"><label class="form-label">Year</label><input type="number" min="2000" max="2100" class="form-control" name="pay_period_year" value="<?php echo date('Y'); ?>" required></div>
                                            <div class="col-md-2"><label class="form-label">Pay Date</label><input type="date" class="form-control" name="pay_date" value="<?php echo date('Y-m-d'); ?>" required></div>
                                            <div class="col-md-2 d-flex align-items-center">
                                                <div class="form-check form-switch form-check-custom form-check-solid mt-8">
                                                    <input class="form-check-input" type="checkbox" name="send_now" value="1">
                                                    <label class="form-check-label ms-3">Send now</label>
                                                </div>
                                            </div>
                                            <div class="col-md-4"><label class="form-label">Working Days</label><input class="form-control" name="working_days" value="30"></div>
                                            <div class="col-md-4"><label class="form-label">Payable Days</label><input class="form-control" name="payable_days" value="30"></div>
                                            <div class="col-md-4"><label class="form-label">LOP Days</label><input class="form-control" name="lop_days" value="0"></div>
                                            <div class="col-md-3"><label class="form-label">Reimbursement</label><input class="form-control" name="reimbursement" value="0"></div>
                                            <div class="col-md-3"><label class="form-label">Incentives</label><input class="form-control" name="incentives" value="0"></div>
                                            <div class="col-md-3"><label class="form-label">Arrears</label><input class="form-control" name="arrears" value="0"></div>
                                            <div class="col-md-3"><label class="form-label">Additional Deductions</label><input class="form-control" name="additional_deductions" value="0"></div>
                                        </div>

                                        <?php if ($selectedEmployee): ?>
                                            <div class="alert alert-info mb-8">
                                                Salary breakup for <strong><?php echo htmlspecialchars(trim($selectedEmployee['first_name'] . ' ' . $selectedEmployee['last_name'])); ?></strong>.
                                                You can edit these values before generating the payslip.
                                            </div>
                                            <div class="hr-pay-grid">
                                                <div class="hr-block">
                                                    <h4 class="mb-5">Earnings</h4>
                                                    <?php foreach ($defaultComponents['earning'] as $index => $component): ?>
                                                        <div class="row g-3 mb-3">
                                                            <div class="col-7"><input class="form-control" name="earning_name[]" value="<?php echo htmlspecialchars($component['name']); ?>"></div>
                                                            <div class="col-5"><input class="form-control" name="earning_amount[]" value="<?php echo htmlspecialchars((string) $component['amount']); ?>"></div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                    <div class="row g-3 mb-3">
                                                        <div class="col-7"><input class="form-control" name="earning_name[]" placeholder="Custom earning"></div>
                                                        <div class="col-5"><input class="form-control" name="earning_amount[]" value="0"></div>
                                                    </div>
                                                </div>
                                                <div class="hr-block">
                                                    <h4 class="mb-5">Deductions</h4>
                                                    <?php foreach ($defaultComponents['deduction'] as $index => $component): ?>
                                                        <div class="row g-3 mb-3">
                                                            <div class="col-7"><input class="form-control" name="deduction_name[]" value="<?php echo htmlspecialchars($component['name']); ?>"></div>
                                                            <div class="col-5"><input class="form-control" name="deduction_amount[]" value="<?php echo htmlspecialchars((string) $component['amount']); ?>"></div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                    <div class="row g-3 mb-3">
                                                        <div class="col-7"><input class="form-control" name="deduction_name[]" placeholder="Custom deduction"></div>
                                                        <div class="col-5"><input class="form-control" name="deduction_amount[]" value="0"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mt-8">
                                                <label class="form-label">Remarks</label>
                                                <textarea class="form-control" rows="3" name="remarks" placeholder="Optional note for this payslip"></textarea>
                                            </div>
                                            <div class="d-flex justify-content-end mt-8">
                                                <button type="submit" class="btn btn-primary">Generate Payslip</button>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-muted">Choose an employee to prefill salary breakup and create the payslip.</div>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>

                            <div class="card card-flush">
                                <div class="card-header">
                                    <div class="card-title"><h2>Payslip History</h2></div>
                                </div>
                                <div class="card-body table-responsive">
                                    <table class="table align-middle table-row-dashed fs-6 gy-5">
                                        <thead>
                                            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                                <th>Period</th>
                                                <th>Employee</th>
                                                <th>Gross</th>
                                                <th>Deduction</th>
                                                <th>Net Pay</th>
                                                <th>Email Status</th>
                                                <th class="text-end">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="fw-semibold text-gray-600">
                                        <?php foreach ($payslips as $payslip): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars(hrMonthName($payslip['pay_period_month'], $payslip['pay_period_year'])); ?><br><span class="text-muted fs-7"><?php echo htmlspecialchars($payslip['pay_date'] ?: '-'); ?></span></td>
                                                <td><?php echo htmlspecialchars(trim($payslip['first_name'] . ' ' . $payslip['last_name'])); ?><br><span class="text-muted fs-7"><?php echo htmlspecialchars($payslip['employee_code']); ?></span></td>
                                                <td>₹<?php echo hrFormatCurrency($payslip['gross_earnings']); ?></td>
                                                <td>₹<?php echo hrFormatCurrency($payslip['gross_deductions']); ?></td>
                                                <td class="fw-bold text-gray-900">₹<?php echo hrFormatCurrency($payslip['net_pay']); ?></td>
                                                <td>
                                                    <span class="badge badge-light-<?php echo $payslip['email_status'] === 'sent' ? 'success' : ($payslip['email_status'] === 'failed' ? 'danger' : 'warning'); ?>">
                                                        <?php echo htmlspecialchars(ucfirst($payslip['email_status'])); ?>
                                                    </span>
                                                    <div class="text-muted fs-7"><?php echo htmlspecialchars($payslip['last_sent_at'] ?: 'Not sent yet'); ?></div>
                                                </td>
                                                <td class="text-end">
                                                    <a target="_blank" href="payslip_view.php?id=<?php echo (int) $payslip['id']; ?>" class="btn btn-sm btn-light-primary">View</a>
                                                    <form method="post" action="hr_actions.php" class="d-inline">
                                                        <input type="hidden" name="action" value="send_payslip">
                                                        <input type="hidden" name="payslip_id" value="<?php echo (int) $payslip['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-light-success">Send</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (!$payslips): ?>
                                            <tr><td colspan="7" class="text-center text-muted py-10">No payslips generated yet.</td></tr>
                                        <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
</body>
</html>

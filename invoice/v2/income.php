<?php
session_start();
// check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    // if not logged in, redirect to signin.html
    $_SESSION['redirect_back'] = $_SERVER['REQUEST_URI']; // or you could use a full URL if necessary
    header('Location: signin.php');
    exit;
}
require_once 'config.php';

$conn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
    $pdo = new PDO($conn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Corrected delete request logic
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    $type = $_POST['type'];
    $sql = "DELETE FROM Invoice WHERE id = ? AND invoiceOrQuote = ?";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([$id, $type])) {
        echo "<script>alert('Record deleted successfully');</script>";
    } else {
        echo "<script>alert('Error deleting record.');</script>";
    }
}

// Corrected fetchData function definition
function fetchData($pdo) {
    $sql = "SELECT * FROM incomes";
    $stmt = $pdo->query($sql);
    $result = $stmt->fetchAll();

    if ($result) {
        foreach ($result as $row) {
            $formattedDate = date('Y-m-d', strtotime($row['UpdatedOn']));
            echo "<tr>
                    <td>{$formattedDate}</td>
                    <td>{$row['ClientName']}</td>
                    <td>{$row['ProjectName']}</td>
                    <td>{$row['PaymentDescription']}</td>
                    <td>{$row['Amount']}</td>
                    <td>{$row['Source']}</td>
                    <td>{$row['Notes']}</td>
                    <td>{$row['InvoiceID']}</td>
                    <td>{$row['PaymentStatus']}</td>
                    <td>{$row['CreditDate']}</td>
                    <td class='text-end'>
                        <button class='btn btn-bg-light btn-color-muted btn-active-color-primary btn-sm px-4 me-1 delete-btn' data-id='{$row['ID']}'>Delete</button>
                        <a href='edit_page.php?id={$row['ID']}' class='btn btn-bg-light btn-color-muted btn-active-color-primary btn-sm px-4 badge-light-warning'>Edit</a>
                    </td>
                  </tr>";
        }
    } else {
        echo "<tr><td colspan='11'>No Data Found</td></tr>";
    }
}

function getTotalInvoices($pdo) {
    $sql = "SELECT COUNT(*) as invoiceTotal FROM Invoice WHERE invoiceOrQuote = 'Invoice'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['invoiceTotal'];
}
$totalInvoices = getTotalInvoices($pdo);

function getPaidInvoices($pdo) {
    $sql = "SELECT COUNT(*) as paidInvoiceTotal FROM Invoice WHERE invoiceOrQuote = 'Invoice' AND paid_flag = '1'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['paidInvoiceTotal'];
}
$paidInvoices = getPaidInvoices($pdo);

function getRevenueFromInvoices($pdo) {
    $sql = "SELECT SUM((qty * price) - discount) AS total_revenue FROM InvoiceData INNER JOIN Invoice ON InvoiceData.invoiceId = Invoice.id WHERE Invoice.invoiceOrQuote = 'Invoice' AND Invoice.paid_flag = '1'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total_revenue'];
}
$revenueFromInvoices = getRevenueFromInvoices($pdo);

function getPaidTaxFromInvoices($pdo) {
    $sql = "SELECT SUM(((qty * price) - discount)*0.18) AS total_paid_tax FROM InvoiceData INNER JOIN Invoice ON InvoiceData.invoiceId = Invoice.id WHERE Invoice.invoiceOrQuote = 'Invoice' AND Invoice.paid_flag = '1'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total_paid_tax'];
}
$paidTaxFromInvoices = getPaidTaxFromInvoices($pdo);

function getUnpaidTaxFromInvoices($pdo) {
    $sql = "SELECT SUM(((qty * price) - discount)*0.18) AS total_unpaid_tax FROM InvoiceData INNER JOIN Invoice ON InvoiceData.invoiceId = Invoice.id WHERE Invoice.invoiceOrQuote = 'Invoice' AND Invoice.paid_flag = '0'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total_unpaid_tax'];
}
$unpaidTaxFromInvoices = getUnpaidTaxFromInvoices($pdo);

function getAccountReceivable($pdo) {
    $sql = "SELECT SUM((qty * price) - discount) AS ar FROM InvoiceData INNER JOIN Invoice ON InvoiceData.invoiceId = Invoice.id WHERE Invoice.invoiceOrQuote = 'Invoice' AND Invoice.paid_flag = '0'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['ar'];
}
$accountReceivable = getAccountReceivable($pdo);

function getClientNames($pdo) {
    $sql = "SELECT Name FROM clientDetails";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
$clientNames = getClientNames($pdo);

// Fetch projects by client name
function getProjectsByClientName($pdo, $clientName) {
    $sql = "SELECT Name FROM projectDetails WHERE Client_Name = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$clientName]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle AJAX request
if (isset($_GET['clientName'])) {
    $clientName = $_GET['clientName'];
    $projects = getProjectsByClientName($pdo, $clientName);
    echo json_encode($projects);
    exit;
}
?>
<!DOCTYPE html>
<!--
Author: Keenthemes
Product Name: MetronicProduct Version: 8.2.6
Purchase: https://1.envato.market/EA4JP
Website: http://www.keenthemes.com
Contact: support@keenthemes.com
Follow: www.twitter.com/keenthemes
Dribbble: www.dribbble.com/keenthemes
Like: www.facebook.com/keenthemes
License: For each use you must have a valid license purchased only from above link in order to legally use the theme for your project.
-->
<html lang="en">
	<!--begin::Head-->
	<head>
<base href="./" />
		<title>S.T.R.In.G - Income</title>
		<meta charset="utf-8" />
		<meta name="description" content="The most advanced Bootstrap 5 Admin Theme with 40 unique prebuilt layouts on Themeforest trusted by 100,000 beginners and professionals. Multi-demo, Dark Mode, RTL support and complete React, Angular, Vue, Asp.Net Core, Rails, Spring, Blazor, Django, Express.js, Node.js, Flask, Symfony & Laravel versions. Grab your copy now and get life-time updates for free." />
		<meta name="keywords" content="metronic, bootstrap, bootstrap 5, angular, VueJs, React, Asp.Net Core, Rails, Spring, Blazor, Django, Express.js, Node.js, Flask, Symfony & Laravel starter kits, admin themes, web design, figma, web development, free templates, free admin themes, bootstrap theme, bootstrap template, bootstrap dashboard, bootstrap dak mode, bootstrap button, bootstrap datepicker, bootstrap timepicker, fullcalendar, datatables, flaticon" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<meta property="og:locale" content="en_US" />
		<meta property="og:type" content="article" />
		<meta property="og:title" content="Metronic - The World's #1 Selling Bootstrap Admin Template by KeenThemes" />
		<meta property="og:url" content="https://keenthemes.com/metronic" />
		<meta property="og:site_name" content="Metronic by Keenthemes" />
		<link rel="canonical" href="http://preview.keenthemes.comapps/ecommerce/reports/sales.html" />
		<link rel="shortcut icon" href="assets/media/logos/favicon.ico" />
		<!--begin::Fonts(mandatory for all pages)-->
		<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
		<!--end::Fonts-->
		<!--begin::Vendor Stylesheets(used for this page only)-->
		<link href="assets/plugins/custom/datatables/datatables.bundle.css" rel="stylesheet" type="text/css" />
		<!--end::Vendor Stylesheets-->
		<!--begin::Global Stylesheets Bundle(mandatory for all pages)-->
		<link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
		<link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css" />
		<!--end::Global Stylesheets Bundle-->
		<script>// Frame-busting to prevent site from being loaded within a frame without permission (click-jacking) if (window.top != window.self) { window.top.location.replace(window.self.location.href); }</script>
        
    </head>
	<!--end::Head-->
	<!--begin::Body-->
	<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-hoverable="true" data-kt-app-sidebar-push-header="true" data-kt-app-sidebar-push-toolbar="true" data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="app-default">
		<!--begin::Theme mode setup on page load-->
		<script>var defaultThemeMode = "light"; var themeMode; if ( document.documentElement ) { if ( document.documentElement.hasAttribute("data-bs-theme-mode")) { themeMode = document.documentElement.getAttribute("data-bs-theme-mode"); } else { if ( localStorage.getItem("data-bs-theme") !== null ) { themeMode = localStorage.getItem("data-bs-theme"); } else { themeMode = defaultThemeMode; } } if (themeMode === "system") { themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light"; } document.documentElement.setAttribute("data-bs-theme", themeMode); }</script>
		<!--end::Theme mode setup on page load-->
		<!--begin::App-->
		<div class="d-flex flex-column flex-root app-root" id="kt_app_root">
			<!--begin::Page-->
			<div class="app-page flex-column flex-column-fluid" id="kt_app_page">
				<!--begin::Header-->
				
				<!--end::Header-->
				<!--begin::Wrapper-->
				<div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
					<!--begin::Sidebar-->
					<?php include 'sidebar.html'; ?>
					<!--end::Sidebar-->
					<!--begin::Main-->
					<div class="app-main flex-column flex-row-fluid" id="kt_app_main">
						<!--begin::Content wrapper-->
						<div class="d-flex flex-column flex-column-fluid">
							<!--begin::Toolbar-->
							<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
								<!--begin::Toolbar container-->
								<div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
									<!--begin::Page title-->
									<div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
										<!--begin::Title-->
										<h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">Income Details Dashboard</h1>
									</div>
									<!--end::Page title-->
									<!--begin::Actions-->
									<div class="d-flex align-items-center gap-2 gap-lg-3">
										<!--begin::Primary button-->
										<a href="#" class="btn btn-sm fw-bold btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_new_target">Add Income</a>
										<!--end::Primary button-->
									</div>
									<!--end::Actions-->
								</div>
								<!--end::Toolbar container-->
							</div>
							<!--end::Toolbar-->
							<div id="kt_app_content_container" class="app-container container-xxl">
								<div class="card mb-6">
									<div class="card-body pt-9 pb-0">
										<!--begin::Details-->
										<div class="d-flex flex-wrap flex-sm-nowrap">
											<!--begin: Pic-->
											<div class="me-7 mb-4">
												<div class="symbol symbol-100px symbol-lg-160px symbol-fixed position-relative">
													<img src="assets/media/avatars/income.jpeg" alt="image" />
													<div class="position-absolute translate-middle bottom-0 start-100 mb-6 bg-success rounded-circle border border-4 border-body h-20px w-20px"></div>
												</div>
											</div>
											<!--end::Pic-->
											<!--begin::Info-->
											<div class="flex-grow-1">
												<!--begin::Title-->
												<div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
													<!--begin::User-->
													<div class="d-flex flex-column">
														<!--begin::Name-->
														<div class="d-flex align-items-center mb-2">
															<a class="text-gray-900 text-hover-primary fs-2 fw-bold me-1">Incomes</a>
														</div>
													</div>
													<!--end::User-->
													<!--begin::Actions-->
													<div class="d-flex my-4">
														<!--begin::Menu-->
														<div class="me-0">
															<button class="btn btn-sm btn-icon btn-bg-light btn-active-color-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
																<i class="ki-solid ki-dots-horizontal fs-2x"></i>
															</button>
															<!--begin::Menu 3-->
															<div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold w-200px py-3" data-kt-menu="true">
																<!--begin::Heading-->
																<div class="menu-item px-3">
																	<div class="menu-content text-muted pb-2 px-3 fs-7 text-uppercase">Payments</div>
																</div>
																<!--end::Heading-->
																<!--begin::Menu item-->
																<div class="menu-item px-3">
																	<a href="#" class="menu-link px-3">Create Invoice</a>
																</div>
																<!--end::Menu item-->
																<!--begin::Menu item-->
																<div class="menu-item px-3">
																	<a href="#" class="menu-link flex-stack px-3">Create Payment 
																	<span class="ms-2" data-bs-toggle="tooltip" title="Specify a target name for future usage and reference">
																		<i class="ki-duotone ki-information fs-6">
																			<span class="path1"></span>
																			<span class="path2"></span>
																			<span class="path3"></span>
																		</i>
																	</span></a>
																</div>
																<!--end::Menu item-->
																<!--begin::Menu item-->
																<div class="menu-item px-3">
																	<a href="#" class="menu-link px-3">Generate Bill</a>
																</div>
																<!--end::Menu item-->
																<!--begin::Menu item-->
																<div class="menu-item px-3" data-kt-menu-trigger="hover" data-kt-menu-placement="right-end">
																	<a href="#" class="menu-link px-3">
																		<span class="menu-title">Subscription</span>
																		<span class="menu-arrow"></span>
																	</a>
																	<!--begin::Menu sub-->
																	<div class="menu-sub menu-sub-dropdown w-175px py-4">
																		<!--begin::Menu item-->
																		<div class="menu-item px-3">
																			<a href="#" class="menu-link px-3">Plans</a>
																		</div>
																		<!--end::Menu item-->
																		<!--begin::Menu item-->
																		<div class="menu-item px-3">
																			<a href="#" class="menu-link px-3">Billing</a>
																		</div>
																		<!--end::Menu item-->
																		<!--begin::Menu item-->
																		<div class="menu-item px-3">
																			<a href="#" class="menu-link px-3">Statements</a>
																		</div>
																		<!--end::Menu item-->
																		<!--begin::Menu separator-->
																		<div class="separator my-2"></div>
																		<!--end::Menu separator-->
																		<!--begin::Menu item-->
																		<div class="menu-item px-3">
																			<div class="menu-content px-3">
																				<!--begin::Switch-->
																				<label class="form-check form-switch form-check-custom form-check-solid">
																					<!--begin::Input-->
																					<input class="form-check-input w-30px h-20px" type="checkbox" value="1" checked="checked" name="notifications" />
																					<!--end::Input-->
																					<!--end::Label-->
																					<span class="form-check-label text-muted fs-6">Recuring</span>
																					<!--end::Label-->
																				</label>
																				<!--end::Switch-->
																			</div>
																		</div>
																		<!--end::Menu item-->
																	</div>
																	<!--end::Menu sub-->
																</div>
																<!--end::Menu item-->
																<!--begin::Menu item-->
																<div class="menu-item px-3 my-1">
																	<a href="#" class="menu-link px-3">Settings</a>
																</div>
																<!--end::Menu item-->
															</div>
															<!--end::Menu 3-->
														</div>
														<!--end::Menu-->
													</div>
													<!--end::Actions-->
												</div>
												<!--end::Title-->

												<?php
												$totalInvoices = getTotalInvoices($pdo);
												$paidInvoices = getPaidInvoices($pdo); // Assuming you have a function to get the number of paid invoices
												$revenueFromInvoices = getRevenueFromInvoices($pdo); // Assuming you have a function to get the total revenue
												$paidTaxFromInvoices = getPaidTaxFromInvoices($pdo); // Assuming you have a function to get the total tax

												// Determine the class for the paid invoices icon
												$paidInvoiceIconClass = $paidInvoices < $totalInvoices
													? 'ki-duotone ki-arrow-down fs-3 text-danger me-2'
													: 'ki-duotone ki-arrow-up fs-3 text-success me-2';
												?>
												<!--begin::Stats-->
												<div class="d-flex flex-wrap">
													<!--begin::Stat-->
													<div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
														<!--begin::Number-->
														<div class="d-flex align-items-center">
															<i class="ki-duotone ki-arrow-up fs-3 text-success me-2">
																<span class="path1"></span>
																<span class="path2"></span>
															</i>
															<div class="fs-2 fw-bold" data-kt-countup="true" data-kt-countup-value="<?php echo $totalInvoices; ?>" data-kt-countup-prefix="">0</div>
														</div>
														<!--end::Number-->
														<!--begin::Label-->
														<div class="fw-semibold fs-6 text-gray-500">Total Invoices</div>
														<!--end::Label-->
													</div>
													<!--end::Stat-->
													<!--begin::Stat-->
													<div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
														<!--begin::Number-->
														<div class="d-flex align-items-center">
															<i class="<?php echo $paidInvoiceIconClass; ?>">
																<span class="path1"></span>
																<span class="path2"></span>
															</i>
															<div class="fs-2 fw-bold" data-kt-countup="true" data-kt-countup-value="<?php echo $paidInvoices; ?>">0</div>
														</div>
														<!--end::Number-->
														<!--begin::Label-->
														<div class="fw-semibold fs-6 text-gray-500">Paid Invoices</div>
														<!--end::Label-->
													</div>
													<!--end::Stat-->
													<!--begin::Stat-->
													<div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
														<!--begin::Number-->
														<div class="d-flex align-items-center">
															<i class="ki-duotone ki-arrow-up fs-3 text-success me-2">
																<span class="path1"></span>
																<span class="path2"></span>
															</i>
															<div class="fs-2 fw-bold" data-kt-countup="true" data-kt-countup-value="<?php echo $revenueFromInvoices; ?>" data-kt-countup-prefix="₹">0</div>
														</div>
														<!--end::Number-->
														<!--begin::Label-->
														<div class="fw-semibold fs-6 text-gray-500">Revenue</div>
														<!--end::Label-->
													</div>
													<!--end::Stat-->
													<!--begin::Stat-->
													<div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
														<!--begin::Number-->
														<div class="d-flex align-items-center">
															<i class="ki-duotone ki-arrow-up fs-3 text-success me-2">
																<span class="path1"></span>
																<span class="path2"></span>
															</i>
															<div class="fs-2 fw-bold" data-kt-countup="true" data-kt-countup-value="<?php echo $paidTaxFromInvoices; ?>" data-kt-countup-prefix="₹">0</div>
														</div>
														<!--end::Number-->
														<!--begin::Label-->
														<div class="fw-semibold fs-6 text-gray-500">Paid Tax</div>
														<!--end::Label-->
													</div>
													<!--end::Stat-->
													<!--begin::Stat-->
													<div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
														<!--begin::Number-->
														<div class="d-flex align-items-center">
															<i class="ki-duotone ki-arrow-up fs-3 text-success me-2">
																<span class="path1"></span>
																<span class="path2"></span>
															</i>
															<div class="fs-2 fw-bold" data-kt-countup="true" data-kt-countup-value="<?php echo $unpaidTaxFromInvoices; ?>" data-kt-countup-prefix="₹">0</div>
														</div>
														<!--end::Number-->
														<!--begin::Label-->
														<div class="fw-semibold fs-6 text-gray-500">Unpaid Tax</div>
														<!--end::Label-->
													</div>
													<!--end::Stat-->
													<!--begin::Stat-->
													<div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
														<!--begin::Number-->
														<div class="d-flex align-items-center">
															<i class="ki-duotone ki-arrow-up fs-3 text-success me-2">
																<span class="path1"></span>
																<span class="path2"></span>
															</i>
															<div class="fs-2 fw-bold" data-kt-countup="true" data-kt-countup-value="<?php echo $accountReceivable; ?>" data-kt-countup-prefix="₹">0</div>
														</div>
														<!--end::Number-->
														<!--begin::Label-->
														<div class="fw-semibold fs-6 text-gray-500">Account Receivable</div>
														<!--end::Label-->
													</div>
													<!--end::Stat-->
												</div>
												<!--end::Stats-->
											</div>
											<!--end::Info-->
										</div>
										<!--end::Details-->
										<!--begin::Navs-->
										<ul class="nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold">
											<!--begin::Nav item-->
											<!--end::Nav item-->
											<!--begin::Nav item-->
											<li class="nav-item mt-2">
												<a class="nav-link text-active-primary ms-0 me-10 py-5 active" href="invoices.php">Invoices</a>
											</li>
											<!--end::Nav item-->
											<!--begin::Nav item-->
											<li class="nav-item mt-2">
												<a class="nav-link text-active-primary ms-0 me-10 py-5" href="quotes.php">Quotes</a>
											</li>
											<!--end::Nav item-->
											<!--begin::Nav item-->
											<li class="nav-item mt-2">
												<a class="nav-link text-active-primary ms-0 me-10 py-5" href="receipts.php">Receipts</a>
											</li>
										</ul>
										<!--begin::Navs-->
									</div>
								</div>
							</div>
							<!--begin::Content-->
							<div id="kt_app_content" class="app-content flex-column-fluid">
								<!--begin::Content container-->
								<div id="kt_app_content_container" class="app-container container-xxl">
									<!--begin::Products-->
									<div class="card card-flush">
										<!--begin::Card header-->
										<div class="card-header align-items-center py-5 gap-2 gap-md-5">
											<!--begin::Card title-->
											<div class="card-title">
												<!--begin::Search-->
												<div class="d-flex align-items-center position-relative my-1">
													<i class="ki-duotone ki-magnifier fs-3 position-absolute ms-4">
														<span class="path1"></span>
														<span class="path2"></span>
													</i>
													<input type="text" data-kt-ecommerce-order-filter="search" class="form-control form-control-solid w-250px ps-12" placeholder="Search Report" />
												</div>
												<!--end::Search-->
												<!--begin::Export buttons-->
												<div id="kt_ecommerce_report_sales_export" class="d-none"></div>
												<!--end::Export buttons-->
											</div>
											<!--end::Card title-->
											<!--begin::Card toolbar-->
											<div class="card-toolbar flex-row-fluid justify-content-end gap-5">
												<!--begin::Daterangepicker-->
												<!--<input class="form-control form-control-solid w-100 mw-250px" placeholder="Pick date range" id="kt_ecommerce_report_sales_daterangepicker" />-->
												<!--end::Daterangepicker-->
												<!--begin::Export dropdown-->
												<button type="button" class="btn btn-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
												<i class="ki-duotone ki-exit-up fs-2">
													<span class="path1"></span>
													<span class="path2"></span>
												</i>Export Report</button>
												<!--begin::Menu-->
												<div id="kt_ecommerce_report_sales_export_menu" class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-200px py-4" data-kt-menu="true">
													<!--begin::Menu item-->
													<div class="menu-item px-3">
														<a href="#" class="menu-link px-3" data-kt-ecommerce-export="copy">Copy to clipboard</a>
													</div>
													<!--end::Menu item-->
													<!--begin::Menu item-->
													<div class="menu-item px-3">
														<a href="#" class="menu-link px-3" data-kt-ecommerce-export="excel">Export as Excel</a>
													</div>
													<!--end::Menu item-->
													<!--begin::Menu item-->
													<div class="menu-item px-3">
														<a href="#" class="menu-link px-3" data-kt-ecommerce-export="csv">Export as CSV</a>
													</div>
													<!--end::Menu item-->
													<!--begin::Menu item-->
													<div class="menu-item px-3">
														<a href="#" class="menu-link px-3" data-kt-ecommerce-export="pdf">Export as PDF</a>
													</div>
													<!--end::Menu item-->
												</div>
												<!--end::Menu-->
												<!--end::Export dropdown-->
											</div>
											<!--end::Card toolbar-->
										</div>
										<!--end::Card header-->
										<!--begin::Card body-->
										<div class="card-body pt-0">
                                            <!--begin::Table-->
                                            <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_ecommerce_report_sales_table">
                                                <thead>
                                                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                                        <th class="min-w-125px dt-orderable-asc dt-orderable-desc">Date</th>
                                                        <th class="min-w-125px dt-orderable-asc dt-orderable-desc">Client Name</th>
                                                        <th class="min-w-125px dt-orderable-asc dt-orderable-desc">Project Name</th>
                                                        <th class="min-w-125px dt-orderable-asc dt-orderable-desc">Payment Description</th>
                                                        <th class="min-w-125px dt-orderable-asc dt-orderable-desc">Amount</th>
                                                        <th class="min-w-125px dt-orderable-asc dt-orderable-desc">Source</th>
                                                        <th class="min-w-125px dt-orderable-asc dt-orderable-desc">Notes</th>
                                                        <th class="min-w-125px dt-orderable-asc dt-orderable-desc">Invoice ID</th>
                                                        <th class="min-w-125px dt-orderable-asc dt-orderable-desc">Payment Status</th>
                                                        <th class="min-w-125px dt-orderable-asc dt-orderable-desc">Credit Date</th>
                                                        <th class="min-w-200px text-end rounded-end">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="fw-semibold text-gray-600">
                                                    <?php fetchData($pdo); ?>
                                                </tbody>
                                            </table>
                                            <!--end::Table-->
                                        </div>
										<!--end::Card body-->
									</div>
									<!--end::Products-->
								</div>
								<!--end::Content container-->
							</div>
							<!--end::Content-->
						</div>
						<!--end::Content wrapper-->
						<!--begin::Footer-->
						<div id="kt_app_footer" class="app-footer">
							<!--begin::Footer container-->
							<div class="app-container container-fluid d-flex flex-column flex-md-row flex-center flex-md-stack py-3">
								<!--begin::Copyright-->
								<div class="text-gray-900 order-2 order-md-1">
									<span class="text-muted fw-semibold me-1">2024&copy;</span>
									<a href="https://keenthemes.com" target="_blank" class="text-gray-800 text-hover-primary">Keenthemes</a>
								</div>
								<!--end::Copyright-->
								<!--begin::Menu-->
								<ul class="menu menu-gray-600 menu-hover-primary fw-semibold order-1">
									<li class="menu-item">
										<a href="https://keenthemes.com" target="_blank" class="menu-link px-2">About</a>
									</li>
									<li class="menu-item">
										<a href="https://devs.keenthemes.com" target="_blank" class="menu-link px-2">Support</a>
									</li>
									<li class="menu-item">
										<a href="https://1.envato.market/EA4JP" target="_blank" class="menu-link px-2">Purchase</a>
									</li>
								</ul>
								<!--end::Menu-->
							</div>
							<!--end::Footer container-->
						</div>
						<!--end::Footer-->
					</div>
					<!--end:::Main-->
				</div>
				<!--end::Wrapper-->
			</div>
			<!--end::Page-->
		</div>
		<div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true">
			<i class="ki-duotone ki-arrow-up">
				<span class="path1"></span>
				<span class="path2"></span>
			</i>
		</div>
		<div class="modal fade" id="kt_modal_new_target" tabindex="-1" aria-hidden="true">
			<!--begin::Modal dialog-->
			<div class="modal-dialog modal-dialog-centered mw-650px">
				<!--begin::Modal content-->
				<div class="modal-content rounded">
					<!--begin::Modal header-->
					<div class="modal-header pb-0 border-0 justify-content-end">
						<!--begin::Close-->
						<div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
							<i class="ki-duotone ki-cross fs-1">
								<span class="path1"></span>
								<span class="path2"></span>
							</i>
						</div>
						<!--end::Close-->
					</div>
					<!--begin::Modal header-->
					<!--begin::Modal body-->
					<div class="modal-body scroll-y px-10 px-lg-15 pt-0 pb-15">
                    <form id="client_details_form" class="form" method="post" enctype="multipart/form-data">
                        <div class="mb-13 text-center">
                            <h1 class="mb-3">Income Details</h1>
                        </div>
                        <div class="row g-9 mb-8">
                            <!-- Client Name -->
                            <div class="col-md-6 fv-row">
								<label class="required fs-6 fw-semibold mb-2">Client Name</label>
								<select class="form-select form-select-solid" data-control="select2" data-hide-search="true" data-placeholder="Select..." name="ClientName" id="client_select">
									<option></option>
									<?php foreach ($clientNames as $client): ?>
										<option value="<?php echo htmlspecialchars($client['Name']); ?>"><?php echo htmlspecialchars($client['Name']); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<!-- Project Name -->
							<div class="col-md-6 fv-row">
								<label class="required fs-6 fw-semibold mb-2">Project Name</label>
								<select class="form-select form-select-solid" data-control="select2" data-hide-search="true" data-placeholder="Select..." name="ProjectName" id="project_select">
									<option></option>
								</select>
								<div class="loading" id="loading"></div>
							</div>
                        </div>
                        <div class="row g-9 mb-8">
                            <!-- Payment Description -->
                            <div class="col-md-6 fv-row">
                                <label class="required fs-6 fw-semibold mb-2">Payment Description</label>
                                <input type="text" class="form-control form-control-solid" placeholder="Enter Payment Description" name="PaymentDescription" required />
                            </div>
                            <!-- Amount -->
                            <div class="col-md-6 fv-row">
                                <label class="required fs-6 fw-semibold mb-2">Amount</label>
                                <input type="number" step="0.01" class="form-control form-control-solid" placeholder="Enter Amount" name="Amount" required />
                            </div>
                        </div>
                        <div class="row g-9 mb-8">
                            <!-- Source -->
                            <div class="col-md-6 fv-row">
                                <label class="required fs-6 fw-semibold mb-2">Source</label>
                                <input type="text" class="form-control form-control-solid" placeholder="Enter Source" name="Source" required />
                            </div>
                            <!-- Notes -->
                            <div class="col-md-6 fv-row">
                                <label class="required fs-6 fw-semibold mb-2">Notes</label>
                                <input type="text" class="form-control form-control-solid" placeholder="Enter Notes" name="Notes" required />
                            </div>
                        </div>
                        <div class="row g-9 mb-8">
                            <!-- Invoice ID -->
                            <div class="col-md-6 fv-row">
                                <label class="required fs-6 fw-semibold mb-2">Invoice ID</label>
                                <input type="text" class="form-control form-control-solid" placeholder="Enter Invoice ID" name="InvoiceID" required />
                            </div>
                            <!-- Payment Status -->
                            <div class="col-md-6 fv-row">
                                <label class="required fs-6 fw-semibold mb-2">Payment Status</label>
                                <input type="text" class="form-control form-control-solid" placeholder="Enter Payment Status" name="PaymentStatus" required />
                            </div>
                        </div>
                        <div class="row g-9 mb-8">
                            <!-- Credit Date -->
                            <div class="col-md-6 fv-row">
                                <label class="required fs-6 fw-semibold mb-2">Credit Date</label>
                                <input type="date" class="form-control form-control-solid" placeholder="Enter Credit Date" name="CreditDate" required />
                            </div>
                        </div>
                        <div class="text-center">
                            <button type="button" id="client_details_form_submit" class="btn btn-primary">
                                <span class="indicator-label">Submit</span>
                                <span class="indicator-progress">Please wait... 
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            </button>
                        </div>
                        <div id="response_message" class="text-center mt-3"></div>
                    </form>
						<!--end:Form-->
					</div>
					<!--end::Modal body-->
				</div>
				<!--end::Modal content-->
			</div>
			<!--end::Modal dialog-->
		</div>
		<!--end::Modal - Create App-->
		<!--begin::Modal - Users Search-->
		
		<!--end::Modal - Users Search-->
		<!--begin::Modal - Invite Friends-->
		
		<!--end::Modal - Invite Friend-->
		<!--end::Modals-->
		<!--begin::Javascript-->
		<script>var hostUrl = "assets/";</script>
		<!--begin::Global Javascript Bundle(mandatory for all pages)-->
		<script src="assets/plugins/global/plugins.bundle.js"></script>
		<script src="assets/js/scripts.bundle.js"></script>
		<!--end::Global Javascript Bundle-->
		<!--begin::Vendors Javascript(used for this page only)-->
		<script src="assets/plugins/custom/datatables/datatables.bundle.js"></script>
		<!--end::Vendors Javascript-->
		<!--begin::Custom Javascript(used for this page only)-->
		<script src="assets/js/custom/apps/ecommerce/reports/sales/sales.js"></script>
		<script src="assets/js/widgets.bundle.js"></script>
		<script src="assets/js/custom/widgets.js"></script>
		<script src="assets/js/custom/apps/chat/chat.js"></script>
		<script src="assets/js/custom/utilities/modals/upgrade-plan.js"></script>
		<script src="assets/js/custom/utilities/modals/create-app.js"></script>
		<script src="assets/js/custom/utilities/modals/users-search.js"></script>
        <script src="assets/js/custom/utilities/modals/create-project/files.js"></script>
		<!--end::Custom Javascript-->
        <script>
            $(document).ready(function() {
                $('#client_details_form_submit').click(function() {
                    var formData = new FormData($('#client_details_form')[0]);
                    formData.append('form_type', 'clientDetails');
                    
                    for (var pair of formData.entries()) {
                            console.log(pair[0]+ ': ' + (pair[1].name || pair[1])); // Logging both key and value; handles file inputs as well
                        }
                    $.ajax({
                        url: 'clientsubmit.php',  // Your PHP file for form submission
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            $('#response_message').html('<p class="text-success">' + response + '</p>');
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            $('#response_message').html('<p class="text-danger">Error: ' + errorThrown + '</p>');
                        }
                    });
                });
            });
        </script>
		<script>
			$(document).ready(function() {
				$('#client_select').on('change', function() {
					var clientName = $(this).val();
					var projectSelect = $('#project_select');
					const loadingEl = document.createElement("div");
					document.body.prepend(loadingEl);
					loadingEl.classList.add("page-loader");
					loadingEl.classList.add("flex-column");
					loadingEl.classList.add("bg-dark");
					loadingEl.classList.add("bg-opacity-25");
					loadingEl.innerHTML = `
						<span class="spinner-border text-primary" role="status"></span>
						<span class="text-gray-800 fs-6 fw-semibold mt-5">Loading...</span>
					`;

					if (clientName) {
						KTApp.showPageLoading();// Show loading indicator

						$.ajax({
							url: '', // This will send the request to the same file
							type: 'GET',
							data: { clientName: clientName },
							dataType: 'json',
							success: function(data) {
								projectSelect.empty(); // Clear previous options
								projectSelect.append('<option></option>'); // Add default empty option

								$.each(data, function(index, project) {
									projectSelect.append('<option value="' + project.Name + '">' + project.Name + '</option>');
								});
							},
							complete: function() {
								KTApp.hidePageLoading();
								loadingEl.remove();
							}
						});
					} else {
						projectSelect.empty().append('<option></option>');
					}
				});
			});
		</script>
		<!--end::Javascript-->
	</body>
	<!--end::Body-->
</html>
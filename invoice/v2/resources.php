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
		$sql = "SELECT * FROM resourceDetails";  
		$stmt = $pdo->query($sql);  
		$result = $stmt->fetchAll();  
	  
		if ($result) {  
			foreach ($result as $row) {  
				$formattedDate = date('Y-m-d', strtotime($row['Updated_On']));  
				echo "<tr>  
						<td>{$formattedDate}</td>  
						<td>{$row['Name']}</td>  
						<td>{$row['Contact_Number']}</td>  
						<td>{$row['Address']}</td>  
						<td>{$row['City']}</td>  
						<td>{$row['GST_Number']}</td>  
						<td class='text-end'>  
							<button class='btn btn-bg-light btn-color-muted btn-active-color-primary btn-sm px-4 me-1 delete-btn'   
								data-id='{$row['ID']}'  
								data-type='resourceDetails'>Delete</button>  
							<button class='btn btn-bg-light btn-color-muted btn-active-color-primary btn-sm px-4 me-1 edit-btn'  
								data-id='{$row['ID']}'  
								data-name='" . htmlspecialchars($row['Name'], ENT_QUOTES) . "'  
								data-contact='" . htmlspecialchars($row['Contact_Number'], ENT_QUOTES) . "'  
								data-address='" . htmlspecialchars($row['Address'], ENT_QUOTES) . "'  
								data-city='" . htmlspecialchars($row['City'], ENT_QUOTES) . "'  
								data-state='" . htmlspecialchars($row['State'], ENT_QUOTES) . "'  
								data-pincode='" . htmlspecialchars($row['Pincode'], ENT_QUOTES) . "'  
								data-gst='" . htmlspecialchars($row['GST_Number'], ENT_QUOTES) . "'  
								data-description='" . htmlspecialchars($row['Document_Description'], ENT_QUOTES) . "'  
							>Edit</button>  
						</td>  
					  </tr>";  
			}  
		} else {  
			echo "<tr><td colspan='8'>No Data Found</td></tr>";  
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

	// Corrected delete request logic    
	if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id']) && isset($_POST['action']) && $_POST['action'] == 'delete') {  
		$id = $_POST['id'];  
		$table = $_POST['type'];  
	
		// Security check: ensure the table name is valid  
		if (in_array($table, ['resourceDetails'])) {  
	
			$sql = "DELETE FROM $table WHERE ID = ?";  
			$stmt = $pdo->prepare($sql);  
			if ($stmt->execute([$id])) {  
				echo "Record deleted successfully.";  
			} else {  
				echo "Error deleting record.";  
			}  
		} else {  
			echo "Invalid table.";  
		}  
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
		<title>S.T.R.In.G - Resource Details Dashboard</title>
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
				<div id="kt_app_header" class="app-header" data-kt-sticky="true" data-kt-sticky-activate="{default: true, lg: true}" data-kt-sticky-name="app-header-minimize" data-kt-sticky-offset="{default: '200px', lg: '0'}" data-kt-sticky-animation="false">
					<!--begin::Header container-->
					<div class="app-container container-fluid d-flex align-items-stretch justify-content-between" id="kt_app_header_container">
						<!--begin::Sidebar mobile toggle-->
						<div class="d-flex align-items-center d-lg-none ms-n3 me-1 me-md-2" title="Show sidebar menu">
							<div class="btn btn-icon btn-active-color-primary w-35px h-35px" id="kt_app_sidebar_mobile_toggle">
								<i class="ki-duotone ki-abstract-14 fs-2 fs-md-1">
									<span class="path1"></span>
									<span class="path2"></span>
								</i>
							</div>
						</div>
						<!--end::Sidebar mobile toggle-->
						<!--begin::Mobile logo-->
						<div class="d-flex align-items-center flex-grow-1 flex-lg-grow-0">
							<a href="index.html" class="d-lg-none">
								<img alt="Logo" src="assets/media/logos/default-small.svg" class="h-30px" />
							</a>
						</div>
						<!--end::Mobile logo-->
						<!--begin::Header wrapper-->
						<div class="d-flex align-items-stretch justify-content-between flex-lg-grow-1" id="kt_app_header_wrapper">
							<!--begin::Menu wrapper-->
							<div class="app-header-menu app-header-mobile-drawer align-items-stretch" data-kt-drawer="true" data-kt-drawer-name="app-header-menu" data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="250px" data-kt-drawer-direction="end" data-kt-drawer-toggle="#kt_app_header_menu_toggle" data-kt-swapper="true" data-kt-swapper-mode="{default: 'append', lg: 'prepend'}" data-kt-swapper-parent="{default: '#kt_app_body', lg: '#kt_app_header_wrapper'}">
								<!--begin::Menu-->
								<div class="menu menu-rounded menu-column menu-lg-row my-5 my-lg-0 align-items-stretch fw-semibold px-2 px-lg-0" id="kt_app_header_menu" data-kt-menu="true">
									
								</div>
								<!--end::Menu-->
							</div>
							<!--end::Menu wrapper-->
							<!--begin::Navbar-->
							<div class="app-navbar flex-shrink-0">
								<!--begin::Search-->
								
								<!--end::My apps links-->
								<!--begin::Theme mode-->
								<div class="app-navbar-item ms-1 ms-md-4">
									<!--begin::Menu toggle-->
									<a href="#" class="btn btn-icon btn-custom btn-icon-muted btn-active-light btn-active-color-primary w-35px h-35px" data-kt-menu-trigger="{default:'click', lg: 'hover'}" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">
										<i class="ki-duotone ki-night-day theme-light-show fs-1">
											<span class="path1"></span>
											<span class="path2"></span>
											<span class="path3"></span>
											<span class="path4"></span>
											<span class="path5"></span>
											<span class="path6"></span>
											<span class="path7"></span>
											<span class="path8"></span>
											<span class="path9"></span>
											<span class="path10"></span>
										</i>
										<i class="ki-duotone ki-moon theme-dark-show fs-1">
											<span class="path1"></span>
											<span class="path2"></span>
										</i>
									</a>
									<!--begin::Menu toggle-->
									<!--begin::Menu-->
									<div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-title-gray-700 menu-icon-gray-500 menu-active-bg menu-state-color fw-semibold py-4 fs-base w-150px" data-kt-menu="true" data-kt-element="theme-mode-menu">
										<!--begin::Menu item-->
										<div class="menu-item px-3 my-0">
											<a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="light">
												<span class="menu-icon" data-kt-element="icon">
													<i class="ki-duotone ki-night-day fs-2">
														<span class="path1"></span>
														<span class="path2"></span>
														<span class="path3"></span>
														<span class="path4"></span>
														<span class="path5"></span>
														<span class="path6"></span>
														<span class="path7"></span>
														<span class="path8"></span>
														<span class="path9"></span>
														<span class="path10"></span>
													</i>
												</span>
												<span class="menu-title">Light</span>
											</a>
										</div>
										<!--end::Menu item-->
										<!--begin::Menu item-->
										<div class="menu-item px-3 my-0">
											<a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="dark">
												<span class="menu-icon" data-kt-element="icon">
													<i class="ki-duotone ki-moon fs-2">
														<span class="path1"></span>
														<span class="path2"></span>
													</i>
												</span>
												<span class="menu-title">Dark</span>
											</a>
										</div>
										<!--end::Menu item-->
										<!--begin::Menu item-->
										<div class="menu-item px-3 my-0">
											<a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="system">
												<span class="menu-icon" data-kt-element="icon">
													<i class="ki-duotone ki-screen fs-2">
														<span class="path1"></span>
														<span class="path2"></span>
														<span class="path3"></span>
														<span class="path4"></span>
													</i>
												</span>
												<span class="menu-title">System</span>
											</a>
										</div>
										<!--end::Menu item-->
									</div>
									<!--end::Menu-->
								</div>
								<!--end::Theme mode-->
								<!--begin::User menu-->
								<div class="app-navbar-item ms-1 ms-md-4" id="kt_header_user_menu_toggle">
									<!--begin::Menu wrapper-->
									<div class="cursor-pointer symbol symbol-35px" data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">
										<img src="assets/media/avatars/blank.png" class="rounded-3" alt="user" />
									</div>
									<!--begin::User account menu-->
									<div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-275px" data-kt-menu="true">
										<!--begin::Menu item-->
										<div class="menu-item px-3">
											<div class="menu-content d-flex align-items-center px-3">
												<!--begin::Avatar-->
												<div class="symbol symbol-50px me-5">
													<img alt="Logo" src="assets/media/avatars/blank.png" />
												</div>
												<!--end::Avatar-->
												<!--begin::Username-->
												<div class="d-flex flex-column">
													<div class="fw-bold d-flex align-items-center fs-5">S.T.R.In.G Lab 
													<span class="badge badge-light-success fw-bold fs-8 px-2 py-1 ms-2">Admin</span></div>
													<a href="#" class="fw-semibold text-muted text-hover-primary fs-7">admin@stringlab.org</a>
												</div>
												<!--end::Username-->
											</div>
										</div>
										<!--end::Menu item-->
										<!--begin::Menu separator-->
										<div class="separator my-2"></div>
										<div class="menu-item px-5">
											<a href="dashboard.php" class="menu-link px-5">Dashboard</a>
										</div>
										<!--end::Menu item-->
										<!--begin::Menu separator-->
										<div class="separator my-2"></div>
										<!--end::Menu separator-->
										<!--begin::Menu item-->
										<div class="menu-item px-5" data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="left-start" data-kt-menu-offset="-15px, 0">
											<a href="#" class="menu-link px-5">
												<span class="menu-title position-relative">Mode 
												<span class="ms-5 position-absolute translate-middle-y top-50 end-0">
													<i class="ki-duotone ki-night-day theme-light-show fs-2">
														<span class="path1"></span>
														<span class="path2"></span>
														<span class="path3"></span>
														<span class="path4"></span>
														<span class="path5"></span>
														<span class="path6"></span>
														<span class="path7"></span>
														<span class="path8"></span>
														<span class="path9"></span>
														<span class="path10"></span>
													</i>
													<i class="ki-duotone ki-moon theme-dark-show fs-2">
														<span class="path1"></span>
														<span class="path2"></span>
													</i>
												</span></span>
											</a>
											<!--begin::Menu-->
											<div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-title-gray-700 menu-icon-gray-500 menu-active-bg menu-state-color fw-semibold py-4 fs-base w-150px" data-kt-menu="true" data-kt-element="theme-mode-menu">
												<!--begin::Menu item-->
												<div class="menu-item px-3 my-0">
													<a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="light">
														<span class="menu-icon" data-kt-element="icon">
															<i class="ki-duotone ki-night-day fs-2">
																<span class="path1"></span>
																<span class="path2"></span>
																<span class="path3"></span>
																<span class="path4"></span>
																<span class="path5"></span>
																<span class="path6"></span>
																<span class="path7"></span>
																<span class="path8"></span>
																<span class="path9"></span>
																<span class="path10"></span>
															</i>
														</span>
														<span class="menu-title">Light</span>
													</a>
												</div>
												<!--end::Menu item-->
												<!--begin::Menu item-->
												<div class="menu-item px-3 my-0">
													<a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="dark">
														<span class="menu-icon" data-kt-element="icon">
															<i class="ki-duotone ki-moon fs-2">
																<span class="path1"></span>
																<span class="path2"></span>
															</i>
														</span>
														<span class="menu-title">Dark</span>
													</a>
												</div>
												<!--end::Menu item-->
												<!--begin::Menu item-->
												<div class="menu-item px-3 my-0">
													<a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="system">
														<span class="menu-icon" data-kt-element="icon">
															<i class="ki-duotone ki-screen fs-2">
																<span class="path1"></span>
																<span class="path2"></span>
																<span class="path3"></span>
																<span class="path4"></span>
															</i>
														</span>
														<span class="menu-title">System</span>
													</a>
												</div>
												<!--end::Menu item-->
											</div>
											<!--end::Menu-->
										</div>
										<div class="menu-item px-5">
											<a href="authentication/layouts/corporate/sign-in.html" class="menu-link px-5">Sign Out</a>
										</div>
										<!--end::Menu item-->
									</div>
									<!--end::User account menu-->
									<!--end::Menu wrapper-->
								</div>
								<!--end::User menu-->
								<!--begin::Header menu toggle-->
								<div class="app-navbar-item d-lg-none ms-2 me-n2" title="Show header menu">
									<div class="btn btn-flex btn-icon btn-active-color-primary w-30px h-30px" id="kt_app_header_menu_toggle">
										<i class="ki-duotone ki-element-4 fs-1">
											<span class="path1"></span>
											<span class="path2"></span>
										</i>
									</div>
								</div>
								<!--end::Header menu toggle-->
								<!--begin::Aside toggle-->
								<!--end::Header menu toggle-->
							</div>
							<!--end::Navbar-->
						</div>
						<!--end::Header wrapper-->
					</div>
					<!--end::Header container-->
				</div>
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
										<h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">Resource Details Dashboard</h1>
									</div>
									<!--end::Page title-->
									<!--begin::Actions-->
									<div class="d-flex align-items-center gap-2 gap-lg-3">
										<!--begin::Primary button-->
										<a href="#" class="btn btn-sm fw-bold btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_new_target">Add Resource</a>
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
													<img src="assets/media/avatars/resourcesDashboard.jpeg" alt="image" />
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
															<a class="text-gray-900 text-hover-primary fs-2 fw-bold me-1">Vendors</a>
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
                                                    <th class="min-w-125px dt-orderable-asc dt-orderable-desc">Name</th>
                                                    <th class="min-w-125px dt-orderable-asc dt-orderable-desc">Contact Number</th>
                                                    <th class="min-w-125px dt-orderable-asc dt-orderable-desc">Address</th>
                                                    <th class="min-w-125px dt-orderable-asc dt-orderable-desc">City</th>
                                                    <th class="min-w-125px dt-orderable-asc dt-orderable-desc">GST Number</th>
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
		<!--end::App-->
		<!--begin::Modal-->  
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
						<!--begin:Form-->  
						<form id="resource_details_form" class="form" method="post" enctype="multipart/form-data">  
							<div class="mb-13 text-center">  
								<h1 class="mb-3 modal-title">Resource On-Boarding</h1>  
								<div class="text-muted fw-semibold fs-5">If you need more info, please check   
									<a href="#" class="fw-bold link-primary">On-Boarding Guidelines</a>.  
								</div>  
							</div>  
							<input type="hidden" name="id" id="resource_id" value="">  
							<div class="d-flex flex-column mb-8 fv-row">  
								<label class="d-flex align-items-center fs-6 fw-semibold mb-2">  
									<span class="required">Resource Name</span>  
								</label>  
								<input type="text" class="form-control form-control-solid" placeholder="Enter Resource Name" name="target_title" required />  
							</div>  
							<div class="row g-9 mb-8">  
								<div class="col-md-6 fv-row">  
									<label class="required fs-6 fw-semibold mb-2">Contact Number</label>  
									<input type="tel" class="form-control form-control-solid" placeholder="Enter Contact Number" name="contact_number" required />  
								</div>  
								<div class="col-md-6 fv-row">  
									<label class="fs-6 fw-semibold mb-2">GST Number</label>  
									<input type="text" class="form-control form-control-solid" placeholder="Enter GST Number" name="gst_number" />  
								</div>  
							</div>  
							<div class="d-flex flex-column mb-8">  
								<label class="fs-6 fw-semibold mb-2">Address</label>  
								<textarea class="form-control form-control-solid" rows="3" name="target_details" placeholder="Write Full Address"></textarea>  
							</div>  
							<div class="row g-9 mb-8">  
								<div class="col-md-6 fv-row">  
									<label class="required fs-6 fw-semibold mb-2">City</label>  
									<input type="text" class="form-control form-control-solid" placeholder="Enter City" name="city" required />  
								</div>  
								<div class="col-md-6 fv-row">  
									<label class="required fs-6 fw-semibold mb-2">State</label>  
									<input type="text" class="form-control form-control-solid" placeholder="Enter State" name="state" required />  
								</div>  
								<div class="col-md-6 fv-row">  
									<label class="required fs-6 fw-semibold mb-2">Pin Code</label>  
									<input type="text" class="form-control form-control-solid" placeholder="Enter Pin Code" name="pincode" required />  
								</div>  
								<div class="col-md-6 fv-row">  
									<label class="fs-6 fw-semibold mb-2">Attachment Description</label>  
									<input type="text" class="form-control form-control-solid" placeholder="Enter Description for Attachment" name="document_description" />  
								</div>  
							</div>  
							<div class="fv-row mb-8">  
								<div class="dropzone" id="kt_modal_create_project_files_upload">  
									<div class="dz-message needsclick">  
										<i class="ki-duotone ki-file-up fs-3hx text-primary"></i>  
										<div class="ms-4">  
											<h3 class="dfs-3 fw-bold text-gray-900 mb-1">Drop files here or click to upload.</h3>  
											<span class="fw-semibold fs-4 text-muted">Upload up to 10 files</span>  
										</div>  
									</div>  
									<input type="file" name="document[]" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" />  
								</div>  
							</div>  
							<div class="text-center">  
								<button type="button" id="resource_details_form_submit" class="btn btn-primary">  
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
		<!--end::Modal-->  
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
                $('#resource_details_form_submit').click(function() {
                    var formData = new FormData($('#resource_details_form')[0]);
                    formData.append('form_type', 'resourceDetails');

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
			// Handle Delete Button Click  
			$('.delete-btn').click(function() {  
				var id = $(this).data('id');  
				var type = $(this).data('type');  
		
				if (confirm('Are you sure you want to delete this record?')) {  
					$.ajax({  
						url: '', // Current page  
						type: 'POST',  
						data: {  
							id: id,  
							type: type,  
							action: 'delete'  
						},  
						success: function(response) {  
							alert(response);  
							location.reload();  
						},  
						error: function(xhr, status, error) {  
							alert('Error deleting record.');  
						}  
					});  
				}  
			});  
		
			// Handle Edit Button Click  
			$('.edit-btn').click(function() {  
				// Get data from the clicked button  
				var id = $(this).data('id');  
				var name = $(this).data('name');  
				var contact = $(this).data('contact');  
				var address = $(this).data('address');  
				var city = $(this).data('city');  
				var state = $(this).data('state');  
				var pincode = $(this).data('pincode');  
				var gst = $(this).data('gst');  
				var description = $(this).data('description');  
		
				// Set the form fields with the data  
				$('input[name="target_title"]').val(name);  
				$('input[name="contact_number"]').val(contact);  
				$('textarea[name="target_details"]').val(address);  
				$('input[name="city"]').val(city);  
				$('input[name="state"]').val(state);  
				$('input[name="pincode"]').val(pincode);  
				$('input[name="gst_number"]').val(gst);  
				$('input[name="document_description"]').val(description);  
				$('#resource_id').val(id); // Set the resource ID  
		
				// Change modal title for editing (optional)  
				$('.modal-title').text('Edit Resource Details');  
		
				// Open the modal  
				$('#kt_modal_new_target').modal('show');  
			});  
		
			// When 'Add Resource' is clicked, reset the form  
			$('a[data-bs-target="#kt_modal_new_target"]').click(function() {  
				$('#resource_details_form')[0].reset();  
				$('#resource_id').val(''); // Clear the resource ID  
				$('.modal-title').text('Resource On-Boarding'); // Reset the modal title  
			});  
		
			// Handle Form Submission  
			$('#resource_details_form_submit').click(function() {  
				var formData = new FormData($('#resource_details_form')[0]);  
		
				var id = $('#resource_id').val();  
				if (id) {  
					formData.append('id', id);  
				}  
		
				formData.append('form_type', 'resourceDetails');  
		
				$.ajax({  
					url: 'clientsubmit.php', // Your PHP file for form submission  
					type: 'POST',  
					data: formData,  
					processData: false,  
					contentType: false,  
					dataType: 'json', // Expect JSON response from server  
					success: function(response) {  
						if (response.error) {  
							$('#response_message').html('<p class="text-danger">' + response.error + '</p>');  
						} else if (response.success) {  
							$('#response_message').html('<p class="text-success">' + response.success + '</p>');  
							// Close the modal and reload the page  
							$('#kt_modal_new_target').modal('hide');  
							setTimeout(function() {  
								location.reload();  
							}, 500);  
						}  
					},  
					error: function(jqXHR, textStatus, errorThrown) {  
						$('#response_message').html('<p class="text-danger">Error: ' + errorThrown + '</p>');  
					}  
				});  
			});  
		});  
		</script>  
		<!--end::Javascript-->
	</body>
	<!--end::Body-->
</html>
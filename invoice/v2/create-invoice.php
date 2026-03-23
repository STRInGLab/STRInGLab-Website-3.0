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

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

$stmt = $pdo->query("SELECT MAX(id) as last_invoice FROM Invoice");
$result = $stmt->fetch();
$lastInvoiceNo = $result['last_invoice'];
$newInvoiceNo = $lastInvoiceNo + 1;
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
		<title>S.T.R.In.G - Create Invoice</title>
		<meta charset="utf-8" />
		<meta name="description" content="The most advanced Bootstrap 5 Admin Theme with 40 unique prebuilt layouts on Themeforest trusted by 100,000 beginners and professionals. Multi-demo, Dark Mode, RTL support and complete React, Angular, Vue, Asp.Net Core, Rails, Spring, Blazor, Django, Express.js, Node.js, Flask, Symfony & Laravel versions. Grab your copy now and get life-time updates for free." />
		<meta name="keywords" content="metronic, bootstrap, bootstrap 5, angular, VueJs, React, Asp.Net Core, Rails, Spring, Blazor, Django, Express.js, Node.js, Flask, Symfony & Laravel starter kits, admin themes, web design, figma, web development, free templates, free admin themes, bootstrap theme, bootstrap template, bootstrap dashboard, bootstrap dak mode, bootstrap button, bootstrap datepicker, bootstrap timepicker, fullcalendar, datatables, flaticon" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<meta property="og:locale" content="en_US" />
		<meta property="og:type" content="article" />
		<meta property="og:title" content="Metronic - The World's #1 Selling Bootstrap Admin Template by KeenThemes" />
		<meta property="og:url" content="https://keenthemes.com/metronic" />
		<meta property="og:site_name" content="Metronic by Keenthemes" />
		<link rel="canonical" href="http://preview.keenthemes.comapps/invoices/create.html" />
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
										<h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">Create</h1>
										<!--end::Title-->
										<!--begin::Breadcrumb-->
										<ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
											<!--begin::Item-->
											<li class="breadcrumb-item text-muted">
												<a href="index.html" class="text-muted text-hover-primary">Home</a>
											</li>
											<!--end::Item-->
											<!--begin::Item-->
											<li class="breadcrumb-item">
												<span class="bullet bg-gray-500 w-5px h-2px"></span>
											</li>
											<!--end::Item-->
											<!--begin::Item-->
											<li class="breadcrumb-item text-muted">Invoice Manager</li>
											<!--end::Item-->
										</ul>
										<!--end::Breadcrumb-->
									</div>
									<!--end::Page title-->
									<!--begin::Actions-->
									<div class="d-flex align-items-center gap-2 gap-lg-3">
										<!--begin::Filter menu-->
										<div class="m-0">
											<!--begin::Menu toggle-->
											<a href="#" class="btn btn-sm btn-flex btn-secondary fw-bold" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
											<i class="ki-duotone ki-filter fs-6 text-muted me-1">
												<span class="path1"></span>
												<span class="path2"></span>
											</i>Filter</a>
											<!--end::Menu toggle-->
											<!--begin::Menu 1-->
											<div class="menu menu-sub menu-sub-dropdown w-250px w-md-300px" data-kt-menu="true" id="kt_menu_6678170754bd2">
												<!--begin::Header-->
												<div class="px-7 py-5">
													<div class="fs-5 text-gray-900 fw-bold">Filter Options</div>
												</div>
												<!--end::Header-->
												<!--begin::Menu separator-->
												<div class="separator border-gray-200"></div>
												<!--end::Menu separator-->
												<!--begin::Form-->
												<div class="px-7 py-5">
													<!--begin::Input group-->
													<div class="mb-10">
														<!--begin::Label-->
														<label class="form-label fw-semibold">Status:</label>
														<!--end::Label-->
														<!--begin::Input-->
														<div>
															<select class="form-select form-select-solid" multiple="multiple" data-kt-select2="true" data-close-on-select="false" data-placeholder="Select option" data-dropdown-parent="#kt_menu_6678170754bd2" data-allow-clear="true">
																<option></option>
																<option value="1">Approved</option>
																<option value="2">Pending</option>
																<option value="2">In Process</option>
																<option value="2">Rejected</option>
															</select>
														</div>
														<!--end::Input-->
													</div>
													<!--end::Input group-->
													<!--begin::Input group-->
													<div class="mb-10">
														<!--begin::Label-->
														<label class="form-label fw-semibold">Member Type:</label>
														<!--end::Label-->
														<!--begin::Options-->
														<div class="d-flex">
															<!--begin::Options-->
															<label class="form-check form-check-sm form-check-custom form-check-solid me-5">
																<input class="form-check-input" type="checkbox" value="1" />
																<span class="form-check-label">Author</span>
															</label>
															<!--end::Options-->
															<!--begin::Options-->
															<label class="form-check form-check-sm form-check-custom form-check-solid">
																<input class="form-check-input" type="checkbox" value="2" checked="checked" />
																<span class="form-check-label">Customer</span>
															</label>
															<!--end::Options-->
														</div>
														<!--end::Options-->
													</div>
													<!--end::Input group-->
													<!--begin::Input group-->
													<div class="mb-10">
														<!--begin::Label-->
														<label class="form-label fw-semibold">Notifications:</label>
														<!--end::Label-->
														<!--begin::Switch-->
														<div class="form-check form-switch form-switch-sm form-check-custom form-check-solid">
															<input class="form-check-input" type="checkbox" value="" name="notifications" checked="checked" />
															<label class="form-check-label">Enabled</label>
														</div>
														<!--end::Switch-->
													</div>
													<!--end::Input group-->
													<!--begin::Actions-->
													<div class="d-flex justify-content-end">
														<button type="reset" class="btn btn-sm btn-light btn-active-light-primary me-2" data-kt-menu-dismiss="true">Reset</button>
														<button type="submit" class="btn btn-sm btn-primary" data-kt-menu-dismiss="true">Apply</button>
													</div>
													<!--end::Actions-->
												</div>
												<!--end::Form-->
											</div>
											<!--end::Menu 1-->
										</div>
										<!--end::Filter menu-->
										<!--begin::Secondary button-->
										<!--end::Secondary button-->
										<!--begin::Primary button-->
										<a href="#" class="btn btn-sm fw-bold btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_create_app">Create</a>
										<!--end::Primary button-->
									</div>
									<!--end::Actions-->
								</div>
								<!--end::Toolbar container-->
							</div>
							<!--end::Toolbar-->
							<!--begin::Content-->
							<div id="kt_app_content" class="app-content flex-column-fluid">
								<!--begin::Content container-->
								<div id="kt_app_content_container" class="app-container container-xxl">
									<!--begin::Layout-->
									<div class="d-flex flex-column flex-lg-row">
										<!--begin::Content-->
										<div class="flex-lg-row-fluid mb-10 mb-lg-0 me-lg-7 me-xl-10">
											<!--begin::Card-->
											<div class="card">
												<!--begin::Card body-->
												<div class="card-body p-12">
													<!--begin::Form-->
													<form action="store.php" method="post" id="kt_invoice_form">
														<!--begin::Wrapper-->
														<div class="d-flex flex-column align-items-start flex-xxl-row">
															<!--begin::Input group-->
															<div class="d-flex align-items-center flex-equal fw-row me-4 order-2" data-bs-toggle="tooltip" data-bs-trigger="hover" title="Specify invoice date">
																<!--begin::Date-->
																<div class="fs-6 fw-bold text-gray-700 text-nowrap">Date:</div>
																<!--end::Date-->
																<!--begin::Input-->
																<div class="position-relative d-flex align-items-center w-150px">
																	<!--begin::Datepicker-->
																	<input class="form-control form-control-transparent fw-bold pe-5" placeholder="Select date" name="invoice_date" id="date" required/>
																	<!--end::Datepicker-->
																	<!--begin::Icon-->
																	<i class="ki-duotone ki-down fs-4 position-absolute ms-4 end-0"></i>
																	<!--end::Icon-->
																</div>
																<!--end::Input-->
															</div>
															<!--end::Input group-->
															<!--begin::Input group-->
															<div class="d-flex flex-center flex-equal fw-row text-nowrap order-1 order-xxl-2 me-4" data-bs-toggle="tooltip" data-bs-trigger="hover" title="Enter invoice number">
																<select class="form-select form-select-solid fw-bold text-gray-800" name="invoice_type" aria-label="Select example" data-kt-element="tax-select" required>
																	<option value="Invoice">Invoice #</option>
																	<option value="Quote">Quote #</option>
																	<option value="Receipt">Receipt #</option>
																</select>
																<input type="text" class="form-control form-control-flush fw-bold text-muted fs-3 w-125px" name="invoiceNo" id="invoiceNo" value="<?php echo $newInvoiceNo; ?>" placehoder="..." />
															</div>
															<!--end::Input group-->
															<!--begin::Input group-->
															<div class="d-flex align-items-center justify-content-end flex-equal order-3 fw-row" data-bs-toggle="tooltip" data-bs-trigger="hover" title="Specify invoice due date">
																<!--begin::Date-->
																<div class="fs-6 fw-bold text-gray-700 text-nowrap">Due Date:</div>
																<!--end::Date-->
																<!--begin::Input-->
																<div class="position-relative d-flex align-items-center w-150px">
																	<!--begin::Datepicker-->
																	<input class="form-control form-control-transparent fw-bold pe-5" placeholder="Select date" name="invoice_due_date" />
																	<!--end::Datepicker-->
																	<!--begin::Icon-->
																	<i class="ki-duotone ki-down fs-4 position-absolute end-0 ms-4"></i>
																	<!--end::Icon-->
																</div>
																<!--end::Input-->
															</div>
															<!--end::Input group-->
														</div>
														<!--end::Top-->
														<!--begin::Separator-->
														<div class="separator separator-dashed my-10"></div>
														<!--end::Separator-->
														<!--begin::Wrapper-->
														<div class="mb-0">
														<div class="row gx-10 mb-5">
															<!--begin::Col-->
															<div class="col-lg-6">
																<label class="form-label fs-6 fw-bold text-gray-700 mb-3">Bill To</label>
																<!--begin::Input group-->
																<div class="mb-5">
																	<input type="text" class="form-control form-control-solid" placeholder="Name" name="customer_name" id="customer_name" required />
																</div>
																<!--end::Input group-->
																<!--begin::Input group-->
																<div class="mb-5">
																	<textarea class="form-control form-control-solid" rows="3" placeholder="Address" name="customer_address" id="customer_address" required></textarea>
																</div>
																<!--end::Input group-->
																<!--begin::Input group-->
																<div class="mb-5">
																	<input type="gst" class="form-control form-control-solid" placeholder="GST Number" name="gst_number" id="gst_number" />
																</div>
																<!--end::Input group-->
															</div>
															<!--end::Col-->
															<!--begin::Col-->
															<div class="col-lg-6">
																<label class="form-label fs-6 fw-bold text-gray-700 mb-3">Project Details</label>
																<!--begin::Input group-->
																<div class="mb-5">
																	<input type="text" name="invoiceName" id="invoiceName" class="form-control form-control-solid" placeholder="Project Name" required oninput="generateInvoiceID()"/>
																</div>
																<!--end::Input group-->
																<!--begin::Input group-->
																<div class="mb-5">
																	<input type="text" class="form-control form-control-solid" placeholder="HSN/SAC" name="hsnsac" id="hsnsac" value="998314" required/>
																</div>
																<!--end::Input group-->
															</div>
															<!--end::Col-->
														</div>
															<div class="table-responsive mb-10">
																<table class="table g-5 gs-0 mb-0 fw-bold text-gray-700" data-kt-element="items">
																	<thead>
																		<tr class="border-bottom fs-7 fw-bold text-gray-700 text-uppercase">
																		<th class="min-w-300px w-475px">Item</th>
																		<th class="min-w-100px w-100px">QTY</th>
																		<th class="min-w-125px w-125px">Price</th>
																		<th class="min-w-125px w-125px">Discount</th>
																		<th class="min-w-100px w-150px text-end">Total</th>
																		<th class="text-end"></th>
																		</tr>
																	</thead>
																	<tbody>
																		<tr class="border-bottom border-bottom-dashed" data-kt-element="item">
																		<td class="pe-7">
																			<input type="text" class="form-control form-control-solid mb-2" name="name[]" placeholder="Item name" />
																		</td>
																		<td class="ps-0">
																			<input class="form-control form-control-solid" type="number" min="1" name="quantity[]" placeholder="1" value="1" data-kt-element="quantity" />
																		</td>
																		<td>
																			<input type="text" class="form-control form-control-solid text-end" name="price[]" placeholder="0.00" value="0.00" data-kt-element="price" />
																		</td>
																		<td>
																			<input type="text" class="form-control form-control-solid text-end" name="discount[]" placeholder="0.00" value="0.00" data-kt-element="discount" />
																		</td>
																		<td class="pt-8 text-end text-nowrap">₹<span data-kt-element="total">0.00</span></td>
																		<td class="pt-5 text-end">
																			<button type="button" class="btn btn-sm btn-icon btn-active-color-primary" data-kt-element="remove-item">
																			<i class="ki-duotone ki-trash fs-3">
																				<span class="path1"></span>
																				<span class="path2"></span>
																				<span class="path3"></span>
																				<span class="path4"></span>
																				<span class="path5"></span>
																			</i>
																			</button>
																		</td>
																		</tr>
																	</tbody>
																	<tfoot>
																		<tr class="border-top border-top-dashed align-top fs-6 fw-bold text-gray-700">
																		<th class="text-primary">
																			<button class="btn btn-link py-1" data-kt-element="add-item">Add item</button>
																		</th>
																		<th colspan="3" class="border-bottom border-bottom-dashed ps-0">
																			<div class="d-flex flex-column align-items-start">
																			<div class="fs-5">Subtotal</div>
																			<br>
																			<div class="fs-5">Tax</div>
																			<br>
																			<div class="fs-5">Advance Paid</div>
																			</div>
																		</th>
																		<th colspan="2" class="border-bottom border-bottom-dashed text-end">₹<span data-kt-element="sub-total">0.00</span>
																		
																		<select class="form-select form-select-solid" name="tax_type" aria-label="Select example" data-kt-element="tax-select" required>
																			<option value="">Select Tax</option>
																			<option value="inter-state">Between States</option>
																			<option value="intra-state">Within State</option>
																			<option value="union-teritory">Union Teritory</option>
																		</select>
																		<br>
																		<input class="form-control form-control-solid" type="number" min="0" name="advance_amt[]" placeholder="0" value="0" data-kt-element="advance_amt" />
																		</th>
																		
																		</tr>
																		<tr class="align-top fw-bold text-gray-700">
																		<th></th>
																		<th colspan="3" class="fs-4 ps-0">Total</th>
																		<th colspan="2" class="text-end fs-4 text-nowrap">₹<span data-kt-element="grand-total">0.00</span></th>
																		</tr>
																	</tfoot>
																</table>
															</div>

															<!-- Updated Item Template -->
															<table class="table d-none" data-kt-element="item-template">
																<tr class="border-bottom border-bottom-dashed" data-kt-element="item">
																<td class="pe-7">
																	<input type="text" class="form-control form-control-solid mb-2" name="name[]" placeholder="Item name" />
																</td>
																<td class="ps-0">
																	<input class="form-control form-control-solid" type="number" min="1" name="quantity[]" placeholder="1" data-kt-element="quantity" />
																</td>
																<td>
																	<input type="text" class="form-control form-control-solid text-end" name="price[]" placeholder="0.00" data-kt-element="price" />
																</td>
																<td>
																	<input type="text" class="form-control form-control-solid text-end" name="discount[]" placeholder="0.00" data-kt-element="discount" />
																</td>
																<td class="pt-8 text-end">₹<span data-kt-element="total">0.00</span></td>
																<td class="pt-5 text-end">
																	<button type="button" class="btn btn-sm btn-icon btn-active-color-primary" data-kt-element="remove-item">
																	<i class="ki-duotone ki-trash fs-3">
																		<span class="path1"></span>
																		<span class="path2"></span>
																		<span class="path3"></span>
																		<span class="path4"></span>
																		<span class="path5"></span>
																	</i>
																	</button>
																</td>
																</tr>
															</table>

															<table class="table d-none" data-kt-element="empty-template">
																<tr data-kt-element="empty">
																<th colspan="6" class="text-muted text-center py-10">No items</th>
																</tr>
															</table>
															<div class="mb-5">
																<label class="form-label fs-6 fw-bold text-gray-700">Payment Info</label>
																<textarea name="paymentInfo" class="form-control form-control-solid" rows="3"></textarea>
															</div>
															<div class="mb-0">
																<label class="form-label fs-6 fw-bold text-gray-700">Terms & Conditions</label>
																<textarea name="notes" class="form-control form-control-solid" rows="3"></textarea>
															</div>
														</div>
														<!--end::Wrapper-->
														<div class="mb-0 mt-5">
															<button type="submit" class="btn btn-primary w-100" id="invoice_form">
															<i class="ki-duotone ki-triangle fs-3">
																<span class="path1"></span>
																<span class="path2"></span>
																<span class="path3"></span>
															</i>Save</button>
														</div>
													</form>
													<!--end::Form-->
												</div>
												<!--end::Card body-->
											</div>
											<!--end::Card-->
										</div>
										<!--end::Content-->
										<!--begin::Sidebar-->
										<div class="flex-lg-auto min-w-lg-300px">
											<!--begin::Card-->
											<div class="card" data-kt-sticky="true" data-kt-sticky-name="invoice" data-kt-sticky-offset="{default: false, lg: '200px'}" data-kt-sticky-width="{lg: '250px', lg: '300px'}" data-kt-sticky-left="auto" data-kt-sticky-top="150px" data-kt-sticky-animation="false" data-kt-sticky-zindex="95">
												<!--begin::Card body-->
												<div class="card-body p-10">
													<!--begin::Input group-->
													<div class="mb-10">
														<!--begin::Label-->
														<label class="form-label fw-bold fs-6 text-gray-700">Currency</label>
														<!--end::Label-->
														<!--begin::Select-->
														<select name="currnecy" aria-label="Select a Timezone" data-control="select2" data-placeholder="Select currency" class="form-select form-select-solid">
															<option value=""></option>
															<option data-kt-flag="flags/united-states.svg" value="USD">
															<b>USD</b>&nbsp;-&nbsp;USA dollar</option>
															<option data-kt-flag="flags/united-kingdom.svg" value="GBP">
															<b>GBP</b>&nbsp;-&nbsp;British pound</option>
															<option data-kt-flag="flags/australia.svg" value="AUD">
															<b>AUD</b>&nbsp;-&nbsp;Australian dollar</option>
															<option data-kt-flag="flags/japan.svg" value="JPY">
															<b>JPY</b>&nbsp;-&nbsp;Japanese yen</option>
															<option data-kt-flag="flags/sweden.svg" value="SEK">
															<b>SEK</b>&nbsp;-&nbsp;Swedish krona</option>
															<option data-kt-flag="flags/canada.svg" value="CAD">
															<b>CAD</b>&nbsp;-&nbsp;Canadian dollar</option>
															<option data-kt-flag="flags/switzerland.svg" value="CHF">
															<b>CHF</b>&nbsp;-&nbsp;Swiss franc</option>
														</select>
														<!--end::Select-->
													</div>
													<!--end::Input group-->
													<!--begin::Separator-->
													<div class="separator separator-dashed mb-8"></div>
													<!--end::Separator-->
													<!--begin::Input group-->
													<div class="mb-8">
														<!--begin::Option-->
														<label class="form-check form-switch-sm form-check-custom form-check-solid flex-stack mb-5">
															<span class="form-check-label ms-0 fw-bold fs-6 text-gray-700">Invoice</span>
															<input class="form-check-input" type="checkbox" checked="checked" value="" />
														</label>
														<!--end::Option-->
														<!--begin::Option-->
														<label class="form-check form-switch-sm form-check-custom form-check-solid flex-stack mb-5">
															<span class="form-check-label ms-0 fw-bold fs-6 text-gray-700">Quote</span>
															<input class="form-check-input" type="checkbox" value="" />
														</label>
														<!--end::Option-->
														<!--begin::Option-->
														<label class="form-check form-switch-sm form-check-custom form-check-solid flex-stack">
															<span class="form-check-label ms-0 fw-bold fs-6 text-gray-700">Receipt</span>
															<input class="form-check-input" type="checkbox" value="" />
														</label>
														<!--end::Option-->
													</div>
													<!--end::Input group-->
													<!--begin::Separator-->
													<div class="separator separator-dashed mb-8"></div>
													<!--end::Separator-->
													<!--begin::Actions-->
													<div class="mb-0">
														<button type="submit" href="#" class="btn btn-primary w-100" id="invoice_form">
														<i class="ki-duotone ki-triangle fs-3">
															<span class="path1"></span>
															<span class="path2"></span>
															<span class="path3"></span>
														</i>Save</button>
													</div>
													<!--end::Actions-->
												</div>
												<!--end::Card body-->
											</div>
											<!--end::Card-->
										</div>
										<!--end::Sidebar-->
									</div>
									<!--end::Layout-->
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
		<!--begin::Drawers-->
		<!--begin::Activities drawer-->
		
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
		<script src="assets/js/custom/apps/invoices/create.js"></script>
		<script src="assets/js/widgets.bundle.js"></script>
		<script src="assets/js/custom/widgets.js"></script>
		<script src="assets/js/custom/apps/chat/chat.js"></script>
		<script src="assets/js/custom/utilities/modals/upgrade-plan.js"></script>
		<script src="assets/js/custom/utilities/modals/create-app.js"></script>
		<script src="assets/js/custom/utilities/modals/users-search.js"></script>
		<!-- Include Toastr CSS -->
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
		<!-- Include Toastr JS -->
		<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
		<script>
			function generateInvoiceID() {
				const invoiceNameField = document.getElementById('invoiceName');
				const invoiceNoField = document.getElementById('invoiceNo');
				const prefix = invoiceNameField.value.substr(0, 3).toUpperCase();
				const originalId = "<?php echo $newInvoiceNo; ?>"; // fetch the original invoice number from PHP
				const formattedId = originalId.length === 1 ? '0' + originalId : originalId;
				const generatedId = prefix + '-' + formattedId;
				invoiceNoField.value = generatedId;
			}
		</script>

<script>
$(document).ready(function() {
    $('#kt_invoice_form').on('submit', function(e) {
        e.preventDefault(); // Prevent the default form submission

        $.ajax({
            type: 'POST',
            url: 'store.php',
            data: $(this).serialize(),
            success: function(response) {
                try {
                    const res = typeof response === 'string' ? JSON.parse(response) : response;

                    if (res.success) {
                        // Show Toastr success message
                        toastr.success(res.success);

                        // Reload the page after a short delay to allow the Toastr message to display
                        setTimeout(function() {
                            location.reload(); // This will reload the entire page
                        }, 2000); // Adjust the delay as needed
                    } else if (res.error) {
                        toastr.error('Error: ' + res.error);
                    } else {
                        toastr.error('An unexpected error occurred.');
                    }
                } catch (e) {
                    toastr.error('An unexpected error occurred while processing the response.');
                }
            },
            error: function(xhr, status, error) {
                toastr.error('An error occurred: ' + error); // Display error message
            }
        });
    });
});

toastr.options = {
  "closeButton": true,
  "debug": false,
  "newestOnTop": false,
  "progressBar": true,
  "positionClass": "toast-top-right", // You can change the position (e.g., 'toast-bottom-right')
  "preventDuplicates": true,
  "onclick": null,
  "showDuration": "300",
  "hideDuration": "1000",
  "timeOut": "5000", // Duration the toast will remain visible
  "extendedTimeOut": "1000",
  "showEasing": "swing",
  "hideEasing": "linear",
  "showMethod": "fadeIn",
  "hideMethod": "fadeOut"
}
</script>
		<!--end::Custom Javascript-->
		<!--end::Javascript-->
	</body>
	<!--end::Body-->
</html>
<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['redirect_back'] = $_SERVER['REQUEST_URI'];
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

function fetchClientNames($pdo) {
    $sql = "SELECT Name FROM clientDetails";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}
$clientNames = fetchClientNames($pdo);

function calculateProgress($actualStartDate, $actualEndDate, $endDate) {  
    $start = $actualStartDate ? strtotime($actualStartDate) : time();  
    $end = $actualEndDate ? strtotime($actualEndDate) : strtotime($endDate);  
    $now = time();  
  
    if ($end <= $start) {  
        return 100;  
    }  
  
    if ($now >= $end) {  
        return 100;  
    } elseif ($now <= $start) {  
        return 0;  
    } else {  
        return ($now - $start) / ($end - $start) * 100;  
    }  
}  

function determineStatus($actualStartDate, $actualEndDate, $endDate) {  
    $now = time();  
    $startDate = $actualStartDate ? strtotime($actualStartDate) : null;  
    $endDate = $actualEndDate ? strtotime($actualEndDate) : strtotime($endDate);  
  
    if ($actualEndDate && $endDate <= $now) {  
        return 'success'; // Completed  
    } elseif ($startDate && $startDate <= $now && $endDate >= $now) {  
        return 'primary'; // In Progress  
    } elseif ($endDate < $now) {  
        return 'danger'; // Overdue  
    } else {  
        return 'info'; // Not Started  
    }  
}  

function updateProjectDates($pdo, $projectId, $actualStartDate, $actualEndDate) {
    // Convert '00' or empty string to NULL
    $actualStartDate = ($actualStartDate === '00' || empty($actualStartDate)) ? null : $actualStartDate;
    $actualEndDate = ($actualEndDate === '00' || empty($actualEndDate)) ? null : $actualEndDate;

    $sql = "UPDATE projectDetails SET Actual_Start_Date = :actualStartDate, Actual_End_Date = :actualEndDate WHERE Project_ID = :projectId";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'actualStartDate' => $actualStartDate,
        'actualEndDate' => $actualEndDate,
        'projectId' => $projectId
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['project_id']) && isset($_POST['actual_start_date']) && isset($_POST['actual_end_date'])) {
        $projectId = $_POST['project_id'];
        $actualStartDate = $_POST['actual_start_date'];
        $actualEndDate = $_POST['actual_end_date'];
        updateProjectDates($pdo, $projectId, $actualStartDate, $actualEndDate);
    }
}

function displayProjectDetails($pdo) {
    $sql = "SELECT Project_ID, Project_Type, Client_ID, Client_Name, Name, Description, Start_Date, End_Date, Actual_Start_Date, Actual_End_Date, Budget, Documents, Tags, Updated_On FROM projectDetails";
    $stmt = $pdo->query($sql);
    $result = $stmt->fetchAll();

    if (count($result) > 0) {
        foreach ($result as $row) {
            $progress = calculateProgress($row['Actual_Start_Date'], $row['Actual_End_Date'], $row['End_Date']);
            $status = determineStatus($row['Actual_Start_Date'], $row['Actual_End_Date'], $row['End_Date']);
			$labels = [
				'success' => 'Completed',
				'primary' => 'In Progress',
				'danger'  => 'Overdue',
				'info'    => 'Not Started',
			];

            echo '<div class="col-md-6 col-xl-4 d-flex mt-5">';
            echo '<form method="POST" class="date-form" action="' . htmlspecialchars($_SERVER['PHP_SELF']) . '">';
            echo '<input type="hidden" name="project_id" value="' . htmlspecialchars($row['Project_ID']) . '">';
            echo '<a class="card border-hover-primary">';
            echo '<div class="card-header border-0 pt-9">';
            echo '<div class="card-title m-0">';
            echo '<p style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%;"><strong>Client Name:</strong> ' . htmlspecialchars($row['Client_Name']) . '</p>';
            echo '</div>';
            echo '<div class="card-toolbar">';
			echo '<span class="badge badge-light-' . $status . ' fw-bold me-auto px-4 py-3">'. ($labels[$status] ?? ucfirst($status)) . '</span>';
            echo '</div>';
            echo '</div>';
            echo '<div class="card-body p-9">';
            echo '<div class="fs-3 fw-bold text-gray-900">' . htmlspecialchars($row['Name']) . '</div>';
            echo '<p class="text-gray-500 fw-semibold fs-5 mt-1 mb-7">' . htmlspecialchars($row['Description']) . '</p>';
            echo '<div class="d-flex flex-wrap mb-5">';
            echo '<div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-7 mb-3">';
            echo '<div class="fs-6 text-gray-800 fw-bold">' . htmlspecialchars($row['End_Date']) . '</div>';
            echo '<div class="fw-semibold text-gray-500">Due Date</div>';
            echo '</div>';
            echo '<div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 mb-3">';
            echo '<div class="fs-6 text-gray-800 fw-bold">₹' . number_format($row['Budget'], 2) . '</div>';
            echo '<div class="fw-semibold text-gray-500">Budget</div>';
            echo '</div>';
            echo '</div>';
            echo '<div class="d-flex flex-wrap mb-5">';
            echo '<div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-7 mb-3">';
            echo '<div class="fw-semibold text-gray-500">Actual Start Date</div>';
            echo '<input class="form-control form-control-solid ps-12 flatpickr" placeholder="Pick start date" name="actual_start_date" value="' . htmlspecialchars($row['Actual_Start_Date']) . '" />';
            echo '</div>';
            echo '<div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 mb-3">';
            echo '<div class="fw-semibold text-gray-500">Actual End Date</div>';
            echo '<input class="form-control form-control-solid ps-12 flatpickr" placeholder="Pick end date" name="actual_end_date" value="' . htmlspecialchars($row['Actual_End_Date']) . '" />';
            echo '</div>';
            echo '</div>';
            echo '<div class="h-4px w-100 bg-light mb-5" data-bs-toggle="tooltip" title="This project ' . round($progress) . '% completed">';
            echo '<div class="bg-primary rounded h-4px" role="progressbar" style="width: ' . round($progress) . '%" aria-valuenow="' . round($progress) . '" aria-valuemin="0" aria-valuemax="100"></div>';
            echo '</div>';

            $tags = explode(',', $row['Tags']);
            $colorClasses = ['bg-info', 'bg-primary', 'bg-danger', 'bg-success', 'bg-dark'];
            if (!empty($tags)) {
                echo '<div class="symbol-group symbol-hover">';
                foreach ($tags as $tag) {
                    $tag = trim($tag);
                    $tagInitial = strtoupper($tag[0]);
                    $tagFullName = htmlspecialchars($tag);
                    $randomColor = $colorClasses[array_rand($colorClasses)];
                    echo '<div class="symbol symbol-35px symbol-circle" data-bs-toggle="tooltip" title="' . $tagFullName . '">';
                    echo '<span class="symbol-label ' . $randomColor . ' text-inverse-primary fw-bold">' . $tagInitial . '</span>';
                    echo '</div>';
                }
                echo '</div>';
            }

            echo '</div>';
            echo '<div class="card-footer">';
            echo '<button type="submit" class="btn btn-primary d-none">Update Dates</button>';
            echo '</div>';
            echo '</a>';
            echo '</form>';
            echo '</div>';
        }
    } else {
        echo "0 results";
    }

    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            flatpickr(".flatpickr", {
                enableTime: false,
                dateFormat: "Y-m-d",
                onChange: function(selectedDates, dateStr, instance) {
                    instance.input.closest("form").submit();
                }
            });
        });
    </script>';
}

function getTotalProjects($pdo) {
	$sql = "SELECT COUNT(*) as totalProjects FROM projectDetails";
	$stmt = $pdo->prepare($sql);
	$stmt->execute();
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	return $result['totalProjects'];
}
$totalProjects = getTotalProjects($pdo);

function getActiveProjects($pdo) {
	$sql = "SELECT 
    COUNT(*) AS Active_Count
FROM 
    (
        SELECT 
            CASE
                WHEN CURDATE() BETWEEN Start_Date AND End_Date THEN 'Active'
                WHEN CURDATE() > End_Date THEN 'Completed'
                WHEN CURDATE() < Start_Date THEN 'Yet to start'
            END AS Project_Status
        FROM 
            projectDetails
    ) AS status_table
WHERE 
    Project_Status = 'Active'";
	$stmt = $pdo->prepare($sql);
	$stmt->execute();
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	return $result['Active_Count'];
}
$totalActiveProjects = getActiveProjects($pdo);

function getCompleteProjects($pdo) {
	$sql = "SELECT 
    COUNT(*) AS Complete_Count
FROM 
    (
        SELECT 
            CASE
                WHEN CURDATE() BETWEEN Start_Date AND End_Date THEN 'Active'
                WHEN CURDATE() > End_Date THEN 'Completed'
                WHEN CURDATE() < Start_Date THEN 'Yet to start'
            END AS Project_Status
        FROM 
            projectDetails
    ) AS status_table
WHERE 
    Project_Status = 'Completed'";
	$stmt = $pdo->prepare($sql);
	$stmt->execute();
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	return $result['Complete_Count'];
}
$totalCompleteProjects = getCompleteProjects($pdo);

function getToStartProjects($pdo) {
	$sql = "SELECT 
    COUNT(*) AS ToStart_Count
FROM 
    (
        SELECT 
            CASE
                WHEN CURDATE() BETWEEN Start_Date AND End_Date THEN 'Active'
                WHEN CURDATE() > End_Date THEN 'Completed'
                WHEN CURDATE() < Start_Date THEN 'Yet to start'
            END AS Project_Status
        FROM 
            projectDetails
    ) AS status_table
WHERE 
    Project_Status = 'Yet to start'";
	$stmt = $pdo->prepare($sql);
	$stmt->execute();
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	return $result['ToStart_Count'];
}
$totalToStartProjects = getToStartProjects($pdo);

function getTotalProjectFinance($pdo) {
	$sql = "SELECT SUM(budget) as totalProjectBudget FROM projectDetails";
	$stmt = $pdo->prepare($sql);
	$stmt->execute();
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	return $result['totalProjectBudget'];
}
$totalProjectFinance = getTotalProjectFinance($pdo);

function getTotalProjectFinanceActive($pdo) {
	$sql = "SELECT 
    SUM(Budget) AS totalProjectBudget
FROM 
    (
        SELECT 
            Budget,
            CASE
                WHEN CURDATE() BETWEEN Start_Date AND End_Date THEN 'Active'
                WHEN CURDATE() > End_Date THEN 'Completed'
                WHEN CURDATE() < Start_Date THEN 'Yet to start'
            END AS Project_Status
        FROM 
            projectDetails
    ) AS status_table
WHERE 
    Project_Status = 'Active';";
	$stmt = $pdo->prepare($sql);
	$stmt->execute();
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	return $result['totalProjectBudget'];
}
$totalProjectFinanceActive = getTotalProjectFinanceActive($pdo);

function getTotalProjectFinanceCompleted($pdo) {
	$sql = "SELECT 
    SUM(Budget) AS totalProjectBudget
FROM 
    (
        SELECT 
            Budget,
            CASE
                WHEN CURDATE() BETWEEN Start_Date AND End_Date THEN 'Active'
                WHEN CURDATE() > End_Date THEN 'Completed'
                WHEN CURDATE() < Start_Date THEN 'Yet to start'
            END AS Project_Status
        FROM 
            projectDetails
    ) AS status_table
WHERE 
    Project_Status = 'Completed';";
	$stmt = $pdo->prepare($sql);
	$stmt->execute();
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	return $result['totalProjectBudget'];
}
$totalProjectFinanceCompleted = getTotalProjectFinanceCompleted($pdo);

function getTotalProjectFinanceYet($pdo) {
	$sql = "SELECT 
    SUM(Budget) AS totalProjectBudget
FROM 
    (
        SELECT 
            Budget,
            CASE
                WHEN CURDATE() BETWEEN Start_Date AND End_Date THEN 'Active'
                WHEN CURDATE() > End_Date THEN 'Completed'
                WHEN CURDATE() < Start_Date THEN 'Yet to start'
            END AS Project_Status
        FROM 
            projectDetails
    ) AS status_table
WHERE 
    Project_Status = 'Yet to start';";
	$stmt = $pdo->prepare($sql);
	$stmt->execute();
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	return $result['totalProjectBudget'];
}
$totalProjectFinanceYet = getTotalProjectFinanceYet($pdo);

function uniqueClientCount($pdo) {
	$sql = "SELECT COUNT(DISTINCT Client_Name) AS uniqueClientCount FROM projectDetails";
	$stmt = $pdo->prepare($sql);
	$stmt->execute();
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	return $result['uniqueClientCount'];
}
$clientCount = uniqueClientCount($pdo);

function fetchDistinctClientNames($pdo) {
    $sql = "SELECT DISTINCT Client_Name FROM projectDetails";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}
$distinctClientNames = fetchDistinctClientNames($pdo);

function getColorClass($letter) {
    $colors = ['bg-warning', 'bg-info', 'bg-primary', 'bg-danger', 'bg-success', 'bg-dark'];
    $index = ord(strtoupper($letter)) % count($colors);
    return $colors[$index];
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
		<title>S.T.R.In.G - Projects</title>
		<meta charset="utf-8" />
		<meta name="description" content="The most advanced Bootstrap 5 Admin Theme with 40 unique prebuilt layouts on Themeforest trusted by 100,000 beginners and professionals. Multi-demo, Dark Mode, RTL support and complete React, Angular, Vue, Asp.Net Core, Rails, Spring, Blazor, Django, Express.js, Node.js, Flask, Symfony & Laravel versions. Grab your copy now and get life-time updates for free." />
		<meta name="keywords" content="metronic, bootstrap, bootstrap 5, angular, VueJs, React, Asp.Net Core, Rails, Spring, Blazor, Django, Express.js, Node.js, Flask, Symfony & Laravel starter kits, admin themes, web design, figma, web development, free templates, free admin themes, bootstrap theme, bootstrap template, bootstrap dashboard, bootstrap dak mode, bootstrap button, bootstrap datepicker, bootstrap timepicker, fullcalendar, datatables, flaticon" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<meta property="og:locale" content="en_US" />
		<meta property="og:type" content="article" />
		<meta property="og:title" content="Metronic - The World's #1 Selling Bootstrap Admin Template by KeenThemes" />
		<meta property="og:url" content="https://keenthemes.com/metronic" />
		<meta property="og:site_name" content="Metronic by Keenthemes" />
		<link rel="canonical" href="http://preview.keenthemes.comdashboards/projects.html" />
		<link rel="shortcut icon" href="assets/media/logos/favicon.ico" />
		<!--begin::Fonts(mandatory for all pages)-->
		<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
		<!--end::Fonts-->
		<!--begin::Vendor Stylesheets(used for this page only)-->
		<link href="assets/plugins/custom/datatables/datatables.bundle.css" rel="stylesheet" type="text/css" />
		<link href="assets/plugins/custom/vis-timeline/vis-timeline.bundle.css" rel="stylesheet" type="text/css" />
		<!--end::Vendor Stylesheets-->
		<!--begin::Global Stylesheets Bundle(mandatory for all pages)-->
		<link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
		<link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css" />
		<!--end::Global Stylesheets Bundle-->
		<script>// Frame-busting to prevent site from being loaded within a frame without permission (click-jacking) if (window.top != window.self) { window.top.location.replace(window.self.location.href); }</script>
		<style>
			/* Ensure all cards stretch to equal height */
			.card {
				display: flex;
				flex-direction: column;
				height: 100%; /* All cards will fill the container height */
			}
			/* Make the card body expand to use available space */
			.card-body {
				flex-grow: 1;
			}
			/* Keep the footer at the bottom of the card */
			.card-footer {
				margin-top: auto;
			}
		</style>
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
										<h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">Projects Dashboard</h1>
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
											<li class="breadcrumb-item text-muted">Dashboards</li>
											<!--end::Item-->
										</ul>
										<!--end::Breadcrumb-->
									</div>
									<!--end::Page title-->
									<!--begin::Actions-->
									<div class="d-flex align-items-center gap-2 gap-lg-3">
										<!--begin::Secondary button-->
										<a href="apps/projects/list.html" class="btn btn-sm fw-bold btn-secondary">My Projects</a>
										<!--end::Secondary button-->
										<!--begin::Primary button-->
										<a href="#" class="btn btn-sm fw-bold btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_create_project">Create New Project</a>
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
									<!--begin::Row-->
                                    <div class="row gx-6 gx-xl-9">
										<div class="col-lg-6 col-xxl-4">
											<!--begin::Card-->
											<div class="card h-100">
												<!--begin::Card body-->
												<div class="card-body p-9">
													<!--begin::Heading-->
													<div class="fs-2hx fw-bold"><?php echo $totalProjects; ?></div>
													<div class="fs-4 fw-semibold text-gray-500 mb-7">Current Projects</div>
													<!--end::Heading-->
													<!--begin::Wrapper-->
													<div class="d-flex flex-wrap">
														<!--begin::Chart-->
														<div class="d-flex flex-center h-100px w-100px me-9 mb-5">
															<canvas id="kt_project_list_chart"></canvas>
														</div>
														<!--end::Chart-->
														<!--begin::Labels-->
														<div class="d-flex flex-column justify-content-center flex-row-fluid pe-11 mb-5">
															<!--begin::Label-->
															<div class="d-flex fs-6 fw-semibold align-items-center mb-3">
																<div class="bullet bg-primary me-3"></div>
																<div class="text-gray-500">Active</div>
																<div class="ms-auto fw-bold text-gray-700"><?php echo $totalActiveProjects; ?></div>
															</div>
															<!--end::Label-->
															<!--begin::Label-->
															<div class="d-flex fs-6 fw-semibold align-items-center mb-3">
																<div class="bullet bg-success me-3"></div>
																<div class="text-gray-500">Completed</div>
																<div class="ms-auto fw-bold text-gray-700"><?php echo $totalCompleteProjects; ?></div>
															</div>
															<!--end::Label-->
															<!--begin::Label-->
															<div class="d-flex fs-6 fw-semibold align-items-center">
																<div class="bullet bg-gray-300 me-3"></div>
																<div class="text-gray-500">Yet to start</div>
																<div class="ms-auto fw-bold text-gray-700"><?php echo $totalToStartProjects; ?></div>
															</div>
															<!--end::Label-->
														</div>
														<!--end::Labels-->
													</div>
													<!--end::Wrapper-->
												</div>
												<!--end::Card body-->
											</div>
											<!--end::Card-->
										</div>
										<div class="col-lg-6 col-xxl-4">
											<!--begin::Budget-->
											<div class="card h-100">
												<div class="card-body p-9">
													<div class="fs-2hx fw-bold">Total: ₹<?php echo $totalProjectFinance; ?></div>
													<div class="fs-4 fw-semibold text-gray-500 mb-7">Project Finance</div>
													<div class="fs-6 d-flex justify-content-between mb-4">
														<div class="fw-semibold">Active Project Budget</div>
														<div class="d-flex fw-bold">
														<i class="ki-duotone fs-3 me-1 text-success">
															<span class="path1"></span>
															<span class="path2"></span>
														</i>₹<?php echo $totalProjectFinanceActive; ?></div>
													</div>
													<div class="separator separator-dashed"></div>
													<div class="fs-6 d-flex justify-content-between my-4">
														<div class="fw-semibold">Completed Project Budget</div>
														<div class="d-flex fw-bold">
														<i class="ki-duotone fs-3 me-1 text-danger">
															<span class="path1"></span>
															<span class="path2"></span>
														</i>₹<?php echo $totalProjectFinanceCompleted; ?></div>
													</div>
													<div class="separator separator-dashed"></div>
													<div class="fs-6 d-flex justify-content-between mt-4">
														<div class="fw-semibold">Yet to Start Project Budget</div>
														<div class="d-flex fw-bold">
														<i class="ki-duotone fs-3 me-1 text-success">
															<span class="path1"></span>
															<span class="path2"></span>
														</i>₹<?php echo $totalProjectFinanceYet; ?></div>
													</div>
												</div>
											</div>
											<!--end::Budget-->
										</div>
										<div class="col-lg-6 col-xxl-4">
											<!--begin::Clients-->
											<div class="card h-100">
												<div class="card-body p-9">
													<!--begin::Heading-->
													<div class="fs-2hx fw-bold"><?php echo $clientCount; ?></div>
													<div class="fs-4 fw-semibold text-gray-500 mb-7">Project's Clients</div>
													<!--end::Heading-->
													<!--begin::Users group-->
													<div class="symbol-group symbol-hover mb-9">
														<?php
														$maxVisible = 10;
														$totalClients = count($distinctClientNames);
														for ($i = 0; $i < min($totalClients, $maxVisible); $i++) {
															$client = $distinctClientNames[$i]['Client_Name'];
															$initial = strtoupper($client[0]);
															$colorClass = getColorClass($initial);
															echo '<div class="symbol symbol-35px symbol-circle" data-bs-toggle="tooltip" title="' . htmlspecialchars($client) . '">';
															echo '<span class="symbol-label ' . $colorClass . ' text-inverse-warning fw-bold">' . $initial . '</span>';
															echo '</div>';
														}
														if ($totalClients > $maxVisible) {
															echo '<a href="#" class="symbol symbol-35px symbol-circle" data-bs-toggle="modal" data-bs-target="#kt_modal_view_users">';
															echo '<span class="symbol-label bg-dark text-gray-300 fs-8 fw-bold">+' . ($totalClients - $maxVisible) . '</span>';
															echo '</a>';
														}
														?>
													</div>
													<!--end::Users group-->
													<!--begin::Actions-->
													<div class="d-flex">
														<a href="clients.php" class="btn btn-primary btn-sm me-3">All Clients</a>
													</div>
													<!--end::Actions-->
												</div>
											</div>
											<!--end::Clients-->
										</div>
									</div>
                                    <div class="d-flex flex-wrap flex-stack my-5">
										<!--begin::Heading-->
										<h2 class="fs-2 fw-semibold my-2">Projects 
										<span class="fs-6 text-gray-500 ms-1">by Status</span></h2>
										<!--end::Heading-->
										<!--begin::Controls-->
										<div class="d-flex flex-wrap my-1">
											<!--begin::Select wrapper-->
											<div class="m-0">
												<!--begin::Select-->
												<select name="status" data-control="select2" data-hide-search="true" class="form-select form-select-sm form-select-solid fw-bold w-125px">
													<option value="Active" selected="selected">Active</option>
													<option value="Approved">In Progress</option>
													<option value="Declined">To Do</option>
													<option value="In Progress">Completed</option>
												</select>
												<!--end::Select-->
											</div>
											<!--end::Select wrapper-->
										</div>
										<!--end::Controls-->
									</div>
									<div class="row gx-5 gx-xl-10 mb-xl-10">
										<!--begin::Col-->
										<?php displayProjectDetails($pdo); ?>
										<!--end::Col-->
									</div>
									<!--end::Row-->
									<!--begin::Row-->
									
									<!--end::Row-->
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
		<div class="modal fade" id="kt_modal_create_project" tabindex="-1" aria-hidden="true">
			<!--begin::Modal dialog-->
			<div class="modal-dialog modal-fullscreen p-9">
				<!--begin::Modal content-->
				<div class="modal-content modal-rounded">
					<!--begin::Modal header-->
					<div class="modal-header">
						<!--begin::Modal title-->
						<h2>Create Project</h2>
						<div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
							<i class="ki-duotone ki-cross fs-1">
								<span class="path1"></span>
								<span class="path2"></span>
							</i>
						</div>
						<!--end::Close-->
					</div>
					<!--end::Modal header-->
					<!--begin::Modal body-->
					<div class="modal-body scroll-y m-5">
						<!--begin::Stepper-->
						<div class="stepper stepper-links d-flex flex-column" id="kt_modal_create_project_stepper">
							<!--begin::Container-->
							<div class="container">
								<!--begin::Nav-->
								<div class="stepper-nav justify-content-center py-2">
									<!--begin::Step 1-->
									<div class="stepper-item me-5 me-md-15 current" data-kt-stepper-element="nav">
										<h3 class="stepper-title">Project Type</h3>
									</div>
									<!--end::Step 1-->
									<!--begin::Step 2-->
									<div class="stepper-item me-5 me-md-15" data-kt-stepper-element="nav">
										<h3 class="stepper-title">Project Settings</h3>
									</div>
									<!--end::Step 2-->
									<!--begin::Step 3-->
									<div class="stepper-item me-5 me-md-15" data-kt-stepper-element="nav">
										<h3 class="stepper-title">Budget</h3>
									</div>
									<!--end::Step 3-->
									<!--begin::Step 4-->
									<div class="stepper-item me-5 me-md-15" data-kt-stepper-element="nav">
										<h3 class="stepper-title">Build A Team</h3>
									</div>
									<!--end::Step 4-->
									<!--begin::Step 5-->
									<!--<div class="stepper-item me-5 me-md-15" data-kt-stepper-element="nav">
										<h3 class="stepper-title">Set First Target</h3>
									</div>-->
									<!--end::Step 5-->
									<!--begin::Step 6-->
									<!--<div class="stepper-item me-5 me-md-15" data-kt-stepper-element="nav">
										<h3 class="stepper-title">Upload Files</h3>
									</div>-->
									<!--end::Step 6-->
									<!--begin::Step 7-->
									<div class="stepper-item" data-kt-stepper-element="nav">
										<h3 class="stepper-title">Completed</h3>
									</div>
									<!--end::Step 7-->
								</div>
								<!--end::Nav-->
								<!--begin::Form-->
								<form class="mx-auto w-100 mw-600px pt-15 pb-10" novalidate="novalidate" id="kt_modal_create_project_form" method="post">
									<!--begin::Type-->
									<div class="current" data-kt-stepper-element="content">
										<!--begin::Wrapper-->
										<div class="w-100">
											<!--begin::Heading-->
											<div class="pb-7 pb-lg-12">
												<!--begin::Title-->
												<h1 class="fw-bold text-gray-900">Project Type</h1>
												<!--end::Title-->
											</div>
											<!--end::Heading-->
											<!--begin::Input group-->
											<div class="fv-row mb-15" data-kt-buttons="true">
												<!--begin::Option-->
												<label class="btn btn-outline btn-outline-dashed btn-active-light-primary d-flex text-start p-6 mb-6 active">
													<!--begin::Input-->
													<input class="btn-check" type="radio" checked="checked" name="project_type" value="1" />
													<!--end::Input-->
													<!--begin::Label-->
													<span class="d-flex">
														<!--begin::Icon-->
														<i class="ki-duotone ki-profile-circle fs-3hx">
															<span class="path1"></span>
															<span class="path2"></span>
															<span class="path3"></span>
														</i>
														<!--end::Icon-->
														<!--begin::Info-->
														<span class="ms-4">
															<span class="fs-3 fw-bold text-gray-900 mb-2 d-block">Internal Project</span>
															<span class="fw-semibold fs-4 text-muted">Project that is done internal in and for S.T.R.In.G</span>
														</span>
														<!--end::Info-->
													</span>
													<!--end::Label-->
												</label>
												<!--end::Option-->
												<!--begin::Option-->
												<label class="btn btn-outline btn-outline-dashed btn-active-light-primary d-flex text-start p-6">
													<!--begin::Input-->
													<input class="btn-check" type="radio" name="project_type" value="2" />
													<!--end::Input-->
													<!--begin::Label-->
													<span class="d-flex">
														<!--begin::Icon-->
														<i class="ki-duotone ki-rocket fs-3hx">
															<span class="path1"></span>
															<span class="path2"></span>
														</i>
														<!--end::Icon-->
														<!--begin::Info-->
														<span class="ms-4">
															<span class="fs-3 fw-bold text-gray-900 mb-2 d-block">Client Project</span>
															<span class="fw-semibold fs-4 text-muted">Project that is done for external client</span>
														</span>
														<!--end::Info-->
													</span>
													<!--end::Label-->
												</label>
												<!--end::Option-->
											</div>
											<!--end::Input group-->
											<!--begin::Actions-->
											<div class="d-flex justify-content-end">
												<button type="button" class="btn btn-lg btn-primary" data-kt-element="type-next">
													<span class="indicator-label">Project Settings</span>
													<span class="indicator-progress">Please wait... 
													<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
												</button>
											</div>
											<!--end::Actions-->
										</div>
										<!--end::Wrapper-->
									</div>
									<!--end::Type-->
									<!--begin::Settings-->
									<div data-kt-stepper-element="content">
										<!--begin::Wrapper-->
										<div class="w-100">
											<!--begin::Input group-->
											<div class="fv-row mb-8">
												<!--begin::Dropzone-->
												<div class="dropzone" id="kt_modal_create_project_settings_logo">
													<!--begin::Message-->
													<div class="dz-message needsclick">
														<!--begin::Icon-->
														<i class="ki-duotone ki-file-up fs-3hx text-primary">
															<span class="path1"></span>
															<span class="path2"></span>
														</i>
														<!--end::Icon-->
														<!--begin::Info-->
														<div class="ms-4">
															<h3 class="dfs-3 fw-bold text-gray-900 mb-1">Drop files here or click to upload.</h3>
															<span class="fw-semibold fs-4 text-muted">Upload up to 10 files</span>
														</div>
														<!--end::Info-->
													</div>
												</div>
												<!--end::Dropzone-->
											</div>
											<!--end::Input group-->
											<!--begin::Input group-->
											<div class="fv-row mb-8">
												<!--begin::Label-->
												<label class="required fs-6 fw-semibold mb-2">Customer</label>
												<!--end::Label-->
												<!--begin::Input-->
												<select class="form-select form-select-solid" data-control="select2" data-hide-search="true" data-placeholder="Select..." name="settings_customer" id="customer_select">
													<option></option>
													<?php
													foreach ($clientNames as $client) {
														echo '<option value="' . htmlspecialchars($client['Name']) . '">' . htmlspecialchars($client['Name']) . '</option>';
													}
													?>
												</select>
												<!--end::Input-->
											</div>
											<!--end::Input group-->
											<!--begin::Input group-->
											<div class="fv-row mb-8">
												<!--begin::Label-->
												<label class="d-flex align-items-center fs-6 fw-semibold form-label mb-2">
													<span class="required">Project Name</span>
													<span class="ms-1" data-bs-toggle="tooltip" title="Specify project name">
														<i class="ki-duotone ki-information-5 text-gray-500 fs-6">
															<span class="path1"></span>
															<span class="path2"></span>
															<span class="path3"></span>
														</i>
													</span>
												</label>
												<!--end::Label-->
												<!--begin::Input-->
												<input type="text" class="form-control form-control-solid" placeholder="Enter Project Name" name="settings_name" />
												<!--end::Input-->
											</div>
											<!--end::Input group-->
											<!--begin::Input group-->
											<div class="fv-row mb-8">
												<!--begin::Label-->
												<label class="required fs-6 fw-semibold mb-2">Project Description</label>
												<!--end::Label-->
												<!--begin::Input-->
												<textarea class="form-control form-control-solid" rows="3" placeholder="Enter Project Description" name="settings_description">Experience share market at your fingertips with TICK PRO stock investment mobile trading app</textarea>
												<!--end::Input-->
											</div>
											<!--end::Input group-->
											<!--begin::Input group-->
											<div class="fv-row mb-8">
												<!--begin::Label-->
												<label class="required fs-6 fw-semibold mb-2">Release Date</label>
												<!--end::Label-->
												<!--begin::Wrapper-->
												<div class="position-relative d-flex align-items-center">
													<!--begin::Icon-->
													<i class="ki-duotone ki-calendar-8 fs-2 position-absolute mx-4">
														<span class="path1"></span>
														<span class="path2"></span>
														<span class="path3"></span>
														<span class="path4"></span>
														<span class="path5"></span>
														<span class="path6"></span>
													</i>
													<!--end::Icon-->
													<!--begin::Input-->
													<input class="form-control form-control-solid ps-12" placeholder="Pick date range" name="settings_release_date" />
													<!--end::Input-->
												</div>
												<!--end::Wrapper-->
											</div>
											<div class="fv-row mb-8">
												<!--begin::Label-->
												<label class="required fs-6 fw-semibold mb-2">Estimated Completion Date</label>
												<!--end::Label-->
												<!--begin::Wrapper-->
												<div class="position-relative d-flex align-items-center">
													<!--begin::Icon-->
													<i class="ki-duotone ki-calendar-8 fs-2 position-absolute mx-4">
														<span class="path1"></span>
														<span class="path2"></span>
														<span class="path3"></span>
														<span class="path4"></span>
														<span class="path5"></span>
														<span class="path6"></span>
													</i>
													<!--end::Icon-->
													<!--begin::Input-->
													<input class="form-control form-control-solid ps-12" placeholder="Pick date range" name="settings_due_date" />
													<!--end::Input-->
												</div>
												<!--end::Wrapper-->
											</div>
											<div class="d-flex flex-stack">
												<button type="button" class="btn btn-lg btn-light me-3" data-kt-element="settings-previous">Project Type</button>
												<button type="button" class="btn btn-lg btn-primary" data-kt-element="settings-next">
													<span class="indicator-label">Budget</span>
													<span class="indicator-progress">Please wait... 
													<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
												</button>
											</div>
											<!--end::Actions-->
										</div>
										<!--end::Wrapper-->
									</div>
									<!--end::Settings-->
									<!--begin::Budget-->
									<div data-kt-stepper-element="content">
										<!--begin::Wrapper-->
										<div class="w-100">
											<!--begin::Heading-->
											<div class="pb-10 pb-lg-12">
												<!--begin::Title-->
												<h1 class="fw-bold text-gray-900">Budget</h1>
												<!--end::Title-->
												<!--begin::Description-->
												<div class="text-muted fw-semibold fs-4">If you need more info, please check 
												<a href="#" class="link-primary">Project Guidelines</a></div>
												<!--end::Description-->
											</div>
											<!--end::Heading-->
											<!--begin::Input group-->
											<div class="fv-row mb-8">
												<!--begin::Label-->
												<label class="d-flex align-items-center fs-6 fw-semibold mb-2">
													<span class="required">Setup Budget</span>
													<span class="lh-1 ms-1" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-html="true" data-bs-content="&lt;div class=&#039;p-4 rounded bg-light&#039;&gt; &lt;div class=&#039;d-flex flex-stack text-muted mb-4&#039;&gt; &lt;i class=&quot;ki-duotone ki-bank fs-3 me-3&quot;&gt;&lt;span class=&quot;path1&quot;&gt;&lt;/span&gt;&lt;span class=&quot;path2&quot;&gt;&lt;/span&gt;&lt;/i&gt; &lt;div class=&#039;fw-bold&#039;&gt;INCBANK **** 1245 STATEMENT&lt;/div&gt; &lt;/div&gt; &lt;div class=&#039;d-flex flex-stack fw-semibold text-gray-600&#039;&gt; &lt;div&gt;Amount&lt;/div&gt; &lt;div&gt;Transaction&lt;/div&gt; &lt;/div&gt; &lt;div class=&#039;separator separator-dashed my-2&#039;&gt;&lt;/div&gt; &lt;div class=&#039;d-flex flex-stack text-gray-900 fw-bold mb-2&#039;&gt; &lt;div&gt;USD345.00&lt;/div&gt; &lt;div&gt;KEENTHEMES*&lt;/div&gt; &lt;/div&gt; &lt;div class=&#039;d-flex flex-stack text-muted mb-2&#039;&gt; &lt;div&gt;USD75.00&lt;/div&gt; &lt;div&gt;Hosting fee&lt;/div&gt; &lt;/div&gt; &lt;div class=&#039;d-flex flex-stack text-muted&#039;&gt; &lt;div&gt;USD3,950.00&lt;/div&gt; &lt;div&gt;Payrol&lt;/div&gt; &lt;/div&gt; &lt;/div&gt;">
														<i class="ki-duotone ki-information-5 text-gray-500 fs-6">
															<span class="path1"></span>
															<span class="path2"></span>
															<span class="path3"></span>
														</i>
													</span>
												</label>
												<!--end::Label-->
												<!--begin::Dialer-->
												<div class="position-relative w-lg-250px" id="kt_modal_create_project_budget_setup" data-kt-dialer="true" data-kt-dialer-min="0" data-kt-dialer-max="" data-kt-dialer-step="100" data-kt-dialer-prefix="" data-kt-dialer-decimals="">
													<!--begin::Decrease control-->
													<button type="button" class="btn btn-icon btn-active-color-gray-700 position-absolute translate-middle-y top-50 start-0" data-kt-dialer-control="decrease">
														<i class="ki-duotone ki-minus-circle fs-1">
															<span class="path1"></span>
															<span class="path2"></span>
														</i>
													</button>
													<!--end::Decrease control-->
													<!--begin::Input control-->
													<input type="text" class="form-control form-control-solid border-0 ps-12" data-kt-dialer-control="input" placeholder="Amount" name="budget_setup" value="$50" />
													<!--end::Input control-->
													<!--begin::Increase control-->
													<button type="button" class="btn btn-icon btn-active-color-gray-700 position-absolute translate-middle-y top-50 end-0" data-kt-dialer-control="increase">
														<i class="ki-duotone ki-plus-circle fs-1">
															<span class="path1"></span>
															<span class="path2"></span>
														</i>
													</button>
													<!--end::Increase control-->
												</div>
												<!--end::Dialer-->
											</div>
											<!--end::Input group-->
											<!--begin::Input group-->
											<div class="fv-row mb-8">
												<!--begin::Label-->
												<label class="fs-6 fw-semibold mb-2">Budget Usage</label>
												<!--end::Label-->
												<!--begin::Row-->
												<div class="row g-9" data-kt-buttons="true" data-kt-buttons-target="[data-kt-button='true']">
													<!--begin::Col-->
													<div class="col-md-6 col-lg-12 col-xxl-6">
														<!--begin::Option-->
														<label class="btn btn-outline btn-outline-dashed btn-active-light-primary active d-flex text-start p-6" data-kt-button="true">
															<!--begin::Radio-->
															<span class="form-check form-check-custom form-check-solid form-check-sm align-items-start mt-1">
																<input class="form-check-input" type="radio" name="budget_usage" value="1" checked="checked" />
															</span>
															<!--end::Radio-->
															<!--begin::Info-->
															<span class="ms-5">
																<span class="fs-4 fw-bold text-gray-800 mb-2 d-block">Precise Usage</span>
																<span class="fw-semibold fs-7 text-gray-600">Withdraw money to your bank account per transaction under $50,000 budget</span>
															</span>
															<!--end::Info-->
														</label>
														<!--end::Option-->
													</div>
													<!--end::Col-->
													<!--begin::Col-->
													<div class="col-md-6 col-lg-12 col-xxl-6">
														<!--begin::Option-->
														<label class="btn btn-outline btn-outline-dashed btn-active-light-primary d-flex text-start p-6" data-kt-button="true">
															<!--begin::Radio-->
															<span class="form-check form-check-custom form-check-solid form-check-sm align-items-start mt-1">
																<input class="form-check-input" type="radio" name="budget_usage" value="2" />
															</span>
															<!--end::Radio-->
															<!--begin::Info-->
															<span class="ms-5">
																<span class="fs-4 fw-bold text-gray-800 mb-2 d-block">Extreme Usage</span>
																<span class="fw-semibold fs-7 text-gray-600">Withdraw money to your bank account per transaction under $50,000 budget</span>
															</span>
															<!--end::Info-->
														</label>
														<!--end::Option-->
													</div>
													<!--end::Col-->
												</div>
												<!--end::Row-->
											</div>
											<!--end::Input group-->
											<!--begin::Actions-->
											<div class="d-flex flex-stack">
												<button type="button" class="btn btn-lg btn-light me-3" data-kt-element="budget-previous">Project Settings</button>
												<button type="button" class="btn btn-lg btn-primary" data-kt-element="budget-next">
													<span class="indicator-label">Build Team</span>
													<span class="indicator-progress">Please wait... 
													<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
												</button>
											</div>
											<!--end::Actions-->
										</div>
										<!--end::Wrapper-->
									</div>
									<!--end::Budget-->
									<!--begin::Team-->
									<div data-kt-stepper-element="content">
										<!--begin::Wrapper-->
										<div class="w-100">
											<!--begin::Heading-->
											<div class="pb-12">
												<!--begin::Title-->
												<h1 class="fw-bold text-gray-900">Build a Team</h1>
												<!--end::Title-->
												<!--begin::Description-->
												<div class="text-muted fw-semibold fs-4">If you need more info, please check 
												<a href="#" class="link-primary">Project Guidelines</a></div>
												<!--end::Description-->
											</div>
											<!--end::Heading-->
											<div class="fv-row mb-8">
												<label class="required fs-6 fw-semibold mb-2">Tags</label>
												<input class="form-control form-control-solid" value="Important, Urgent" name="target_tags" />
											</div>
											<!--begin::Actions-->
											<div class="d-flex flex-stack">
                                                <button type="button" class="btn btn-lg btn-light me-3" data-kt-element="team-previous" id="budget_button">Budget</button>
                                                <button type="button" id="project_details_form_submit" class="btn btn-primary">
                                                    <span class="indicator-label">Submit</span>
                                                    <span class="indicator-progress">Please wait... 
                                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                                </button>
                                                <div id="response_message" class="text-center mt-3"></div>
                                                <button type="button" class="btn btn-lg btn-primary" data-kt-element="team-next" id="complete_button" style="display: none;">
                                                    <span class="indicator-label">Complete</span>
                                                    <span class="indicator-progress">Please wait...
                                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                                </button>
											</div>
											<!--end::Actions-->
										</div>
										<!--end::Wrapper-->
									</div>
									<!--end::Team-->
									<!--begin::Complete-->
									<div data-kt-stepper-element="content">
										<!--begin::Wrapper-->
										<div class="w-100">
											<!--begin::Heading-->
											<div class="pb-12 text-center">
												<!--begin::Title-->
												<h1 class="fw-bold text-gray-900">Project Created!</h1>
												<!--end::Title-->
												<!--begin::Description-->
												<div class="text-muted fw-semibold fs-4">If you need more info, please check how to create project</div>
												<!--end::Description-->
											</div>
											<!--end::Heading-->
											<!--begin::Actions-->
											<div class="d-flex flex-center pb-20">
												<button type="button" class="btn btn-lg btn-light me-3" data-kt-element="complete-start" data-bs-toggle="modal" data-bs-target="#kt_modal_create_project">Create New Project</button>
												<a href="projects.php" class="btn btn-lg btn-primary" data-bs-toggle="tooltip" title="Coming Soon">View Project</a>
											</div>
											<!--end::Actions-->
											<!--begin::Illustration-->
											<div class="text-center px-4">
												<img src="assets/media/illustrations/sketchy-1/9.png" alt="" class="mww-100 mh-350px" />
											</div>
											<!--end::Illustration-->
										</div>
									</div>
									<!--end::Complete-->
								</form>
								<!--end::Form-->
							</div>
							<!--begin::Container-->
						</div>
						<!--end::Stepper-->
					</div>
					<!--end::Modal body-->
				</div>
				<!--end::Modal content-->
			</div>
			<!--end::Modal dialog-->
		</div>
		<!--begin::Javascript-->
		<script>var hostUrl = "assets/";</script>
		<!--begin::Global Javascript Bundle(mandatory for all pages)-->
		<script src="assets/plugins/global/plugins.bundle.js"></script>
		<script src="assets/js/scripts.bundle.js"></script>
		<!--end::Global Javascript Bundle-->
		<!--begin::Vendors Javascript(used for this page only)-->
		<script src="assets/plugins/custom/datatables/datatables.bundle.js"></script>
		<script src="assets/plugins/custom/vis-timeline/vis-timeline.bundle.js"></script>
		<script src="https://cdn.amcharts.com/lib/5/index.js"></script>
		<script src="https://cdn.amcharts.com/lib/5/xy.js"></script>
		<script src="https://cdn.amcharts.com/lib/5/percent.js"></script>
		<script src="https://cdn.amcharts.com/lib/5/radar.js"></script>
		<script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script>
		<!--end::Vendors Javascript-->
		<!--begin::Custom Javascript(used for this page only)-->
		<script src="assets/js/widgets.bundle.js"></script>
		<script src="assets/js/custom/widgets.js"></script>
		<script src="assets/js/custom/apps/chat/chat.js"></script>
		<script src="assets/js/custom/utilities/modals/upgrade-plan.js"></script>
		<script src="assets/js/custom/utilities/modals/create-project/type.js"></script>
		<script src="assets/js/custom/utilities/modals/create-project/budget.js"></script>
		<script src="assets/js/custom/utilities/modals/create-project/settings.js"></script>
		<script src="assets/js/custom/utilities/modals/create-project/team.js"></script>
		<script src="assets/js/custom/utilities/modals/create-project/targets.js"></script>
		<script src="assets/js/custom/utilities/modals/create-project/files.js"></script>
		<script src="assets/js/custom/utilities/modals/create-project/complete.js"></script>
		<script src="assets/js/custom/utilities/modals/create-project/main.js"></script>
		<script src="assets/js/custom/utilities/modals/create-app.js"></script>
		<script src="assets/js/custom/utilities/modals/new-address.js"></script>
		<script src="assets/js/custom/utilities/modals/users-search.js"></script>
		<!--end::Custom Javascript-->
        <script>
			document.getElementById('project_details_form_submit').addEventListener('click', function(event) {
				// Initialize Dropzone
				var myDropzone = Dropzone.forElement("#kt_modal_create_project_settings_logo");

				// Prevent default form submission
				event.preventDefault();

				// Collect regular form data
				var formData = new FormData(document.getElementById('kt_modal_create_project_form'));

				// Get uploaded files from Dropzone and add to formData
				myDropzone.getAcceptedFiles().forEach(function(file) {
					formData.append('logo', file); // Assuming 'logo' is the correct input name in your PHP
				});

				// Send the form data (including the uploaded file)
				fetch('projectSubmit.php', {
					method: 'POST',
					body: formData
				})
				.then(response => response.text())
				.then(data => {
					// Display the response message
					document.getElementById('response_message').innerText = data;

					// Make Budget and Complete buttons visible
					document.getElementById('complete_button').style.display = 'inline-block';
					document.getElementById('project_details_form_submit').style.display = 'none';
				})
				.catch(error => {
					console.error('Error:', error);
					document.getElementById('response_message').innerText = 'An error occurred. Please try again.';
				});
			});
		</script>
		<!--end::Javascript-->
	</body>
	<!--end::Body-->
</html>
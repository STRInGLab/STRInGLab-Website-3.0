<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-SQW6G6NF3G"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-SQW6G6NF3G');
</script>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>S.T.R.In.G - Admin Panel</title>
    <!-- Font Icons -->
    <link media="all" rel="stylesheet" href="css/fonts/icomoon/icomoon.css">
    <link media="all" rel="stylesheet" href="css/fonts/roxine-font-icon/roxine-font.css">
    <link media="all" rel="stylesheet" href="vendors/font-awesome/css/font-awesome.css">
    <!-- Vendors -->
    <link media="all" rel="stylesheet" href="vendors/owl-carousel/dist/assets/owl.carousel.min.css">
    <link media="all" rel="stylesheet" href="vendors/owl-carousel/dist/assets/owl.theme.default.min.css">
    <link media="all" rel="stylesheet" href="vendors/animate/animate.css">
    <link media="all" rel="stylesheet" href="vendors/rateyo/jquery.rateyo.css">
    <link media="all" rel="stylesheet" href="vendors/bootstrap-datepicker/css/bootstrap-datepicker.css">
    <link media="all" rel="stylesheet" href="vendors/fancyBox/source/jquery.fancybox.css">
    <link media="all" rel="stylesheet" href="vendors/fancyBox/source/helpers/jquery.fancybox-thumbs.css">
    <!-- Bootstrap 4 -->
    <link media="all" rel="stylesheet" href="css/bootstrap.css">
    <!-- Rev Slider -->
    <link rel="stylesheet" type="text/css" href="vendors/rev-slider/revolution/css/settings.css">
    <link rel="stylesheet" type="text/css" href="vendors/rev-slider/revolution/css/layers.css">
    <link rel="stylesheet" type="text/css" href="vendors/rev-slider/revolution/css/navigation.css">
    <!-- Theme CSS -->
    <link media="all" rel="stylesheet" href="css/main.css">
    <!-- Custom CSS -->
    <link media="all" rel="stylesheet" href="css/custom.css">
</head>

<body>
    <!-- main wrapper -->
    <div id="wrapper">
        <div class="page-wrapper">
            <main>
                <div class="content-wrapper">
                    <div class="row  no-gutters">
                        <div class="col-lg-6 hidden-md-down">
                            <div class="bg-stretch img-wrap">
                                <img src="https://stringlabspace.blr1.cdn.digitaloceanspaces.com/signin-bg.jpg" alt="images">
                            </div>
                        </div>
                        <div class="col-lg-6 signup-block">
                            <div class="signup-wrap text-center">
                                <div class="inner-wrap">
                                    <div class="circular-icon bottom-space"><i class="icon-sign-in"></i></div>
                                    <form action="login.php" method="post" id="contact_form" class="waituk_contact-form signup-form">
                                        <h2 class="bottom-space">User Login</h2>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <input type="text" placeholder="USERNAME OR EMAIL" id="con_uname" name="username" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <input type="password" placeholder="PASSWORD" id="con_password" name="password" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="waituk_select-box">
                                                    <div class="waituk_select-box-default square-box">
                                                        <input type="checkbox" name="remember_me" id="checkbox11">
                                                        <label for="checkbox11" class="m-0">REMEMBER ME</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <p><a href="reset_password.php">Forgot Password ?</a></p>
                                            </div>
                                        </div>
                                        <div class="btn-container mb-3  mb-xl-3 mt-xl-5 mt-lg-2">
                                            <button id="btn_sent" class="btn btn-primary has-radius-small" type="submit">Login</button>
                                        </div>
                                    </form>                                    
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!--/main content wrapper -->
            </main>
        </div>
    </div>
    <!-- search form wrapper -->
    <div class="search-form-wrapper">
        <a href="#" class="nav-search-link close"><span class="icon-android-close"></span></a>
        <div class="holder">
            <input type="search" class="form-control form-control-v1" placeholder="Enter Your Search">
            <button type="submit"><span class="custom-icon-search"></span></button>
        </div>
    </div>
    <a href="#" class="section-scroll" id="scroll-to-top"><i class="fa fa-angle-up"></i></a>
    <!-- jquery library -->
    <script src="vendors/jquery/jquery-2.1.4.min.js"></script>
    <!-- external scripts -->
    <script src="vendors/tether/dist/js/tether.min.js"></script>
    <script src="vendors/bootstrap/js/bootstrap.min.js"></script>
    <script src="vendors/stellar/jquery.stellar.min.js"></script>
    <script src="vendors/isotope/javascripts/isotope.pkgd.min.js"></script>
    <script src="vendors/isotope/javascripts/packery-mode.pkgd.js"></script>
    <script src="vendors/owl-carousel/dist/owl.carousel.js"></script>
    <script src="vendors/waypoint/waypoints.min.js"></script>
    <script src="vendors/counter-up/jquery.counterup.min.js"></script>
    <script src="vendors/fancyBox/source/jquery.fancybox.pack.js"></script>
    <script src="vendors/fancyBox/source/helpers/jquery.fancybox-thumbs.js"></script>
    <script src="vendors/image-stretcher-master/image-stretcher.js"></script>
    <script src="vendors/wow/wow.min.js"></script>
    <script src="vendors/rateyo/jquery.rateyo.js"></script>
    <script src="vendors/bootstrap-datepicker/js/bootstrap-datepicker.js"></script>
    <script src="vendors/bootstrap-slider-master/src/js/bootstrap-slider.js"></script>
    <script src="vendors/bootstrap-select/dist/js/bootstrap-select.min.js"></script>
    <script src="js/mega-menu.js"></script>
    <!-- custom jquery script -->
    <script src="js/jquery.main.js"></script>
    <!-- REVOLUTION JS FILES -->
    <script type="text/javascript" src="vendors/rev-slider/revolution/js/jquery.themepunch.tools.min.js"></script>
    <script type="text/javascript" src="vendors/rev-slider/revolution/js/jquery.themepunch.revolution.min.js"></script>
    <!-- SLIDER REVOLUTION 5.0 EXTENSIONS  (Load Extensions only on Local File Systems !  The following part can be removed on Server for On Demand Loading) -->
    <script type="text/javascript" src="vendors/rev-slider/revolution/js/extensions/revolution.extension.actions.min.js"></script>
    <script type="text/javascript" src="vendors/rev-slider/revolution/js/extensions/revolution.extension.carousel.min.js"></script>
    <script type="text/javascript" src="vendors/rev-slider/revolution/js/extensions/revolution.extension.kenburn.min.js"></script>
    <script type="text/javascript" src="vendors/rev-slider/revolution/js/extensions/revolution.extension.layeranimation.min.js"></script>
    <script type="text/javascript" src="vendors/rev-slider/revolution/js/extensions/revolution.extension.migration.min.js"></script>
    <script type="text/javascript" src="vendors/rev-slider/revolution/js/extensions/revolution.extension.navigation.min.js"></script>
    <script type="text/javascript" src="vendors/rev-slider/revolution/js/extensions/revolution.extension.parallax.min.js"></script>
    <script type="text/javascript" src="vendors/rev-slider/revolution/js/extensions/revolution.extension.slideanims.min.js"></script>
    <script type="text/javascript" src="vendors/rev-slider/revolution/js/extensions/revolution.extension.video.min.js"></script>
    <!-- SNOW ADD ON -->
    <script type="text/javascript" src="vendors/rev-slider/revolution-addons/snow/revolution.addon.snow.min.js"></script>
    <!-- revolutions slider script -->
    <script src="js/revolution.js"></script>
</body>

</html>

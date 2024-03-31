<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// start the session
session_start();

// check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    // if not logged in, redirect to signin.php
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
    
    $invoiceId = $_GET['invoiceNo']; // Assuming you are passing the ID in the URL
    
    $stmt = $pdo->prepare('SELECT * FROM Invoice WHERE invoiceNo = ?');
    $stmt->execute([$invoiceId]);
    $invoice = $stmt->fetch();
    
    $stmt = $pdo->prepare('SELECT * FROM InvoiceData WHERE invoiceNo = ?');
    $stmt->execute([$invoiceId]);
    $invoiceData = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html class="no-js" lang="en">

<head>
  <!-- Meta Tags -->
  <meta charset="utf-8">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="author" content="Laralink">
  <!-- Site Title -->
  <title>Invoice Module</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <!-- Digital PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>

<body>
  <div class="tm_container">
    <div class="tm_invoice_wrap">
      <div class="tm_invoice tm_style3" id="tm_download_section">
        <div class="tm_invoice_in">
          <div class="tm_invoice_head tm_align_center tm_accent_bg">
            <div class="tm_invoice_left">
              <div class="tm_logo"><img src="https://stringspace.s3.ap-south-1.amazonaws.com/string-fullform-logo.png" alt="Logo" width="350"></div>
            </div>
            <div class="tm_invoice_right">
              <div class="tm_head_address tm_white_color">
                STRINGLAB TECHNOLOGY SOLUTIONS PVT. LTD<br>
                B-29, SAIDARSHAN CHS LTD, PLOT NO. 98,<br>
                Maharashtra, 400091. <br>
                GSTIN: 27ABKCS2127E1ZG
              </div>
            </div>
            <div class="tm_primary_color tm_text_uppercase tm_watermark_title tm_white_color"><?php echo $invoice['invoiceOrQuote']; ?></div>
          </div>
          <div class="tm_invoice_info">
            <div class="tm_invoice_info_left tm_gray_bg">
              <p class="tm_mb2"><b class="tm_primary_color"><?php echo $invoice['invoiceOrQuote']; ?> To:</b></p>
              <p class="tm_mb0">
                <?php 
                    $words = explode(' ', $invoice['invoiceTo']);
                    $modifiedSentence = '';
                    foreach ($words as $index => $word) {
                        $modifiedSentence .= $word . ' ';
                        if (($index + 1) % 5 == 0 && $index != count($words) - 1) {
                            $modifiedSentence .= '<br>';
                        }
                    }
                    echo rtrim($modifiedSentence);
                ?>
              </p>
            </div>
            <div class="tm_invoice_info_right tm_text_right">
              <p class="tm_invoice_number tm_m0">Project: <b class="tm_primary_color"><?php echo $invoice['invoiceName']; ?></b></p>
              <p class="tm_invoice_number tm_m0"><?php echo $invoice['invoiceOrQuote']; ?> ID: <b class="tm_primary_color"><?php echo $invoice['invoiceNo']; ?></b></p>
              <p class="tm_invoice_date tm_m0">Date: <b class="tm_primary_color"><?php echo (new DateTime($invoice['date']))->format('d-m-Y'); ?></p></b></p>
            </div>
          </div>
          <div class="tm_invoice_details">
            <div class="tm_table tm_style1 tm_mb30">
              <div class="tm_border">
                <div class="tm_table_responsive">
                  <table class="tm_gray_bg">
                    <thead>
                      <tr>
                        <th class="tm_width_5 tm_semi_bold tm_white_color tm_accent_bg">Description</th>
                        <th class="tm_width_1 tm_semi_bold tm_white_color tm_accent_bg tm_border_left tm_text_center">Qty</th>
                        <th class="tm_width_2 tm_semi_bold tm_white_color tm_accent_bg tm_border_left tm_text_center">Price</th>
                        <th class="tm_width_2 tm_semi_bold tm_white_color tm_accent_bg tm_border_left tm_text_center">Discount</th>
                        <th class="tm_width_2 tm_semi_bold tm_white_color tm_accent_bg tm_border_left tm_text_right">Total</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php 
                      $sumTotal = 0;
                        foreach ($invoiceData as $item): 
                        $finalPrice = ($item['qty'] * $item['price']) - $item['discount'];
                        $sumTotal += $finalPrice;
                      ?>
                            <tr>
                                <td><?php echo $item['description']; ?></td>
                                <td class="tm_text_center"><?php echo $item['qty']; ?></td>
                                <td class="tm_text_center">₹<?php echo $item['price']; ?></td>
                                <td class="tm_text_center">₹<?php echo $item['discount']; ?></td>
                                <td class="tm_text_right">₹<?php echo ($item['qty'] * $item['price']) - $item['discount']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
              <div class="tm_invoice_footer">
                <div class="tm_left_footer">
                  <p class="tm_mb2"><b class="tm_primary_color">Payment info:</b></p>
                  <p class="tm_m0"><?php echo nl2br($invoice['paymentInfo']); ?></p>
                </div>
                <div class="tm_right_footer">
                  <table class="tm_gray_bg">
                      <tbody>
                        <?php if ($invoice['invoiceOrQuote'] == 'Receipt'): ?>
                          <tr class="tm_border_top tm_border_bottom tm_accent_bg">
                              <td class="tm_width_3 tm_border_top_0 tm_bold tm_f16 tm_white_color">Paid	</td>
                              <td class="tm_width_3 tm_border_top_0 tm_bold tm_f16 tm_white_color tm_text_right">₹ <?php echo $sumTotal; ?>
                              </td>
                          </tr>
                        <?php else: ?>
                          <tr>
                              <td class="tm_width_3 tm_primary_color tm_bold">Subtotal</td>
                              <td class="tm_width_3 tm_primary_color tm_text_right tm_bold">₹ <?php echo $sumTotal; ?></td>
                          </tr>
                          
                          <!-- Conditional tax calculations based on Intra-state or Inter-state -->
                          <?php if ($invoice['taxType'] == 'Intra-state'): ?>
                              <tr>
                                  <td class="tm_width_3 tm_primary_color tm_bold">CGST (9%)</td>
                                  <td class="tm_width_3 tm_primary_color tm_text_right tm_bold">₹ <?php echo $sumTotal * 0.09;?></td>
                              </tr>
                              <tr>
                                  <td class="tm_width_3 tm_primary_color tm_bold">SGST (9%)</td>
                                  <td class="tm_width_3 tm_primary_color tm_text_right tm_bold">₹ <?php echo $sumTotal * 0.09;?></td>
                              </tr>
                          <?php else: ?>
                              <tr>
                                  <td class="tm_width_3 tm_primary_color tm_bold">IGST (18%)</td>
                                  <td class="tm_width_3 tm_primary_color tm_text_right tm_bold">₹ <?php echo $sumTotal * 0.18;?></td>
                              </tr>
                          <?php endif; ?>
                          <tr>
                              <td class="tm_width_3 tm_primary_color tm_bold">Paid</td>
                              <td class="tm_width_3 tm_primary_color tm_bold tm_text_right">₹<?php echo $invoice['advancePaid']; ?></td>
                          </tr>
                          <tr class="tm_border_top tm_border_bottom tm_accent_bg">
                              <td class="tm_width_3 tm_border_top_0 tm_bold tm_f16 tm_white_color">Total	</td>
                              <td class="tm_width_3 tm_border_top_0 tm_bold tm_f16 tm_white_color tm_text_right">₹ <?php $taxAmount = ($invoice['taxType'] == 'Intra-state') ? $sumTotal * 0.18 : $sumTotal * 0.09 * 2;
                                echo $taxAmount + $sumTotal - $invoice['advancePaid'];?>
                              </td>
                          </tr>
                        <?php endif; ?>
                      </tbody>
                  </table>
              </div>
              </div>
            </div>
            <div class="tm_padd_15_20 tm_gray_bg">
              <p class="tm_mb5"><b class="tm_primary_color">Terms & Conditions:</b></p>
              <ul class="tm_m0 tm_note_list">
                <?php echo nl2br($invoice['terms']); ?>
              </ul>
            </div><!-- .tm_note -->
          </div>
        </div>
      </div>
      <div class="tm_invoice_btns tm_hide_print">
        <a href="javascript:window.print()" class="tm_invoice_btn tm_color1">
          <span class="tm_btn_icon">
            <svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><path d="M384 368h24a40.12 40.12 0 0040-40V168a40.12 40.12 0 00-40-40H104a40.12 40.12 0 00-40 40v160a40.12 40.12 0 0040 40h24" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="32"/><rect x="128" y="240" width="256" height="208" rx="24.32" ry="24.32" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="32"/><path d="M384 128v-24a40.12 40.12 0 00-40-40H168a40.12 40.12 0 00-40 40v24" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="32"/><circle cx="392" cy="184" r="24" fill='currentColor'/></svg>
          </span>
          <span class="tm_btn_text">Print</span>
        </a>
        <button id="tm_download_btn" class="tm_invoice_btn tm_color2">
          <span class="tm_btn_icon">
            <svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><path d="M320 336h76c55 0 100-21.21 100-75.6s-53-73.47-96-75.6C391.11 99.74 329 48 256 48c-69 0-113.44 45.79-128 91.2-60 5.7-112 35.88-112 98.4S70 336 136 336h56M192 400.1l64 63.9 64-63.9M256 224v224.03" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/></svg>
          </span>
          <span class="tm_btn_text">Download</span>
        </button>
      </div>
    </div>
  </div>
  <script src="assets/js/jquery.min.js"></script>
  <script src="assets/js/jspdf.min.js"></script>
  <script src="assets/js/html2canvas.min.js"></script>
  <script src="assets/js/main.js"></script>
</body>
</html>
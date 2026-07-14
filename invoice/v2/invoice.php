<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['redirect_back'] = $_SERVER['REQUEST_URI'];
    header('Location: signin.php');
    exit;
}

require_once 'php/db.php';
require_once 'php/invoice_config.php';

$invoiceId = $_GET['invoiceNo'] ?? '';
if ($invoiceId === '') {
    http_response_code(400);
    echo 'Invoice number is required.';
    exit;
}

try {
    $pdo = getPdo();

    $stmt = $pdo->prepare('SELECT id, invoiceNo, invoiceOrQuote, name, invoiceTo, gst_number, invoiceName, date, taxType, advancePaid, paymentInfo, terms FROM Invoice WHERE invoiceNo = ?');
    $stmt->execute([$invoiceId]);
    $invoice = $stmt->fetch();

    if (!$invoice) {
        http_response_code(404);
        echo 'Invoice not found.';
        exit;
    }

    $stmt = $pdo->prepare('SELECT id, invoiceNo, description, qty, price, discount FROM InvoiceData WHERE invoiceNo = ?');
    $stmt->execute([$invoiceId]);
    $invoiceData = $stmt->fetchAll();
} catch (PDOException $e) {
    http_response_code(500);
    error_log('Invoice view error: ' . $e->getMessage());
    echo 'Unable to load invoice. Please try again later.';
    exit;
}

function e(string $text): string {
    // Decode entities that may have been stored by old htmlspecialchars() sanitization,
    // then re-escape safely for HTML output.
    $decoded = html_entity_decode($text ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return htmlspecialchars($decoded, ENT_QUOTES, 'UTF-8');
}

$taxType = InvoiceConfig::normalizeTaxType($invoice['taxType'] ?? '');
$isReceipt = ($invoice['invoiceOrQuote'] ?? '') === 'Receipt';

$sumTotal = 0;
foreach ($invoiceData as $item) {
    $sumTotal += ($item['qty'] * $item['price']) - $item['discount'];
}

$taxAmount = $isReceipt ? 0 : InvoiceConfig::taxAmount($sumTotal, $taxType);
$grandTotal = InvoiceConfig::grandTotal($sumTotal, $taxType, floatval($invoice['advancePaid'] ?? 0));
$displayDate = '';
if (!empty($invoice['date'])) {
    try {
        $displayDate = (new DateTime($invoice['date']))->format('d-m-Y');
    } catch (Exception $ex) {
        $displayDate = e($invoice['date']);
    }
}
?>
<!DOCTYPE html>
<html class="no-js" lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="author" content="Laralink">
  <title><?php echo e($invoice['invoiceOrQuote']); ?> - <?php echo e($invoice['invoiceNo']); ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .tm_invoice_to_text {
      max-width: 40ch;
      word-wrap: break-word;
      overflow-wrap: break-word;
      white-space: normal;
    }
    .tm_invoice_logo {
      max-width: 100%;
      height: auto;
      display: block;
    }
    .tm_invoice_left {
      flex: 0 0 50%;
      max-width: 50%;
    }
    .tm_invoice_right {
      flex: 0 0 45%;
      max-width: 45%;
      text-align: right;
    }
  </style>
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
              <div class="tm_logo"><img src="assets/media/logos/string-fullform-logo.png" alt="Logo" class="tm_invoice_logo" onerror="this.style.display='none'"></div>
            </div>
            <div class="tm_invoice_right">
              <div class="tm_head_address tm_white_color">
                STRINGLAB TECHNOLOGY SOLUTIONS PVT. LTD<br>
                B-29, SAIDARSHAN CHS LTD, PLOT NO. 98,<br>
                Maharashtra, 400091. <br>
                GSTIN: 27ABKCS2127E1ZG
              </div>
            </div>
            <div class="tm_primary_color tm_text_uppercase tm_watermark_title tm_white_color"><?php echo e($invoice['invoiceOrQuote']); ?></div>
          </div>
          <div class="tm_invoice_info">
            <div class="tm_invoice_info_left tm_gray_bg">
              <p class="tm_mb2"><b class="tm_primary_color"><?php echo e($invoice['invoiceOrQuote']); ?> To:</b></p>
              <p class="tm_mb0 tm_invoice_to_text">
                <?php echo nl2br(e($invoice['name'])); ?><br>
                <?php echo nl2br(e($invoice['invoiceTo'])); ?><br>
                GSTIN: <?php echo e($invoice['gst_number']); ?>
              </p>
            </div>
            <div class="tm_invoice_info_right tm_text_right">
              <p class="tm_invoice_number tm_m0">Project: <b class="tm_primary_color"><?php echo e($invoice['invoiceName']); ?></b></p>
              <p class="tm_invoice_number tm_m0"><?php echo e($invoice['invoiceOrQuote']); ?> ID: <b class="tm_primary_color"><?php echo e($invoice['invoiceNo']); ?></b></p>
              <p class="tm_invoice_date tm_m0">Date: <b class="tm_primary_color"><?php echo e($displayDate); ?></b></p>
              <p class="tm_invoice_date tm_m0">HSN/SAC: <b class="tm_primary_color"><?php echo e($invoice['hsnsac'] ?? '998314'); ?></b></p>
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
                      <?php foreach ($invoiceData as $item):
                        $lineTotal = ($item['qty'] * $item['price']) - $item['discount'];
                      ?>
                        <tr>
                          <td><?php echo e($item['description']); ?></td>
                          <td class="tm_text_center"><?php echo e($item['qty']); ?></td>
                          <td class="tm_text_center">₹<?php echo e($item['price']); ?></td>
                          <td class="tm_text_center">₹<?php echo e($item['discount']); ?></td>
                          <td class="tm_text_right">₹<?php echo number_format($lineTotal, 2); ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
              <div class="tm_invoice_footer">
                <div class="tm_left_footer">
                  <p class="tm_mb2"><b class="tm_primary_color">Payment info:</b></p>
                  <p class="tm_m0"><?php echo nl2br(e($invoice['paymentInfo'])); ?></p>
                </div>
                <div class="tm_right_footer">
                  <table class="tm_gray_bg">
                    <tbody>
                      <?php if ($isReceipt): ?>
                        <tr class="tm_border_top tm_border_bottom tm_accent_bg">
                          <td class="tm_width_3 tm_border_top_0 tm_bold tm_f16 tm_white_color">Paid</td>
                          <td class="tm_width_3 tm_border_top_0 tm_bold tm_f16 tm_white_color tm_text_right">₹ <?php echo number_format($sumTotal, 2); ?></td>
                        </tr>
                      <?php else: ?>
                        <tr>
                          <td class="tm_width_3 tm_primary_color tm_bold">Subtotal</td>
                          <td class="tm_width_3 tm_primary_color tm_text_right tm_bold">₹ <?php echo number_format($sumTotal, 2); ?></td>
                        </tr>
                        <?php if ($taxType === InvoiceConfig::INTRA_STATE): ?>
                          <tr>
                            <td class="tm_width_3 tm_primary_color tm_bold">CGST (<?php echo intval(InvoiceConfig::CGST_RATE * 100); ?>%)</td>
                            <td class="tm_width_3 tm_primary_color tm_text_right tm_bold">₹ <?php echo number_format($sumTotal * InvoiceConfig::CGST_RATE, 2); ?></td>
                          </tr>
                          <tr>
                            <td class="tm_width_3 tm_primary_color tm_bold">SGST (<?php echo intval(InvoiceConfig::SGST_RATE * 100); ?>%)</td>
                            <td class="tm_width_3 tm_primary_color tm_text_right tm_bold">₹ <?php echo number_format($sumTotal * InvoiceConfig::SGST_RATE, 2); ?></td>
                          </tr>
                        <?php else: ?>
                          <tr>
                            <td class="tm_width_3 tm_primary_color tm_bold">IGST (<?php echo intval(InvoiceConfig::IGST_RATE * 100); ?>%)</td>
                            <td class="tm_width_3 tm_primary_color tm_text_right tm_bold">₹ <?php echo number_format($sumTotal * InvoiceConfig::IGST_RATE, 2); ?></td>
                          </tr>
                        <?php endif; ?>
                        <tr>
                          <td class="tm_width_3 tm_primary_color tm_bold">Paid</td>
                          <td class="tm_width_3 tm_primary_color tm_bold tm_text_right">₹<?php echo number_format($invoice['advancePaid'] ?? 0, 2); ?></td>
                        </tr>
                        <tr class="tm_border_top tm_border_bottom tm_accent_bg">
                          <td class="tm_width_3 tm_border_top_0 tm_bold tm_f16 tm_white_color">Total</td>
                          <td class="tm_width_3 tm_border_top_0 tm_bold tm_f16 tm_white_color tm_text_right">₹ <?php echo number_format($grandTotal, 2); ?></td>
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
                <?php echo nl2br(e($invoice['terms'])); ?>
              </ul>
            </div>
          </div>
        </div>
      </div>
      <div class="tm_invoice_btns tm_hide_print">
        <a href="javascript:window.print()" class="tm_invoice_btn tm_color1" id="tm_print_btn">
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
  <script>
    document.getElementById('tm_print_btn').addEventListener('click', function() {
      this.classList.add('tm_disabled');
      setTimeout(function() { window.print(); }, 50);
      setTimeout(function() { document.getElementById('tm_print_btn').classList.remove('tm_disabled'); }, 1000);
    });
  </script>
</body>
</html>

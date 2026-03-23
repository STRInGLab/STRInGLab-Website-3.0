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
<html class="no-js" lang="en">

<head>
  <!-- Meta Tags -->
  <meta charset="utf-8">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="author" content="Laralink">
  <!-- Site Title -->
  <title>Invoicing Module</title>
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
            <div class="tm_primary_color tm_text_uppercase tm_watermark_title tm_white_color" style="font-size: 120px;">Invoicing</div>
          </div>
          <form action="store.php" method="POST">
            <div class="tm_invoice_info">
              <div class="tm_invoice_info_left tm_gray_bg">
                <p class="tm_mb2"><b class="tm_primary_color">To:</b></p>
                <textarea style="height: 60px; width: 200px;" type="text" name="invoiceTo" id="invoiceTo" required></textarea>
              </div>
              <div class="tm_invoice_info_right tm_text_right">
                <p class="tm_invoice_number tm_m0">Project: <input style="height: 20px; width: 200px;" type="text" name="invoiceName" id="invoiceName" required oninput="generateInvoiceID()"></p>
                <p class="tm_invoice_number tm_m0"><?php echo $invoice['invoiceOrQuote']; ?> ID: <input style="height: 20px; width: 200px;" type="text" name="invoiceNo" id="invoiceNo" value="<?php echo $newInvoiceNo; ?>" required></p>
                <p class="tm_invoice_date tm_m0">Date: <input style="height: 20px; width: 200px;" type="date" name="date" id="date" required></p>
                <p class="tm_invoice_date tm_m0">HSN/SAC: <input style="height: 20px; width: 200px;" type="text" name="hsnsac" id="hsnsac" value="998314" required></p>
              </div>
            </div>
            <div class="tm_invoice_details">
              <div class="tm_table tm_style1 tm_mb30">
                <div class="tm_border">
                  <div class="tm_table_responsive">
                    <table class="tm_gray_bg" id="data-table">
                      <thead>
                        <tr>
                          <th class="tm_width_5 tm_semi_bold tm_white_color tm_accent_bg tm_text_center">Description</th>
                          <th class="tm_width_1 tm_semi_bold tm_white_color tm_accent_bg tm_border_left tm_text_center">Qty</th>
                          <th class="tm_width_2 tm_semi_bold tm_white_color tm_accent_bg tm_border_left tm_text_center">Price</th>
                          <th class="tm_width_2 tm_semi_bold tm_white_color tm_accent_bg tm_border_left tm_text_center">Discount</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr>
                            <td><textarea style="width: 300px;" name="description[]" required></textarea></td>
                            <td><input style="height: 35px; width: 60px;" type="number" name="qty[]" required></td>
                            <td><input style="height: 35px; width: 85px;" type="number" name="price[]" required></td>
                            <td><input style="height: 35px; width: 75px;" type="number" name="discount[]" required></td>
                        </tr>
                      </tbody>
                    </table>
                    <button type="button" id="addRow">Add Row</button>
                  </div>
                </div>
                <div class="tm_invoice_footer">
                  <div class="tm_left_footer">
                    <p class="tm_mb2"><b class="tm_primary_color">Payment info:</b></p>
                    <textarea name="paymentInfo" id="paymentInfo" required></textarea>
                    <p class="tm_mb2" style="padding-top: 25px;"><b class="tm_primary_color">Type:</b></p>
                    <label style="display: inline-block; padding-right: 10px;">
                        <input type="radio" name="invoiceOrQuote" value="Invoice" checked> Invoice
                    </label>
                    <label style="display: inline-block; padding-left: 10px; padding-right: 10px;">
                        <input type="radio" name="invoiceOrQuote" value="Quote"> Quote
                    </label>
                    <label style="display: inline-block; padding-left: 10px;">
                        <input type="radio" name="invoiceOrQuote" value="Receipt"> Receipt
                    </label>
                    <p class="tm_mb2" style="padding-top: 25px;"><b class="tm_primary_color">Taxes:</b></p>
                    <label style="display: inline-block; padding-right: 10px;">
                        <input type="radio" name="taxType" value="Intra-state" checked> Intra-state
                    </label>
                    <label style="display: inline-block; padding-right: 10px;">
                        <input type="radio" name="taxType" value="Inter-state"> Inter-state
                    </label>
                  </div>
                  <div class="tm_right_footer">
                    <table class="tm_gray_bg">
                        <tbody>
                            <tr class="tm_border_top tm_border_bottom tm_accent_bg">
                                <td class="tm_width_3 tm_border_top_0 tm_bold tm_f16 tm_white_color">Advance Paid ₹</td>
                                <td class="tm_width_3 tm_border_top_0 tm_bold tm_f16 tm_white_color tm_text_right"><input style="height: 30px; width: 100px;" type="number" step="0.01" name="advancePaid" id="advancePaid" required>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                  </div>
                </div>
              </div>
              <div class="tm_padd_15_20 tm_gray_bg">
                <p class="tm_mb5"><b class="tm_primary_color">Terms & Conditions:</b></p>
                <textarea style="height: 100px; width: 100%;" name="terms" id="terms" required></textarea>
              </div>
            </div>
            <div class="tm_invoice_btns tm_hide_print">
              <button type="submit" class="tm_invoice_btn tm_color1">
                <span class="tm_btn_icon">
                  <svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><path d="M320 336h76c55 0 100-21.21 100-75.6s-53-73.47-96-75.6C391.11 99.74 329 48 256 48c-69 0-113.44 45.79-128 91.2-60 5.7-112 35.88-112 98.4S70 336 136 336h56M192 400.1l64 63.9 64-63.9M256 224v224.03" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/></svg>
                </span>
                <span class="tm_btn_text">Submit</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  <script src="assets/js/jquery.min.js"></script>
  <script src="assets/js/jspdf.min.js"></script>
  <script src="assets/js/html2canvas.min.js"></script>
  <script src="assets/js/main.js"></script>
  <script src="script.js"></script>
  <script>
    document.getElementById('addRow').addEventListener('click', function() {
      let table = document.getElementById('data-table').getElementsByTagName('tbody')[0];
      let newRow = table.insertRow();

      // Define the fields for the new row
      let fields = ['description', 'qty', 'price', 'discount'];

      fields.forEach(field => {
        let cell = newRow.insertCell();
        let element;
        if (field === 'description') {
          element = document.createElement('textarea');
          element.setAttribute('name', `${field}[]`);
          element.setAttribute('required', 'true');
          element.style.width = '300px';
        } else {
          element = document.createElement('input');
          element.setAttribute('type', 'number');
          element.setAttribute('name', `${field}[]`);
          element.setAttribute('required', 'true');

          // Set the style for each input field
          switch (field) {
            case 'qty':
              element.style.height = '35px';
              element.style.width = '60px';
              break;
            case 'price':
              element.style.height = '35px';
              element.style.width = '85px';
              break;
            case 'discount':
              element.style.height = '35px';
              element.style.width = '75px';
              break;
          }
        }
        cell.appendChild(element);
      });
    });
  </script>
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
</body>
</html>
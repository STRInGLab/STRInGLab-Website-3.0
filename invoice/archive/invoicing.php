<?php
    require_once 'config.php';
    
    session_start();
    if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
        header('Location: signin.php');
        exit;
    }
    
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title>Invoice/Quote/Receipt Entry</title>
    <style>
        body {
    font-family: Arial, sans-serif;
    margin: 2em;
        }
        
        label, input, textarea, button {
            display: block;
            margin-bottom: 1em;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            border: 1px solid #ccc;
            padding: 0.5em;
        }
        
        th {
            background-color: #eee;
        }
        
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            cursor: pointer;
        }
        
        button:hover {
            background-color: #45a049;
        }

        .form-group {
            display: inline-block;
            vertical-align: top; /* Align elements at the top */
            margin-right: 10px; /* Add some spacing between the elements */
        }

    </style>
</head>
<body>
    <h1 style="text-align: center;">Invoice Module</h1>
<form action="store.php" method="POST">
    <label for="invoiceTo">Invoice/Quote/Receipt To:</label>
    <textarea style="height: 60px; width: 200px;" type="text" name="invoiceTo" id="invoiceTo" required></textarea>

    <label for="invoiceName">Invoice/Quote Name:(Guidelines - {Client Name}-{Project})</label>
    <input style="height: 40px; width: 200px;" type="text" name="invoiceName" id="invoiceName" required oninput="generateInvoiceID()">
    
    <label for="invoiceNo">Invoice No:</label>
    <input type="text" name="invoiceNo" id="invoiceNo" value="<?php echo $newInvoiceNo; ?>" required>

    <label style="margin-top: 20px;" for="date">Date:</label>
    <input style="margin-bottom: 35px;" type="date" name="date" id="date" required>

    <table id="data-table">
        <thead>
            <tr>
                <th>Description</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Discount</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><input type="text" name="description[]" required></td>
                <td><input type="number" name="qty[]" required></td>
                <td><input type="number" name="price[]" required></td>
                <td><input type="number" name="discount[]" required></td>
                <td><input type="text" name="total[]" disabled></td>
            </tr>
        </tbody>
    </table>
    <button type="button" id="addRow">Add Row</button>

    <hr>
    <div class="form-group" style="padding-top: 25px;">
        <label for="advancePaid">Advance Paid (₹):</label>
        <input style="height: 40px; width: 100px;" type="number" step="0.01" name="advancePaid" id="advancePaid" required>
    </div>

    <div class="form-group" style="padding-top: 25px;">
        <label for="paymentInfo">Payment Info:</label>
        <textarea name="paymentInfo" id="paymentInfo" required></textarea>
    </div>

    <div class="form-group" style="padding-top: 25px;">
        <label for="terms">Terms & Conditions:</label>
        <textarea name="terms" id="terms" required></textarea>
    </div>

    <hr>
    <h3 style="padding-top: 25px;">Invoice or Quote:</h3>
    <div>
        <label style="display: inline-block; padding-right: 10px;">
            <input type="radio" name="invoiceOrQuote" value="Invoice" checked> Invoice
        </label>
        <label style="display: inline-block; padding-left: 10px; padding-right: 10px;">
            <input type="radio" name="invoiceOrQuote" value="Quote"> Quote
        </label>
        <label style="display: inline-block; padding-left: 10px;">
            <input type="radio" name="invoiceOrQuote" value="Receipt"> Receipt
        </label>
    </div>    
    <hr>

    <h3 style="padding-top: 25px;">Taxes:</h3>
    <div>
        <label style="display: inline-block; padding-right: 10px;">
            <input type="radio" name="taxType" value="Intra-state" checked> Intra-state
        </label>
        <label style="display: inline-block; padding-right: 10px;">
            <input type="radio" name="taxType" value="Inter-state"> Inter-state
        </label>
    </div>
    <hr>

    <input type="submit" value="Submit">
</form>

<script src="script.js"></script>
<script>
    document.getElementById('addRow').addEventListener('click', function() {
    let table = document.getElementById('data-table').getElementsByTagName('tbody')[0];
    let newRow = table.insertRow();

    let fields = ['description', 'qty', 'price', 'discount', 'total'];

    fields.forEach(field => {
        let cell = newRow.insertCell();
        let input = document.createElement('input');
        if (field === 'total') {
            input.setAttribute('disabled', 'true');
        } else {
            input.setAttribute('name', `${field}[]`);
        }
        cell.appendChild(input);
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

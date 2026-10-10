<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

include "config/database.php";

$billNo = intval($_GET['bill_no'] ?? 0);

$stmt = $conn->prepare("
    SELECT b.Bill_No, b.Bill_Date, b.Total_Amount, b.Status,
           c.Name AS Customer_Name, c.Phone,
           bi.Quantity, bi.Amount, m.Name AS Medicine_Name, m.Price
    FROM bills b
    JOIN customers c ON c.Cust_ID = b.Customer_ID
    JOIN bill_items bi ON bi.Bill_No = b.Bill_No
    JOIN medicines m ON m.Med_ID = bi.Medicine_ID
    WHERE b.Bill_No = ?
");
$stmt->bind_param("i", $billNo);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
$header = null;

while ($row = $result->fetch_assoc()) {
    if (!$header) $header = $row;
    $items[] = $row;
}

if (!$header) {
    die("Bill not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bill #<?php echo $header['Bill_No']; ?> — Medipoint</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="receipt-body">

  <div class="receipt">
    <div class="receipt-header">
      <h1>Medipoint Medical Stores</h1>
      <p>Near Ruby Hall Clinic, Pune</p>
    </div>

    <div class="receipt-meta">
      <div><strong>Bill No:</strong> #<?php echo $header['Bill_No']; ?></div>
      <div><strong>Date:</strong> <?php echo date('d M Y', strtotime($header['Bill_Date'])); ?></div>
      <div><strong>Customer:</strong> <?php echo htmlspecialchars($header['Customer_Name']); ?></div>
      <div><strong>Phone:</strong> <?php echo htmlspecialchars($header['Phone']); ?></div>
    </div>

    <table class="receipt-table">
      <thead>
        <tr><th>Medicine</th><th class="num">Qty</th><th class="num">Unit price</th><th class="num">Amount</th></tr>
      </thead>
      <tbody>
        <?php foreach ($items as $item): ?>
          <tr>
            <td><?php echo htmlspecialchars($item['Medicine_Name']); ?></td>
            <td class="num"><?php echo $item['Quantity']; ?></td>
            <td class="num">&#8377;<?php echo number_format($item['Price'], 2); ?></td>
            <td class="num">&#8377;<?php echo number_format($item['Amount'], 2); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="receipt-total">Total: &#8377;<?php echo number_format($header['Total_Amount'], 2); ?></div>
    <div class="receipt-status">Status: <?php echo htmlspecialchars($header['Status']); ?></div>

    <button class="btn btn-primary btn-block no-print" onclick="window.print()">Download / Print bill</button>
  </div>

</body>
</html>

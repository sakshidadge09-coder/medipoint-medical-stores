<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Session expired. Please log in again.']);
    exit;
}

include "config/database.php";

$customerID = $_POST['customer_id'] ?? '';
$medicineID = $_POST['medicine_id'] ?? '';
$quantity   = intval($_POST['quantity'] ?? 0);

if ($customerID === '' || $medicineID === '') {
    echo json_encode(['success' => false, 'message' => 'Please select a customer and a medicine.']);
    exit;
}

$stmt = $conn->prepare("SELECT Name, Price, Quantity, Expiry_Date FROM medicines WHERE Med_ID = ?");
$stmt->bind_param("s", $medicineID);
$stmt->execute();
$medicine = $stmt->get_result()->fetch_assoc();

if (!$medicine) {
    echo json_encode(['success' => false, 'message' => 'Medicine not found.']);
    exit;
}

$price       = $medicine['Price'];
$stock       = $medicine['Quantity'];
$expiryDate  = $medicine['Expiry_Date'];

if ($quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid quantity.']);
    exit;
}
if ($quantity > $stock) {
    echo json_encode(['success' => false, 'message' => 'Not enough stock available.']);
    exit;
}
if (strtotime($expiryDate) < strtotime(date('Y-m-d'))) {
    echo json_encode(['success' => false, 'message' => 'This medicine has expired and cannot be sold.']);
    exit;
}

$total = $price * $quantity;

$result = $conn->query("SELECT COALESCE(MAX(Bill_No), 0) + 1 AS next_bill FROM bills");
$bill = $result->fetch_assoc();
$billNo = $bill['next_bill'];

$stmt = $conn->prepare("
    INSERT INTO bills (Bill_No, Bill_Date, Customer_ID, Total_Amount, Status)
    VALUES (?, CURDATE(), ?, ?, 'Completed')
");
$stmt->bind_param("isd", $billNo, $customerID, $total);
$stmt->execute();

$stmt = $conn->prepare("
    INSERT INTO bill_items (Bill_No, Medicine_ID, Quantity, Amount)
    VALUES (?, ?, ?, ?)
");
$stmt->bind_param("isid", $billNo, $medicineID, $quantity, $total);
$stmt->execute();

$stmt = $conn->prepare("UPDATE medicines SET Quantity = Quantity - ? WHERE Med_ID = ?");
$stmt->bind_param("is", $quantity, $medicineID);
$stmt->execute();

$custStmt = $conn->prepare("SELECT Name FROM customers WHERE Cust_ID = ?");
$custStmt->bind_param("s", $customerID);
$custStmt->execute();
$custRow = $custStmt->get_result()->fetch_assoc();
$customerName = $custRow ? $custRow['Name'] : 'Unknown';

echo json_encode([
    'success'         => true,
    'bill_no'         => $billNo,
    'customer_name'   => $customerName,
    'medicine_name'   => $medicine['Name'],
    'quantity'        => $quantity,
    'unit_price'      => $price,
    'total'           => $total,
    'date'            => date('d M Y'),
    'remaining_stock' => $stock - $quantity
]);

$conn->close();
?>

<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

include "config/database.php";

function stockStatus($qty) {
    if ($qty <= 10) return ['crit', 'Critical'];
    if ($qty <= 50) return ['low', 'Low stock'];
    return ['ok', 'In stock'];
}

/* ---------- DASHBOARD STATS ---------- */

$totalMedicines = 0;
$result = $conn->query("SELECT COUNT(*) AS total FROM medicines");
if ($result) $totalMedicines = $result->fetch_assoc()['total'];

$totalSuppliers = 0;
$result = $conn->query("SELECT COUNT(*) AS total FROM suppliers");
if ($result) $totalSuppliers = $result->fetch_assoc()['total'];

/* ---------- SALES TREND (LAST 7 DAYS) ---------- */

$salesTrend = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i day"));
    $salesTrend[$d] = 0.0;
}
$result = $conn->query("
    SELECT Bill_Date, SUM(Total_Amount) AS total
    FROM bills
    WHERE Bill_Date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY Bill_Date
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $d = date('Y-m-d', strtotime($row['Bill_Date']));
        if (isset($salesTrend[$d])) $salesTrend[$d] = (float)$row['total'];
    }
}
$trendMax = max(array_merge($salesTrend, [1]));

$result = $conn->query("SELECT COALESCE(SUM(Total_Amount),0) AS total FROM bills WHERE Bill_Date = DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
$yesterdaySales = $result ? (float)$result->fetch_assoc()['total'] : 0.0;

/* ---------- TOP SELLERS ---------- */

$topSellers = [];
$result = $conn->query("
    SELECT m.Med_ID, m.Name, m.Category, m.Price, m.Quantity, SUM(bi.Quantity) AS Sold
    FROM bill_items bi
    JOIN medicines m ON m.Med_ID = bi.Medicine_ID
    GROUP BY bi.Medicine_ID
    ORDER BY Sold DESC
    LIMIT 6
");
if ($result) $topSellers = $result->fetch_all(MYSQLI_ASSOC);

$lowStockRows = [];
$result = $conn->query("SELECT Name, Quantity FROM medicines WHERE Quantity <= 50 ORDER BY Quantity ASC");
if ($result) $lowStockRows = $result->fetch_all(MYSQLI_ASSOC);

$expiryRows = [];
$result = $conn->query("SELECT Name, Expiry_Date FROM medicines WHERE Expiry_Date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY Expiry_Date ASC");
if ($result) $expiryRows = $result->fetch_all(MYSQLI_ASSOC);

/* ---------- MEDICINE STOCK ---------- */

$medicines = [];
$result = $conn->query("SELECT Med_ID, Name, Category, Batch_No, Expiry_Date, Quantity, Price FROM medicines ORDER BY Med_ID");
if ($result) $medicines = $result->fetch_all(MYSQLI_ASSOC);

$categoryFilter = $_GET['category'] ?? '';
$filteredMedicines = $categoryFilter === ''
    ? $medicines
    : array_values(array_filter($medicines, function ($m) use ($categoryFilter) {
          return isset($m['Category']) && $m['Category'] === $categoryFilter;
      }));

$showcaseMeds  = count($topSellers) > 0 ? $topSellers : array_slice($medicines, 0, 6);
$showcaseTitle = count($topSellers) > 0 ? 'Top sellers' : 'Medicine catalog';

/* ---------- SUPPLIERS ---------- */

$suppliers = [];
$result = $conn->query("SELECT Supp_ID, Name, Contact, Address FROM suppliers ORDER BY Supp_ID");
if ($result) $suppliers = $result->fetch_all(MYSQLI_ASSOC);

/* ---------- BILLING DATA ---------- */

$billingMeds = [];
$result = $conn->query("SELECT Med_ID, Name, Price, Quantity FROM medicines WHERE Quantity > 0 ORDER BY Name");
if ($result) $billingMeds = $result->fetch_all(MYSQLI_ASSOC);

$customers = [];
$result = $conn->query("SELECT Cust_ID, Name, Phone FROM customers ORDER BY Name");
if ($result) $customers = $result->fetch_all(MYSQLI_ASSOC);

/* ---------- SALES REPORTS ---------- */

$result = $conn->query("SELECT COALESCE(SUM(Total_Amount),0) AS total FROM bills WHERE Bill_Date = CURDATE()");
$dailySales = $result ? $result->fetch_assoc()['total'] : 0;

$trendPct = $yesterdaySales > 0 ? round((($dailySales - $yesterdaySales) / $yesterdaySales) * 100) : null;

$result = $conn->query("SELECT COALESCE(SUM(Total_Amount),0) AS total FROM bills WHERE YEARWEEK(Bill_Date,1) = YEARWEEK(CURDATE(),1)");
$weeklySales = $result ? $result->fetch_assoc()['total'] : 0;

$result = $conn->query("SELECT COALESCE(SUM(Total_Amount),0) AS total FROM bills WHERE YEAR(Bill_Date)=YEAR(CURDATE()) AND MONTH(Bill_Date)=MONTH(CURDATE())");
$monthlySales = $result ? $result->fetch_assoc()['total'] : 0;

$result = $conn->query("SELECT COUNT(*) AS total FROM bills");
$totalBills = $result ? $result->fetch_assoc()['total'] : 0;

$recentBills = [];
$result = $conn->query("
    SELECT b.Bill_No, b.Bill_Date, c.Name AS Customer_Name, m.Name AS Medicine_Name, bi.Quantity, bi.Amount
    FROM bills b
    JOIN bill_items bi ON bi.Bill_No = b.Bill_No
    JOIN customers c ON c.Cust_ID = b.Customer_ID
    JOIN medicines m ON m.Med_ID = bi.Medicine_ID
    ORDER BY b.Bill_No DESC
    LIMIT 25
");
if ($result) $recentBills = $result->fetch_all(MYSQLI_ASSOC);

$activeTab = $_GET['tab'] ?? 'dashboard';
$msg = $_GET['msg'] ?? '';
$msgText = [
    'medicine_added' => 'New medicine added to stock.',
    'stock_added'     => 'Stock quantity updated.',
][$msg] ?? '';
$errText = '';
if ($msg === 'medicine_error') {
    $errText = 'Could not add medicine: ' . ($_SESSION['stock_error'] ?? 'unknown error');
    unset($_SESSION['stock_error']);
}

function cls($tab, $active) { return 'section' . ($tab === $active ? ' active' : ''); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Medipoint Medical Stores</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="topbar">
  <div class="topbar-brand">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
      <path d="M4.5 14.5 14.5 4.5a5 5 0 1 1 7 7L11.5 21.5a5 5 0 0 1-7-7Z"/>
      <line x1="9" y1="10" x2="15" y2="16"/>
    </svg>
    <div class="topbar-brand-text">
      <strong>Medipoint</strong>
      <span>Near Ruby Hall Clinic, Pune</span>
    </div>
  </div>
  <div class="topbar-user">
    <span class="chip"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
    <a href="logout.php">Log out</a>
  </div>
</div>

<div id="shell">

  <div id="sidebar">
    <button class="nav-item" data-target="dashboard" onclick="showSection('dashboard')">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
      Dashboard
    </button>
    <button class="nav-item" data-target="billing" onclick="showSection('billing')">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="9" y1="12" x2="15" y2="12"/></svg>
      Bill Generation
    </button>
    <button class="nav-item" data-target="stock" onclick="showSection('stock')">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 14.5 14.5 4.5a5 5 0 1 1 7 7L11.5 21.5a5 5 0 0 1-7-7Z"/><line x1="9" y1="10" x2="15" y2="16"/></svg>
      Medicine Stock
    </button>
    <button class="nav-item" data-target="supplier" onclick="showSection('supplier')">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="13" height="10" rx="1.5"/><path d="M15 10h4l3 3v4h-7z"/><circle cx="7" cy="19" r="1.8"/><circle cx="17.5" cy="19" r="1.8"/></svg>
      Supplier Details
    </button>
    <button class="nav-item" data-target="reports" onclick="showSection('reports')">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="21" x2="5" y2="11"/><line x1="12" y1="21" x2="12" y2="6"/><line x1="19" y1="21" x2="19" y2="14"/></svg>
      Report Generation
    </button>
  </div>

  <div id="content">

    <!-- ============ DASHBOARD ============ -->
    <div id="dashboard" class="<?php echo cls('dashboard', $activeTab); ?>">
      <div class="welcome-banner">
        <div>
          <h1>Hello, <?php echo htmlspecialchars($_SESSION['username']); ?> &#128075;</h1>
          <p>Here's how Medipoint is doing today.</p>
        </div>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M4.5 14.5 14.5 4.5a5 5 0 1 1 7 7L11.5 21.5a5 5 0 0 1-7-7Z"/>
          <line x1="9" y1="10" x2="15" y2="16"/>
        </svg>
      </div>

      <div class="hero-row">
        <div class="hero-stat">
          <div class="hero-label">Today's sales</div>
          <div class="hero-number">&#8377;<?php echo number_format($dailySales, 2); ?></div>
          <?php if ($trendPct !== null): ?>
            <span class="trend-badge <?php echo $trendPct >= 0 ? 'trend-up' : 'trend-down'; ?>">
              <?php echo $trendPct >= 0 ? '&#9650;' : '&#9660;'; ?> <?php echo abs($trendPct); ?>% vs yesterday
            </span>
          <?php endif; ?>
        </div>
        <div class="stat-list">
          <div class="stat">
            <div class="stat-number"><?php echo $totalMedicines; ?></div>
            <div class="stat-label">Medicines</div>
          </div>
          <div class="stat">
            <div class="stat-number"><?php echo $totalSuppliers; ?></div>
            <div class="stat-label">Suppliers</div>
          </div>
          <div class="stat">
            <div class="stat-number"><?php echo $totalBills; ?></div>
            <div class="stat-label">Total bills</div>
          </div>
        </div>
      </div>

      <div class="chart-card">
        <div class="chart-head">
          <h2>Sales — last 7 days</h2>
          <span class="chart-total">&#8377;<?php echo number_format(array_sum($salesTrend), 2); ?> total</span>
        </div>
        <div class="bar-chart">
          <?php foreach ($salesTrend as $trendDate => $trendAmt):
              $barPct = $trendMax > 0 ? round(($trendAmt / $trendMax) * 100) : 0;
              $isToday = ($trendDate === date('Y-m-d'));
          ?>
            <div class="bar-col">
              <div class="bar-track">
                <div class="bar-fill<?php echo $isToday ? ' bar-today' : ''; ?>" style="height: <?php echo max($barPct, 3); ?>%;"></div>
              </div>
              <div class="bar-value">&#8377;<?php echo number_format($trendAmt, 0); ?></div>
              <div class="bar-label"><?php echo date('D', strtotime($trendDate)); ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <h2 class="section-subhead">Browse by category</h2>
      <div class="category-grid">
        <a class="category-tile cat-teal" href="dashboard.php?tab=stock&amp;category=<?php echo urlencode('Tablets & Capsules'); ?>">
          <svg viewBox="0 0 64 64"><rect x="8" y="24" width="30" height="16" rx="8" fill="var(--teal)"/><path d="M23 24h7a8 8 0 0 1 0 16h-7Z" fill="#fff"/><circle cx="46" cy="18" r="6" fill="var(--amber)"/><circle cx="50" cy="46" r="5" fill="var(--clay)"/></svg>
          <span>Tablets &amp; Capsules</span>
        </a>
        <a class="category-tile cat-amber" href="dashboard.php?tab=stock&amp;category=<?php echo urlencode('Syrups & Drops'); ?>">
          <svg viewBox="0 0 64 64"><rect x="22" y="10" width="12" height="6" fill="var(--amber)"/><path d="M20 16h16l4 8v26a4 4 0 0 1-4 4H20a4 4 0 0 1-4-4V24Z" fill="var(--amber)"/><rect x="18" y="32" width="20" height="10" fill="#fff" opacity="0.85"/><path d="M46 30c4 5 4 9 0 12-4-3-4-7 0-12Z" fill="var(--teal-bright)"/></svg>
          <span>Syrups &amp; Drops</span>
        </a>
        <a class="category-tile cat-clay" href="dashboard.php?tab=stock&amp;category=<?php echo urlencode('Personal Care'); ?>">
          <svg viewBox="0 0 64 64"><rect x="16" y="22" width="24" height="28" rx="6" fill="var(--clay)"/><rect x="22" y="12" width="12" height="12" rx="3" fill="var(--clay)"/><rect x="20" y="30" width="20" height="6" fill="#fff" opacity="0.85"/><circle cx="48" cy="20" r="5" fill="var(--amber)"/></svg>
          <span>Personal Care</span>
        </a>
        <a class="category-tile cat-plum" href="dashboard.php?tab=stock&amp;category=<?php echo urlencode('Baby & Mother Care'); ?>">
          <svg viewBox="0 0 64 64"><path d="M32 48S14 36 14 24a10 10 0 0 1 18-6 10 10 0 0 1 18 6c0 12-18 24-18 24Z" fill="var(--plum)"/><circle cx="46" cy="14" r="5" fill="var(--amber)"/></svg>
          <span>Baby &amp; Mother Care</span>
        </a>
        <a class="category-tile cat-sky" href="dashboard.php?tab=stock&amp;category=<?php echo urlencode('Devices & Monitors'); ?>">
          <svg viewBox="0 0 64 64"><rect x="8" y="16" width="40" height="28" rx="5" fill="var(--sky)"/><rect x="13" y="21" width="30" height="14" fill="#fff" opacity="0.9"/><polyline points="15,28 20,28 23,21 27,35 30,25 33,28 41,28" fill="none" stroke="var(--sky)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="50" cy="46" r="5" fill="var(--amber)"/></svg>
          <span>Devices &amp; Monitors</span>
        </a>
      </div>

      <h2 class="section-subhead"><?php echo htmlspecialchars($showcaseTitle); ?></h2>
      <div class="med-grid">
        <?php foreach ($showcaseMeds as $scIndex => $m): list($scStatusClass, ) = stockStatus($m['Quantity']); ?>
          <div class="med-card status-<?php echo $scStatusClass; ?>">
            <?php if ($scIndex === 0 && $showcaseTitle === 'Top sellers'): ?>
              <span class="badge-ribbon">Bestseller</span>
            <?php endif; ?>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 14.5 14.5 4.5a5 5 0 1 1 7 7L11.5 21.5a5 5 0 0 1-7-7Z"/><line x1="9" y1="10" x2="15" y2="16"/></svg>
            <div>
              <div class="med-card-name"><?php echo htmlspecialchars($m['Name']); ?></div>
              <?php if (!empty($m['Category'])): ?>
                <div class="med-card-category"><?php echo htmlspecialchars($m['Category']); ?></div>
              <?php endif; ?>
              <div class="med-card-price">&#8377;<?php echo number_format($m['Price'], 2); ?></div>
              <?php if (isset($m['Sold'])): ?>
                <span class="stock-dot"><?php echo $m['Sold']; ?> sold</span>
              <?php else: ?>
                <span class="stock-dot"><?php echo $m['Quantity']; ?> in stock</span>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="alert-panel">
        <div class="alert-panel-head">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 22 20H2Z"/><line x1="12" y1="9" x2="12" y2="14"/><line x1="12" y1="17" x2="12" y2="17.01"/></svg>
          Low stock alert
        </div>
        <?php if (count($lowStockRows) === 0): ?>
          <div class="empty-note">No medicines are currently low in stock.</div>
        <?php else: foreach ($lowStockRows as $row): ?>
          <div class="alert-row">
            <span><?php echo htmlspecialchars($row['Name']); ?></span>
            <span class="tag <?php echo $row['Quantity'] <= 10 ? 'tag-clay' : 'tag-amber'; ?>"><?php echo $row['Quantity']; ?> left</span>
          </div>
        <?php endforeach; endif; ?>
      </div>

      <div class="alert-panel">
        <div class="alert-panel-head">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 22 20H2Z"/><line x1="12" y1="9" x2="12" y2="14"/><line x1="12" y1="17" x2="12" y2="17.01"/></svg>
          Expired / expiring within 30 days
        </div>
        <?php if (count($expiryRows) === 0): ?>
          <div class="empty-note">No medicines are expired or expiring within the next 30 days.</div>
        <?php else: foreach ($expiryRows as $row): ?>
          <div class="alert-row">
            <span><?php echo htmlspecialchars($row['Name']); ?></span>
            <?php $isExpired = strtotime($row['Expiry_Date']) < strtotime(date('Y-m-d')); ?>
            <span class="tag <?php echo $isExpired ? 'tag-clay' : 'tag-amber'; ?>"><?php echo $isExpired ? 'Expired' : 'Expires'; ?> <?php echo date('d M Y', strtotime($row['Expiry_Date'])); ?></span>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>

    <!-- ============ BILL GENERATION ============ -->
    <div id="billing" class="<?php echo cls('billing', $activeTab); ?>">
      <div class="page-head">
        <h1>Bill Generation</h1>
        <p>Create a bill for a walk-in or returning customer.</p>
      </div>

      <div class="form-card">
        <form id="billingForm">

          <label for="customerSearch">Customer</label>
          <div class="combobox">
            <input type="text" id="customerSearch" placeholder="Type a name to search…" autocomplete="off">
            <input type="hidden" name="customer_id" id="bcustomer_id">
            <div class="combobox-list" id="customerList"></div>
          </div>

          <label for="bmedicine">Medicine</label>
          <select name="medicine_id" id="bmedicine" onchange="updatePrice()" required>
            <option value="">— Select medicine —</option>
            <?php foreach ($billingMeds as $m): ?>
              <option value="<?php echo htmlspecialchars($m['Med_ID']); ?>"
                      data-price="<?php echo $m['Price']; ?>"
                      data-stock="<?php echo $m['Quantity']; ?>">
                <?php echo htmlspecialchars($m['Name']); ?>
              </option>
            <?php endforeach; ?>
          </select>

          <div class="field-row">
            <div>
              <label for="bprice">Price</label>
              <input type="number" id="bprice" readonly>
            </div>
            <div>
              <label for="bstock">Available stock</label>
              <input type="number" id="bstock" readonly>
            </div>
          </div>

          <label for="bqty">Quantity</label>
          <input type="number" name="quantity" id="bqty" min="1" required>

          <p id="bill-error" style="color:var(--clay); font-size:0.875rem; margin-top:10px;"></p>

          <button type="submit" id="bill-submit-btn" class="btn btn-primary btn-block" style="margin-top:10px;">
            Generate bill
          </button>
        </form>
      </div>

      <div id="receiptStub" class="receipt-stub" style="display:none;"></div>
    </div>

    <!-- ============ MEDICINE STOCK ============ -->
    <div id="stock" class="<?php echo cls('stock', $activeTab); ?>">
      <div class="page-head">
        <h1>Medicine Stock</h1>
        <p>Browse, search, and manage inventory.</p>
      </div>

      <?php if ($msgText): ?>
        <div class="banner banner-success"><?php echo htmlspecialchars($msgText); ?></div>
      <?php endif; ?>
      <?php if ($errText): ?>
        <div class="banner banner-error"><?php echo htmlspecialchars($errText); ?></div>
      <?php endif; ?>

      <div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:16px;">
        <button class="btn btn-primary" onclick="openModal('addMedicineModal')">+ Add new medicine</button>
        <button class="btn btn-ghost" onclick="openModal('addStockModal')">+ Add stock</button>
      </div>

      <?php if ($categoryFilter !== ''): ?>
        <div class="filter-chip">
          Showing: <?php echo htmlspecialchars($categoryFilter); ?>
          <a href="dashboard.php?tab=stock">Clear filter</a>
        </div>
      <?php endif; ?>

      <input type="text" class="search-bar" placeholder="Search medicine…" onkeyup="searchMedicine(this.value)">

      <?php if (count($filteredMedicines) === 0): ?>
        <div class="empty-note">No medicines in this category yet.</div>
      <?php endif; ?>

      <div class="med-grid" id="stock-cards">
        <?php foreach ($filteredMedicines as $m): list($statusClass, $statusLabel) = stockStatus($m['Quantity']); ?>
          <div class="med-card status-<?php echo $statusClass; ?>" data-name="<?php echo htmlspecialchars($m['Name']); ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 14.5 14.5 4.5a5 5 0 1 1 7 7L11.5 21.5a5 5 0 0 1-7-7Z"/><line x1="9" y1="10" x2="15" y2="16"/></svg>
            <div>
              <div class="med-card-name"><?php echo htmlspecialchars($m['Name']); ?></div>
              <?php if (!empty($m['Category'])): ?>
                <div class="med-card-category"><?php echo htmlspecialchars($m['Category']); ?></div>
              <?php endif; ?>
              <div class="med-card-meta">Batch <?php echo htmlspecialchars($m['Batch_No']); ?> · Exp <?php echo date('M Y', strtotime($m['Expiry_Date'])); ?></div>
              <div class="med-card-price">&#8377;<?php echo number_format($m['Price'], 2); ?></div>
              <span class="stock-dot"><?php echo $statusLabel; ?> (<?php echo $m['Quantity']; ?>)</span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Name</th><th>Category</th><th>Batch No</th><th>Expiry</th><th class="num">Qty</th><th class="num">Price</th></tr>
          </thead>
          <tbody id="stock-table">
            <?php foreach ($filteredMedicines as $m): ?>
              <tr>
                <td><?php echo htmlspecialchars($m['Name']); ?></td>
                <td><?php echo htmlspecialchars($m['Category'] ?? '—'); ?></td>
                <td><?php echo htmlspecialchars($m['Batch_No']); ?></td>
                <td><?php echo date('d M Y', strtotime($m['Expiry_Date'])); ?></td>
                <td class="num"><?php echo $m['Quantity']; ?></td>
                <td class="num">&#8377;<?php echo number_format($m['Price'], 2); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ============ SUPPLIER DETAILS ============ -->
    <div id="supplier" class="<?php echo cls('supplier', $activeTab); ?>">
      <div class="page-head">
        <h1>Supplier Details</h1>
        <p>Contacts for medicine procurement.</p>
      </div>

      <div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:16px;">
        <button class="btn btn-primary" onclick="openModal('addSupplierModal')">+ Add supplier</button>
      </div>

      <div class="table-wrap">
        <table>
          <thead><tr><th>ID</th><th>Name</th><th>Contact</th><th>Address</th></tr></thead>
          <tbody id="supplier-table">
            <?php if (count($suppliers) === 0): ?>
              <tr><td colspan="4" class="empty-note">No suppliers on record yet.</td></tr>
            <?php else: foreach ($suppliers as $s): ?>
              <tr>
                <td><?php echo htmlspecialchars($s['Supp_ID']); ?></td>
                <td><?php echo htmlspecialchars($s['Name']); ?></td>
                <td><?php echo htmlspecialchars($s['Contact']); ?></td>
                <td><?php echo htmlspecialchars($s['Address']); ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ============ REPORT GENERATION ============ -->
    <div id="reports" class="<?php echo cls('reports', $activeTab); ?>">
      <div class="page-head">
        <h1>Report Generation</h1>
        <p>Sales performance and transaction history.</p>
      </div>

      <div class="hero-row">
        <div class="hero-stat">
          <div class="hero-label">This month's sales</div>
          <div class="hero-number">&#8377;<?php echo number_format($monthlySales, 2); ?></div>
        </div>
        <div class="stat-list">
          <div class="stat">
            <div class="stat-number">&#8377;<?php echo number_format($dailySales, 2); ?></div>
            <div class="stat-label">Today</div>
          </div>
          <div class="stat">
            <div class="stat-number">&#8377;<?php echo number_format($weeklySales, 2); ?></div>
            <div class="stat-label">This week</div>
          </div>
          <div class="stat">
            <div class="stat-number"><?php echo $totalBills; ?></div>
            <div class="stat-label">Total bills</div>
          </div>
        </div>
      </div>

      <h2 style="font-size:1rem; margin-bottom:10px;">Recent transactions</h2>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Bill No</th><th>Date</th><th>Customer</th><th>Medicine</th><th class="num">Qty</th><th class="num">Amount</th><th></th></tr>
          </thead>
          <tbody>
            <?php if (count($recentBills) === 0): ?>
              <tr><td colspan="7" class="empty-note">No bills generated yet.</td></tr>
            <?php else: foreach ($recentBills as $b): ?>
              <tr>
                <td>#<?php echo $b['Bill_No']; ?></td>
                <td><?php echo date('d M Y', strtotime($b['Bill_Date'])); ?></td>
                <td><?php echo htmlspecialchars($b['Customer_Name']); ?></td>
                <td><?php echo htmlspecialchars($b['Medicine_Name']); ?></td>
                <td class="num"><?php echo $b['Quantity']; ?></td>
                <td class="num">&#8377;<?php echo number_format($b['Amount'], 2); ?></td>
                <td><a href="view_bill.php?bill_no=<?php echo $b['Bill_No']; ?>" target="_blank">View</a></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<!-- ============ MODAL: ADD CUSTOMER ============ -->
<div class="modal-backdrop" id="addCustomerModal">
  <div class="modal">
    <div class="modal-head">
      <h2 style="font-size:1.05rem; margin:0;">Add new customer</h2>
      <button class="modal-close" onclick="closeModal('addCustomerModal')">&#10005;</button>
    </div>
    <label for="newCustomerName">Name</label>
    <input type="text" id="newCustomerName">
    <label for="newCustomerPhone">Phone</label>
    <input type="text" id="newCustomerPhone">
    <p id="newCustomerMsg" style="color:var(--clay); font-size:0.8125rem; margin-top:8px;"></p>
    <div class="modal-actions">
      <button class="btn btn-ghost" style="flex:1;" onclick="closeModal('addCustomerModal')">Cancel</button>
      <button class="btn btn-primary" style="flex:1;" onclick="submitNewCustomer()">Save customer</button>
    </div>
  </div>
</div>

<!-- ============ MODAL: ADD NEW MEDICINE ============ -->
<div class="modal-backdrop" id="addMedicineModal">
  <div class="modal">
    <div class="modal-head">
      <h2 style="font-size:1.05rem; margin:0;">Add new medicine</h2>
      <button class="modal-close" onclick="closeModal('addMedicineModal')">&#10005;</button>
    </div>
    <form method="POST" action="manage_stock.php">
      <input type="hidden" name="action" value="add_new">
      <label for="newMedName">Name</label>
      <input type="text" id="newMedName" name="name" required>
      <label for="newMedCategory">Category</label>
      <select id="newMedCategory" name="category">
        <option value="">— Uncategorized —</option>
        <option value="Tablets &amp; Capsules">Tablets &amp; Capsules</option>
        <option value="Syrups &amp; Drops">Syrups &amp; Drops</option>
        <option value="Personal Care">Personal Care</option>
        <option value="Baby &amp; Mother Care">Baby &amp; Mother Care</option>
        <option value="Devices &amp; Monitors">Devices &amp; Monitors</option>
      </select>
      <label for="newMedBatch">Batch No</label>
      <input type="text" id="newMedBatch" name="batch_no" required>
      <label for="newMedExpiry">Expiry date</label>
      <input type="date" id="newMedExpiry" name="expiry_date" required>
      <div class="field-row">
        <div>
          <label for="newMedQty">Quantity</label>
          <input type="number" id="newMedQty" name="quantity" min="0" required>
        </div>
        <div>
          <label for="newMedPrice">Price</label>
          <input type="number" id="newMedPrice" name="price" min="0" step="0.01" required>
        </div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-ghost" style="flex:1;" onclick="closeModal('addMedicineModal')">Cancel</button>
        <button type="submit" class="btn btn-primary" style="flex:1;">Add medicine</button>
      </div>
    </form>
  </div>
</div>

<!-- ============ MODAL: ADD STOCK ============ -->
<div class="modal-backdrop" id="addStockModal">
  <div class="modal">
    <div class="modal-head">
      <h2 style="font-size:1.05rem; margin:0;">Add stock</h2>
      <button class="modal-close" onclick="closeModal('addStockModal')">&#10005;</button>
    </div>
    <form method="POST" action="manage_stock.php">
      <input type="hidden" name="action" value="restock">
      <label for="restockMed">Medicine</label>
      <select id="restockMed" name="medicine_id" required>
        <option value="">— Select medicine —</option>
        <?php foreach ($medicines as $m): ?>
          <option value="<?php echo htmlspecialchars($m['Med_ID']); ?>"><?php echo htmlspecialchars($m['Name']); ?> (current: <?php echo $m['Quantity']; ?>)</option>
        <?php endforeach; ?>
      </select>
      <label for="restockQty">Quantity to add</label>
      <input type="number" id="restockQty" name="add_quantity" min="1" required>
      <div class="modal-actions">
        <button type="button" class="btn btn-ghost" style="flex:1;" onclick="closeModal('addStockModal')">Cancel</button>
        <button type="submit" class="btn btn-primary" style="flex:1;">Add stock</button>
      </div>
    </form>
  </div>
</div>

<!-- ============ MODAL: ADD SUPPLIER ============ -->
<div class="modal-backdrop" id="addSupplierModal">
  <div class="modal">
    <div class="modal-head">
      <h2 style="font-size:1.05rem; margin:0;">Add new supplier</h2>
      <button class="modal-close" onclick="closeModal('addSupplierModal')">&#10005;</button>
    </div>
    <label for="newSupplierName">Name</label>
    <input type="text" id="newSupplierName">
    <label for="newSupplierContact">Contact</label>
    <input type="text" id="newSupplierContact">
    <label for="newSupplierAddress">Address</label>
    <input type="text" id="newSupplierAddress">
    <p id="newSupplierMsg" style="color:var(--clay); font-size:0.8125rem; margin-top:8px;"></p>
    <div class="modal-actions">
      <button class="btn btn-ghost" style="flex:1;" onclick="closeModal('addSupplierModal')">Cancel</button>
      <button class="btn btn-primary" style="flex:1;" onclick="submitNewSupplier()">Save supplier</button>
    </div>
  </div>
</div>

<script>
  window.MEDIPOINT_CUSTOMERS = <?php echo json_encode($customers, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
</script>
<script src="assets/app.js"></script>

</body>
</html>

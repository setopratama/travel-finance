<?php
require_once 'includes/functions.php';
requireLogin();

$type = $_POST['type'] ?? ($_GET['type'] ?? 'INCOME');
$action = $_GET['action'] ?? 'list';

// AJAX: Get DP Details
if ($action === 'get_dp_info' && isset($_GET['dp_id'])) {
    header('Content-Type: application/json');
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ? AND payment_type = 'DP'");
    $stmt->execute([$_GET['dp_id']]);
    $dp = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($dp ?: ['error' => 'not found']);
    exit;
}

// Handle Save & Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'save' || $action === 'update')) {
    try {
        $pdo->beginTransaction();

        $t_type = $_POST['type'];
        $category = $_POST['category'];
        $ref_no = $_POST['ref_no'] ?: 'REF-' . time();
        $date = $_POST['date'];
        $customer_name = $_POST['customer_name'];
        $status = $_POST['status'];
        $payment_type = $_POST['payment_type'] ?? 'FULL';
        $dp_id = !empty($_POST['dp_id']) ? $_POST['dp_id'] : null;
        $contract_amount = !empty($_POST['contract_amount']) ? (float)$_POST['contract_amount'] : 0;

        // Auto-save new customer if not exists
        if (!empty($customer_name)) {
            $custStmt = $pdo->prepare("SELECT id FROM customers WHERE name = ?");
            $custStmt->execute([$customer_name]);
            if (!$custStmt->fetch()) {
                $pdo->prepare("INSERT INTO customers (name) VALUES (?)")->execute([$customer_name]);
            }
        }
        
        // Calculate total
        $total_amount = 0;
        $items = [];
        if (isset($_POST['items'])) {
            foreach ($_POST['items'] as $item) {
                if (!empty($item['description'])) {
                    $qty = (float)$item['qty'];
                    $price = (float)$item['price'];
                    $subtotal = $qty * $price;
                    $total_amount += $subtotal;
                    $items[] = [
                        'description' => $item['description'],
                        'qty' => $qty,
                        'unit_price' => $price,
                        'subtotal' => $subtotal
                    ];
                }
            }
        }

        if ($action === 'update' && isset($_POST['id'])) {
            $transaction_id = $_POST['id'];
            // Update transaction header
            $stmt = $pdo->prepare("UPDATE transactions SET type = ?, category = ?, ref_no = ?, date = ?, customer_name = ?, total_amount = ?, status = ?, payment_type = ?, dp_id = ?, contract_amount = ? WHERE id = ?");
            $stmt->execute([$t_type, $category, $ref_no, $date, $customer_name, $total_amount, $status, $payment_type, $dp_id, $contract_amount, $transaction_id]);

            // Delete old items
            $stmt = $pdo->prepare("DELETE FROM transaction_items WHERE transaction_id = ?");
            $stmt->execute([$transaction_id]);
        } else {
            // INSERT
            $stmt = $pdo->prepare("INSERT INTO transactions (type, category, ref_no, date, customer_name, total_amount, status, payment_type, dp_id, contract_amount, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$t_type, $category, $ref_no, $date, $customer_name, $total_amount, $status, $payment_type, $dp_id, $contract_amount, $_SESSION['user_id']]);
            $transaction_id = $pdo->lastInsertId();
        }

        $itemStmt = $pdo->prepare("INSERT INTO transaction_items (transaction_id, description, qty, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)");
        foreach ($items as $item) {
            $itemStmt->execute([$transaction_id, $item['description'], $item['qty'], $item['unit_price'], $item['subtotal']]);
        }

        $pdo->commit();
        setFlash('success', 'Transaction saved successfully!');
        header("Location: transactions.php?type=$t_type");
        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}
// Handle Duplicate
if ($action === 'duplicate' && isset($_GET['id'])) {
    try {
        $source_id = $_GET['id'];
        $pdo->beginTransaction();

        // Fetch source transaction
        $stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ?");
        $stmt->execute([$source_id]);
        $source = $stmt->fetch();

        if ($source) {
            $new_ref = 'COPY-' . time();
            $new_date = date('Y-m-d');
            
            // Insert new transaction header
            $stmt = $pdo->prepare("INSERT INTO transactions (type, category, ref_no, date, customer_name, total_amount, status, payment_type, dp_id, contract_amount, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $source['type'], 
                $source['category'], 
                $new_ref, 
                $new_date, 
                $source['customer_name'], 
                $source['total_amount'], 
                'PENDING', 
                $source['payment_type'],
                $source['dp_id'],
                $source['contract_amount'],
                $_SESSION['user_id']
            ]);
            $new_id = $pdo->lastInsertId();

            // Fetch and Insert items
            $stmtItems = $pdo->prepare("SELECT * FROM transaction_items WHERE transaction_id = ?");
            $stmtItems->execute([$source_id]);
            $items = $stmtItems->fetchAll();

            $itemStmt = $pdo->prepare("INSERT INTO transaction_items (transaction_id, description, qty, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)");
            foreach ($items as $item) {
                $itemStmt->execute([$new_id, $item['description'], $item['qty'], $item['unit_price'], $item['subtotal']]);
            }

            $pdo->commit();
            setFlash('success', 'Invoice berhasil diduplikasi! (Ref: ' . $new_ref . ')');
        }
        header("Location: transactions.php?type=" . $type);
        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = "Duplicate Error: " . $e->getMessage();
    }
}

// Handle Status Update
if ($action === 'update_status' && isset($_GET['id'])) {
    try {
        $id = $_GET['id'];
        $stmt = $pdo->prepare("UPDATE transactions SET status = 'PAID' WHERE id = ?");
        $stmt->execute([$id]);
        
        setFlash('success', 'Status transaksi berhasil diperbarui menjadi PAID!');
        header("Location: transactions.php?type=" . $type);
        exit;
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch for Edit
$editData = null;
$editItems = [];
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $editData = $stmt->fetch();
    if ($editData) {
        $type = $editData['type'];
        $stmtItems = $pdo->prepare("SELECT * FROM transaction_items WHERE transaction_id = ?");
        $stmtItems->execute([$editData['id']]);
        $editItems = $stmtItems->fetchAll();
    }
}

// Fetch all customers for datalist
$customers = $pdo->query("SELECT name FROM customers ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);

// Fetch DP transactions for linking (exclude those already settled/linked)
$dpTransactions = $pdo->query("SELECT id, ref_no, customer_name, total_amount, contract_amount FROM transactions WHERE payment_type = 'DP' AND status != 'CANCELLED' AND id NOT IN (SELECT dp_id FROM transactions WHERE dp_id IS NOT NULL AND status != 'CANCELLED') ORDER BY date DESC")->fetchAll(PDO::FETCH_ASSOC);

// If editing, ensure the currently linked DP is in the list
if ($action === 'edit' && !empty($editData['dp_id'])) {
    $exists = false;
    foreach ($dpTransactions as $dp) {
        if ($dp['id'] == $editData['dp_id']) { $exists = true; break; }
    }
    if (!$exists) {
        $stmt = $pdo->prepare("SELECT id, ref_no, customer_name, total_amount, contract_amount FROM transactions WHERE id = ?");
        $stmt->execute([$editData['dp_id']]);
        $currentDp = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($currentDp) {
            array_unshift($dpTransactions, $currentDp);
        }
    }
}

// Fetch Invoices for Refund
$invoiceTransactions = [];
if ($type === 'REFUND') {
    $invoiceTransactions = $pdo->query("SELECT id, ref_no, customer_name, total_amount FROM transactions WHERE type = 'INCOME' AND status != 'CANCELLED' ORDER BY date DESC")->fetchAll(PDO::FETCH_ASSOC);
    
    // If editing a refund, make sure its linked invoice is in the list
    if ($action === 'edit' && !empty($editData['dp_id'])) {
        $exists = false;
        foreach ($invoiceTransactions as $inv) {
             if ($inv['id'] == $editData['dp_id']) { $exists = true; break; }
        }
        if (!$exists) {
            $stmt = $pdo->prepare("SELECT id, ref_no, customer_name, total_amount FROM transactions WHERE id = ?");
            $stmt->execute([$editData['dp_id']]);
            $currentInv = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($currentInv) {
                array_unshift($invoiceTransactions, $currentInv);
            }
        }
    }
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<main class="flex-1 p-8 overflow-y-auto">
    <?php if (isset($error)): ?>
        <div class="bg-red-50 text-red-600 p-4 rounded-xl mb-6 border border-red-100">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if (in_array($action, ['add', 'edit', 'save', 'update'])): ?>
        <!-- FORM -->
        <header class="flex justify-between items-center mb-10">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">
                    <?php 
                        if ($type === 'INCOME') echo (in_array($action, ['edit', 'update']) ? 'Edit ' : 'New ') . 'Invoice';
                        elseif ($type === 'EXPENSE') echo (in_array($action, ['edit', 'update']) ? 'Edit ' : 'New ') . 'Expense';
                        else echo (in_array($action, ['edit', 'update']) ? 'Edit ' : 'New ') . 'Refund';
                    ?>
                </h2>
                <p class="text-sm text-slate-500"><?php echo in_array($action, ['edit', 'update']) ? 'Update financial record' : 'Create a new financial record'; ?></p>
            </div>
            <a href="transactions.php?type=<?php echo $type; ?>" class="text-slate-500 hover:text-slate-800 font-medium flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                </svg>
                Back to List
            </a>
        </header>

        <form action="transactions.php?action=<?php echo in_array($action, ['edit', 'update']) ? 'update' : 'save'; ?>" method="POST" class="space-y-8">
            <input type="hidden" name="type" value="<?php echo $type; ?>">
            <?php if (in_array($action, ['edit', 'update'])): ?>
                <input type="hidden" name="id" value="<?php echo isset($editData['id']) ? $editData['id'] : ($_POST['id'] ?? ''); ?>">
            <?php endif; ?>
            
            <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Category</label>
                        <select name="category" required class="w-full p-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                            <?php 
                            $catStmt = $pdo->prepare("SELECT name FROM categories WHERE type = ? ORDER BY name ASC");
                            $catStmt->execute([$type]);
                            $dbCategories = $catStmt->fetchAll(PDO::FETCH_COLUMN);
                            
                            foreach ($dbCategories as $catName): 
                                $currentCat = isset($editData['category']) ? $editData['category'] : ($_POST['category'] ?? '');
                                $selected = ($currentCat === $catName) ? 'selected' : '';
                            ?>
                                <option value="<?php echo $catName; ?>" <?php echo $selected; ?>><?php echo $catName; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Reference No</label>
                        <input type="text" name="ref_no" value="<?php echo isset($editData['ref_no']) ? $editData['ref_no'] : ($_POST['ref_no'] ?? ''); ?>" placeholder="Auto-generated if empty" class="w-full p-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Date</label>
                        <input type="date" name="date" value="<?php echo isset($editData['date']) ? $editData['date'] : ($_POST['date'] ?? date('Y-m-d')); ?>" required class="w-full p-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Status</label>
                        <select name="status" class="w-full p-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                            <?php $currentStatus = isset($editData['status']) ? $editData['status'] : ($_POST['status'] ?? 'PENDING'); ?>
                            <option value="PAID" <?php echo ($currentStatus === 'PAID') ? 'selected' : ''; ?>>Paid</option>
                            <option value="PENDING" <?php echo ($currentStatus === 'PENDING') ? 'selected' : ''; ?>>Pending</option>
                            <option value="CANCELLED" <?php echo ($currentStatus === 'CANCELLED') ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="<?php echo ($type === 'REFUND' ? 'hidden' : ''); ?>">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Payment Type</label>
                        <select name="payment_type" id="payment_type" class="w-full p-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                            <?php $currentPT = isset($editData['payment_type']) ? $editData['payment_type'] : ($_POST['payment_type'] ?? 'FULL'); ?>
                            <option value="FULL" <?php echo ($currentPT === 'FULL') ? 'selected' : ''; ?>>Full Payment (Lunas)</option>
                            <option value="DP" <?php echo ($currentPT === 'DP') ? 'selected' : ''; ?>>Down Payment (DP)</option>
                            <option value="REMAINDER" <?php echo ($currentPT === 'REMAINDER') ? 'selected' : ''; ?>>Pelunasan (Remainder)</option>
                        </select>
                    </div>
                </div>

                <!-- Contract & DP Info Section -->
                <div id="paymentInfo" class="mt-6 p-6 bg-blue-50 rounded-2xl border border-blue-100 <?php echo ($action === 'edit' && in_array($editData['payment_type'], ['DP', 'REMAINDER'])) ? '' : 'hidden'; ?>">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Contract Amount: Visible for DP and Remainder -->
                        <div id="contractField">
                            <label class="block text-sm font-semibold text-blue-700 mb-2">Total Contract Amount (Full Price)</label>
                            <div class="relative">
                                <span class="absolute left-4 top-3 text-blue-400 font-bold">Rp</span>
                                <input type="number" name="contract_amount" id="contract_amount" value="<?php echo isset($editData['contract_amount']) ? $editData['contract_amount'] : ($_POST['contract_amount'] ?? '0'); ?>" class="w-full p-3 pl-12 rounded-xl border border-blue-200 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                            </div>
                            <p class="text-[10px] text-blue-400 mt-1">Nilai total tagihan (sebelum dipotong DP)</p>
                        </div>

                        <!-- DP Selection: Only for Remainder -->
                        <div id="dpSelectionField" class="<?php echo ($action === 'edit' && $editData['payment_type'] === 'REMAINDER') ? '' : 'hidden'; ?>">
                            <label class="block text-sm font-semibold text-blue-700 mb-2">Select DP Transaction</label>
                            <?php $currentDP = isset($editData['dp_id']) ? $editData['dp_id'] : ($_POST['dp_id'] ?? ''); ?>
                            <select name="dp_id" id="dp_id" class="w-full p-3 rounded-xl border border-blue-200 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                                <option value="">-- Choose DP Reference --</option>
                                <?php foreach ($dpTransactions as $dp): ?>
                                    <option value="<?php echo $dp['id']; ?>" <?php echo ($currentDP == $dp['id']) ? 'selected' : ''; ?> data-amount="<?php echo $dp['total_amount']; ?>" data-contract-amount="<?php echo $dp['contract_amount']; ?>" data-customer="<?php echo htmlspecialchars($dp['customer_name']); ?>">
                                        <?php echo $dp['ref_no']; ?> - <?php echo htmlspecialchars($dp['customer_name']); ?> (<?php echo formatCurrency($dp['total_amount']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Calculation Summary: Only for Remainder -->
                    <div id="remainderCalcInfo" class="mt-4 text-sm text-blue-600 bg-white/50 p-4 rounded-xl border border-blue-100 flex justify-between items-center <?php echo ($action === 'edit' && $editData['payment_type'] === 'REMAINDER') ? '' : 'hidden'; ?>">
                        <div>
                            Linked DP: <span id="display_dp_amount" class="font-bold">Rp 0</span>
                            <span id="display_dp_ref" class="text-[10px] ml-2 opacity-60"></span>
                        </div>
                        <div class="text-lg">
                            Remaining Balance: <span id="display_remainder" class="font-bold text-blue-800">Rp 0</span>
                        </div>
                    </div>
                </div>

                <!-- Refund Linking Section -->
                <?php if ($type === 'REFUND'): ?>
                <div id="refundLinking" class="mt-6 p-6 bg-amber-50 rounded-2xl border border-amber-100">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-amber-700 mb-2">Select Invoice to Refund</label>
                            <?php $currentLinked = isset($editData['dp_id']) ? $editData['dp_id'] : ($_POST['dp_id'] ?? ($_GET['linked_id'] ?? '')); ?>
                            <select name="dp_id" id="refund_link_id" class="w-full p-3 rounded-xl border-amber-200 focus:ring-2 focus:ring-amber-500 outline-none transition-all">
  <option value="">-- No Linked Invoice (Ad-hoc Refund) --</option>
                                <?php foreach ($invoiceTransactions as $inv): ?>
                                    <option value="<?php echo $inv['id']; ?>" <?php echo ($currentLinked == $inv['id']) ? 'selected' : ''; ?> data-amount="<?php echo $inv['total_amount']; ?>" data-customer="<?php echo htmlspecialchars($inv['customer_name']); ?>">
                                        <?php echo $inv['ref_no']; ?> - <?php echo htmlspecialchars($inv['customer_name']); ?> (<?php echo formatCurrency($inv['total_amount']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="text-[10px] text-amber-500 mt-1">Pilih invoice asal untuk mengisi otomatis detail refund.</p>
                        </div>
                        <div id="refund_info_box" class="flex items-center <?php echo empty($currentLinked) ? 'hidden' : ''; ?>">
                             <div class="p-4 bg-white/50 rounded-xl border border-amber-100 w-full">
                                <div class="text-[10px] uppercase font-bold text-amber-400">Invoice Amount</div>
                                <div id="display_refund_inv_amount" class="text-xl font-bold text-amber-800">Rp 0</div>
                             </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="mt-6">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        <?php 
                            if ($type === 'INCOME') echo 'Customer Name';
                            elseif ($type === 'EXPENSE') echo 'Vendor Name';
                            else echo 'Recipient / Customer Name';
                        ?>
                    </label>
                    <input type="text" name="customer_name" list="customerList" value="<?php echo isset($editData['customer_name']) ? $editData['customer_name'] : ($_POST['customer_name'] ?? ''); ?>" required class="w-full p-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none transition-all" placeholder="Type name or select from list...">
                    <datalist id="customerList">
                        <?php foreach ($customers as $c): ?>
                            <option value="<?php echo htmlspecialchars($c); ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
            </div>

            <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100">
                <h4 class="text-lg font-bold text-slate-800 mb-6">Transaction Items</h4>
                <div class="overflow-x-auto">
                    <table class="w-full" id="itemsTable">
                        <thead>
                            <tr class="text-left text-sm font-bold text-slate-400 uppercase tracking-wider">
                                <th class="pb-4 pr-4">Description</th>
                                <th class="pb-4 pr-4 w-24">Qty</th>
                                <th class="pb-4 pr-4 w-48">Unit Price</th>
                                <th class="pb-4 text-right w-48">Subtotal</th>
                                <th class="pb-4 pl-4 w-10"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            <?php 
                            $displayItems = !empty($editItems) ? $editItems : [];
                            if (isset($_POST['items'])) {
                                $displayItems = [];
                                foreach ($_POST['items'] as $pItem) {
                                    $displayItems[] = [
                                        'description' => $pItem['description'],
                                        'qty' => $pItem['qty'],
                                        'unit_price' => $pItem['price'],
                                        'subtotal' => $pItem['qty'] * $pItem['price']
                                    ];
                                }
                            }
                            
                            if (!empty($displayItems)): 
                                foreach ($displayItems as $index => $item): 
                            ?>
                                    <tr class="item-row">
                                        <td class="py-2 pr-4">
                                            <input type="text" name="items[<?php echo $index; ?>][description]" value="<?php echo htmlspecialchars($item['description']); ?>" required class="w-full p-3 rounded-xl border border-slate-100 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition-all" placeholder="Item description">
                                        </td>
                                        <td class="py-2 pr-4">
                                            <input type="number" name="items[<?php echo $index; ?>][qty]" value="<?php echo $item['qty']; ?>" min="1" required class="qty-input w-full p-3 rounded-xl border border-slate-100 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                                        </td>
                                        <td class="py-2 pr-4">
                                            <input type="number" name="items[<?php echo $index; ?>][price]" value="<?php echo $item['unit_price']; ?>" step="0.01" required class="price-input w-full p-3 rounded-xl border border-slate-100 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                                        </td>
                                        <td class="py-2 text-right font-bold text-slate-700 subtotal-text">
                                            <?php echo number_format($item['subtotal'], 2, '.', ','); ?>
                                        </td>
                                        <td class="py-2 pl-4">
                                            <button type="button" class="text-slate-300 hover:text-red-500 remove-row">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM7 9a1 1 0 000 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr class="item-row">
                                    <td class="py-2 pr-4">
                                        <input type="text" name="items[0][description]" required class="w-full p-3 rounded-xl border border-slate-100 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition-all" placeholder="Item description">
                                    </td>
                                    <td class="py-2 pr-4">
                                        <input type="number" name="items[0][qty]" value="1" min="1" required class="qty-input w-full p-3 rounded-xl border border-slate-100 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                                    </td>
                                    <td class="py-2 pr-4">
                                        <input type="number" name="items[0][price]" value="0" step="0.01" required class="price-input w-full p-3 rounded-xl border border-slate-100 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                                    </td>
                                    <td class="py-2 text-right font-bold text-slate-700 subtotal-text">
                                        0.00
                                    </td>
                                    <td class="py-2 pl-4">
                                        <button type="button" class="text-slate-300 hover:text-red-500 remove-row">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM7 9a1 1 0 000 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <button type="button" id="addRow" class="mt-4 text-blue-600 font-bold flex items-center hover:text-blue-700 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" />
                    </svg>
                    Add Item
                </button>

                <div class="mt-8 border-t border-slate-100 pt-8 flex justify-end">
                    <div class="w-64">
                        <div class="flex justify-between items-center text-xl">
                            <span class="font-bold text-slate-400">Total</span>
                            <span class="font-bold text-slate-900" id="grandTotalText">Rp <?php echo $action === 'edit' ? number_format($editData['total_amount'], 0, ',', '.') : '0'; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end space-x-4">
                <button type="submit" class="bg-blue-600 text-white font-bold py-4 px-10 rounded-2xl hover:bg-blue-700 transition-all shadow-lg shadow-blue-100 active:scale-95">
                    <?php echo in_array($action, ['edit', 'update']) ? 'Update Transaction' : 'Save Transaction'; ?>
                </button>
            </div>
        </form>

        <script>
            const itemsBody = document.getElementById('itemsBody');
            const addRowBtn = document.getElementById('addRow');
            const grandTotalText = document.getElementById('grandTotalText');
            let rowCount = <?php echo count($displayItems) ?: 1; ?>;

            function formatIDR(amount) {
                return 'Rp ' + Number(amount).toLocaleString('id-ID');
            }

            function updateTotals() {
                let grandTotal = 0;
                document.querySelectorAll('.item-row').forEach(row => {
                    const qtyInput = row.querySelector('.qty-input');
                    const priceInput = row.querySelector('.price-input');
                    if (qtyInput && priceInput) {
                        const qty = qtyInput.value;
                        const price = priceInput.value;
                        const subtotal = qty * price;
                        row.querySelector('.subtotal-text').innerText = subtotal.toLocaleString('id-ID', { minimumFractionDigits: 2 });
                        grandTotal += subtotal;
                    }
                });
                grandTotalText.innerText = formatIDR(grandTotal);
            }

            addRowBtn.addEventListener('click', () => {
                const newRow = document.createElement('tr');
                newRow.className = 'item-row';
                newRow.innerHTML = `
                    <td class="py-2 pr-4">
                        <input type="text" name="items[${rowCount}][description]" required class="w-full p-3 rounded-xl border border-slate-100 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition-all" placeholder="Item description">
                    </td>
                    <td class="py-2 pr-4">
                        <input type="number" name="items[${rowCount}][qty]" value="1" min="1" required class="qty-input w-full p-3 rounded-xl border border-slate-100 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                    </td>
                    <td class="py-2 pr-4">
                        <input type="number" name="items[${rowCount}][price]" value="0" step="0.01" required class="price-input w-full p-3 rounded-xl border border-slate-100 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                    </td>
                    <td class="py-2 text-right font-bold text-slate-700 subtotal-text">
                        0.00
                    </td>
                    <td class="py-2 pl-4">
                        <button type="button" class="text-slate-300 hover:text-red-500 remove-row">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM7 9a1 1 0 000 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </td>
                `;
                itemsBody.appendChild(newRow);
                rowCount++;
            });

            itemsBody.addEventListener('click', (e) => {
                if (e.target.closest('.remove-row')) {
                    if (document.querySelectorAll('.item-row').length > 1) {
                        e.target.closest('.item-row').remove();
                        updateTotals();
                    }
                }
            });

            itemsBody.addEventListener('input', (e) => {
                if (e.target.classList.contains('qty-input') || e.target.classList.contains('price-input')) {
                    updateTotals();
                }
            });

            // Remainder Logic
            const ptSelect = document.getElementById('payment_type');
            const paymentInfo = document.getElementById('paymentInfo');
            const dpSelectionField = document.getElementById('dpSelectionField');
            const remainderCalcInfo = document.getElementById('remainderCalcInfo');

            const dpSelect = document.getElementById('dp_id');
            const contractInput = document.getElementById('contract_amount');
            const displayDp = document.getElementById('display_dp_amount');
            const displayDpRef = document.getElementById('display_dp_ref');
            const displayRemainder = document.getElementById('display_remainder');
            const customerInput = document.querySelector('input[name="customer_name"]');

            function updateRemainderCalc() {
                const selectedOpt = dpSelect.options[dpSelect.selectedIndex];
                const dpAmount = selectedOpt ? parseFloat(selectedOpt.dataset.amount || 0) : 0;
                
                // Auto-fill contract amount from DP if still 0 or empty
                if (selectedOpt && selectedOpt.value && (parseFloat(contractInput.value) === 0 || contractInput.value === '')) {
                    const savedContract = parseFloat(selectedOpt.dataset.contractAmount || 0);
                    if (savedContract > 0) {
                        contractInput.value = savedContract;
                    }
                }

                const contractTotal = parseFloat(contractInput.value || 0);
                const remainder = contractTotal - dpAmount;

                displayDp.innerText = formatIDR(dpAmount);
                displayDpRef.innerText = selectedOpt && selectedOpt.value ? '(Ref: ' + selectedOpt.innerText.split(' - ')[0] + ')' : '';
                displayRemainder.innerText = formatIDR(remainder);

                // Auto-fill customer if selected
                if (selectedOpt && selectedOpt.value && !customerInput.value) {
                     customerInput.value = selectedOpt.dataset.customer;
                }

                // If it's a remainder and we only have 1 row and it's empty, auto-fill it
                if (ptSelect.value === 'REMAINDER' && remainder > 0) {
                    const rows = document.querySelectorAll('.item-row');
                    if (rows.length === 1) {
                        const desc = rows[0].querySelector('input[name*="description"]');
                        const price = rows[0].querySelector('.price-input');
                        if (!desc.value || desc.value.includes('Pelunasan')) {
                            desc.value = 'Pelunasan (Ref: ' + (selectedOpt ? selectedOpt.innerText.split(' - ')[0] : '') + ')';
                            price.value = remainder;
                            updateTotals();
                        }
                    }
                }
            }

            function updateContractStatus() {
                const val = ptSelect.value;
                if (val === 'REMAINDER') {
                    contractInput.readOnly = true;
                    contractInput.classList.add('bg-slate-50', 'text-slate-500', 'cursor-not-allowed');
                } else {
                    contractInput.readOnly = false;
                    contractInput.classList.remove('bg-slate-50', 'text-slate-500', 'cursor-not-allowed');
                }
            }

            ptSelect.addEventListener('change', () => {
                const val = ptSelect.value;
                if (val === 'DP' || val === 'REMAINDER') {
                    paymentInfo.classList.remove('hidden');
                    if (val === 'REMAINDER') {
                        dpSelectionField.classList.remove('hidden');
                        remainderCalcInfo.classList.remove('hidden');
                    } else {
                        dpSelectionField.classList.add('hidden');
                        remainderCalcInfo.classList.add('hidden');
                    }
                } else {
                    paymentInfo.classList.add('hidden');
                }
                updateContractStatus();
                updateRemainderCalc();
            });

            dpSelect.addEventListener('change', updateRemainderCalc);
            contractInput.addEventListener('input', updateRemainderCalc);

            // Refund Linking Script
            const refundSelect = document.getElementById('refund_link_id');
            const refundInfoBox = document.getElementById('refund_info_box');
            const displayRefundInvAmount = document.getElementById('display_refund_inv_amount');

            if (refundSelect) {
                refundSelect.addEventListener('change', () => {
                    const opt = refundSelect.options[refundSelect.selectedIndex];
                    if (opt && opt.value) {
                        const amount = parseFloat(opt.dataset.amount || 0);
                        const customer = opt.dataset.customer;
                        
                        refundInfoBox.classList.remove('hidden');
                        displayRefundInvAmount.innerText = formatIDR(amount);
                        
                        // Auto-fill and LOCK customer
                        customerInput.value = customer;
                        customerInput.readOnly = true;
                        customerInput.classList.add('bg-slate-50', 'text-slate-500', 'cursor-not-allowed');

                        // Auto-fill first item if empty
                        const rows = document.querySelectorAll('.item-row');
                        if (rows.length === 1) {
                            const desc = rows[0].querySelector('input[name*="description"]');
                            const price = rows[0].querySelector('.price-input');
                            if (!desc.value || desc.value.includes('Refund')) {
                                desc.value = 'Refund of ' + opt.innerText.split(' - ')[0];
                                price.value = amount;
                                updateTotals();
                            }
                        }
                    } else {
                        refundInfoBox.classList.add('hidden');
                        customerInput.readOnly = false;
                        customerInput.classList.remove('bg-slate-50', 'text-slate-500', 'cursor-not-allowed');
                    }
                });

                // Trigger on load if edit OR pre-selected from list
                if (refundSelect.value) {
                    const event = new Event('change');
                    refundSelect.dispatchEvent(event);
                }
            }

            // Initial trigger
            updateContractStatus();
            if (ptSelect.value === 'REMAINDER') {
                updateRemainderCalc();
            }
        </script>

    <?php else: ?>
        <!-- LIST VIEW -->
        <header class="flex justify-between items-center mb-10">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">
                    <?php 
                        if ($type === 'INCOME') echo 'Sales Invoices';
                        elseif ($type === 'EXPENSE') echo 'Expense Vouchers';
                        else echo 'Refunds & Reversals';
                    ?>
                </h2>
                <p class="text-sm text-slate-500">History of your <?php echo strtolower($type); ?> transactions</p>
            </div>
            <a href="transactions.php?action=add&type=<?php echo $type; ?>" class="bg-blue-600 text-white px-5 py-3 rounded-xl font-bold hover:bg-blue-700 transition-all shadow-lg shadow-blue-100 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Create New
            </a>
        </header>

        <?php if ($msg = getFlash('success')): ?>
            <div class="bg-green-50 text-green-600 p-4 rounded-xl mb-6 border border-green-100">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs font-bold text-slate-400 uppercase tracking-wider">
                        <th class="p-6">Date</th>
                        <th class="p-6">Ref No</th>
                        <th class="p-6">Name</th>
                        <th class="p-6">Category</th>
                        <th class="p-6">Total</th>
                        <th class="p-6">Status</th>
                        <th class="p-6 text-center">Admin</th>
                        <th class="p-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php
                    $stmt = $pdo->prepare("SELECT t.*, u.username as creator_name FROM transactions t LEFT JOIN users u ON t.created_by = u.id WHERE t.type = ? ORDER BY t.date DESC, t.id DESC");
                    $stmt->execute([$type]);
                    $transactions = $stmt->fetchAll();

                    foreach ($transactions as $t):
                    ?>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="p-6 text-slate-600 font-medium"><?php echo formatDate($t['date']); ?></td>
                        <td class="p-6 text-slate-900 font-bold"><?php echo $t['ref_no']; ?></td>
                        <td class="p-6 text-slate-600"><?php echo $t['customer_name']; ?></td>
                        <td class="p-6">
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600">
                                <?php echo $t['category']; ?>
                            </span>
                        </td>
                        <td class="p-6 text-slate-900 font-bold">
                            <?php echo formatCurrency($t['total_amount']); ?>
                            <div class="text-[10px] uppercase tracking-tighter text-slate-400 mt-1">
                                <?php 
                                    if($t['type'] == 'REFUND') echo '<span class="text-amber-600 font-bold">REFUND</span>';
                                    elseif($t['payment_type'] == 'DP') echo '<span class="text-amber-500">Down Payment</span>';
                                    elseif($t['payment_type'] == 'REMAINDER') echo '<span class="text-indigo-500">Pelunasan</span>';
                                    else echo '<span>Lunas / Full</span>';
                                ?>
                            </div>
                        </td>
                        <td class="p-6">
                            <?php if ($t['status'] === 'PAID'): ?>
                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-50 text-green-600">Paid</span>
                            <?php elseif ($t['status'] === 'CANCELLED'): ?>
                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-red-50 text-red-600">Cancelled</span>
                            <?php else: ?>
                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-600">Pending</span>
                            <?php endif; ?>
                        <td class="p-6 text-center">
                            <span class="text-xs font-medium text-slate-500 bg-slate-100 px-2 py-1 rounded">
                                <?php echo htmlspecialchars($t['creator_name'] ?: 'System'); ?>
                            </span>
                        </td>
                        <td class="p-6 text-right relative">
                            <div class="flex justify-end">
                                <button type="button" class="dropdown-toggle p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                    </svg>
                                </button>
                                <div class="dropdown-menu absolute right-16 mt-2 w-48 bg-white border border-slate-100 rounded-xl shadow-xl z-50 overflow-hidden">
                                    <div class="py-1">
                                        <a href="update_status.php?id=<?php echo $t['id']; ?>" class="flex items-center px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 font-medium">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-3 text-amber-500" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                                            </svg>
                                            Update Status
                                        </a>
                                        <a href="transactions.php?action=edit&id=<?php echo $t['id']; ?>" class="flex items-center px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 font-medium border-t border-slate-50">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-3 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                            </svg>
                                            Edit Record
                                        </a>
                                        <a href="print.php?id=<?php echo $t['id']; ?>" class="flex items-center px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 font-medium">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-3 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                            </svg>
                                            View Details
                                        </a>
                                        <a href="print.php?id=<?php echo $t['id']; ?>" target="_blank" class="flex items-center px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 font-medium">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-3 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" />
                                            </svg>
                                            Print Invoice
                                        </a>
                                        <?php if ($t['type'] === 'INCOME'): ?>
                                        <a href="transactions.php?action=add&type=REFUND&linked_id=<?php echo $t['id']; ?>" class="flex items-center px-4 py-2.5 text-sm text-amber-600 hover:bg-amber-50 font-bold border-t border-slate-50">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-3" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M4 2a2 2 0 00-2 2v2a2 2 0 104 0V4a2 2 0 00-2-2H4zm1 2v2H4V4h1zm8-2a2 2 0 00-2 2v2a2 2 0 104 0V4a2 2 0 00-2-2h-1zm1 2v2h-1V4h1zm-11 8a2 2 0 00-2 2v2a2 2 0 104 0v-2a2 2 0 00-2-2H4zm1 2v2H4v-2h1zm8-2a2 2 0 00-2 2v2a2 2 0 104 0v-2a2 2 0 00-2-2h-1zm1 2v2h-1v-2h1z" clip-rule="evenodd" />
                                            </svg>
                                            Create Refund
                                        </a>
                                        <?php endif; ?>
                                        <div class="border-t border-slate-50">
                                            <button onclick="confirmDuplicate(<?php echo $t['id']; ?>)" class="w-full flex items-center px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 font-medium text-left">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-3 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M7 9a2 2 0 012-2h6a2 2 0 012 2v6a2 2 0 01-2 2H9a2 2 0 01-2-2V9z" />
                                                    <path d="M5 5a2 2 0 012-2h6a2 2 0 012 2v2H7a4 4 0 00-4 4v6a2 2 0 01-2-2V7a2 2 0 012-2h2V5z" />
                                                </svg>
                                                Duplicate
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="7" class="p-20 text-center text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20 mx-auto mb-4 opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            No transactions found.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>

<script>
function confirmDuplicate(id) {
    if (confirm("Apakah kamu ingin duplikasi invoice ini?")) {
        window.location.href = `transactions.php?type=<?php echo $type; ?>&action=duplicate&id=${id}`;
    }
}
</script>

<?php include 'includes/footer.php'; ?>

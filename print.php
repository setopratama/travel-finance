<?php
require_once 'includes/functions.php';
requireLogin();

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ?");
$stmt->execute([$id]);
$t = $stmt->fetch();

if (!$t) die("Transaction not found");

$stmtItems = $pdo->prepare("SELECT * FROM transaction_items WHERE transaction_id = ?");
$stmtItems->execute([$id]);
$items = $stmtItems->fetchAll();

$settings = getSettings($pdo);

// Handle Status Update in Detail View
if (isset($_GET['action']) && $_GET['action'] === 'update_status' && isset($_GET['status'])) {
    try {
        $new_status = $_GET['status'];
        $reason = $_GET['reason'] ?? null;
        
        $stmt = $pdo->prepare("UPDATE transactions SET status = ?, cancel_reason = ? WHERE id = ?");
        $stmt->execute([$new_status, $reason, $id]);
        
        header("Location: print.php?id=" . $id);
        exit;
    } catch (Exception $e) {
        $error = "Error updating status: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['type'] === 'INCOME' ? 'Invoice' : 'Voucher'; ?> - <?php echo $t['ref_no']; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; margin: 0; }
            .print-border-0 { border: 0 !important; }
        }
    </style>
</head>
<body class="bg-slate-100 p-8">
    <div class="max-w-4xl mx-auto bg-white p-12 shadow-sm print:shadow-none print:p-0">
        <div class="flex justify-between items-start mb-12">
            <div>
                <h1 class="text-4xl font-bold text-slate-900 mb-2"><?php echo $t['type'] === 'INCOME' ? 'INVOICE' : 'PAYMENT VOUCHER'; ?></h1>
                <p class="text-slate-500 font-bold"><?php echo $t['ref_no']; ?></p>
            </div>
            <div class="text-right">
                <h2 class="text-xl font-bold text-blue-600"><?php echo $settings['company_name']; ?></h2>
                <div class="text-slate-500 text-sm whitespace-pre-line mt-2">
                    <?php echo $settings['company_address']; ?>
                </div>
            </div>
        </div>

        <hr class="border-slate-100 mb-12">

        <div class="grid grid-cols-2 gap-12 mb-12">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3"><?php echo $t['type'] === 'INCOME' ? 'Bill To' : 'Paid To'; ?></p>
                <p class="text-xl font-bold text-slate-800"><?php echo $t['customer_name']; ?></p>
            </div>
            <div class="text-right">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Date</p>
                <p class="text-xl font-bold text-slate-800 mb-4"><?php echo formatDate($t['date']); ?></p>
                
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Payment Status</p>
                <?php if ($t['status'] === 'PAID'): ?>
                    <p class="text-xl font-bold text-green-600 uppercase tracking-wider italic">✓ PAID / LUNAS</p>
                <?php elseif ($t['status'] === 'CANCELLED'): ?>
                    <p class="text-xl font-bold text-red-600 uppercase tracking-wider italic">✖ CANCELLED / DIBATALKAN</p>
                    <?php if ($t['cancel_reason']): ?>
                        <p class="text-sm text-red-400 mt-1 italic">Alasan: <?php echo htmlspecialchars($t['cancel_reason']); ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-xl font-bold text-amber-500 uppercase tracking-wider italic">PENDING / BELUM LUNAS</p>
                <?php endif; ?>

                <?php if ($t['payment_type'] !== 'FULL'): ?>
                    <div class="mt-4 p-2 border-2 border-slate-900 inline-block">
                        <p class="text-xs font-bold text-slate-900 uppercase">Payment Classification</p>
                        <p class="text-sm font-bold text-slate-900 uppercase">
                            <?php echo $t['payment_type'] === 'DP' ? 'DOWN PAYMENT (DP)' : 'PELUNASAN (REMAINDER)'; ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <table class="w-full mb-12">
            <thead>
                <tr class="bg-slate-50">
                    <th class="p-4 text-left text-xs font-bold text-slate-400 uppercase">Item Description</th>
                    <th class="p-4 text-center text-xs font-bold text-slate-400 uppercase w-20">Qty</th>
                    <th class="p-4 text-right text-xs font-bold text-slate-400 uppercase w-40">Price</th>
                    <th class="p-4 text-right text-xs font-bold text-slate-400 uppercase w-40">Subtotal</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($items as $item): ?>
                <tr>
                    <td class="p-4 text-slate-700 font-medium"><?php echo $item['description']; ?></td>
                    <td class="p-4 text-center text-slate-600"><?php echo $item['qty']; ?></td>
                    <td class="p-4 text-right text-slate-600"><?php echo formatCurrency($item['unit_price']); ?></td>
                    <td class="p-4 text-right text-slate-900 font-bold"><?php echo formatCurrency($item['subtotal']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <?php if ($t['payment_type'] === 'REMAINDER' && $t['dp_id']): 
                    $stmtDp = $pdo->prepare("SELECT ref_no, total_amount FROM transactions WHERE id = ?");
                    $stmtDp->execute([$t['dp_id']]);
                    $dp_record = $stmtDp->fetch(PDO::FETCH_ASSOC);
                    $dp_val = $dp_record['total_amount'] ?? 0;
                    $dp_ref = $dp_record['ref_no'] ?? '-';
                ?>
                <tr>
                    <td colspan="2" class="border-0"></td>
                    <td class="p-4 text-right text-slate-400 font-bold uppercase text-xs">Total Bill</td>
                    <td class="p-4 text-right text-slate-900 font-bold"><?php echo formatCurrency($t['contract_amount']); ?></td>
                </tr>
                <tr>
                    <td colspan="2" class="border-0"></td>
                    <td class="p-4 text-right text-slate-400 font-bold uppercase text-xs">
                        Paid DP
                        <div class="text-[9px] font-normal normal-case text-slate-400">Ref: <?php echo $dp_ref; ?></div>
                    </td>
                    <td class="p-4 text-right text-red-500 font-bold">- <?php echo formatCurrency($dp_val); ?></td>
                </tr>
                <?php elseif ($t['payment_type'] === 'DP' && $t['contract_amount'] > 0): ?>
                <tr>
                    <td colspan="2" class="border-0"></td>
                    <td class="p-4 text-right text-slate-400 font-bold uppercase text-xs">Total Contract Value</td>
                    <td class="p-4 text-right text-slate-900 font-bold"><?php echo formatCurrency($t['contract_amount']); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td colspan="2" class="border-0"></td>
                    <td class="p-6 text-right text-slate-400 font-bold uppercase tracking-widest"><?php echo $t['payment_type'] === 'REMAINDER' ? 'Final Payment' : 'Total'; ?></td>
                    <td class="p-6 text-right text-2xl font-bold text-blue-600"><?php echo formatCurrency($t['total_amount']); ?></td>
                </tr>
            </tfoot>
        </table>

        <div class="grid grid-cols-2 gap-12 mt-20">
            <div class="text-center pt-8 border-t border-slate-100">
                <p class="text-xs font-bold text-slate-400 uppercase mb-20">Prepared By</p>
                <p class="font-bold text-slate-800">Finance Team</p>
            </div>
            <div class="text-center pt-8 border-t border-slate-100">
                <p class="text-xs font-bold text-slate-400 uppercase mb-20">Approved By</p>
                <div class="w-48 h-12 mx-auto border-b border-slate-300"></div>
            </div>
        </div>

        <div class="mt-20 text-center text-slate-400 text-sm italic">
            Thank you for choosing <?php echo $settings['company_name']; ?>
        </div>
    </div>

    <div class="fixed bottom-8 right-8 no-print flex space-x-4">
        <a href="transactions.php?type=<?php echo $t['type']; ?>" class="bg-white text-slate-600 px-8 py-4 rounded-2xl font-bold shadow-xl hover:bg-slate-50 transition-all border border-slate-200 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back
        </a>

        <a href="update_status.php?id=<?php echo $id; ?>" class="bg-amber-100 text-amber-700 px-8 py-4 rounded-2xl font-bold shadow-xl hover:bg-amber-200 transition-all border border-amber-200 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            Edit Status
        </a>

        <button onclick="window.print()" class="bg-blue-600 text-white px-8 py-4 rounded-2xl font-bold shadow-2xl hover:bg-blue-700 transition-all flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            Print Documents
        </button>
    </div>
</main>
</body>
</html>

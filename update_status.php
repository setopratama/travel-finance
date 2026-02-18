<?php
require_once 'includes/functions.php';
requireLogin();

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ?");
$stmt->execute([$id]);
$t = $stmt->fetch();

if (!$t) die("Transaction not found");

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_status = $_POST['status'];
    $reason = $_POST['cancel_reason'] ?? null;
    
    try {
        // Check if cancel_reason column exists first
        $checkCol = $pdo->query("SHOW COLUMNS FROM transactions LIKE 'cancel_reason'");
        $hasReasonCol = ($checkCol->rowCount() > 0);

        if (!$hasReasonCol && $new_status === 'CANCELLED') {
            throw new Exception("ini table transaksi lama tidak bisa diupdate karena ada pembaruan database table. Silakan jalankan DB Sync di menu Settings.");
        }

        if ($hasReasonCol) {
            $stmt = $pdo->prepare("UPDATE transactions SET status = ?, cancel_reason = ? WHERE id = ?");
            $stmt->execute([$new_status, $reason, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE transactions SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $id]);
        }
        
        setFlash('success', 'Status transaksi berhasil diperbarui!');
        header("Location: transactions.php?type=" . $t['type']);
        exit;
    } catch (Exception $e) {
        $msg = $e->getMessage();
    }
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<main class="flex-1 p-8 overflow-y-auto">
    <header class="mb-10 flex justify-between items-center">
        <div>
            <h2 class="text-3xl font-bold text-slate-800">Update Status</h2>
            <p class="text-slate-500">Ubah status pembayaran untuk Ref: <?php echo $t['ref_no']; ?></p>
        </div>
        <a href="transactions.php?type=<?php echo $t['type']; ?>" class="text-slate-500 hover:text-slate-800 font-medium flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
            </svg>
            Back to List
        </a>
    </header>

    <?php if ($msg): ?>
        <div class="bg-red-50 text-red-600 p-4 rounded-xl mb-6 border border-red-100 italic">
            <?php echo $msg; ?>
        </div>
    <?php endif; ?>

    <div class="max-w-xl mx-auto">
        <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100">
            <div class="mb-8">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Transaction Data</p>
                <h3 class="text-xl font-bold text-slate-800"><?php echo $t['customer_name']; ?></h3>
                <p class="text-blue-600 font-bold"><?php echo formatCurrency($t['total_amount']); ?></p>
            </div>

            <form method="POST" class="space-y-6" id="statusForm">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Pilih Status Baru</label>
                    <select name="status" id="statusSelect" required class="w-full p-4 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none transition-all appearance-none bg-slate-50">
                        <option value="PENDING" <?php echo $t['status'] === 'PENDING' ? 'selected' : ''; ?>>PENDING (Belum Lunas)</option>
                        <option value="PAID" <?php echo $t['status'] === 'PAID' ? 'selected' : ''; ?>>PAID (Sudah Lunas)</option>
                        <option value="CANCELLED" <?php echo $t['status'] === 'CANCELLED' ? 'selected' : ''; ?>>CANCELLED (Dibatalkan)</option>
                    </select>
                </div>

                <div id="cancelReasonBox" class="<?php echo $t['status'] === 'CANCELLED' ? '' : 'hidden'; ?>">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Alasan Pembatalan</label>
                    <textarea name="cancel_reason" id="cancelReasonInput" placeholder="Sebutkan alasan kenapa transaksi ini dibatalkan..." class="w-full p-4 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none transition-all bg-slate-50 h-32"><?php echo htmlspecialchars($t['cancel_reason'] ?? ''); ?></textarea>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full bg-blue-600 text-white font-bold py-4 rounded-2xl hover:bg-blue-700 transition-all shadow-lg shadow-blue-100 active:scale-95">
                        Simpan Perubahan Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
    const statusSelect = document.getElementById('statusSelect');
    const cancelReasonBox = document.getElementById('cancelReasonBox');
    const cancelReasonInput = document.getElementById('cancelReasonInput');

    statusSelect.addEventListener('change', function() {
        if (this.value === 'CANCELLED') {
            cancelReasonBox.classList.remove('hidden');
            cancelReasonInput.setAttribute('required', 'required');
        } else {
            cancelReasonBox.classList.add('hidden');
            cancelReasonInput.removeAttribute('required');
        }
    });
</script>

<?php include 'includes/footer.php'; ?>

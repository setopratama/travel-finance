<?php
require_once 'includes/functions.php';
requireLogin();

$msg = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
    $stmt->execute([$id]);
    setFlash('success', 'Customer berhasil dihapus!');
    header("Location: customers.php");
    exit;
}

// Handle Add/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_customer'])) {
    $id = $_POST['id'] ?? null;
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    
    if (!empty($name)) {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE customers SET name = ?, phone = ?, email = ?, address = ? WHERE id = ?");
            $stmt->execute([$name, $phone, $email, $address, $id]);
            setFlash('success', 'Data customer berhasil diperbarui!');
        } else {
            $stmt = $pdo->prepare("INSERT INTO customers (name, phone, email, address) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $phone, $email, $address]);
            setFlash('success', 'Customer baru berhasil ditambahkan!');
        }
    }
    header("Location: customers.php");
    exit;
}

// Fetch for Edit
$editData = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editData = $stmt->fetch();
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<main class="flex-1 p-8 overflow-y-auto">
    <header class="mb-10">
        <h2 class="text-3xl font-bold text-slate-800">Customer Management</h2>
        <p class="text-slate-500">Kelola data pelanggan dan vendor</p>
    </header>

    <?php if ($msg = getFlash('success')): ?>
        <div class="bg-green-50 text-green-600 p-4 rounded-xl mb-6 border border-green-100">
            <?php echo $msg; ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Add/Edit Customer Form -->
        <div class="lg:col-span-1">
            <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100 sticky top-8">
                <h4 class="text-lg font-bold text-slate-800 mb-6"><?php echo $editData ? 'Edit Customer' : 'Tambah Customer'; ?></h4>
                <form method="POST" class="space-y-6">
                    <?php if ($editData): ?>
                        <input type="hidden" name="id" value="<?php echo $editData['id']; ?>">
                    <?php endif; ?>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Lengkap</label>
                        <input type="text" name="name" value="<?php echo $editData['name'] ?? ''; ?>" required placeholder="Nama Customer / Perusahaan" class="w-full p-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">No. Telepon</label>
                        <input type="text" name="phone" value="<?php echo $editData['phone'] ?? ''; ?>" placeholder="08xxxx" class="w-full p-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Email</label>
                        <input type="email" name="email" value="<?php echo $editData['email'] ?? ''; ?>" placeholder="email@example.com" class="w-full p-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Alamat</label>
                        <textarea name="address" rows="3" placeholder="Alamat lengkap" class="w-full p-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none transition-all"><?php echo $editData['address'] ?? ''; ?></textarea>
                    </div>
                    <div class="flex space-x-3">
                        <button type="submit" name="save_customer" class="flex-1 bg-blue-600 text-white font-bold py-3 rounded-xl hover:bg-blue-700 transition-all shadow-lg shadow-blue-100">
                            <?php echo $editData ? 'Update Customer' : 'Simpan Customer'; ?>
                        </button>
                        <?php if ($editData): ?>
                            <a href="customers.php" class="bg-slate-100 text-slate-600 font-bold py-3 px-6 rounded-xl hover:bg-slate-200 transition-all">Batal</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Customers List -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-bold text-slate-400 border-b border-slate-100 uppercase tracking-wider">
                            <th class="p-4">Customer</th>
                            <th class="p-4">Contact</th>
                            <th class="p-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php
                        $stmt = $pdo->prepare("SELECT * FROM customers ORDER BY name ASC");
                        $stmt->execute();
                        $customers = $stmt->fetchAll();
                        foreach ($customers as $c):
                        ?>
                        <tr class="hover:bg-slate-50 transition-colors group">
                            <td class="p-4">
                                <div class="font-bold text-slate-800"><?php echo $c['name']; ?></div>
                                <div class="text-xs text-slate-400"><?php echo $c['address'] ? (strlen($c['address']) > 50 ? substr($c['address'], 0, 50) . '...' : $c['address']) : '-'; ?></div>
                            </td>
                            <td class="p-4">
                                <div class="text-sm text-slate-600"><?php echo $c['phone'] ?: '-'; ?></div>
                                <div class="text-xs text-slate-400"><?php echo $c['email'] ?: '-'; ?></div>
                            </td>
                            <td class="p-4 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="customers.php?edit=<?php echo $c['id']; ?>" class="p-2 text-blue-500 hover:bg-blue-50 rounded-lg transition-all">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                        </svg>
                                    </a>
                                    <a href="customers.php?delete=<?php echo $c['id']; ?>" onclick="return confirm('Hapus data customer ini?')" class="p-2 text-slate-300 hover:text-red-500 hover:bg-red-50 rounded-lg transition-all">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($customers)): ?>
                            <tr>
                                <td colspan="3" class="p-10 text-center text-slate-400">Belum ada data customer.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

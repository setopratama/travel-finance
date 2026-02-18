<?php
require_once 'includes/functions.php';
requireLogin();

$msg = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    setFlash('success', 'Kategori berhasil dihapus!');
    header("Location: categories.php");
    exit;
}

// Handle Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = strtoupper(trim($_POST['name']));
    $type = $_POST['type'];
    
    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO categories (name, type) VALUES (?, ?)");
        $stmt->execute([$name, $type]);
        setFlash('success', 'Kategori baru berhasil ditambahkan!');
    }
    header("Location: categories.php");
    exit;
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<main class="flex-1 p-8 overflow-y-auto">
    <header class="mb-10">
        <h2 class="text-3xl font-bold text-slate-800">Category Management</h2>
        <p class="text-slate-500">Kelola kategori untuk Income dan Expense</p>
    </header>

    <?php if ($msg = getFlash('success')): ?>
        <div class="bg-green-50 text-green-600 p-4 rounded-xl mb-6 border border-green-100">
            <?php echo $msg; ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Add Category Form -->
        <div class="lg:col-span-1">
            <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100 sticky top-8">
                <h4 class="text-lg font-bold text-slate-800 mb-6">Tambah Kategori</h4>
                <form method="POST" class="space-y-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Kategori</label>
                        <input type="text" name="name" required placeholder="Contoh: LAUNDRY, WIFI, dll" class="w-full p-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-3">Tipe Transaksi</label>
                        <div class="flex space-x-6">
                            <label class="flex items-center cursor-pointer group">
                                <input type="radio" name="type" value="INCOME" checked class="hidden peer">
                                <div class="w-5 h-5 border-2 border-slate-300 rounded-full flex items-center justify-center peer-checked:border-blue-600 peer-checked:bg-blue-600 transition-all mr-2">
                                    <div class="w-2 h-2 bg-white rounded-full"></div>
                                </div>
                                <span class="text-slate-600 group-hover:text-blue-600 font-medium capitalize">Pemasukan</span>
                            </label>
                            <label class="flex items-center cursor-pointer group">
                                <input type="radio" name="type" value="EXPENSE" class="hidden peer" id="type_expense">
                                <div class="w-5 h-5 border-2 border-slate-300 rounded-full flex items-center justify-center peer-checked:border-red-600 peer-checked:bg-red-600 transition-all mr-2">
                                    <div class="w-2 h-2 bg-white rounded-full"></div>
                                </div>
                                <span class="text-slate-600 group-hover:text-red-600 font-medium capitalize">Pengeluaran</span>
                            </label>
                        </div>
                    </div>
                    <button type="submit" name="add_category" class="w-full bg-blue-600 text-white font-bold py-3 rounded-xl hover:bg-blue-700 transition-all shadow-lg shadow-blue-100">
                        Simpan Kategori
                    </button>
                </form>
            </div>
        </div>

        <!-- Categories List -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Income Categories -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-6 border-b border-slate-50 bg-slate-50/50">
                    <h4 class="font-bold text-slate-800 flex items-center text-green-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd" />
                        </svg>
                        Kategori Pemasukan (Income)
                    </h4>
                </div>
                <table class="w-full">
                    <tbody class="divide-y divide-slate-50">
                        <?php
                        $stmt = $pdo->prepare("SELECT * FROM categories WHERE type='INCOME' ORDER BY name ASC");
                        $stmt->execute();
                        $cats = $stmt->fetchAll();
                        foreach ($cats as $c):
                        ?>
                        <tr class="hover:bg-slate-50 transition-colors group">
                            <td class="p-4 text-slate-700 font-medium"><?php echo $c['name']; ?></td>
                            <td class="p-4 text-right">
                                <a href="categories.php?delete=<?php echo $c['id']; ?>" onclick="return confirm('Hapus kategori ini?')" class="text-slate-300 hover:text-red-500 transition-colors opacity-0 group-hover:opacity-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Expense Categories -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-6 border-b border-slate-50 bg-slate-50/50">
                    <h4 class="font-bold text-slate-800 flex items-center text-red-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M12 13a1 1 0 110 2h-5a1 1 0 01-1-1v-5a1 1 0 112 0v2.586l4.293-4.293a1 1 0 011.414 0L12 9.586l4.293-4.293a1 1 0 111.414 1.414l-5 5a1 1 0 01-1.414 0L9 9.414 5.414 13H12z" clip-rule="evenodd" />
                        </svg>
                        Kategori Pengeluaran (Expense)
                    </h4>
                </div>
                <table class="w-full">
                    <tbody class="divide-y divide-slate-50">
                        <?php
                        $stmt = $pdo->prepare("SELECT * FROM categories WHERE type='EXPENSE' ORDER BY name ASC");
                        $stmt->execute();
                        $cats = $stmt->fetchAll();
                        foreach ($cats as $c):
                        ?>
                        <tr class="hover:bg-slate-50 transition-colors group">
                            <td class="p-4 text-slate-700 font-medium"><?php echo $c['name']; ?></td>
                            <td class="p-4 text-right">
                                <a href="categories.php?delete=<?php echo $c['id']; ?>" onclick="return confirm('Hapus kategori ini?')" class="text-slate-300 hover:text-red-500 transition-colors opacity-0 group-hover:opacity-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

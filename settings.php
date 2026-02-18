<?php
require_once 'includes/functions.php';
requireLogin();

$msg = '';

// Handle Settings Save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $name = $_POST['company_name'];
    $address = $_POST['company_address'];
    
    $stmt = $pdo->prepare("UPDATE settings SET company_name = ?, company_address = ? WHERE id = 1");
    $stmt->execute([$name, $address]);
    $msg = "Settings updated successfully!";
}

// Handle DB Sync
if (isset($_GET['sync'])) {
    try {
        $schema = file_get_contents(__DIR__ . '/sql/schema.sql');
        // Execute multi-query (PDO doesn't support exec for multiple statements in some drivers, but for schema it should work or we split)
        // For safety/compatibility, we split by semicolon
        $queries = explode(';', $schema);
        foreach ($queries as $q) {
            $trimmed = trim($q);
            if (!empty($trimmed)) {
                try {
                    $pdo->exec($trimmed);
                } catch (Exception $e) {
                    // Ignore errors like "Duplicate column" (1060) or "Duplicate entry" (1062)
                    // and "Table already exists" (1050)
                    $errorCode = $e->getCode();
                    $errorInfo = $pdo->errorInfo();
                    if (!in_array($errorInfo[1], [1050, 1060, 1061, 1062, 1091])) {
                        // Throw other critical errors
                        throw $e;
                    }
                }
            }
        }
        $msg = "Database synchronized successfully! Semua tabel dan kolom terbaru telah diterapkan.";
    } catch (Exception $e) {
        $msg = "Sync error: " . $e->getMessage();
    }
}

$settings = getSettings($pdo);

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<main class="flex-1 p-8 overflow-y-auto">
    <header class="mb-10">
        <h2 class="text-3xl font-bold text-slate-800">System Settings</h2>
        <p class="text-slate-500">Manage your company profile and system maintenance</p>
    </header>

    <?php if ($msg): ?>
        <div class="bg-blue-50 text-blue-600 p-4 rounded-xl mb-6 border border-blue-100">
            <?php echo $msg; ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Company Profile -->
        <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100">
            <h4 class="text-lg font-bold text-slate-800 mb-6 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4zm3 1h6v4H7V5zm6 6H7v2h6v-2z" clip-rule="evenodd" />
                </svg>
                Company Profile
            </h4>
            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Company Name</label>
                    <input type="text" name="company_name" value="<?php echo $settings['company_name']; ?>" required class="w-full p-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Address & Contact</label>
                    <textarea name="company_address" rows="4" class="w-full p-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none transition-all"><?php echo $settings['company_address']; ?></textarea>
                </div>
                <button type="submit" name="save_settings" class="w-full bg-blue-600 text-white font-bold py-3 rounded-xl hover:bg-blue-700 transition-all shadow-lg shadow-blue-100">
                    Save Changes
                </button>
            </form>
        </div>

        <!-- Maintenance -->
        <div class="space-y-8">
            <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100">
                <h4 class="text-lg font-bold text-slate-800 mb-4 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-amber-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                    </svg>
                    Database Maintenance
                </h4>
                <p class="text-slate-500 text-sm mb-6">If you manually edited the schema file or experiencing database errors, use this to sync the tables.</p>
                <a href="settings.php?sync=1" class="inline-flex items-center px-6 py-3 bg-slate-100 text-slate-800 rounded-xl font-bold hover:bg-slate-200 transition-all border border-slate-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    One-Click DB Sync
                </a>
            </div>

            <div class="bg-red-50 p-8 rounded-2xl border border-red-100">
                <h4 class="text-lg font-bold text-red-800 mb-2">Danger Zone</h4>
                <p class="text-red-600 text-sm mb-6">Logout from all sessions on this device.</p>
                <a href="logout.php" class="text-red-700 font-bold underline">Log Out Now</a>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

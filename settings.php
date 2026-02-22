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

// Handle User Management (Superadmin Only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user']) && $_SESSION['role'] === 'superadmin') {
    $new_username = $_POST['new_username'];
    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    $new_role = $_POST['new_role'];

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->execute([$new_username, $new_password, $new_role]);
        $msg = "User $new_username added successfully!";
    } catch (Exception $e) {
        $msg = "Error adding user: " . $e->getMessage();
    }
}

if (isset($_GET['delete_user']) && $_SESSION['role'] === 'superadmin') {
    $user_id = $_GET['delete_user'];
    if ($user_id != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $msg = "User deleted successfully!";
    } else {
        $msg = "Error: You cannot delete yourself!";
    }
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

    <?php if ($_SESSION['role'] === 'superadmin'): ?>
    <!-- User Management Section (Superadmin Only) -->
    <div class="mt-12">
        <header class="mb-6">
            <h3 class="text-2xl font-bold text-slate-800">User Management</h3>
            <p class="text-slate-500">Create and manage administrative accounts</p>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Add User Form -->
            <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100 h-fit">
                <h4 class="text-lg font-bold text-slate-800 mb-6 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z" />
                    </svg>
                    Add New Admin
                </h4>
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Username</label>
                        <input type="text" name="new_username" required class="w-full p-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Password</label>
                        <input type="password" name="new_password" required class="w-full p-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Role</label>
                        <select name="new_role" class="w-full p-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                            <option value="admin">Admin</option>
                            <option value="superadmin">Superadmin</option>
                        </select>
                    </div>
                    <button type="submit" name="add_user" class="w-full bg-blue-600 text-white font-bold py-3 rounded-xl hover:bg-blue-700 transition-all shadow-lg shadow-blue-100">
                        Create Account
                    </button>
                </form>
            </div>

            <!-- User List -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-bold text-slate-400 uppercase tracking-wider">
                            <th class="p-6">Username</th>
                            <th class="p-6">Role</th>
                            <th class="p-6 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php
                        $users = $pdo->query("SELECT * FROM users ORDER BY role DESC, username ASC")->fetchAll();
                        foreach ($users as $u):
                        ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="p-6">
                                <div class="font-bold text-slate-800"><?php echo htmlspecialchars($u['username']); ?></div>
                                <div class="text-xs text-slate-400">Created <?php echo date('d M Y', strtotime($u['created_at'])); ?></div>
                            </td>
                            <td class="p-6">
                                <span class="px-3 py-1 rounded-full text-xs font-bold <?php echo $u['role'] === 'superadmin' ? 'bg-purple-50 text-purple-600' : 'bg-blue-50 text-blue-600'; ?>">
                                    <?php echo ucfirst($u['role']); ?>
                                </span>
                            </td>
                            <td class="p-6 text-right">
                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                    <a href="settings.php?delete_user=<?php echo $u['id']; ?>" onclick="return confirm('Are you sure you want to delete this user?')" class="text-red-500 hover:text-red-700 font-bold text-sm">Delete</a>
                                <?php else: ?>
                                    <span class="text-slate-300 text-sm italic">You</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>

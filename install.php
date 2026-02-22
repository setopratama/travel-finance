<?php
/**
 * TRAVEL-FINANCE PRO - Installation Script
 */

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = $_POST['db_host'];
    $db_name = $_POST['db_name'];
    $db_user = $_POST['db_user'];
    $db_pass = $_POST['db_pass'];
    $admin_user = $_POST['admin_user'];
    $admin_pass = $_POST['admin_pass'];

    try {
        // 1. Connect to MySQL
        $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 2. Create Database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$db_name` "); // Using the DB

        // 3. Import Schema
        $schema = file_get_contents(__DIR__ . '/sql/schema.sql');
        $queries = explode(';', $schema);
        foreach ($queries as $q) {
            $trimmed = trim($q);
            if (!empty($trimmed)) {
                $pdo->exec($trimmed);
            }
        }

        // 4. Create Admin User
        $hashed_pass = password_hash($admin_pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'superadmin') ON DUPLICATE KEY UPDATE password = ?, role = 'superadmin'");
        $stmt->execute([$admin_user, $hashed_pass, $hashed_pass]);

        // 5. Generate config/db.php
        $config_content = "<?php
define('DB_HOST', '$db_host');
define('DB_NAME', '$db_name');
define('DB_USER', '$db_user');
define('DB_PASS', '$db_pass');

\$pdo = new PDO(\"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME, DB_USER, DB_PASS);
\$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
";
        if (!is_dir(__DIR__ . '/config')) {
            mkdir(__DIR__ . '/config', 0775, true);
        }
        
        $write_success = @file_put_contents(__DIR__ . '/config/db.php', $config_content);

        if ($write_success === false) {
            $success = "Database setup complete, but <strong>permission denied</strong> to write <code>config/db.php</code>. <br><br> Please create the file manually with this content:<br><pre class='bg-slate-800 text-white p-4 rounded mt-2 text-xs overflow-x-auto'>" . htmlspecialchars($config_content) . "</pre>";
        } else {
            $success = "Installation successful! <a href='login.php' class='underline'>Login here</a>";
        }
    } catch (PDOException $e) {
        $error = "Connection failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install - TRAVEL-FINANCE PRO</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .glass { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-6">
    <div class="max-w-md w-full glass p-8 rounded-2xl shadow-xl border border-white">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-blue-600">Travel Finance Pro</h1>
            <p class="text-slate-500">System Installation</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 text-red-600 p-4 rounded-lg mb-6 border border-red-100 italic">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-50 text-green-600 p-4 rounded-lg mb-6 border border-green-100">
                <?php echo $success; ?>
            </div>
        <?php else: ?>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Database Host</label>
                    <input type="text" name="db_host" value="localhost" required class="w-full p-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Database Name</label>
                    <input type="text" name="db_name" value="tfinance" required class="w-full p-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">DB User</label>
                        <input type="text" name="db_user" value="root" required class="w-full p-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">DB Password</label>
                        <input type="password" name="db_pass" class="w-full p-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                    </div>
                </div>
                <hr class="my-6">
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Admin Username</label>
                    <input type="text" name="admin_user" value="admin" required class="w-full p-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Admin Password</label>
                    <input type="password" name="admin_pass" required class="w-full p-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg hover:bg-blue-700 transition-colors shadow-lg shadow-blue-200">
                    Install System
                </button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>

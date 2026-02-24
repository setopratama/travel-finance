<?php
// includes/sidebar.php
?>
<aside class="w-64 bg-white border-r border-slate-200 hidden md:flex flex-col no-print">
    <div class="p-6">
        <div class="flex items-center space-x-3 text-blue-600 mb-10">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
            </svg>
            <span class="text-xl font-bold tracking-tight text-slate-800 uppercase">T-Finance <span class="text-blue-600">Pro</span></span>
        </div>
        
        <nav class="space-y-1">
            <a href="index.php" class="flex items-center space-x-3 p-3 rounded-xl transition-all <?php echo $current_page == 'index.php' ? 'sidebar-item-active font-semibold' : 'text-slate-500 hover:bg-slate-50 hover:text-blue-600'; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span>Dashboard</span>
            </a>
            
            <div class="pt-4 pb-2 px-3 text-xs font-bold text-slate-400 uppercase tracking-widest">Transactions</div>
            
            <a href="transactions.php?type=INCOME" class="flex items-center space-x-3 p-3 rounded-xl transition-all <?php echo ($current_page == 'transactions.php' && ($_GET['type'] ?? '') == 'INCOME') ? 'sidebar-item-active font-semibold' : 'text-slate-500 hover:bg-slate-50 hover:text-green-600'; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 11l3-3m0 0l3 3m-3-3v8m0-13a9 9 0 110 18 9 9 0 010-18z" />
                </svg>
                <span>Sales / Invoices</span>
            </a>
            
            <a href="transactions.php?type=EXPENSE" class="flex items-center space-x-3 p-3 rounded-xl transition-all <?php echo ($current_page == 'transactions.php' && ($_GET['type'] ?? '') == 'EXPENSE') ? 'sidebar-item-active font-semibold' : 'text-slate-500 hover:bg-slate-50 hover:text-red-600'; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13l-3 3m0 0l-3-3m3 3V8m0 13a9 9 0 110-18 9 9 0 010-18z" />
                </svg>
                <span>Purchases / Expenses</span>
            </a>

            <a href="transactions.php?type=REFUND" class="flex items-center space-x-3 p-3 rounded-xl transition-all <?php echo ($current_page == 'transactions.php' && ($_GET['type'] ?? '') == 'REFUND') ? 'sidebar-item-active font-semibold' : 'text-slate-500 hover:bg-slate-50 hover:text-amber-600'; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z" />
                </svg>
                <span>Refund / Reversal</span>
            </a>

            <a href="categories.php" class="flex items-center space-x-3 p-3 rounded-xl transition-all <?php echo $current_page == 'categories.php' ? 'sidebar-item-active font-semibold' : 'text-slate-500 hover:bg-slate-50 hover:text-indigo-600'; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <span>Manage Categories</span>
            </a>

            <a href="customers.php" class="flex items-center space-x-3 p-3 rounded-xl transition-all <?php echo $current_page == 'customers.php' ? 'sidebar-item-active font-semibold' : 'text-slate-500 hover:bg-slate-50 hover:text-blue-600'; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span>Manage Customers</span>
            </a>

            <div class="pt-4 pb-2 px-3 text-xs font-bold text-slate-400 uppercase tracking-widest">System</div>
            
            <a href="settings.php" class="flex items-center space-x-3 p-3 rounded-xl transition-all <?php echo $current_page == 'settings.php' ? 'sidebar-item-active font-semibold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800'; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>Settings</span>
            </a>

            <a href="logout.php" class="flex items-center space-x-3 p-3 rounded-xl transition-all text-red-500 hover:bg-red-50 mt-auto">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span>Logout</span>
            </a>
        </nav>
        
        <div class="mt-auto pt-6 border-t border-slate-100">
            <div class="flex items-center space-x-3 p-3 bg-slate-50 rounded-2xl">
                <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center text-blue-600 font-bold">
                    <?php echo strtoupper(substr($_SESSION['username'] ?? '', 0, 1)); ?>
                </div>
                <div>
                    <div class="text-sm font-bold text-slate-800"><?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></div>
                    <div class="text-[10px] uppercase font-bold text-blue-500"><?php echo htmlspecialchars($_SESSION['role'] ?? ''); ?></div>
                </div>
            </div>
        </div>
    </div>
</aside>

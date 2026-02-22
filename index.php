<?php
require_once 'includes/functions.php';
requireLogin();

include 'includes/header.php';
include 'includes/sidebar.php';

// Fetch Statistics
// Fetch Statistics
$revenue = $pdo->query("SELECT SUM(total_amount) FROM transactions WHERE type='INCOME' AND status='PAID'")->fetchColumn() ?: 0;

// Total COGS (Direct Costs: Ticket, Hotel, Tour)
$cogs = $pdo->query("SELECT SUM(total_amount) FROM transactions WHERE type='EXPENSE' AND category IN ('TICKET', 'HOTEL', 'TOUR') AND status='PAID'")->fetchColumn() ?: 0;

// Total OPEX (Operating Expenses: Operational, Marketing, etc.)
$opex = $pdo->query("SELECT SUM(total_amount) FROM transactions WHERE type='EXPENSE' AND category NOT IN ('TICKET', 'HOTEL', 'TOUR') AND status='PAID'")->fetchColumn() ?: 0;

$total_expenses = $cogs + $opex;
$net_profit = $revenue - $total_expenses;
$pending = $pdo->query("SELECT SUM(total_amount) FROM transactions WHERE status='PENDING'")->fetchColumn() ?: 0;

// Chart Data (Last 7 Days)
$chart_data = $pdo->query("
    SELECT date, 
           SUM(CASE WHEN type='INCOME' THEN total_amount ELSE 0 END) as income,
           SUM(CASE WHEN type='EXPENSE' THEN total_amount ELSE 0 END) as expense
    FROM transactions 
    WHERE date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    AND status != 'CANCELLED'
    GROUP BY date 
    ORDER BY date ASC
")->fetchAll(PDO::FETCH_ASSOC);

$labels = [];
$income_vals = [];
$expense_vals = [];
foreach ($chart_data as $row) {
    $labels[] = date('d M', strtotime($row['date']));
    $income_vals[] = $row['income'];
    $expense_vals[] = $row['expense'];
}
?>

<main class="flex-1 p-8 overflow-y-auto">
    <header class="flex justify-between items-center mb-10">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Financial Dashboard</h2>
            <p class="text-sm text-slate-500">Welcome back, <?php echo $_SESSION['username']; ?>!</p>
        </div>
        <div class="flex space-x-3">
            <a href="transactions.php?action=add&type=INCOME" class="bg-blue-600 text-white px-5 py-3 rounded-xl font-bold hover:bg-blue-700 transition-all shadow-lg shadow-blue-100 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                New Invoice
            </a>
        </div>
    </header>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
            <p class="text-slate-400 text-[10px] font-bold mb-1 uppercase tracking-widest">Total Revenue</p>
            <h3 class="text-xl font-bold text-slate-800"><?php echo formatCurrency($revenue); ?></h3>
            <div class="mt-4 flex items-center text-green-500 text-sm font-medium">
                <span class="w-8 h-8 rounded-lg bg-green-50 flex items-center justify-center mr-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </span>
                Paid Income
            </div>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
            <p class="text-slate-400 text-[10px] font-bold mb-1 uppercase tracking-widest">Total Expense</p>
            <h3 class="text-xl font-bold text-slate-800"><?php echo formatCurrency($total_expenses); ?></h3>
            <div class="mt-4 flex items-center text-red-500 text-sm font-medium">
                <span class="w-8 h-8 rounded-lg bg-red-50 flex items-center justify-center mr-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6" />
                    </svg>
                </span>
                COGS + Operational
            </div>
        </div>
        <div class="bg-blue-600 p-6 rounded-2xl shadow-xl shadow-blue-100">
            <p class="text-blue-100 text-[10px] font-bold mb-1 uppercase tracking-widest">Net Profit</p>
            <h3 class="text-xl font-bold text-white"><?php echo formatCurrency($net_profit); ?></h3>
            <div class="mt-4 text-blue-200 text-sm font-medium">
                Sisa bersih (Income-All)
            </div>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
            <p class="text-slate-400 text-[10px] font-bold mb-1 uppercase tracking-widest">Pending Payments</p>
            <h3 class="text-xl font-bold text-slate-800"><?php echo formatCurrency($pending); ?></h3>
            <div class="mt-4 flex items-center text-amber-500 text-sm font-medium">
                <span class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center mr-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
                To be collected
            </div>
        </div>
    </div>

    <!-- Chart and Recent -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 bg-white p-8 rounded-2xl border border-slate-100 shadow-sm">
            <h4 class="text-base font-bold text-slate-800 mb-6">Financial Trends</h4>
            <canvas id="balanceChart" height="200"></canvas>
        </div>
        <div class="bg-white p-8 rounded-2xl border border-slate-100 shadow-sm">
            <h4 class="text-base font-bold text-slate-800 mb-6">Quick Stats</h4>
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Tickets Sold</span>
                    <span class="font-bold text-slate-800"><?php echo $pdo->query("SELECT COUNT(*) FROM transactions WHERE category='TICKET'")->fetchColumn(); ?></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Hotels Booked</span>
                    <span class="font-bold text-slate-800"><?php echo $pdo->query("SELECT COUNT(*) FROM transactions WHERE category='HOTEL'")->fetchColumn(); ?></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Unpaid Invoices</span>
                    <span class="font-bold text-amber-600"><?php echo $pdo->query("SELECT COUNT(*) FROM transactions WHERE status='PENDING' AND type='INCOME'")->fetchColumn(); ?></span>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
const ctx = document.getElementById('balanceChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($labels); ?>,
        datasets: [{
            label: 'Income',
            data: <?php echo json_encode($income_vals); ?>,
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37, 99, 235, 0.1)',
            fill: true,
            tension: 0.4
        }, {
            label: 'Expense',
            data: <?php echo json_encode($expense_vals); ?>,
            borderColor: '#dc2626',
            backgroundColor: 'rgba(220, 38, 38, 0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>

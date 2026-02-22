<?php
// includes/header.php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel Finance Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        html { font-size: 14px; }
        body { font-family: 'Outfit', sans-serif; background-color: #f8fafc; color: #334155; }
        .sidebar-item-active { background-color: #eff6ff; color: #2563eb; border-right: 4px solid #2563eb; }
        .glass-card { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.5); }
        h1, h2, h3, h4 { letter-spacing: -0.025em; }
        .dropdown-menu { display: none; }
        .dropdown-menu.show { display: block; }
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
            body { background: white; }
        }
    </style>
    <script>
        document.addEventListener('click', function(e) {
            const dropdowns = document.querySelectorAll('.dropdown-menu');
            dropdowns.forEach(d => {
                if (!d.contains(e.target) && !d.previousElementSibling.contains(e.target)) {
                    d.classList.remove('show');
                }
            });

            const toggle = e.target.closest('.dropdown-toggle');
            if (toggle) {
                const menu = toggle.nextElementSibling;
                menu.classList.toggle('show');
            }
        });
    </script>
</head>
<body class="flex min-h-screen">

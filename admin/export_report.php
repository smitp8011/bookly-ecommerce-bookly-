<?php
require_once '../config.php';
require_once '../includes/functions.php';
require_admin();

if (!csrf_check($_GET['csrf'] ?? '')) {
    flash('error', 'Session expired, please try again.');
    redirect('reports.php');
}

$from = $_GET['from'] ?? date('Y-m-d', strtotime('-29 days'));
$to   = $_GET['to'] ?? date('Y-m-d');
if (strtotime($from) > strtotime($to)) { [$from, $to] = [$to, $from]; }
$to_inclusive = $to . ' 23:59:59';

$stmt = $pdo->prepare(
    "SELECT o.id, o.full_name, o.email, o.phone, o.payment_method, o.coupon_code, o.discount_amount,
            o.total_price, o.status, o.placed_on
     FROM orders o WHERE o.placed_on BETWEEN ? AND ? ORDER BY o.placed_on DESC"
);
$stmt->execute([$from, $to_inclusive]);
$orders = $stmt->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="booknest_orders_' . $from . '_to_' . $to . '.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Order ID', 'Customer', 'Email', 'Phone', 'Payment Method', 'Coupon', 'Discount', 'Total', 'Status', 'Placed On']);

foreach ($orders as $o) {
    fputcsv($out, [
        $o['id'],
        $o['full_name'],
        $o['email'],
        $o['phone'],
        $o['payment_method'],
        $o['coupon_code'] ?? '—',
        number_format((float) $o['discount_amount'], 2),
        number_format((float) $o['total_price'], 2),
        ucfirst($o['status']),
        $o['placed_on'],
    ]);
}
fclose($out);
exit;

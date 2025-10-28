<?php
// backend/api/finance.php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config.php';

try {
    // ---------- Helper: ambil daftar kolom tabel ----------
    function tableColumns(mysqli $conn, string $table): array {
        $cols = [];
        if ($res = $conn->query("SHOW COLUMNS FROM $table")) {
            while ($r = $res->fetch_assoc()) $cols[] = $r['Field'];
            $res->close();
        }
        return $cols;
    }

    // ---------- Konfigurasi dinamis ----------
    $ordersTable   = 'orders';
    $paymentsTable = 'payments';

    $ordCols = tableColumns($conn, $ordersTable);
    $payCols = tableColumns($conn, $paymentsTable);

    // Kolom jumlah (prioritas)
    $orderAmountCol = in_array('total', $ordCols, true) ? 'total'
                    : (in_array('total_price', $ordCols, true) ? 'total_price'
                    : (in_array('grand_total', $ordCols, true) ? 'grand_total'
                    : 'total')); // fallback

    $paymentAmountCol = in_array('amount_gross', $payCols, true) ? 'amount_gross'
                      : (in_array('amount', $payCols, true) ? 'amount'
                      : (in_array('total', $payCols, true) ? 'total'
                      : 'amount_gross')); // fallback

    // Kolom status
    $orderStatusCol = in_array('order_status',  $ordCols, true) ? 'order_status'
                    : (in_array('status', $ordCols, true)       ? 'status'
                    : 'order_status'); // fallback

    $paymentStatusCol = in_array('status', $payCols, true) ? 'status' : 'status';

    // Kolom tanggal
    $orderDateCol = in_array('created_at', $ordCols, true) ? 'created_at'
                  : (in_array('issued_at',  $ordCols, true) ? 'issued_at'
                  : (in_array('createdAt', $ordCols, true) ? 'createdAt'
                  : 'created_at')); // fallback

    $paymentDateCol = in_array('paid_at', $payCols, true) ? 'paid_at'
                    : (in_array('created_at', $payCols, true) ? 'created_at'
                    : (in_array('updated_at', $payCols, true) ? 'updated_at'
                    : 'created_at')); // fallback

    // Status yang dianggap paid / selesai
    $paidPayment = 'paid';
    $doneOrders  = 'completed';

    // ---------- Range tanggal ----------
    $rawStart = $_GET['start'] ?? '';
    $rawEnd   = $_GET['end']   ?? '';

    // normalisasi YYYY-MM-DD
    $start = preg_replace('/[^0-9\-]/', '', $rawStart);
    $end   = preg_replace('/[^0-9\-]/', '', $rawEnd);

    $today = new DateTime('today');
    if (!$start) $start = (clone $today)->modify('-6 days')->format('Y-m-d');
    if (!$end)   $end   = $today->format('Y-m-d');

    $dtStart = new DateTime($start . ' 00:00:00');
    $dtEnd   = new DateTime($end   . ' 23:59:59');

    // ---------- Total revenue (payments paid) ----------
    $sql = "SELECT COALESCE(SUM($paymentAmountCol),0) AS rev
            FROM $paymentsTable
            WHERE $paymentStatusCol = ? AND $paymentDateCol BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql);
    $s = $paidPayment;
    $a = $dtStart->format('Y-m-d H:i:s');
    $b = $dtEnd->format('Y-m-d H:i:s');
    $stmt->bind_param('sss', $s, $a, $b);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $totalRevenue = (float)($row['rev'] ?? 0);
    $stmt->close();

    // ---------- Total orders (semua) ----------
    $sql = "SELECT COUNT(*) AS cnt
            FROM $ordersTable
            WHERE $orderDateCol BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $a, $b);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $totalOrders = (int)($row['cnt'] ?? 0);
    $stmt->close();

    // ---------- AOV ----------
    $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0.0;

    // ---------- Revenue harian (payments paid) ----------
    $sql = "SELECT DATE($paymentDateCol) AS d, COALESCE(SUM($paymentAmountCol),0) AS rev
            FROM $paymentsTable
            WHERE $paymentStatusCol = ? AND $paymentDateCol BETWEEN ? AND ?
            GROUP BY DATE($paymentDateCol)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sss', $s, $a, $b);
    $stmt->execute();
    $revMap = [];
    $rs = $stmt->get_result();
    while ($r = $rs->fetch_assoc()) {
        $revMap[(string)$r['d']] = (float)$r['rev'];
    }
    $stmt->close();

    // ---------- Orders harian (semua) ----------
    $sql = "SELECT DATE($orderDateCol) AS d, COUNT(*) AS c
            FROM $ordersTable
            WHERE $orderDateCol BETWEEN ? AND ?
            GROUP BY DATE($orderDateCol)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $a, $b);
    $stmt->execute();
    $ordMap = [];
    $rs = $stmt->get_result();
    while ($r = $rs->fetch_assoc()) {
        $ordMap[(string)$r['d']] = (int)$r['c'];
    }
    $stmt->close();

    // ---------- Bentuk deret tanggal lengkap (agar grafik tidak kosong) ----------
    $days = [];
    $iter = new DatePeriod(
        new DateTime($start),
        new DateInterval('P1D'),
        (new DateTime($end))->modify('+1 day')
    );
    foreach ($iter as $d) {
        $key = $d->format('Y-m-d');
        $days[] = [
            'date'    => $key,
            'revenue' => $revMap[$key] ?? 0.0,
            'orders'  => $ordMap[$key] ?? 0,
        ];
    }

    // ---------- Distribusi status pesanan ----------
    $sql = "SELECT $orderStatusCol AS s, COUNT(*) AS cnt
            FROM $ordersTable
            WHERE $orderDateCol BETWEEN ? AND ?
            GROUP BY $orderStatusCol";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $a, $b);
    $stmt->execute();
    $rs = $stmt->get_result();
    $statusCounts = [];
    while ($r = $rs->fetch_assoc()) {
        $statusCounts[(string)$r['s']] = (int)$r['cnt'];
    }
    $stmt->close();

    echo json_encode([
        'ok'               => true,
        'range'            => ['start'=>$start, 'end'=>$end],
        'amount_column'    => ['orders'=>$orderAmountCol, 'payments'=>$paymentAmountCol],
        'date_column'      => ['orders'=>$orderDateCol, 'payments'=>$paymentDateCol],
        'total_revenue'    => $totalRevenue,
        'total_orders'     => $totalOrders,
        'avg_order_value'  => $avgOrderValue,
        'days'             => $days,
        'status_counts'    => $statusCounts,
    ], JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok'    => false,
        'error' => $e->getMessage()
    ]);
}

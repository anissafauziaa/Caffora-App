<?php
// backend/api/orders.php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config.php';

/* ---------- DB CONNECT ---------- */
$mysqli = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_errno) {
  echo json_encode(['ok'=>false,'error'=>'DB connect failed: '.$mysqli->connect_error], JSON_UNESCAPED_SLASHES);
  exit;
}
$mysqli->set_charset('utf8mb4');

/* ---------- HELPERS ---------- */
function out($arr){ echo json_encode($arr, JSON_UNESCAPED_SLASHES); exit; }
function bad($msg, $code=400){ http_response_code($code); out(['ok'=>false,'error'=>$msg]); }

/* ---------- INPUT ---------- */
$action = $_GET['action'] ?? 'list';

/* ---------- CONSTANTS ---------- */
$ALLOWED_ORDER_STATUS   = ['new','processing','ready','completed','cancelled'];
$ALLOWED_PAYMENT_STATUS = ['pending','paid','failed','refunded','overdue'];
$ALLOWED_METHOD         = ['cash','bank_transfer','qris','ewallet'];
$ALLOWED_SERVICE        = ['dine_in','take_away'];

/* ============================================================
 * LIST ORDERS (admin / karyawan)
 * ============================================================ */
if ($action === 'list') {
  $q             = trim((string)($_GET['q'] ?? ''));
  $order_status  = trim((string)($_GET['order_status'] ?? ''));
  $payment_status= trim((string)($_GET['payment_status'] ?? ''));

  $where  = [];
  $types  = '';
  $params = [];

  if ($q !== '') {
    $where[] = '(invoice_no LIKE ? OR customer_name LIKE ?)';
    $like = '%'.$q.'%';
    $params[] = $like; $params[] = $like; $types .= 'ss';
  }
  if ($order_status !== '') {
    if (!in_array($order_status, $ALLOWED_ORDER_STATUS, true)) bad('Invalid order_status');
    $where[] = 'order_status = ?';
    $params[] = $order_status; $types .= 's';
  }
  if ($payment_status !== '') {
    if (!in_array($payment_status, $ALLOWED_PAYMENT_STATUS, true)) bad('Invalid payment_status');
    $where[] = 'payment_status = ?';
    $params[] = $payment_status; $types .= 's';
  }

  $sql = "SELECT id, user_id, invoice_no, customer_name, service_type, table_no, total,
                 order_status, payment_status, payment_method, created_at, updated_at
          FROM orders";
  if ($where) $sql .= ' WHERE '.implode(' AND ', $where);
  $sql .= ' ORDER BY created_at DESC, id DESC';

  if ($params) {
    $stmt = $mysqli->prepare($sql);
    if(!$stmt) bad('Prepare failed: '.$mysqli->error, 500);
    $stmt->bind_param($types, ...$params);
    if(!$stmt->execute()) bad('Execute failed: '.$stmt->error, 500);
    $res = $stmt->get_result();
  } else {
    $res = $mysqli->query($sql);
    if(!$res) bad('Query failed: '.$mysqli->error, 500);
  }

  $items = [];
  while($row = $res->fetch_assoc()){
    $row['id'] = (int)$row['id'];
    $row['user_id'] = $row['user_id']!==null ? (int)$row['user_id'] : null;
    $row['total'] = (float)$row['total'];
    $items[] = $row;
  }
  out(['ok'=>true,'items'=>$items]);
}

/* ============================================================
 * CREATE ORDER (checkout customer)
 *  Body JSON:
 *  {
 *    "customer_name": "...",
 *    "service_type": "dine_in|take_away",
 *    "table_no": "05",
 *    "payment_method": "cash|ewallet|qris|bank_transfer",
 *    "payment_status": "pending|paid|failed|refunded|overdue" (opsional, default pending)
 *    "items":[ { id|menu_id, name?, price, qty }, ... ]
 *  }
 *  -> INSERT orders + order_items + invoices (TRANSACTION)
 * ============================================================ */
if ($action === 'create') {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') bad('Use POST', 405);

  $raw  = file_get_contents('php://input');
  $data = json_decode($raw,true);
  if(!is_array($data)) bad('Invalid JSON body');

  $cust_name = trim((string)($data['customer_name'] ?? ''));
  $service   = (string)($data['service_type'] ?? '');
  $table_no  = trim((string)($data['table_no'] ?? ''));
  $paymethod = (string)($data['payment_method'] ?? '');
  $paystatus = (string)($data['payment_status'] ?? 'pending');
  $items     = $data['items'] ?? [];

  if($cust_name==='') bad('Nama customer wajib');
  if(!in_array($service,$ALLOWED_SERVICE,true)) bad('Invalid service_type');
  if(!in_array($paymethod,$ALLOWED_METHOD,true)) bad('Invalid payment_method');
  if(!in_array($paystatus,$ALLOWED_PAYMENT_STATUS,true)) bad('Invalid payment_status');
  if(!is_array($items) || !count($items)) bad('Items kosong');

  // total
  $total=0.0;
  foreach($items as $it){
    $qty=(int)($it['qty']??0);
    $price=(float)($it['price']??0);
    if($qty<=0||$price<0) continue;
    $total += $price*$qty;
  }
  if($total<=0) bad('Total invalid');

  // invoice number sederhana (urut global)
  $res=$mysqli->query("SELECT COUNT(*) c FROM orders");
  $row=$res->fetch_assoc();
  $invno='INV-'.str_pad((string)($row['c']+1),3,'0',STR_PAD_LEFT);

  $uid = $_SESSION['user_id'] ?? null;

  // === TRANSACTION ===
  $mysqli->begin_transaction();
  try {
    // orders
    $stmt=$mysqli->prepare("INSERT INTO orders (user_id,invoice_no,customer_name,service_type,table_no,total,order_status,payment_status,payment_method,created_at,updated_at)
                            VALUES (?,?,?,?,?,?, 'new', ?, ?, NOW(), NOW())");
    if(!$stmt) throw new Exception('Prepare(order) failed: '.$mysqli->error);
    $stmt->bind_param('issssdss', $uid,$invno,$cust_name,$service,$table_no,$total,$paystatus,$paymethod);
    if(!$stmt->execute()) throw new Exception('Insert order failed: '.$stmt->error);
    $order_id = (int)$stmt->insert_id;
    $stmt->close();

    // order_items
    $stmtItem = $mysqli->prepare("INSERT INTO order_items (order_id, menu_id, qty, price, discount, cogs_unit) VALUES (?, ?, ?, ?, 0.00, NULL)");
    if(!$stmtItem) throw new Exception('Prepare(item) failed: '.$mysqli->error);

    foreach($items as $it){
      // dukung key 'menu_id' maupun 'id' dari front-end
      $menu_id = (int)($it['menu_id'] ?? $it['id'] ?? 0);
      $qty     = (int)($it['qty'] ?? 0);
      $price   = (float)($it['price'] ?? 0);
      if($menu_id<=0 || $qty<=0) continue;

      $stmtItem->bind_param('iiid', $order_id, $menu_id, $qty, $price);
      if(!$stmtItem->execute()) throw new Exception('Insert item failed: '.$stmtItem->error);
    }
    $stmtItem->close();

    // invoices
    $stmtInv = $mysqli->prepare("INSERT INTO invoices (order_id, amount) VALUES (?, ?)");
    if(!$stmtInv) throw new Exception('Prepare(invoice) failed: '.$mysqli->error);
    $stmtInv->bind_param('id', $order_id, $total);
    if(!$stmtInv->execute()) throw new Exception('Insert invoice failed: '.$stmtInv->error);
    $stmtInv->close();

    $mysqli->commit();
    out(['ok'=>true,'id'=>$order_id,'invoice_no'=>$invno]);

  } catch (Throwable $e) {
    $mysqli->rollback();
    bad($e->getMessage(), 500);
  }
}

/* ============================================================
 * UPDATE ORDER (status, pembayaran)
 * ============================================================ */
if ($action === 'update') {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') bad('Use POST', 405);
  $raw=file_get_contents('php://input');
  $data=json_decode($raw,true);
  if(!is_array($data)) bad('Invalid JSON');

  $id=(int)($data['id']??0);
  if($id<=0) bad('Missing id');

  $fields=[]; $params=[]; $types='';
  if(!empty($data['order_status'])){
    $val=(string)$data['order_status'];
    if(!in_array($val,$ALLOWED_ORDER_STATUS,true)) bad('Invalid order_status');
    $fields[]="order_status=?"; $params[]=$val; $types.='s';
  }
  if(!empty($data['payment_status'])){
    $val=(string)$data['payment_status'];
    if(!in_array($val,$ALLOWED_PAYMENT_STATUS,true)) bad('Invalid payment_status');
    $fields[]="payment_status=?"; $params[]=$val; $types.='s';
  }
  if(array_key_exists('payment_method',$data)){
    $val=$data['payment_method'];
    if($val===null||$val===''){ $fields[]="payment_method=NULL"; }
    else {
      if(!in_array($val,$ALLOWED_METHOD,true)) bad('Invalid payment_method');
      $fields[]="payment_method=?"; $params[]=$val; $types.='s';
    }
  }

  if(!$fields) bad('No fields to update');

  $sql="UPDATE orders SET ".implode(', ',$fields).", updated_at=NOW() WHERE id=?";
  $params[]=$id; $types.='i';
  $stmt=$mysqli->prepare($sql);
  if(!$stmt) bad('Prepare failed: '.$mysqli->error,500);
  $stmt->bind_param($types,...$params);
  $ok=$stmt->execute();
  if(!$ok) bad('Execute failed: '.$stmt->error,500);

  out(['ok'=>true]);
}

/* ---------- INVALID ACTION ---------- */
bad('Invalid action',404);
<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id && $action !== 'check_orders') {
    echo json_encode(['success' => false, 'message' => 'Please log in.']);
    exit;
}

if ($action === 'create_order') {
    $coin = $_POST['coin'] ?? '';
    $type = $_POST['type'] ?? '';
    $amount_usd = floatval($_POST['amount_usd'] ?? 0);
    $target_price = floatval($_POST['target_price'] ?? 0);

    if ($amount_usd <= 0 || $target_price <= 0 || !in_array($type, ['buy', 'sell'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
        exit;
    }

    $conn->begin_transaction();
    try {
        if ($type === 'buy') {
            $stmt = $conn->prepare("SELECT usd_balance FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            if ($stmt->get_result()->fetch_assoc()['usd_balance'] < $amount_usd) {
                throw new Exception('Insufficient USD balance.');
            }
            $conn->query("UPDATE users SET usd_balance = usd_balance - $amount_usd WHERE id = $user_id");
        } else {
            $coins_to_sell = $amount_usd / $target_price;
            $stmt = $conn->prepare("SELECT amount FROM holdings WHERE user_id = ? AND coin = ?");
            $stmt->bind_param("is", $user_id, $coin);
            $stmt->execute();
            if (($stmt->get_result()->fetch_assoc()['amount'] ?? 0) < $coins_to_sell) {
                throw new Exception('Insufficient crypto balance.');
            }
            $conn->query("UPDATE holdings SET amount = amount - $coins_to_sell WHERE user_id = $user_id AND coin = '$coin'");
        }

        $stmt = $conn->prepare("INSERT INTO limit_orders (user_id, coin, type, amount_usd, target_price, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("issdd", $user_id, $coin, $type, $amount_usd, $target_price);
        $stmt->execute();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => ucfirst($type) . " Limit Order set at $" . number_format($target_price, 2)]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'check_orders') {
    $current_prices = json_decode($_POST['prices'], true);
    
    if (!$current_prices) { echo json_encode(['success' => false]); exit; }

    $res = $conn->query("SELECT * FROM limit_orders WHERE status = 'pending'");
    $executed = 0;

    while ($order = $res->fetch_assoc()) {
        $coin = $order['coin'];
        if (!isset($current_prices[$coin])) continue;

        $current_price = floatval($current_prices[$coin]);
        $target = floatval($order['target_price']);
        $should_execute = false;

        if ($order['type'] === 'buy' && $current_price <= $target) { $should_execute = true; }
        if ($order['type'] === 'sell' && $current_price >= $target) { $should_execute = true; }

        if ($should_execute) {
            $conn->begin_transaction();
            try {
                $uid = $order['user_id'];
                $coins_traded = $order['amount_usd'] / $current_price;
                
                if ($order['type'] === 'buy') {
                    $conn->query("INSERT INTO holdings (user_id, coin, amount) VALUES ($uid, '$coin', $coins_traded) ON DUPLICATE KEY UPDATE amount = amount + $coins_traded");
                } else {
                    $conn->query("UPDATE users SET usd_balance = usd_balance + {$order['amount_usd']} WHERE id = $uid");
                }

                $conn->query("INSERT INTO transactions (user_id, coin, type, amount_usd, price, coins) VALUES ($uid, '$coin', '{$order['type']}', {$order['amount_usd']}, $current_price, $coins_traded)");
                $conn->query("UPDATE limit_orders SET status = 'completed' WHERE id = {$order['id']}");
                
                $conn->commit();
                $executed++;
            } catch (Exception $e) {
                $conn->rollback();
            }
        }
    }
    echo json_encode(['success' => true, 'executed' => $executed]);
    exit;
}
?>
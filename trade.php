<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to trade.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action  = $_POST['action'] ?? '';
$coin    = $_POST['coin']   ?? '';
$amount_usd = floatval($_POST['amount_usd'] ?? 0);
$price   = floatval($_POST['price'] ?? 0);

if ($amount_usd <= 0 || $price <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid trade amount.']);
    exit;
}

$coins_to_trade = $amount_usd / $price;

$conn->begin_transaction();
try {
    if ($action === 'buy') {
        $stmt = $conn->prepare("SELECT usd_balance FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        
        if ($res['usd_balance'] < $amount_usd) {
            throw new Exception('Insufficient USD balance.');
        }

        $conn->query("UPDATE users SET usd_balance = usd_balance - $amount_usd WHERE id = $user_id");
        $conn->query("INSERT INTO holdings (user_id, coin, amount) VALUES ($user_id, '$coin', $coins_to_trade) ON DUPLICATE KEY UPDATE amount = amount + $coins_to_trade");

    } elseif ($action === 'sell') {
        $stmt = $conn->prepare("SELECT amount FROM holdings WHERE user_id = ? AND coin = ?");
        $stmt->bind_param("is", $user_id, $coin);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        
        if (($res['amount'] ?? 0) < $coins_to_trade) {
            throw new Exception('Insufficient crypto balance.');
        }

        $conn->query("UPDATE users SET usd_balance = usd_balance + $amount_usd WHERE id = $user_id");
        $conn->query("UPDATE holdings SET amount = amount - $coins_to_trade WHERE user_id = $user_id AND coin = '$coin'");
    } else {
        throw new Exception('Unknown action.');
    }

    $stmt = $conn->prepare("INSERT INTO transactions (user_id, coin, type, amount_usd, price, coins) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issddd", $user_id, $coin, $action, $amount_usd, $price, $coins_to_trade);
    $stmt->execute();

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
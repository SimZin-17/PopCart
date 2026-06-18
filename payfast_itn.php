<?php
require 'connection.php';

// 1. Tell PayFast we received the request
header('HTTP/1.0 200 OK');
flush();

// 2. Capture ITN POST variables from PayFast
$pfData = $_POST;

// 3. Verify the payment status
if ($pfData['payment_status'] === 'COMPLETE') {
    
    $order_id = (int)$pfData['m_payment_id'];
    $amount_paid = $pfData['amount_gross'];

    // 4. Verify order exists and is pending
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? AND order_status = 'pending_payment'");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if ($order && number_format($order['total_amount'], 2, '.', '') === number_format($amount_paid, 2, '.', '')) {
        
        try {
            $pdo->beginTransaction();

            // A. Mark order as paid
            $pdo->prepare("UPDATE orders SET order_status = 'paid' WHERE order_id = ?")->execute([$order_id]);

            // B. Fetch the items for this order to deduct stock
            $itemStmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
            $itemStmt->execute([$order_id]);
            $orderItems = $itemStmt->fetchAll();

            // C. Deduct Stock
            $stmtStock = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ?");
            foreach ($orderItems as $item) {
                $stmtStock->execute([$item['quantity'], $item['product_id']]);
            }

            // D. Clear the user's cart (We grab the user_id from the orders table)
            $pdo->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$order['user_id']]);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            // Log error to a file: error_log($e->getMessage());
        }
    }
}
?>
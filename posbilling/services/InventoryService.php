<?php
class InventoryService {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function addStock($product_id, $batch_num, $expiry_date, $qty, $cost_price, $selling_price, $mrp) {
        $stmt = $this->conn->prepare("SELECT id, current_qty FROM batches WHERE product_id = ? AND batch_num = ?");
        $stmt->bind_param("is", $product_id, $batch_num);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($batch = $result->fetch_assoc()) {
            $new_qty = $batch['current_qty'] + $qty;
            $update_stmt = $this->conn->prepare("UPDATE batches SET current_qty = ?, cost_price = ?, selling_price = ?, mrp = ?, expiry_date = ? WHERE id = ?");
            $update_stmt->bind_param("idddsi", $new_qty, $cost_price, $selling_price, $mrp, $expiry_date, $batch['id']);
            return $update_stmt->execute();
        } else {
            $insert_stmt = $this->conn->prepare("INSERT INTO batches (product_id, batch_num, expiry_date, cost_price, selling_price, mrp, current_qty) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("issdddi", $product_id, $batch_num, $expiry_date, $cost_price, $selling_price, $mrp, $qty);
            return $insert_stmt->execute();
        }
    }

    public function deductFEFO($product_id, $qty) {
        $stmt = $this->conn->prepare("SELECT id, current_qty, batch_num FROM batches WHERE product_id = ? AND current_qty > 0 AND expiry_date >= CURDATE() ORDER BY expiry_date ASC");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $batches = $stmt->get_result();

        $remaining_qty = $qty;
        $deducted_batches = [];

        while ($remaining_qty > 0 && $batch = $batches->fetch_assoc()) {
            $deduct = min($remaining_qty, $batch['current_qty']);
            $new_qty = $batch['current_qty'] - $deduct;

            $update_stmt = $this->conn->prepare("UPDATE batches SET current_qty = ? WHERE id = ?");
            $update_stmt->bind_param("ii", $new_qty, $batch['id']);
            $update_stmt->execute();

            $deducted_batches[] = [
                'batch_id' => $batch['id'],
                'batch_num' => $batch['batch_num'],
                'qty' => $deduct
            ];
            $remaining_qty -= $deduct;
        }

        if ($remaining_qty > 0) {
            // Error: Not enough stock
            return false;
        }

        return $deducted_batches;
    }

    public function handleReturn($product_id, $batch_id, $qty, $type = 'sale') {
        if ($type === 'sale') {
            // Restore stock to the specific batch
            $stmt = $this->conn->prepare("UPDATE batches SET current_qty = current_qty + ? WHERE id = ?");
            $stmt->bind_param("ii", $qty, $batch_id);
            return $stmt->execute();
        } elseif ($type === 'purchase') {
            // Deduct stock from the specific batch
            $stmt = $this->conn->prepare("UPDATE batches SET current_qty = current_qty - ? WHERE id = ?");
            $stmt->bind_param("ii", $qty, $batch_id);
            return $stmt->execute();
        }
        return false;
    }
}
?>

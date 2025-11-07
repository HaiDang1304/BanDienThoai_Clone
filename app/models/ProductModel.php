<?php
require_once __DIR__ . '/../config/database.php';

class ProductModel
{
    // Lấy danh sách gợi ý sản phẩm
    public static function getRecommended(mysqli $db, int $limit = 12): array
    {
        $sql = "SELECT 
                    id, name, variant, screen, size_inch,
                    price, price_old, gift_value, rating,
                    sold_k, installment, badge, image_url
                FROM products
                ORDER BY id DESC
                LIMIT ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    // Lấy danh sách sản phẩm đặc quyền
    public static function getExclusive(mysqli $db, int $limit = 12): array
    {
        $sql = "SELECT 
                    id, name, variant, screen, size_inch,
                    price, price_old, gift_value, rating,
                    sold_k, installment, badge, image_url
                FROM products
                WHERE badge <> ''
                ORDER BY id DESC
                LIMIT ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }
}

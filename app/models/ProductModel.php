<?php
// app/models/ProductModel.php
class ProductModel
{
    public static function getRecommended(mysqli $conn, int $limit = 10): array
    {
        $sql = "SELECT id, name, variant, screen, size_inch, price, price_old,
                        gift_value, rating, sold_k, installment, badge, image_url
                 FROM products
                 ORDER BY RAND()
                 LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    public static function getExclusive(mysqli $conn, int $limit = 12): array
    {
        // Lọc theo badge 'ĐẶC QUYỀN' (hoặc bạn đổi điều kiện theo cột is_exclusive nếu có)
        $sql = "SELECT id, name, variant, screen, size_inch, price, price_old,
                        gift_value, rating, sold_k, installment, badge, image_url
                 FROM products
                 WHERE badge IS NOT NULL AND badge <> ''
                 ORDER BY id DESC
                 LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }
}

<?php
class HomeController extends Controller
{
    public function index()
    {
        require_once __DIR__ . '/../config/database.php';
        require_once __DIR__ . '/../models/ProductModel.php';

        $recommended = ProductModel::getRecommended($conn, 10);
        $exclusive = ProductModel::getExclusive($conn, 12);

        $data = [
            "title" => "Trang chủ",
            "recommended" => $recommended,
            "exclusive" => $exclusive
        ];
        $this->view("home", $data);
    }
}

<?php
class Controller {
    public function model($model) {
        require_once "../app/models/" . $model . ".php";
        return new $model;
    }

    public function view($view, $data = []) {
        // ✅ Giải nén mảng $data thành các biến riêng
        if (!empty($data) && is_array($data)) {
            extract($data);
        }

        // ✅ Gọi file view
        require_once "../app/views/" . $view . ".php";
    }
}

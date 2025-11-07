<?php
class Controller
{
    // Hàm hiển thị view
    public function view($view, $data = [])
    {
        extract($data);
        require_once "../app/views/" . $view . ".php";
    }

    // Hàm gọi model
    public function model($model)
    {
        require_once "../app/models/" . $model . ".php";
        return new $model;
    }
}

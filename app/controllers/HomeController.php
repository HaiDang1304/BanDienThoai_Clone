<?php
class HomeController extends Controller {
    public function index() {
        $data = [
            "title" => "Trang chủ"
        ];
        $this->view("home", $data);
    }
}

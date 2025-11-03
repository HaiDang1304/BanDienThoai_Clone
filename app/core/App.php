<?php
class App {
    protected $controller = "HomeController";
    protected $action = "index";
    protected $params = [];

    public function __construct() {
        // ✅ Load controller cha trước
        require_once "../app/core/Controller.php";

        $arr = $this->UrlProcess();

        // ✅ Lấy controller nếu tồn tại
        if (isset($arr[0])) {
            $possibleController = "../app/controllers/" . ucfirst($arr[0]) . "Controller.php";
            if (file_exists($possibleController)) {
                $this->controller = ucfirst($arr[0]) . "Controller";
                unset($arr[0]);
            }
        }

        // ✅ Gọi controller
        require_once "../app/controllers/" . $this->controller . ".php";
        $this->controller = new $this->controller;

        // ✅ Gọi action
        if (isset($arr[1]) && method_exists($this->controller, $arr[1])) {
            $this->action = $arr[1];
            unset($arr[1]);
        }

        // ✅ Gán params
        $this->params = $arr ? array_values($arr) : [];

        // ✅ Gọi hàm Controller::Action(params)
        call_user_func_array([$this->controller, $this->action], $this->params);
    }

    public function UrlProcess() {
        if (isset($_GET["url"])) {
            return explode("/", filter_var(trim($_GET["url"], "/")));
        }
        return [];
    }
}

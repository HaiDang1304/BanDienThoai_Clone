<?php
// app/controllers/AuthController.php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/google.php';
require_once __DIR__ . '/../helpers/Mailer.php';
require_once __DIR__ . '/../../vendor/autoload.php';

class AuthController extends Controller
{
    /** @var mysqli */
    private $db;
    private string $BASE_URL = '/BanDienThoai_Clone/public';

    public function __construct()
    {
        $this->db = db();
        $this->db->set_charset('utf8mb4');
    }

    private function url_to(string $p): string
    {
        return rtrim($this->BASE_URL, '/') . '/' . ltrim($p, '/');
    }

    /* -------------------- EMAIL-PASSWORD -------------------- */

    // POST: name, email, password
    public function register()
    {
        if (session_status() === PHP_SESSION_NONE)
            session_start();

        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');

        try {
            $name = trim($_POST['name'] ?? '');
            $email = strtolower(trim($_POST['email'] ?? ''));
            $pass = (string) ($_POST['password'] ?? '');

            if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['status' => 'error', 'message' => 'Tên hoặc email không hợp lệ.']);
                exit;
            }
            if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{6,}$/', $pass)) {
                echo json_encode(['status' => 'error', 'message' => 'Mật khẩu phải ≥6 ký tự và có hoa, thường, số.']);
                exit;
            }

            // Email đã có?
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
            if (!$stmt)
                throw new Exception('Prepare failed: ' . $this->db->error);
            $stmt->bind_param('s', $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                echo json_encode(['status' => 'error', 'message' => 'Email đã được sử dụng.']);
                exit;
            }

            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $token = bin2hex(random_bytes(20));

            $avatarUrl = '/BanDienThoai_Clone/public/assets/images/avata_user/avatar_defound.png';

            // Thêm avatar vào INSERT
            $ins = $this->db->prepare(
                "INSERT INTO users
             (name, email, password_hash, avatar, role, email_verified, verify_token, created_at, updated_at)
             VALUES (?, ?, ?, ?, 'user', 0, ?, NOW(), NOW())"
            );
            if (!$ins)
                throw new Exception('Prepare failed: ' . $this->db->error);

            // name, email, password_hash, avatar, verify_token
            $ins->bind_param('sssss', $name, $email, $hash, $avatarUrl, $token);
            $ins->execute();

            $verifyLink = "http://localhost/BanDienThoai_Clone/public/index.php?url=Auth/verify&token=" . $token;
            $sent = Mailer::send(
                $email,
                "Xác minh tài khoản",
                "<h3>Chào {$name},</h3><p>Nhấn vào liên kết để xác minh:</p><a href='{$verifyLink}'>{$verifyLink}</a>"
            );

            echo json_encode([
                'status' => $sent ? 'success' : 'warning',
                'message' => $sent
                    ? 'Đăng ký thành công! Vui lòng kiểm tra email để xác minh.'
                    : 'Tạo tài khoản thành công nhưng gửi mail thất bại. Kiểm tra cấu hình SMTP.'
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'SERVER: ' . $e->getMessage()]);
        }
        exit;
    }



    // GET: token
    public function verify()
    {
        $token = $_GET['token'] ?? '';
        if ($token === '') {
            http_response_code(400);
            exit('Thiếu token.');
        }

        $stmt = $this->db->prepare("SELECT id FROM users WHERE verify_token=? LIMIT 1");
        if (!$stmt) {
            http_response_code(500);
            exit('Lỗi hệ thống.');
        }
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $stmt->bind_result($uid);
        $found = $stmt->fetch();
        $stmt->close();

        if (!$found) {
            http_response_code(400);
            exit('Token không hợp lệ hoặc đã sử dụng.');
        }

        $upd = $this->db->prepare("UPDATE users SET email_verified=1, verify_token=NULL, updated_at=NOW() WHERE id=?");
        if (!$upd) {
            http_response_code(500);
            exit('Lỗi hệ thống.');
        }
        $upd->bind_param('i', $uid);
        $upd->execute();
        $upd->close();

        echo "<h2>Xác minh thành công!</h2><p><a href='" . $this->url_to("index.php") . "'>Về trang chủ</a></p>";
    }

    // POST: email, password
    public function login()
    {
        if (session_status() === PHP_SESSION_NONE)
            session_start();
        header('Content-Type: application/json; charset=utf-8');

        $email = strtolower(trim($_POST['email'] ?? ''));
        $pass = (string) ($_POST['password'] ?? '');

        $stmt = $this->db->prepare("SELECT id,name,email,avatar,password_hash,email_verified,role FROM users WHERE email=? LIMIT 1");
        if (!$stmt) {
            echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống.']);
            return;
        }
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->bind_result($id, $name, $em, $avatar, $hash, $verified, $role);
        $has = $stmt->fetch();
        $stmt->close();

        if (!$has || !password_verify($pass, (string) $hash)) {
            echo json_encode(['status' => 'error', 'message' => 'Email hoặc mật khẩu không đúng.']);
            return;
        }
        if ((int) $verified !== 1) {
            echo json_encode(['status' => 'error', 'message' => 'Bạn cần xác minh email trước khi đăng nhập.']);
            return;
        }

        $_SESSION['auth'] = [
            'id' => (int) $id,
            'name' => $name,
            'email' => $em,
            'avatar' => $avatar,
            'role' => $role ?: 'user',
            'provider' => 'local'
        ];
        echo json_encode(['status' => 'success', 'message' => 'Đăng nhập thành công!']);
    }

    /* -------------------- GOOGLE OAUTH (SẴN CÓ) -------------------- */

    public function google()
    {
        $client = makeGoogleClient();
        $authUrl = $client->createAuthUrl();
        header('Location: ' . $authUrl);
        exit;
    }

    public function callback()
    {
        if (session_status() === PHP_SESSION_NONE)
            session_start();

        $client = makeGoogleClient();
        if (empty($_GET['code'])) {
            http_response_code(400);
            exit('Thiếu mã xác thực từ Google.');
        }

        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        if (isset($token['error'])) {
            http_response_code(400);
            exit('Lỗi token: ' . htmlspecialchars($token['error_description'] ?? $token['error']));
        }

        $client->setAccessToken($token['access_token']);
        $googleService = new Google_Service_Oauth2($client);
        $g = $googleService->userinfo->get();

        $email = strtolower($g->email);
        $name = $g->name ?? '';
        $avatar = $g->picture ?? '';
        $gid = $g->id;

        // tìm theo email
        $stmt = $this->db->prepare("SELECT id, role FROM users WHERE email = ? LIMIT 1");
        if (!$stmt) {
            throw new Exception('Prepare failed (SELECT): ' . $this->db->error);
        }
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->bind_result($userId, $role);
        $exists = $stmt->fetch();
        $stmt->close();

        if ($exists) {
            $upd = $this->db->prepare(
                "UPDATE users 
                 SET avatar=?, provider='google', provider_id=?, email_verified=1, updated_at=NOW()
                 WHERE id=?"
            );
            if (!$upd) {
                throw new Exception('Prepare failed (UPDATE): ' . $this->db->error);
            }
            $upd->bind_param('ssi', $avatar, $gid, $userId);
            $upd->execute();
            $upd->close();
        } else {
            $role = 'user';
            $ins = $this->db->prepare(
                "INSERT INTO users
                 (name,email,password_hash,avatar,provider,provider_id,role,email_verified,verify_token,created_at,updated_at)
                 VALUES ( ?, ?, NULL, ?, 'google', ?, ?, 1, NULL, NOW(), NOW())"
            );
            if (!$ins) {
                throw new Exception('Prepare failed (INSERT): ' . $this->db->error);
            }
            $ins->bind_param('sssss', $name, $email, $avatar, $gid, $role);
            $ins->execute();
            $userId = $ins->insert_id;
            $ins->close();
        }

        $_SESSION['auth'] = [
            'id' => (int) $userId,
            'name' => $name,
            'email' => $email,
            'avatar' => $avatar,
            'role' => $role ?? 'user',
            'provider' => 'google',
        ];
        header('Location: ' . $this->url_to('index.php'));
        exit;
    }

    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE)
            session_start();
        session_destroy();
        header('Location: ' . $this->url_to('index.php'));
        exit;
    }
}

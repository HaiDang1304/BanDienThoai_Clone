<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/google.php';
require_once __DIR__ . '/../../vendor/autoload.php';

class AuthController extends Controller
{
    // B1: chuyển hướng sang Google
    public function google()
    {
        $client  = makeGoogleClient();
        $authUrl = $client->createAuthUrl();
        header('Location: ' . $authUrl);
        exit;
    }

    // B2: Google callback
    public function callback()
    {
        global $conn;
        if (session_status() === PHP_SESSION_NONE) session_start();

        $client = makeGoogleClient();

        if (empty($_GET['code'])) {
            http_response_code(400);
            exit('Thiếu mã xác thực từ Google.');
        }

        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        if (isset($token['error'])) {
            http_response_code(400);
            exit('Lỗi token: ' . htmlspecialchars($token['error_description']));
        }

        $client->setAccessToken($token['access_token']);
        $googleService = new Google_Service_Oauth2($client);
        $googleUser    = $googleService->userinfo->get();

        $email  = $conn->real_escape_string(strtolower($googleUser->email));
        $name   = $conn->real_escape_string($googleUser->name);
        $avatar = $conn->real_escape_string($googleUser->picture);
        $gid    = $conn->real_escape_string($googleUser->id);

        $res = $conn->query("SELECT id FROM users WHERE email='$email' LIMIT 1");
        if ($res && $res->num_rows) {
            $row = $res->fetch_assoc();
            $userId = (int)$row['id'];
            $conn->query("UPDATE users 
                          SET avatar='$avatar', provider='google', provider_id='$gid', email_verified=1 
                          WHERE id=$userId");
        } else {
            $conn->query("INSERT INTO users (name,email,avatar,provider,provider_id,email_verified)
                          VALUES ('$name','$email','$avatar','google','$gid',1)");
            $userId = (int)$conn->insert_id;
        }

        $_SESSION['auth'] = [
            'id'       => $userId,
            'name'     => $name,
            'email'    => $email,
            'avatar'   => $avatar,
            'provider' => 'google',
        ];

        header('Location: /BanDienThoai_Clone/public/Home/index');
        exit;
    }

    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        session_destroy();
        header('Location: /BanDienThoai_Clone/public/Home/index');
        exit;
    }
}

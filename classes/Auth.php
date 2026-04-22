<!-- class with db fields and crud over them -->
 <?php
class Auth {
    public static function check() {
        if(session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if(!isset($_SESSION['user_id'])) {
            header("Location: /features/auth/login.php?error=must_login");
            exit();
        }
    }

    public static function checkRole($allowed_roles) {
        self::check();
        if(!is_array($allowed_roles)) {
            $allowed_roles = [$allowed_roles];
        }
        if(!in_array($_SESSION['role'], $allowed_roles)) {
            header("Location: /index.php?error=unauthorized");
            exit();
        }
    }

    public static function login($user) {
        if(session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['email']     = $user['email'];
    }

    public static function logout() {
        if(session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_unset();
        session_destroy();
    }

    public static function user() {
        return [
            'id'        => $_SESSION['user_id']   ?? null,
            'full_name' => $_SESSION['full_name'] ?? null,
            'role'      => $_SESSION['role']       ?? null,
            'email'     => $_SESSION['email']      ?? null,
        ];
    }
}
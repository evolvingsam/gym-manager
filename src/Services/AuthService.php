<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Session;
use PDO;

class AuthService
{
    /**
     * Attempt to log in a user.
     */
    public function login(string $username, string $password): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id, username, password, role, full_name FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            Session::start();
            Session::set('user_id', $user['id']);
            Session::set('username', $user['username']);
            Session::set('role', $user['role']);
            Session::set('full_name', $user['full_name']);
            return true;
        }

        return false;
    }

    public static function isLoggedIn(): bool
    {
        Session::start();
        return Session::get('user_id') !== null;
    }

    public static function requireRole(string $role): void
    {
        if (!self::isLoggedIn() || Session::get('role') !== $role) {
            header("Location: /login.php?error=unauthorized");
            exit;
        }
    }

    /**
     * Attempt to log in a gym member (customer).
     */
    public function loginMember(string $email, string $password): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id, member_code, password, full_name FROM members WHERE email = ? AND deleted_at IS NULL");
        $stmt->execute([$email]);
        $member = $stmt->fetch();

        if ($member && !empty($member['password']) && password_verify($password, $member['password'])) {
            Session::start();
            Session::set('user_id', $member['id']);
            Session::set('username', $member['member_code']);
            Session::set('role', 'member'); // Explicitly define this is a customer
            Session::set('full_name', $member['full_name']);
            return true;
        }

        return false;
    }
}
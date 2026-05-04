<?php

namespace App\Core;

/**
 * Class Session
 * Manages PHP sessions and CSRF protection.
 */
class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key): mixed
    {
        return $_SESSION[$key] ?? null;
    }

    public static function destroy(): void
    {
        session_unset();
        session_destroy();
    }

    /**
     * Generate and store a CSRF token.
     */
    public static function generateCsrfToken(): string
    {
        $token = bin2hex(random_bytes(32));
        self::set('csrf_token', $token);
        return $token;
    }

    /**
     * Validate the provided CSRF token against the session.
     */
    public static function validateCsrfToken(string $token): bool
    {
        return hash_equals(self::get('csrf_token') ?? '', $token);
    }
}
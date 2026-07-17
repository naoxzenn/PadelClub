<?php
// helpers/AuthHelper.php

class AuthHelper {
    /**
     * Check password validation using bcrypt with plain text fallback
     * 
     * @param string $inputPassword User inputted password
     * @param string $storedPassword Stored password (hash or plain text)
     * @return bool True if valid, false otherwise
     */
    public static function verifyPassword($inputPassword, $storedPassword) {
        // Try password_verify first
        if (password_verify($inputPassword, $storedPassword)) {
            return true;
        }
        // Fallback to plain text comparison (for legacy/test users)
        return $inputPassword === $storedPassword;
    }

    /**
     * Enforce rate limit on forgot password request (max 3 requests per 15 minutes)
     * 
     * @param string $email Email address to throttle
     * @return array [ 'allowed' => bool, 'remaining' => int, 'retry_after' => int ]
     */
    public static function checkResetRateLimit($email) {
        if (!isset($_SESSION)) {
            session_start();
        }

        $now = time();
        $limitDuration = 15 * 60; // 15 minutes
        $maxAttempts = 3;

        // Initialize rate limit data in session if not present
        if (!isset($_SESSION['reset_attempts'])) {
            $_SESSION['reset_attempts'] = [];
        }

        // Clean up attempts older than 15 minutes
        $_SESSION['reset_attempts'] = array_filter(
            $_SESSION['reset_attempts'],
            function($timestamp) use ($now, $limitDuration) {
                return ($now - $timestamp) < $limitDuration;
            }
        );

        $attemptsCount = count($_SESSION['reset_attempts']);

        if ($attemptsCount >= $maxAttempts) {
            $oldestAttempt = !empty($_SESSION['reset_attempts']) ? min($_SESSION['reset_attempts']) : $now;
            $retryAfter = $limitDuration - ($now - $oldestAttempt);
            return [
                'allowed' => false,
                'remaining' => 0,
                'retry_after' => ceil($retryAfter / 60) // in minutes
            ];
        }

        return [
            'allowed' => true,
            'remaining' => $maxAttempts - $attemptsCount,
            'retry_after' => 0
        ];
    }

    /**
     * Log a reset password attempt in session
     */
    public static function logResetAttempt() {
        if (!isset($_SESSION)) {
            session_start();
        }
        if (!isset($_SESSION['reset_attempts'])) {
            $_SESSION['reset_attempts'] = [];
        }
        $_SESSION['reset_attempts'][] = time();
    }
}

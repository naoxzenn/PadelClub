<?php
// helpers/TokenHelper.php

class TokenHelper {
    /**
     * Generate secure random token of minimum 32 characters (hex encoded)
     * 
     * @param int $length Byte size. 16 bytes = 32 characters hex. We will use 32 bytes = 64 characters hex.
     * @return string Secure hex token string
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
}

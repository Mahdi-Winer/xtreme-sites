<?php
// auth-helper.php

/**
 * گرفتن توکن bearer از هدر Authorization
 * @return string|null
 */
function extractBearerTokenFromHeader() {
    $header = '';
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $header = $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    } elseif (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (isset($headers['Authorization'])) {
            $header = $headers['Authorization'];
        }
    }
    if (!$header || !preg_match('/Bearer\s+(\S+)/', $header, $matches)) {
        return null;
    }
    return $matches[1];
}

/**
 * گرفتن user_id (کاربر عادی) از توکن
 * @return int|null
 */
function getUserIdFromBearerToken() {
    $access_token = extractBearerTokenFromHeader();
    if (!$access_token) return null;

    $auth_db = new mysqli("localhost", "xtreme_auth", "kCi^4=]Gz0{EYd06", "xtreme_auth");
    if ($auth_db->connect_error) return null;

    $stmt = $auth_db->prepare("SELECT user_id FROM oauth_tokens WHERE access_token=? AND expires_at > NOW() AND is_admin=0 LIMIT 1");
    $stmt->bind_param('s', $access_token);
    $stmt->execute();
    $stmt->bind_result($user_id);
    $result = $stmt->fetch() ? $user_id : null;
    $stmt->close();
    $auth_db->close();
    return $result;
}

/**
 * گرفتن admin_id (ادمین) از توکن ادمین
 * @return int|null
 */
function getAdminIdFromBearerToken() {
    $access_token = extractBearerTokenFromHeader();
    if (!$access_token) return null;

    $auth_db = new mysqli("localhost", "xtreme_auth", "kCi^4=]Gz0{EYd06", "xtreme_auth");
    if ($auth_db->connect_error) return null;

    $stmt = $auth_db->prepare("SELECT user_id FROM oauth_tokens WHERE access_token=? AND expires_at > NOW() AND is_admin=1 LIMIT 1");
    $stmt->bind_param('s', $access_token);
    $stmt->execute();
    $stmt->bind_result($admin_id);
    $result = $stmt->fetch() ? $admin_id : null;
    $stmt->close();
    $auth_db->close();
    return $result;
}
?>
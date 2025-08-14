<?php
// shared/auth-admin-helper.php

function getAdminInfoOrExit($roles_allowed = ['superadmin', 'manager', 'support', 'read_only']) {
    // گرفتن توکن Bearer
    $header = '';
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) $header = $_SERVER['HTTP_AUTHORIZATION'];
    elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) $header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    elseif (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (isset($headers['Authorization'])) $header = $headers['Authorization'];
    }
    if ($header && preg_match('/Bearer\s+(\S+)/', $header, $matches)) {
        $access_token = $matches[1];
    } else {
        http_response_code(401); echo json_encode(['error'=>'unauthorized']); exit;
    }

    // اعتبارسنجی توکن و نقش ادمین
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://auth.xtremedev.co/admininfo.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $access_token"
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    $resp = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $admininfo = @json_decode($resp, true);
    if ($httpcode != 200 || !$admininfo || empty($admininfo['role']) || !in_array($admininfo['role'], $roles_allowed)) {
        http_response_code(403); echo json_encode(['error'=>'forbidden']); exit;
    }

    return $admininfo; // شامل آیدی، ایمیل، role و ... 
}
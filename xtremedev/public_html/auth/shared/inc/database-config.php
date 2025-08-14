<?php
$mysqli = new mysqli('localhost', 'xtreme_auth', 'kCi^4=]Gz0{EYd06', 'xtreme_auth');
if ($mysqli->connect_error) {
    die('Database error: ' . $mysqli->connect_error);
}
$mysqli->set_charset("utf8mb4");
?>
<?php
$mysqli = new mysqli("localhost", "xtreme_client", "I8(TR~4Pi)nO6Z~t", "xtreme_client");
if ($mysqli->connect_error) {
    die("DB Error: " . $mysqli->connect_error);
}
?>
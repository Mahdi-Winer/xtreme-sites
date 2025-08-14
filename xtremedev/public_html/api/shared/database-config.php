<?php
$mysqli = new mysqli("localhost", "xtreme_core", "Qn+u?+whMNj,A5F7", "xtreme_core");
if ($mysqli->connect_error) {
    die("DB Error: " . $mysqli->connect_error);
}
?>
<?php
// database-config.php
$dbhost = "localhost";
$dbuser = "xtreme_xtremedevelopment";      // <-- change to your db username
$dbpass = "sr+XFqKxcMg#nwID";      // <-- change to your db password
$dbname = "xtreme_xtremedevelopment";     // <-- change to your db name

$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if ($mysqli->connect_errno) {
    die("Database connection failed: " . $mysqli->connect_error);
}
$mysqli->set_charset("utf8mb4");
?>
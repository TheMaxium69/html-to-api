<?php

$host = "127.0.0.7";
$userDB = "root";
$passDB = "";
$Database = "gamenium_api";

//MySQLi
//$ConnectDB = mysqli_connect($host, $userDB, $passDB, $Database);

//PDO
try {
    $db = new PDO("mysql:host=" . $host . ";dbname=" . $Database, $userDB, $passDB);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
} catch (PDOEXeption $e) {
    // Do nothing
}

?>
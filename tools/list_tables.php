<?php
require_once __DIR__ . '/../config.php';

$res = $conn->query("SHOW TABLES");
if (!$res) {
    echo "Error: " . $conn->error . PHP_EOL;
    exit(1);
}

echo "Tables in database '" . DB_NAME . "':\n";
while ($row = $res->fetch_array()) {
    echo " - " . $row[0] . "\n";
}

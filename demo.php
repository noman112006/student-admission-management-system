<?php

$conn = mysqli_connect(
    "127.0.0.1:3307",
    "root",
    "",
    "demo",
    3307
);

if (!$conn) {
    die("Database connection failed");
}

?>

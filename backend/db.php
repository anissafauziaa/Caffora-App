<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "caffora";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_errno) {
  die("Failed to connect DB: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
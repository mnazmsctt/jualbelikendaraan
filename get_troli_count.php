<?php
// get_troli_count.php
ob_start();
session_start();
include 'config.php';
ob_clean();

$total = isset($_SESSION['troli']) ? count($_SESSION['troli']) : 0;
echo json_encode(['total' => $total]);
?>
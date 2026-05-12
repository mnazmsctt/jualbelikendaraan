<?php
session_start();
require 'config.php';

$id = (int)($_GET['id'] ?? 0);
if ($id > 0 && isset($_SESSION['troli'])) {
    $_SESSION['troli'] = array_values(array_diff($_SESSION['troli'], [$id]));
}
header('Location: troli.php');
exit;
?>
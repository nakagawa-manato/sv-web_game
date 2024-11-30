<?php
session_start();
require('../../../conf/dbconnect.php');

$room_id = $_POST['room_id'];

// プレイヤーの行動回数を0に設定
$stmt = $db->prepare('INSERT INTO player (move_count) VALUES (0)');

header('Location: client_sub.php');
exit;

?>
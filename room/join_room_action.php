<?php
session_start();
require('../../../../conf/dbconnect.php');

// セッション
$pl_id = $_SESSION['id'];

$room_id = $_POST['room_id'];

// プレイヤーを部屋に追加
$stmt = $db->prepare('UPDATE player SET room_id = ? WHERE pl_id = ?');
$stmt->execute(array($room_id, $pl_id));

header('Location: join_room_detail.php?room_id=' . $room_id);
exit;

?>
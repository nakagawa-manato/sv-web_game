<?php
session_start();
require('../../../conf/dbconnect.php');

$pl_id = $_SESSION['id'];

// room_idを取得
$stmt = $db->prepare('SELECT room_id FROM player WHERE pl_id = ?');
$stmt->execute(array($pl_id));
$room_id = $stmt->fetch();

sleep(1);
$timer = $timer + 1;

$stmt = $db->prepare('UPDATE timer SET timer = ? WHERE room_id = ?');
$stmt->execute(array($timer, $room_id));

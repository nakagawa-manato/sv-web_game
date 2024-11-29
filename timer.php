<?php
session_start();
require('../../../conf/dbconnect.php');

// プレイヤーIDをセッションから取得
$pl_id = $_SESSION['id'] ?? null;

// room_idを取得
$stmt = $db->prepare('SELECT room_id FROM player WHERE pl_id = ?');
$stmt->execute(array($pl_id));
$room = $stmt->fetch();
$room_id = $room['room_id'];  // 正しく取得

// 現在のタイマー値を取得
$stmt = $db->prepare('SELECT timer FROM timer WHERE room_id = ?');
$stmt->execute(array($room_id));
$timer_row = $stmt->fetch();

if (isset($timer_row)) {
    $timer = $timer_row;
} else {
    $timer = $timer_row ? $timer_row['timer'] : 0;  // 初期値として0を設定
}

// 1秒待機
sleep(1);

// タイマーを加算
$timer++;

// データベースに更新
$stmt = $db->prepare('UPDATE timer SET timer = ? WHERE room_id = ?');
$stmt->execute(array($timer, $room_id));
?>
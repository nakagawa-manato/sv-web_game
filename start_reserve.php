<?php
session_start();
require('../../../conf/dbconnect.php');

$room_id = $_POST['room_id'];

// ゲーム中に変更
$stmt = $db->prepare('UPDATE rooms SET is_game = 1 WHERE room_id = ?');
$stmt->execute(array($room_id));

// 時間の初期設定
// うまく機能しない
$stmt = $db->prepare('INSERT INTO timer (timer, room_id, round) VALUE (0, ?, 0)');
$stmt->execute(array($room_id));

for ($i = 0; $i < 5; $i++) {
    // armoryエリアをランダムデータベースに登録
    $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H']; //横軸
    $rows = [1, 2, 3, 4, 5, 6, 7]; //縦軸

    $randomX = $columns[array_rand($columns)]; //横軸をランダムに選択
    $randomY = $rows[array_rand($rows)]; //縦軸をランダムに選択
    $armory_pos = $randomX . $randomY;

    $stmt = $db->prepare('INSERT INTO armory (armory_pos, room_id) VALUE (?, ?)');
    $stmt->execute(array($armory_pos, $room_id));
}

for ($i = 0; $i < 3; $i++) {
    // hospitalエリアをランダムデータベースに登録
    $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H']; //横軸
    $rows = [1, 2, 3, 4, 5, 6, 7]; //縦軸

    $randomX = $columns[array_rand($columns)]; //横軸をランダムに選択
    $randomY = $rows[array_rand($rows)]; //縦軸をランダムに選択
    $hos_pos = $randomX . $randomY;

    $stmt = $db->prepare('INSERT INTO hospital (hos_pos, room_id) VALUE (?, ?)');
    $stmt->execute(array($hos_pos, $room_id));
}

header('Location: item.php');
exit;

?>


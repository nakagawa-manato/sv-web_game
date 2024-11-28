<?php
session_start();
require('../../../conf/dbconnect.php');

$room_id = $_POST['room_id'];

var_dump($room_id);

$stmt = $db->prepare('UPDATE rooms SET is_game = 1 WHERE room_id = ?');
$stmt->execute(array($room_id));

for ($i = 0; $i < 5; $i++) {
    // armoryエリアをランダムデータベースに登録
    $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H']; //横軸
    $rows = [1, 2, 3, 4, 5, 6, 7]; //縦軸

    $randomX = $columns[array_rand($columns)]; //横軸をランダムに選択
    $randomY = $rows[array_rand($rows)]; //縦軸をランダムに選択
    $armory_pos = $randomX . $randomY;

    $stmt = $db->prepare('INSERT INTO armory SET armory_pos = ?');
    $stmt->execute(array($armory_pos));
}

for ($i = 0; $i < 3; $i++) {
    // hospitalエリアをランダムデータベースに登録
    $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H']; //横軸
    $rows = [1, 2, 3, 4, 5, 6, 7]; //縦軸

    $randomX = $columns[array_rand($columns)]; //横軸をランダムに選択
    $randomY = $rows[array_rand($rows)]; //縦軸をランダムに選択
    $hos_pos = $randomX . $randomY;

    $stmt = $db->prepare('INSERT INTO hospital SET hos_pos = ?');
    $stmt->execute(array($hos_pos));
}

?>


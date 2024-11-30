<?php
session_start();
require('../../../conf/dbconnect.php');

$room_id = $_POST['room_id'];

// ゲーム中に変更
$stmt = $db->prepare('UPDATE rooms SET is_game = 1 WHERE room_id = ?');
$stmt->execute(array($room_id));

// 時間の初期設定
$stmt = $db->prepare('INSERT INTO timer (timer, room_id, round) VALUES (0, ?, 1)');
if (!$stmt->execute(array($room_id))) {
    print_r($stmt->errorInfo());
}

// プレイヤーの行動回数を0に設定
$stmt = $db->prepare('INSERT INTO player (move_count) VALUES (0)');

// アンチエリアのカウントを1に設定
$_SESSION['dangerCount'] = 1;

// armory_pos を登録する
$armory_positions = [];
for ($i = 0; $i < 5; $i++) {
    // armoryエリアをランダムデータベースに登録
    $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H']; //横軸
    $rows = [1, 2, 3, 4, 5, 6, 7]; //縦軸

    $randomX = $columns[array_rand($columns)]; //横軸をランダムに選択
    $randomY = $rows[array_rand($rows)]; //縦軸をランダムに選択
    $armory_pos = $randomX . $randomY;

    // armory_posを既に登録した位置として配列に追加
    $armory_positions[] = $armory_pos;

    $stmt = $db->prepare('INSERT INTO armory (armory_pos, room_id) VALUES (?, ?)');
    $stmt->execute(array($armory_pos, $room_id));
}

// hospital_pos を登録する（armory_posを避ける）
$hospital_positions = [];
for ($i = 0; $i < 3; $i++) {
    // hospitalエリアをランダムデータベースに登録
    $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H']; //横軸
    $rows = [1, 2, 3, 4, 5, 6, 7]; //縦軸

    // armory_posに配置された場所を避けるため、whileループを使用
    do {
        $randomX = $columns[array_rand($columns)]; //横軸をランダムに選択
        $randomY = $rows[array_rand($rows)]; //縦軸をランダムに選択
        $hos_pos = $randomX . $randomY;
    } while (in_array($hos_pos, $armory_positions)); // armory_posの位置と重複しないまで繰り返し

    // hospital_posを登録する
    $hospital_positions[] = $hos_pos;

    $stmt = $db->prepare('INSERT INTO hospital (hos_pos, room_id) VALUES (?, ?)');
    $stmt->execute(array($hos_pos, $room_id));
}

header('Location: host_sub.php');
exit;

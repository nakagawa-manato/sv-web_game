<?php
session_start();
require('../../../../conf/dbconnect.php');

//プレイヤーidをセッションから取得
$pl_id = $_SESSION['id'] ?? null;
$room_id = $_POST['room_id'];

// プレイヤーを生存判定にする
$stmt = $db->prepare('UPDATE player SET alive = 1 WHERE pl_id = ?');
$stmt->execute(array($pl_id));

// プレイヤーの行動回数を0に設定
$stmt = $db->prepare('INSERT INTO player (move_count) VALUES (0) WHERE pl_id = ?');
$stmt->execute(array($pl_id));

// ゲーム開始時に武器をランダムで配布する
$rand_item_id = ['1', '2', '3', '4'];
$r = rand(1, count($rand_item_id));
$stmt = $db->prepare('INSERT INTO backpack SET item_id = ?, amount = 1 WHERE pl_id = ?');
$stmt->execute(array($r, $pl_id));

// 回復アイテム
$stmt = $db->prepare('INSERT INTO backpack SET item_id = 6, amount = 3 WHERE pl_id = ?');
$stmt->execute(array($pl_id));

// プレイヤーのhpを100に設定
$stmt = $db->prepare('UPDATE player SET pl_hp = ? WHERE pl_id = ?');
$stmt->execute(array('100', $pl_id));

// プレイヤーの座標がまだ設定されていない場合、ランダムな座標を設定
$columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H']; //横軸
$rows = [1, 2, 3, 4, 5, 6, 7]; //縦軸

$randomX = $columns[array_rand($columns)]; //横軸をランダムに選択
$randomY = $rows[array_rand($rows)]; //縦軸をランダムに選択
$playerPos = $randomX . $randomY;

// ランダムな座標をデータベースに登録
$stmt = $db->prepare('UPDATE player SET pos = ? WHERE pl_id = ?');
$stmt->execute(array($playerPos, $pl_id));

header('Location: client_main.php');
exit;
?>
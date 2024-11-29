<?php
session_start();
require('../../../conf/dbconnect.php');

// セッション
$pl_id = $_SESSION['id'];

//playerの人数をカウントする
$sql = "SELECT * FROM player";
$sth = $db->query($sql);
$pl_count = $sth->rowCount();

// ゲーム開始時に武器をランダムで配布する
$item_id = ['1', '2', '3', '4'];
$r = rand(1, count($item_id));
$stmt = $db->prepare('INSERT INTO backpack SET item_id=?,pl_id=?,amount=?');
$usersArray = $stmt->fetchALL();
$stmt->execute(array($r, $pl_id, 1));

// 回復アイテム
$stmt = $db->prepare('INSERT INTO backpack SET item_id=?,pl_id=?,amount=?');
$usersArray = $stmt->fetchALL();
$stmt->execute(array(6, $pl_id, 3));

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
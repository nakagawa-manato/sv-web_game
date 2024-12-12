<?php
session_start();
require('../../../../conf/dbconnect.php');

//プレイヤーidをセッションから取得
$pl_id = $_SESSION['id'] ?? null;
$deadCount = $_SESSION['dangerCount'] ?? null;

// room_idを取得
$stmt = $db->prepare('SELECT room_id FROM player WHERE pl_id = ?');
$stmt->execute(array($pl_id));
$room = $stmt->fetch();
$room_id = $room['room_id'];

// ランキングの取得
$stmt = $db->prepare('SELECT pl_id, pl_name, rank FROM player WHERE room_id = ?');
$stmt->execute(array($room_id));
$ranking = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>タイトル</title>
    <link rel="stylesheet" href="reset.css">
    <link rel="stylesheet" href="end_style.css">
</head>

<body>
    <div class="rank">
        <h1>ランキング</h1>
        <?php
        foreach ($ranking as $rank) {
            $pl_rank = (int)$rank['rank'];
            $pl_name = htmlspecialchars($rank['pl_name'], ENT_QUOTES, 'UTF-8');

            echo "<p>順位: $prank</p>";
            echo "</>$pl_name</p>";
        }
        ?>
    </div>

</body>

</html>
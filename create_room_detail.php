<?php
session_start();
require('../../../conf/dbconnect.php');

// セッション
$pl_id = $_SESSION['id'];

// URLパラメータから部屋IDを取得
$room_id = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;

// 部屋情報を取得
$stmt = $db->prepare('SELECT * FROM rooms WHERE room_id = ?');
$stmt->execute(array($room_id));
$room = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$room) {
    echo "部屋が見つかりませんでした。";
    exit;
}

// 部屋に参加しているプレイヤーを取得
$stmt = $db->prepare('SELECT pl_name FROM player WHERE room_id = ?');
$stmt->execute(array($room_id));
$players = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>部屋詳細</title>
</head>

<body>
    <h1>部屋詳細</h1>
    <h2>部屋名: <?php echo htmlspecialchars($room['room_name'], ENT_QUOTES, 'UTF-8'); ?></h2>

    <h3>この部屋にいるプレイヤー:</h3>
    <?php if ($players): ?>
        <ul>
            <?php foreach ($players as $player): ?>
                <li><?php echo htmlspecialchars($player['pl_name'], ENT_QUOTES, 'UTF-8'); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>プレイヤーはまだいません。</p>
    <?php endif; ?>

    <!-- スタートボタン -->
    <form action="start_reserve.php" method="POST">
        <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">
        <button type="submit" name="action" value="start">スタート</button>
    </form>

    <!-- キャンセルボタン -->
    <form action="cancel.php" method="get">
	<input type="submit" value="キャンセル">
</body>

</html>
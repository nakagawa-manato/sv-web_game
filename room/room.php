<?php
session_start();
require('../../../../conf/dbconnect.php');

// セッション
$pl_id = $_SESSION['id'];

// 部屋を作成する処理
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $room_name = $_POST['room_name'];

    // 部屋の作成
    $stmt = $db->prepare('INSERT INTO rooms (room_name, pl_id) VALUES (?, ?)');
    $stmt->execute(array($room_name, $pl_id));

    // 作成した部屋のIDを取得
    $stmt = $db->prepare('SELECT room_id FROM rooms WHERE room_name = ?');
    $stmt->execute(array($room_name));
    $room_id = $stmt->fetch();

    // プレイヤーのroom_idを更新
    $stmt = $db->prepare('UPDATE player SET room_id = ? WHERE pl_id = ?');
    $stmt->execute(array($room_id['room_id'], $pl_id));

    // 部屋作成後にリダイレクト
    header('Location: create_room_detail.php?room_id=' . $room_id['room_id']);
    exit;
}

// 部屋のリストを取得
$stmt = $db->prepare('SELECT * FROM rooms WHERE is_game = 0');
$stmt->execute(array());
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>部屋を作成</title>
</head>

<body>
    <h1>部屋を作成する</h1>
    <form action="create_room.php" method="POST">
        <label for="room_name">部屋名: </label>
        <input type="text" id="room_name" name="room_name" required><br>
        <button type="submit">部屋を作成</button>
    </form>

    <h1>部屋に参加する</h1>
    <form action="join_room_action.php" method="POST">
        <label for="room_id">部屋を選択:</label>
        <select name="room_id" id="room_id" required>
            <?php foreach ($result as $row) { ?>
                <option value="<?php echo $row['room_id']; ?>">
                    <?php echo htmlspecialchars($row['room_name'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php } ?>
        </select><br>
        <button type="submit">参加する</button>
    </form>
</body>

</html>
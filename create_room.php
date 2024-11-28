<?php
session_start();
require('../../../conf/dbconnect.php');

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

    var_dump($room_id);

    // 部屋作成後にリダイレクト
    header('Location: create_room_detail.php?room_id=' . $room_id['room_id']);
    exit;
}

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
</body>

</html>
<?php
session_start();
require('../../../../conf/dbconnect.php');

// セッション
$pl_id = $_SESSION['id'];

// 部屋のリストを取得
$stmt = $db->prepare('SELECT * FROM rooms');
$stmt->execute(array());
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>部屋に参加</title>
</head>

<body>
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
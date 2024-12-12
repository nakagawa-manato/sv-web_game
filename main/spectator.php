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

// タイマー値を取得
$stmt = $db->prepare('SELECT timer FROM timer WHERE room_id = ?');
$stmt->execute(array($room_id));
$timer_row = $stmt->fetch();

// ラウンド数
try {
    $stmt = $db->prepare('SELECT round FROM timer WHERE room_id = ?');
    $stmt->execute(array($room_id));
    $round = $stmt->fetch();
    $round = $round['round'];
} catch (PDOException $e) {
    echo '接続エラー: ' . $e->getMessage();
}

// 次のラウンドまでの秒数
$next_round_time = ceil($timer / 30) * 30; // 30秒の倍数
$time_to_next_round = $next_round_time - $timer; // 次のラウンドまでの秒数

// セルIDの定義（A1, B1, C1, ...）
$cell_ids = [];
for ($row = 1; $row <= 7; $row++) {
    for ($col = 'A'; $col <= 'H'; $col++) {
        $cell_ids[] = $col . $row; // A1, B1, ..., H7を生成
    }
}

// 全プレイヤーの位置を取得
try {
    $stmt = $db->prepare('SELECT pl_id, pl_name, pl_hp, pos FROM player WHERE room_id = ?');
    $stmt->execute(array($room_id));
    $all_pl_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo '接続エラー: ' . $e->getMessage();
}

$all_pl_pos = $all_pl_data['pos'];

if ($pl_id !== null) {
    //プレイヤーごとのテキストファイルパスを設定
    $filePath = "pl_log/player_" . $pl_id . ".txt";

    //ファイルが存在すれば読み込む
    if (file_exists($filePath)) {
        $fileContent = file_get_contents($filePath);
    } else {
        //ファイルが存在しない場合
        $fileContent = "まだ行動した履歴がありません";
    }
} else {
    //プレイヤーが不明な場合
    $fileContent = "プレイヤーIDが不明です";
}

try {
    //armory_posの情報を取得
    $stmt = $db->prepare('SELECT armory_pos FROM armory WHERE room_id = ?');
    $stmt->execute(array($room_id));
    $armory_pos = $stmt->fetchALL(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo '接続エラー: ' . $e->getMessage();
}

try {
    //hos_posの情報を取得
    $stmt = $db->prepare('SELECT hos_pos FROM hospital WHERE room_id = ?');
    $stmt->execute(array($room_id));
    $hospital_pos = $stmt->fetchALL(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo '接続エラー: ' . $e->getMessage();
}

try {
    //登録したdangerエリアをすべて取得
    $stmt = $db->prepare('SELECT area FROM danger WHERE num = 0 AND room_id = ?');
    $stmt->execute(array($room_id));
    $dangerAreas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo '接続エラー:' . $e->getMessage();
}

// 取得したエリアをJavaScript用に整形
$dangerCells = array_map(function ($row) {
    return $row['area'];
}, $dangerAreas);
$dangerCellsJson = json_encode($dangerCells);

// 生存者の確認
try {
    $stmt = $db->prepare('SELECT pl_id, pl_name FROM player WHERE alive = 1');
    $stmt->execute();
    $aleve = $stmt->fetchALL(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo '接続エラー:' . $e->getMessage();
}

// 生存者のカウント
$aliveCount = count($alive);

// 死者の確認
try {
    $stmt = $db->prepare('SELECT pl_id, pl_name FROM player WHERE alive = 0');
    $stmt->execute();
    $dead = $stmt->fetchALL(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo '接続エラー:' . $e->getMessage();
}

// 死者のカウント
$deadCount = count($dead);

// 生存者が残り1名になればゲーム終了
if ($aliveCount == 1) {
    header('Location: end_game');
    exit;
}

?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="refresh" content="1; URL=http://gameg.s322.xrea.com/s2g3/game/client_spectator.php">
    <title>タイトル</title>
    <link rel="stylesheet" href="reset.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* 生存者リストのスタイル */
        .alive_list {
            margin-top: 20px;
        }

        .player {
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }

        .player h3 {
            margin: 0;
            font-size: 1.2em;
        }
    </style>
</head>

<body>
    <div class="cotainer">
        <div class="round">
            <h2><?php echo $round ?>ラウンド</h2>
        </div>

        <!-- 次のラウンドまでの秒数を表示 -->
        <div id="next-round-timer" class="timer">
            次のラウンドまで: <span id="countdown"><?php echo $time_to_next_round; ?></span> 秒
        </div>

        <div class="aliveCount">
            <h2>生存者<?php echo ($aliveCount); ?>人</h2>
        </div>

        <div class="deadCount">
            <h2>死者<?php echo ($deadCount); ?>人</h2>
        </div>


        <form id="cellForm" action="client_spectator.php" method="POST">
            <input type="hidden" name="cellId" id="cellId">
            <div class="board">

                <?php
                // 56セルを生成
                foreach ($cell_ids as $cell_id) {
                    // armory_posに含まれている座標かどうかを確認
                    $armory_class = in_array($cell_id, $armory_pos) ? 'armory_cell' : '';
                    // hospital_posに含まれている座標かどうかを確認
                    $hospital_class = in_array($cell_id, $hospital_pos) ? 'hospital_cell' : '';

                    // armory_cellとhospital_cellのクラスを追加
                    $class = trim($armory_class . ' ' . $hospital_class); // クラス名を結合

                    // セルのHTMLを出力
                    echo '<div class="cell ' . $class . '" id="' . $cell_id . '">';

                    // armory_posのセルにはarmory画像を埋め込む
                    if (in_array($cell_id, $armory_pos)) {
                        echo '<div class="aromory-cell" aromory_cell_id="' . htmlspecialchars($cell_id) . '">';
                        echo '<img src="./root_ico/armory.png" alt="armory_ico">';
                        echo '</div>';
                    }

                    // hospital_posのセルにはhospital画像を埋め込む
                    if (in_array($cell_id, $hospital_pos)) {
                        echo '<div class="hospital-cell" data-item-id="' . htmlspecialchars($cell_id) . '">';
                        echo '<img src="./root_ico/hospital.png" alt="hospital_ico">';
                        echo '</div>';
                    }

                    echo '</div>';
                }
                ?>

            </div>

            <div class="log">
                <h2>ログ</h2>
                <p><?php echo nl2br(htmlspecialchars($fileContent)); ?></p>
            </div>

        </form>

        <h1>生存者リスト</h1>

        <div class="alive_list">
            <?php
            // プレイヤーデータを表示
            foreach ($all_pl_data as $player) {
                $pl_name = htmlspecialchars($player['pl_name'], ENT_QUOTES, 'UTF-8');
                $pl_hp = (int)$player['pl_hp'];
                $pos = htmlspecialchars($player['pos'], ENT_QUOTES, 'UTF-8');

                // HPの最大値
                $max_hp = 100;

                // HPの割合（進行度）を計算
                $hp_percentage = ($pl_hp / $max_hp) * 100;

                // HPバーの色を決定
                if ($hp_percentage > 50) {
                    $hp_color = '#4caf50'; // 緑色 (50%以上)
                } elseif ($hp_percentage > 20) {
                    $hp_color = '#ff9800'; // オレンジ色 (20%〜50%)
                } else {
                    $hp_color = '#f44336'; // 赤色 (20%以下)
                }

                // プレイヤー情報を表示
                echo "<div class='player'>";
                echo "<h3>$pl_name</h3>";
                echo "<p>位置: $pos</p>";

                // HPバーの表示
                echo "<div class='hp-bar-container'>";
                echo "<div class='hp-bar' style='width: {$hp_percentage}%; background-color: {$hp_color};'>";
                echo "{$pl_hp} / {$max_hp}";  // HP表示
                echo "</div>";
                echo "</div>";

                echo "</div>"; // プレイヤー情報の終了
            }
            ?>
        </div>

        <script>
            // 次のラウンドまでの秒数
            let countdown = <?php echo $time_to_next_round; ?>;

            // カウントダウンの処理
            function updateCountdown() {
                if (countdown > 0) {
                    countdown--;
                    document.getElementById("countdown").innerText = countdown;
                } else {
                    // 次のラウンドまでの秒数が0になったら自動で再読み込みまたは別の処理を実行
                    document.getElementById("countdown").innerText = "次のラウンドです！";
                    // 必要に応じてページをリロード
                    // location.reload(); 
                }
            }

            // 1秒ごとにカウントダウンを更新
            setInterval(updateCountdown, 1000);

            // PHPで取得したプレイヤーの位置情報をJavaScriptに渡す
            var playerPositions = <?php echo json_encode(array_column($all_pl_data, 'pos')); ?>;

            // PHPから取得したランダムに登録された危険なエリアをJavaScriptに渡す
            const dangerCells = <?php echo $dangerCellsJson; ?>;

            //A-H と 1-7 の範囲を定義
            const columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
            const rows = [1, 2, 3, 4, 5, 6, 7];

            //すべてのセルを取得
            const cells = document.querySelectorAll('.cell');

            // 取得した危険なエリアに基づいてオレンジ斜線に変更
            cells.forEach(cell => {
                if (dangerCells.includes(cell.id)) {
                    cell.style.background = 'orange';
                }
            });

            // 線形探索法　配列の先頭からしらみつぶしに調べていく
            for (let i = 0; i < dangerCells.length; i++) {
                if (dangerCells[i] === playerPos) {
                    document.getElementById(playerPos).classList.add('coverhighlight');
                }
            };

            // playerPositions配列のすべての位置に対して処理を行う
            playerPositions.forEach(function(playerPos) {
                var positionElement = document.getElementById(playerPos);
                if (positionElement) {
                    positionElement.classList.add('highlight'); // 座標を黄色に変更
                }
            });
        </script>
</body>

</html>
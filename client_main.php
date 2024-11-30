<?php
session_start();
require('../../../conf/dbconnect.php');

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
$timer = $timer_row['timer'];
$next_round_time = ceil($timer_row / 30) * 30; // 30秒の倍数
$time_to_next_round = $next_round_time - $timer_row; // 次のラウンドまでの秒数

// プレイヤーの行動回数取得
try {
    $stmt = $db->prepare('SELECT move_count FROM player WHERE pl_id = ?');
    $stmt->execute(array($pl_id));
    $moveCount = $stmt->fetch();
    $moveCount = $moveCount['move_count'];
} catch (PDOException $e) {
    echo '接続エラー: ' . $e->getMessage();
}

// プレイヤーのアイテムの制限
$buttonState = ($moveCount + 1 == $roud) ? 'enable' : 'disabled';

// セルIDの定義（A1, B1, C1, ...）
$cell_ids = [];
for ($row = 1; $row <= 7; $row++) {
    for ($col = 'A'; $col <= 'H'; $col++) {
        $cell_ids[] = $col . $row; // A1, B1, ..., H7を生成
    }
}

//プレイヤー座標を取得
$pl_pos = $db->prepare('SELECT pos FROM player WHERE pl_id = ?');
$pl_pos->execute(array($pl_id));
$playerPos = $pl_pos->fetch(); //プレイヤーの座標
$playerPos = $playerPos['pos'];

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
    //アイテムの情報を取得
    $stmt = $db->prepare('SELECT b.item_id, b.amount, i.item_name, i.item_ico FROM backpack b JOIN item i ON b.item_id = i.item_id WHERE b.pl_id = ?');
    $stmt->execute(array($pl_id));
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //var_dump($items);
} catch (PDOException $e) {
    echo '接続エラー: ' . $e->getMessage();
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

// 隣接するプレイヤーの位置を取得
$adjacentPlayers = [];
// 自分ではない,同じ部屋,生きている
$stmt = $db->prepare('SELECT pl_id, pos, pl_name FROM player WHERE pl_id != ? AND room_id = ? AND alive = 1');
$stmt->execute(array($pl_id, $room_id));
$players = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($players as $player) {
    $otherPlayerPos = $player['pos'];
    $adjacentPlayers[] = $otherPlayerPos; // プレイヤー位置を追加
}

// 攻撃ボタンの表示
$canAttack = in_array($playerPos, $adjacentPlayers); // 隣接しているプレイヤーがいる場合

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

// プレイヤーのhp管理
try {
    $stmt = $db->prepare('SELECT pl_hp FROM player WHERE pl_id = ?');
    $stmt->execute(array($pl_id));
    $pl_hp = $stmt->fetch();
} catch (PDOException $e) {
    echo '接続エラー:' . $e->getMessage();
}

// プレイヤーのHPを取得
$current_hp = $pl_hp['pl_hp'] ?? 0; // もしHPが取得できなければ0を設定
$max_hp = 100; // 最大HP

// HPの割合（進行度）を計算
$hp_percentage = ($current_hp / $max_hp) * 100;

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
    <meta http-equiv="refresh" content="1; URL=http://gameg.s322.xrea.com/s2g3/game/client_main.php">
    <title>タイトル</title>
    <link rel="stylesheet" href="reset.css">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="cotainer">

        <div class="hp">
            <!-- HPバーの表示 -->
            <div class="hp-bar-container">
                <div class="hp-bar" style="width: <?php echo $hp_percentage; ?>%;">
                    <?php echo $current_hp . ' / ' . $max_hp; ?>
                </div>
            </div>
        </div>

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


        <form id="cellForm" action="client_move.php" method="POST">
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

        <h1>プレイヤーインベントリ</h1>

        <div class="inventory">
            <!-- インベントリの格子（7x12のマス） -->
            <div class="inventory-cell"></div>
            <div class="inventory-cell"></div>
            <div class="inventory-cell"></div>
            <div class="inventory-cell"></div>
            <div class="inventory-cell"></div>
            <div class="inventory-cell"></div>
            <div class="inventory-cell"></div>

            <div class="inventory-cell"></div>
            <div class="inventory-cell"></div>
            <div class="inventory-cell"></div>
            <div class="inventory-cell"></div>
            <div class="inventory-cell"></div>
            <div class="inventory-cell"></div>
            <div class="inventory-cell"></div>

            <div class="inventory-cell"></div>
            <div class="inventory-cell"></div>
            <div class="inventory-cell"></div>
            <div class="inventory-cell"></div>
            <div class="inventory-cell"></div>
            <div class="inventory-cell"></div>
            <div class="inventory-cell"></div>

            <div class="inventory-cell"></div>
            <div class="inventory-cell"></div>
            <div class="inventory-cell"></div>
            <div class="inventory-cell"></div>
            <div class="inventory-cell"></div>
            <div class="inventory-cell"></div>
            <div class="inventory-cell"></div>

            <div class="inventory-cell"></div>
            <div class="inventory-cell"></div>
            <div class="inventory-cell"></div>
            <div class="inventory-cell"></div>
            <div class="inventory-cell"></div>
            <div class="inventory-cell"></div>
            <div class="inventory-cell"></div>

            <div class="inventory-cell"></div>
            <div class="inventory-cell"></div>
            <div class="inventory-cell"></div>
            <div class="inventory-cell"></div>
            <div class="inventory-cell"></div>
            <div class="inventory-cell"></div>
            <div class="inventory-cell"></div>

            <!-- アイテム格子に画像を埋め込む -->
            <?php foreach ($items as $item): ?>
                <div class="inventory-cell" data-item-id="<?php echo htmlspecialchars($item['item_id']); ?>">
                    <img src="<?php echo htmlspecialchars($item['item_ico']); ?>" alt="<?php echo htmlspecialchars($item['item_name']); ?>">
                </div>
            <?php endforeach; ?>
        </div>

        <!-- 使用するアイテムを確認するポップアップ -->
        <div id="use-item-popup" class="popup" style="display:none;">
            <p>このアイテムを使用しますか？</p>
            <button id="use-item-btn" class="<?= $buttonState; ?>" <?php if ($buttonState == 'disabled') echo 'disabled'; ?>>使用する</button>
            <button id="close-popup-btn">キャンセル</button>
        </div>

        <!-- 攻撃相手選択のポップアップ -->
        <div id="attack-popup" class="popup" style="display:none;">
            <p>誰に攻撃しますか？</p>
            <select id="attack-target">
                <!-- プレイヤー名がここに動的に追加 -->
            </select>
            <button id="confirm-attack-btn">攻撃する</button>
            <button id="close-attack-popup-btn">キャンセル</button>
        </div>

    </div>

    <script>
        // 1秒ごとにタイマーを更新するAJAXリクエスト
        setInterval(function() {
            // PHPファイルにリクエストを送る
            fetch('client_main.php', {
                    method: 'GET',
                })
                .then(response => response.text())
                .then(data => {
                    // タイマーの更新結果などをここで扱うことができます
                    console.log('Timer updated:', data);
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }, 1000); // 1秒ごとに実行

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

        //PHPからプレイヤーの現在座標を取得
        const playerPos = '<?php echo htmlspecialchars($playerPos); ?>';

        // PHPから取得したランダムに登録された危険なエリアをJavaScriptに渡す
        const dangerCells = <?php echo $dangerCellsJson; ?>;

        //プレイヤーの現在座標を横（A-H）と縦（1-7）に分ける
        const currentX = playerPos.charAt(0); //横
        const currentY = parseInt(playerPos.charAt(1)); //縦

        //横軸（A-H）と縦軸（1-7）の隣接セルを計算
        const adjacentCells = [];
        const directions = [
            [-1, 0],
            [1, 0], //左右
            [0, -1],
            [0, 1], //上下
            [-1, -1],
            [1, 1], //左上、右下
            [-1, 1],
            [1, -1] //右上、左下
        ];

        //A-H と 1-7 の範囲を定義
        const columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        const rows = [1, 2, 3, 4, 5, 6, 7];

        //隣接セルを計算
        directions.forEach(([dx, dy]) => {
            const newX = columns.indexOf(currentX) + dx;
            const newY = currentY + dy;

            if (newX >= 0 && newX < columns.length && newY >= 1 && newY <= 7) {
                adjacentCells.push(columns[newX] + newY);
            }
        });


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


        //プレイヤーがいる座標を黄色に変更
        document.getElementById(playerPos).classList.add('highlight');

        //すべてのセルにクリック可能な状態を適用
        cells.forEach(cell => {
            const cellId = cell.id;
            if (moveCount + 1 == round && adjacentCells.includes(cellId)) {
                cell.classList.add('moveable'); //隣接セルをハイライトして光らせる
                cell.style.cursor = 'pointer'; //隣接セルを選択可能に
                cell.addEventListener('click', function() {
                    //クリックされたセルの処理
                    document.getElementById('cellId').value = cellId;
                    document.getElementById('cellForm').submit();
                });
            } else {
                //隣接セルでない場合はクリックできないように
                cell.style.cursor = 'not-allowed';
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            const inventoryCells = document.querySelectorAll('.inventory-cell');
            const popup = document.getElementById('use-item-popup');
            const closePopupBtn = document.getElementById('close-popup-btn');
            const useItemBtn = document.getElementById('use-item-btn');
            const attackPopup = document.getElementById('attack-popup');
            const attackTargetSelect = document.getElementById('attack-target');
            const confirmAttackBtn = document.getElementById('confirm-attack-btn');
            const closeAttackPopupBtn = document.getElementById('close-attack-popup-btn');
            let selectedItemId = null;

            // アイテム画像がクリックされたとき
            inventoryCells.forEach(cell => {
                cell.addEventListener('click', (e) => {
                    const itemId = cell.getAttribute('data-item-id');
                    selectedItemId = itemId; // 選択されたアイテムIDを保持

                    // ポップアップを表示
                    if (selectedItemId) {
                        popup.style.display = 'flex';
                    }
                });
            });

            // ポップアップのキャンセルボタンをクリックしたとき
            closePopupBtn.addEventListener('click', () => {
                popup.style.display = 'none';
            });

            // ボタンが無効ならスタイルを変更してブラックアウト（無効化）
            if (useItemBtn.disabled) {
                useItemBtn.style.backgroundColor = 'gray';
                useItemBtn.style.cursor = 'not-allowed';
            }

            // ポップアップの使用ボタンをクリックしたとき
            useItemBtn.addEventListener('click', () => {
                if (!useItemBtn.disabled && selectedItemId) {
                    switch (selectedItemId) {
                        case '1':
                        case '2':
                        case '3':
                        case '4':
                        case '5':
                            // アイテムIDが1～5の時に攻撃対象選択ポップアップを表示
                            attackPopup.style.display = 'flex';
                            // プレイヤーリストを攻撃対象選択肢に追加
                            loadAttackTargets();
                            break;
                        case '6':
                            window.location.href = 'client_attack.php?item_id=' + selectedItemId;
                            break;
                        default:
                            alert('不明なアイテムです');
                            break;
                    }
                    // ポップアップを非表示にする
                    popup.style.display = 'none';
                }
            });

            // 攻撃対象選択肢を読み込み
            function loadAttackTargets() {
                // プレイヤー名をPHPから取得してJavaScriptで処理（例: json_encode($players)で渡されたデータ）
                const players = <?php echo ($players); ?>;

                // 既存の選択肢をクリア
                attackTargetSelect.innerHTML = '';

                // プレイヤーを攻撃対象として追加
                players.forEach(player => {
                    const option = document.createElement('option');
                    option.value = player.pl_name; // プレイヤー名をvalueとして設定
                    option.textContent = player.pl_name; // プレイヤー名を表示
                    attackTargetSelect.appendChild(option); // セレクトボックスに追加
                });
            }

            // 攻撃確認ボタンをクリックしたとき
            confirmAttackBtn.addEventListener('click', () => {
                const selectedTarget = attackTargetSelect.value; // 選ばれたターゲット

                if (selectedTarget) {
                    // 攻撃処理を実行
                    window.location.href = 'client_attack.php?item_id=' + selectedItemId + '&target=' + selectedTarget;
                } else {
                    alert('攻撃対象を選択してください');
                }

                // 攻撃対象ポップアップを非表示にする
                attackPopup.style.display = 'none';
            });

            // 攻撃対象ポップアップのキャンセルボタン
            closeAttackPopupBtn.addEventListener('click', () => {
                attackPopup.style.display = 'none';
            });

            // ポップアップ外のクリックで閉じる
            window.addEventListener('click', (e) => {
                if (e.target === popup) {
                    popup.style.display = 'none';
                } else if (e.target === attackPopup) {
                    attackPopup.style.display = 'none';
                }
            });
        });
    </script>
</body>

</html>
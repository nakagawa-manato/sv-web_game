<?php
session_start();
require('../../../../conf/dbconnect.php');

// プレイヤーIDをセッションから取得
$pl_id = $_SESSION['id'] ?? null;

// URLのクエリパラメータから item_id を取得
$itemId = isset($_GET['item_id']) ? $_GET['item_id'] : null; // クエリパラメータが存在する場合に値を取得

// URLのクエリパラメータから item_id を取得
$target_name = isset($_GET['target']) ? $_GET['target'] : null; // クエリパラメータが存在する場合に値を取得

if ($itemId) {
    // 自身の位置を取得
    $stmt = $db->prepare('SELECT pos FROM player WHERE pl_id = ?');
    $stmt->execute(array($pl_id));
    $playerPos = $stmt->fetchColumn();

    // 自身の名前を取得
    $stmt = $db->prepare('SELECT pl_name FROM player WHERE pl_id = ?');
    $stmt->execute(array($pl_id));
    $playerName = $stmt->fetchColumn();

    // 選択したプレイヤーを探して攻撃処理
    $stmt = $db->prepare('SELECT pl_id, pos, pl_hp, pl_name FROM player WHERE pl_name = ?');
    $stmt->execute(array($target_name));
    $attackedPlayer = $stmt->fetch(PDO::FETCH_ASSOC);

    switch ($itemId) {
        case '1':
            $dmg = 10;
            break;
        case '2':
            $dmg = 10;
            break;
        case '3':
            $dmg = 10;
            break;
        case '4':
            $dmg = 10;
            break;
        case '5':
            $dmg = 10;
            break;
        case '6':
            $heal = 10;
            break;
        default:
            $dmg = 0;
            break;
    }

    // ダメージの処理
    if ($dmg) {
        $attackedPlayerName = $attackedPlayer['pl_name'];

        // 攻撃対象プレイヤーのHPを減少
        $attackedPlayerId = $attackedPlayer['pl_id'];
        $newHp = $attackedPlayer['pl_hp'] - $dmg;
        // hpが0以下にならないようにする
        if ($newHp < 0) {
            $newHp = 0;
        }

        // プレイヤーのHPを更新
        $stmt = $db->prepare('UPDATE player SET pl_hp = ? WHERE pl_id = ?');
        $stmt->execute(array($newHp, $attackedPlayerId));

        // ログに記録
        $filePath = "pl_log/player_" . $pl_id . ".txt";
        $content = "プレイヤー名:" . $attackedPlayerName . " を攻撃しました。HPは-" . $dmg . "\n";

        $fileHandle = fopen($filePath, 'a');
        if ($fileHandle) {
            fwrite($fileHandle, $content);
            fclose($fileHandle);
        }

        // 相手のログに記録
        if ($newHp == 0) {
            $filePath = "pl_log/player_" . $attackedPlayerId . ".txt";
            $content = "プレイヤー名:" . $playerName . " に倒されました\n";    
        } else {
            $filePath = "pl_log/player_" . $attackedPlayerId . ".txt";
            $content = "プレイヤー名:" . $playerName . " に攻撃されました。HPは-" . $dmg . "\n";    
        }

        $fileHandle = fopen($filePath, 'a');
        if ($fileHandle) {
            fwrite($fileHandle, $content);
            fclose($fileHandle);
        }

        // プレイヤーの行動回数
        $stmt = $db->prepare('SELECT move_count FROM player WHERE pl_id = ?');
        $stmt->execute(array($pl_id));
        $move_count = $stmt->fetch();
        $move_count = $move_count['move_count'];

        $move_count++;

        $stmt = $db->prepare('UPDATE player SET move_count = ? WHERE pl_id = ?');
        $stmt->execute(array($move_count, $pl_id));

        // 攻撃後、元のページにリダイレクト
        header("Location: client_main.php");
        exit();
    } else {

        // ログに記録
        $filePath = "pl_log/player_" . $pl_id . ".txt";
        $content = "攻撃に失敗しました\n";

        $fileHandle = fopen($filePath, 'a');
        if ($fileHandle) {
            fwrite($fileHandle, $content);
            fclose($fileHandle);
        }

        // プレイヤーの行動回数
        $stmt = $db->prepare('SELECT move_count FROM player WHERE pl_id = ?');
        $stmt->execute(array($pl_id));
        $move_count = $stmt->fetch();
        $move_count = $move_count['move_count'];

        $move_count++;

        $stmt = $db->prepare('UPDATE player SET move_count = ? WHERE pl_id = ?');
        $stmt->execute(array($move_count, $pl_id));

        header("Location: client_main.php");
        exit();
    }

    // 回復の処理
    if ($heal) {
        try {
            $stmt = $db->prepare('SELECT pl_hp FROM player WHERE pl_id = ?');
            $stmt->execute(array($pl_id));
            $pl_hp = $stmt->fetch();
            $pl_hp = $pl_hp['pl_hp'];
        } catch (PDOException $e) {
            echo '接続エラー:' . $e->getMessage();
        }

        if ($pl_hp + $heal > 100) {
            for ($pl_hp; $pl_hp == 100; $pl_hp--) {
                $new_pl_hp = $pl_hp;
            }
        } else {
            $new_pl_hp = $heal;
        }

        $stmt = $db->prepare('UPDATE player SET pl_hp = pl_hp + ? WHERE pl_id = ?');
        $stmt->execute(array($new_pl_hp, $pl_id));

        // ログに記録
        $filePath = "pl_log/player_" . $pl_id . ".txt";
        $content = "プレイヤー名:" . $playerName . "を" . $new_pl_hp . "回復しました\n";

        $fileHandle = fopen($filePath, 'a');
        if ($fileHandle) {
            fwrite($fileHandle, $content);
            fclose($fileHandle);
        }

        // プレイヤーの行動回数
        $stmt = $db->prepare('SELECT move_count FROM player WHERE pl_id = ?');
        $stmt->execute(array($pl_id));
        $move_count = $stmt->fetch();
        $move_count = $move_count['move_count'];

        $move_count++;

        $stmt = $db->prepare('UPDATE player SET move_count = ? WHERE pl_id = ?');
        $stmt->execute(array($move_count, $pl_id));

        header("Location: client_main.php");
        exit();
    }
}

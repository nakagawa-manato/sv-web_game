<?php
session_start();
require('../../../conf/dbconnect.php');

// プレイヤーIDをセッションから取得
$pl_id = $_SESSION['id'] ?? null;

// URLのクエリパラメータから item_id を取得
$itemId = isset($_GET['item_id']) ? $_GET['item_id'] : null; // クエリパラメータが存在する場合に値を取得

if ($itemId) {
    // 自身の位置を取得
    $stmt = $db->prepare('SELECT pos FROM player WHERE pl_id = ?');
    $stmt->execute(array($pl_id));
    $playerPos = $stmt->fetchColumn();

    // 自分の隣接プレイヤーを探して攻撃処理
    $stmt = $db->prepare('SELECT pl_id, pos, pl_hp, pl_name FROM player WHERE pos = ? AND pl_id != ?');
    $stmt->execute([$playerPos, $pl_id]);
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
        default:
            $dmg = 0;
            break;
    }
    if ($attackedPlayer) {
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
        $stmt->execute([$newHp, $attackedPlayerId]);

        // ログに記録
        $filePath = "pl_log/player_" . $pl_id . ".txt";
        $content = "プレイヤー名:" . $attackedPlayerName . " を攻撃しました。HPは-". $dmg ."\n";

        $fileHandle = fopen($filePath, 'a');
        if ($fileHandle) {
            fwrite($fileHandle, $content);
            fclose($fileHandle);
        }

        // 攻撃後、元のページにリダイレクト
        header("Location: host_main.php");
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

        header("Location: host_main.php");
        exit();
    }
}
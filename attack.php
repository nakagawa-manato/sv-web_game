<?php
session_start();
require('../../../conf/dbconnect.php');

// プレイヤーIDをセッションから取得
$pl_id = $_SESSION['id'] ?? null;

if ($pl_id !== null && isset($_POST['attack'])) {
    // 自身の位置を取得
    $stmt = $db->prepare('SELECT pos FROM player WHERE pl_id = ?');
    $stmt->execute(array($pl_id));
    $playerPos = $stmt->fetchColumn();

    // 自分の隣接プレイヤーを探して攻撃処理
    $stmt = $db->prepare('SELECT pl_id, pos, pl_hp FROM player WHERE pos = ? AND pl_id != ?');
    $stmt->execute([$playerPos, $pl_id]);
    $attackedPlayer = $stmt->fetch(PDO::FETCH_ASSOC);

    // 武器ごとにダメージを決める
    // 武器は一度使用したら消える?
    if ($attackedPlayer) {
        // 攻撃対象プレイヤーのHPを減少
        $attackedPlayerId = $attackedPlayer['pl_id'];
        $newHp = max(0, $attackedPlayer['pl_hp'] - 10); // HPが0未満にならないようにする

        // プレイヤーのHPを更新
        $stmt = $db->prepare('UPDATE player SET pl_hp = ? WHERE pl_id = ?');
        $stmt->execute([$newHp, $attackedPlayerId]);

        // ログに記録
        $filePath = "pl_log/player_" . $pl_id . ".txt";
        $content = "プレイヤー " . $attackedPlayerId . " を攻撃しました。HPは -10\n";

        $fileHandle = fopen($filePath, 'a');
        if ($fileHandle) {
            fwrite($fileHandle, $content);
            fclose($fileHandle);
        }

        // 攻撃後、元のページにリダイレクト
        header("Location:canvas.php");
        exit();
    }
} else {
    echo "攻撃できるプレイヤーがいません。";
}
?>
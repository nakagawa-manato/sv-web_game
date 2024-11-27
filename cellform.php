<?php
session_start();
require('../../../conf/dbconnect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // POSTデータからcellIdを取得
    $cellId = $_POST['cellId'] ?? '';
    //POSTデータからitemidを取得
    $itemId = $_POST['use_item_id'] ?? '';
    //プレイヤーidをsessionから$idに入れる
    $pl_id = $_SESSION['id'];

    $raw = file_get_contents('php://input'); // POSTされた生のデータを受け取る
    $data = json_decode($raw); // json形式をphp変数に変換

    $itemId = $data;

    //セルidがあるかつ、pl_idがnullではないときにposをupdateする
    if ($cellId) {
        if ($pl_id !== null) {
            $stmt = $db->prepare('UPDATE player SET pos = ? WHERE pl_id = ?');
            $stmt->execute(array($cellId, $pl_id));
        }
    }

    //プレイヤーidごとにログファイルを作成
    //chmodで権限を755,644
    //ディレクトリをあらかじめ作成
    if ($pl_id !== null) {
        //プレイヤーidごとにテキストファイルパスを設定(player/player_1.txt)
        $filePath = "pl_log/player_" . $pl_id . ".txt";

        //書き込む内容を作成
        $content = $pl_id . "は" . $cellId . "に移動しました\n";

        //ファイルが存在する場合は上書き保存、存在しない場合は新規作成
        $fileHandle = fopen($filePath, 'a'); //aは上書き保存

        if ($fileHandle) {
            //ファイルに内容を書き込む
            fwrite($fileHandle, $content);
            fclose($fileHandle);
        } else {
            echo "ファイルの書き込みに失敗しました";
        }
    } else {
        echo "プレイヤーIDが不明です";
    }

    //var_dump($itemId);
    if (isset($itemId)) {
        // アイテムを使用した処理（例：数量を減らす）
        $stmt = $db->prepare('UPDATE backpack SET amount = amount - 1 WHERE pl_id = ? AND item_id = ?');
        $stmt->execute(array($pl_id, $itemId));

        // アイテム使用履歴を記録
        $filePath = "pl_log/player_" . $pl_id . ".txt";
        $content = "包帯を使用しました\n";

        $fileHandle = fopen($filePath, 'a'); //aは上書き保存

        if ($fileHandle) {
            //ファイルに内容を書き込む
            fwrite($fileHandle, $content);
            fclose($fileHandle);
        } else {
            echo "ファイルの書き込みに失敗しました";
        }

        //包帯使用時にpl_hpを+15
        if ($itemId = 6) {
            $stmt = $db->prepare('UPDATE player SET pl_hp = pl_hp + 15 WHERE pl_id = ?');
            $stmt->execute(array($pl_id));
        }

        //pl_hpを取得
        $stmt = $db->prepare('SELECT pl_hp FROM player WHERE pl_id = ?');
        $stmt->execute(array($pl_id));
        $pl_hp = $stmt->fetch();

        //pl_hpが100以上の場合100に設定
        if ($pl_hp > 100) {
            $pl_hp = 100;
        }
    }

    // 処理が終わったら元のHTMLにリダイレクト
    header("Location:canvas.php");
    exit(); // スクリプトを終了
}
?>
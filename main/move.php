<?php
session_start();
require('../../../../conf/dbconnect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // POSTデータからcellIdを取得
    $cellId = $_POST['cellId'] ?? '';
    //プレイヤーidをsessionから$idに入れる
    $pl_id = $_SESSION['id'];

    $raw = file_get_contents('php://input'); // POSTされた生のデータを受け取る
    $data = json_decode($raw); // json形式をphp変数に変換

    $itemId = $data;

    // 自身の名前を取得
    $stmt = $db->prepare('SELECT pl_name FROM player WHERE pl_id = ?');
    $stmt->execute(array($pl_id));
    $playerName = $stmt->fetchColumn();

    //セルidがあるかつ、pl_idがnullではないときにposをupdateする
    if ($cellId) {
        if ($pl_id !== null) {
            $stmt = $db->prepare('UPDATE player SET pos = ? WHERE pl_id = ?');
            $stmt->execute(array($cellId, $pl_id));
        }
    }

    //プレイヤーidごとにログファイルを作成
    //ディレクトリをあらかじめ作成
    if ($pl_id !== null) {
        //プレイヤーidごとにテキストファイルパスを設定(player/player_1.txt)
        $filePath = "pl_log/player_" . $pl_id . ".txt";

        //書き込む内容を作成
        $content = $playerName . "は" . $cellId . "に移動しました\n";

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

    // プレイヤーの行動回数
    $stmt = $db->prepare('SELECT move_count FROM player WHERE pl_id = ?');
    $stmt->execute(array($pl_id));
    $move_count = $stmt->fetch();
    $move_count = $move_count['move_count'];

    $move_count++;

    $stmt = $db->prepare('UPDATE player SET move_count = ? WHERE pl_id = ?');
    $stmt->execute(array($move_count, $pl_id));

    // 処理が終わったら元のHTMLにリダイレクト
    header("Location:main.php");
    exit(); // スクリプトを終了
}

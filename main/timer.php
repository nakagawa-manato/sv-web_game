<?php
session_start();
require('../../../../conf/dbconnect.php');

$stmt = $db->prepare('SELECT room_id FROM rooms WHERE is_game = 1');
$stmt->execute();
$room_id = $stmt;

$stmt = $db->prepare('SELECT timer FROM timer WHERE room_id = ?');
$stmt->execute(array($room_id));
$timer_row = $stmt;

$stmt = $db->prepare('SELECT round FROM timer WHERE room_id = ?');
$stmt->execute(array($room_id));
$round = $stmt->fetch();

header('Content-Type: text/event-stream');
header('Cache-Control: no-store');

while (true) {

    $timer_row ++;

    $stmt = $db->prepare('UPDATE timer SET timer = ? WHERE room_id = ?');
    $stmt->execute(array($timer_row));

    if ($timer_row % 30 == 0) {
        $round++;
        $stmt = $db->prepare('UPDATE timer SET round = ? WHERE room_id = ?');
        $stmt->execute(array($round, $room_id));
    }

    printf("data: %s\n\n", json_encode([
        'time' => $timer_row,
    ]));

    ob_end_flush();
    flush();
    sleep(1);
}

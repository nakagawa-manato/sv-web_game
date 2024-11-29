<?php
session_start();
require('../../../conf/dbconnect.php');

$pl_id = $_SESSION['id'];

$stmt = $db->prepare('UPDATE player SET room_id = 0 WHERE pl_id = ?');
$stmt->execute(array($pl_id));

$stmt = $db->prepare('DELETE FROM rooms WHERE pl_id = ?');
$stmt->execute(array($pl_id));

header('Location: room.php');
exit;

?>
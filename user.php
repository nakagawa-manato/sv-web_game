<?php
session_start();
require('../../../conf/dbconnect.php');

if (!empty($_POST['name'])) {
        $name = $_POST['name'];
        //登録
        $statement = $db->prepare('INSERT INTO player SET pl_name = ?');
        $statement->execute(array($name));

        $ids = $db->prepare('SELECT pl_id FROM player where pl_name = ?');
        $ids->execute(array($name));
        $id = $ids->fetch();

        $_SESSION['id'] = $id['pl_id'];

        header('Location: create_room.php');
}
?>
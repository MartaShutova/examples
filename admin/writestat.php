<?php
require_once('../ajax/system/db.php');
require_once('../ajax/system/user.php');

if(isset($_POST['writeTimeForStatistics'])){
    if (isset($_SESSION['user_id']) && $_SESSION['user_id']!=0){
        $db=getDB();
        $ws = $db->prepare("select user_id from user_stat where user_id=:uid;");
        $ws->bindParam('uid',$_SESSION['user_id']);
        $ws -> execute();
        $result = $ws->fetch(PDO::FETCH_ASSOC);
        if (count($result)>0){
            $uslt = $db->prepare("update user_stat set seconds_spent_live=seconds_spent_live+50 where user_id=:uid;");
            $uslt->bindParam('uid',$_SESSION['user_id']);
            $uslt -> execute();
        }
    }

};
?>
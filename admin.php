<?php
//require_once("./system/session.php");
//require_once("./system/user.php");
require_once('../ajax/system/db.php');
require_once('../ajax/system/user.php');

if(isset($_POST['changeStatusByCompany'])){
    if(isset($_POST['country']) && isset($_POST['status']) && $_POST['status']>0 && $_POST['country']>0){
        $countryId = $_POST['country'];
        $statusId = $_POST['status'];
        $db=getDB();
        $qry = "update \"user\" set user_status_id = :sid where company_id=:cid";
        $smtx = $db->prepare($qry);
        $smtx->bindParam(':cid',$countryId);
        $smtx->bindParam(':sid',$statusId);
        if($smtx->execute()){
            $result['text'] = 'User status successfully changed!';
        } else {
            $result['text'] = "User status hasn't changed!";
        }
        $result['ref'] = "../admin/index.php";
        $result['ref']="../admin/index.php";
        $result['text']= $_POST['country'];
        echo json_encode($result);
    }
} else if(isset($_POST['changeUserStatus'])) {
    if (isset($_POST['user']) && isset($_POST['status2']) && $_POST['status2'] > 0 && $_POST['user'] > 0) {
        $userId = $_POST['user'];
        $statusId = $_POST['status2'];
        $db = getDB();
        $qry = "update \"user\" set user_status_id = :sid where user_id=:cid";
        $smtx = $db->prepare($qry);
        $smtx->bindParam(':cid', $userId);
        $smtx->bindParam(':sid', $statusId);
        if($smtx->execute()){
            $result['text'] = 'Users status successfully changed!';
        } else {
            $result['text'] = "User status hasn't changed!";
        }
        $result['ref'] = "../admin/index.php";
        echo json_encode($result);
    }
} else if(isset($_POST['deleteArticles'])) {
    if (isset($_POST['editor']) && $_POST['editor'] > 0) {
        $editorId = $_POST['editor'];
        $db = getDB();
        $qry = "select article_id from article where editor=:cid";
        $smtx = $db->prepare($qry);
        $smtx->bindParam(':cid', $editorId);
        $smtx->execute();

        while ($row = $smtx->fetch(PDO::FETCH_ASSOC)) {
            $articleId = $row['article_id'];
                $prepare = $db->prepare("delete from article where article_id=:aai");
                $prepare->bindParam('aai', $articleId);
                $prepare -> execute();

                $prepare1 = $db->prepare("delete from article_cat where article_id=:aai");
                $prepare1->bindParam('aai', $articleId);
                $prepare1 -> execute();

                $prepare2 = $db->prepare("delete from article_text where article_id=:aai");
                $prepare2->bindParam('aai', $articleId);
                $prepare2 -> execute();

                $prepare3 = $db->prepare("delete from article_region where article_id=:aai");
                $prepare3->bindParam('aai', $articleId);
                $prepare3 -> execute();
        }
        $result['ref'] = "../admin/index.php";
        $result['text'] = $_POST['editor'];
        echo json_encode($result);
    }
} else if(isset($_POST['deleteChosenArticle'])) {
    if (isset($_POST['id']) && $_POST['id'] > 0) {
        $Id = $_POST['id'];
        $db = getDB();
        $qry = "select * from article where article_id=:aid";
        $smtx = $db->prepare($qry);
        $smtx->bindParam(':aid', $Id);
        $smtx->execute();
        $row = $smtx->fetch(PDO::FETCH_ASSOC);
        if(count($row)>0) {
            $prepare1 = $db->prepare("delete from article_cat where article_id=:aid");
            $prepare1->bindParam(':aid', $Id);
            $prepare1->execute();

            $prepare2 = $db->prepare("delete from article_text where article_id=:aid");
            $prepare2->bindParam(':aid', $Id);
            $prepare2->execute();

            $prepare3 = $db->prepare("delete from article_region where article_id=:aid");
            $prepare3->bindParam(':aid', $Id);
            $prepare3->execute();

            $prepare4 = $db->prepare("delete from article_comment where article_id=:aid");
            $prepare4->bindParam(':aid', $Id);
            $prepare4->execute();

            $prepare5 = $db->prepare("delete from article_hashtag where article_id=:aid");
            $prepare5->bindParam(':aid', $Id);
            $prepare5->execute();

            $prepare6 = $db->prepare("delete from article_picture where article_id=:aid");
            $prepare6->bindParam(':aid', $Id);
            $prepare6->execute();

            $prepare7 = $db->prepare("delete from article_source where article_id=:aid");
            $prepare7->bindParam(':aid', $Id);
            $prepare7->execute();

            $prepare8 = $db->prepare("delete from article_source_intern where article_id=:aid");
            $prepare8->bindParam(':aid', $Id);
            $prepare8->execute();

            $prepare9 = $db->prepare("delete from article_customer_group where article_id=:aid");
            $prepare9->bindParam(':aid', $Id);
            $prepare9->execute();

            $prepare = $db->prepare("delete from article where article_id=:aid");
            $prepare->bindParam(':aid', $Id);
            $prepare->execute();
        }
        $qry = "select * from article where article_id=:aid";
        $check = $db->prepare($qry);
        $check->bindParam(':aid', $Id);
        $check->execute();
        if(count($check->fetchAll(PDO::FETCH_ASSOC))==0){
            $result['text'] = 'deleted';
        } else {
            $result['text'] = 'no';
        }
        $result['ref'] = "../admin/articles.php";
        echo json_encode($result);
    };
} else if(isset($_POST['statistics'])) {
    $db = getDB();
//    $users = $db->prepare("select * from \"user\" where user_status_id=1 and deactivated_at is null;");
//    $users->execute();
//    $result = array();
//    $result["text"] = array();
//    //extract($row);
//    $numberUser = count($users->fetchAll(PDO::FETCH_ASSOC));
//    $companies = $db->prepare("select * from company LEFT JOIN (SELECT company_id, count(*) as cun from \"user\" group by company_id) as u on company.company_id=u.company_id ");
//    $companies->execute();
//    $companydetails = array();
//    while ($company = $companies->fetch(PDO::FETCH_ASSOC)) {
//        array_push($companydetails, array("cname" => $company["cname"], "cusers" => $company["cun"]));
//    }
//    $article_item=array(
//        "numberUser" => $numberUser,
//        "companies" => $companydetails
//    );
//    array_push($result["text"], $article_item);
//    echo json_encode($result);
    $users = $db->prepare("select * from user_stat INNER JOIN \"user\" ON user_stat.user_id=\"user\".user_id ORDER BY logon_count DESC;");
    $users->execute();
    $user_time_count = array();
    //extract($row);
    while ($user = $users->fetch(PDO::FETCH_ASSOC)) {
        array_push($user_time_count, array("user_name" => $user["surname"].' '.$user["lastname"], "counts" => $user["logon_count"], "time" => $user["seconds_spent_live"]));
    }
    echo json_encode($user_time_count);
}else if(isset($_POST['articleStatistics']) && $_POST['articleStatistics']!=0) {
    $article_stat_type_id = $_POST['articleStatistics'];
    $db = getDB();
    $articles = $db->prepare("select article_id, COUNT(article_id) as article_count from article_stat WHERE article_stat_type_id=:asti GROUP BY article_id ORDER BY COUNT(article_id) DESC;");
    $articles->bindParam(':asti', $article_stat_type_id);
    $articles->execute();
    $article_using_count = array();
    //extract($row);
    while ($article = $articles->fetch(PDO::FETCH_ASSOC)) {
        array_push($article_using_count, array("article_id" => $article["article_id"], "article_count" => $article["article_count"]));
    }
    echo json_encode($article_using_count);
} else if(isset($_POST['changeFullText'])) {
    $changeText = $_POST['changeText'];
    $articleId = $_POST['id'];
    if($articleId!=0 && $changeText!=''){
        $db = getDB();
        $replace_text = $db->prepare("UPDATE article_text SET full_text=:fulltext WHERE article_id = :aid;");
        $replace_text->bindParam(':fulltext', $changeText);
        $replace_text->bindParam(':aid', $articleId);
        if($replace_text->execute()){
            $result['text'] = 'Text successfully changed!';
        } else {
            $result['text'] = "Text hasn't changed!";
        };
    };
    echo json_encode($result);
} else if(isset($_POST['statisticByUser'])) {
    $db = getDB();
    $users = $db->prepare("select user_id, concat(surname,' ',lastname) as user_name from \"user\" ORDER BY user_name;");
    $users->execute();
    $users_names = array();
    //extract($row);
    while ($user = $users->fetch(PDO::FETCH_ASSOC)) {
        array_push($users_names, array("user_id" => $user["user_id"], "user_name" => $user["user_name"]));
    }
    echo json_encode($users_names);
} else if(isset($_POST['StatisticForChoosingUser']) && $_POST['StatisticForChoosingUser']!=0 && $_POST['StatisticForChoosingUser']!='') {
    $Id = $_POST['StatisticForChoosingUser'];
    $db = getDB();
    $usersStatistic = $db->prepare("select user_stat.*, \"user\".*, security_level.name as slname, user_status.status_name from user_stat INNER JOIN \"user\" ON user_stat.user_id=\"user\".user_id LEFT JOIN security_level on \"user\".security_level_id=security_level.security_level_id LEFT JOIN user_status on \"user\".user_status_id=user_status.user_status_id  WHERE \"user\".user_id=:aid;");
    $usersStatistic->bindParam(':aid', $Id);
    $usersStatistic->execute();
    $articlesStatistic = $db->prepare("select a_s.article_id, COUNT(a_s.*) as article_count, a_s_t.* from article_stat a_s LEFT JOIN article_stat_type a_s_t ON a_s.article_stat_type_id=a_s_t.article_stat_type_id WHERE user_id=:aid GROUP BY a_s.article_id, a_s_t.article_stat_type_id ORDER BY article_count DESC;");
    $articlesStatistic->bindParam(':aid', $Id);
    $articlesStatistic->execute();
    $personalStatistic = array();
    $personalArticleStatistic = array();
    $personalStatistic['byUser'] = array();
    $personalStatistic['articlesByUser'] = array();
    while ($userStatistic = $usersStatistic->fetch(PDO::FETCH_ASSOC)) {
        $personalStatistic['byUser'] = array("user_name" => $userStatistic["surname"].' '.$userStatistic["lastname"], "user_status" => $userStatistic["status_name"],
            "security_level" => $userStatistic["slname"], "last_logon" => $userStatistic["last_logon"], "email" => $userStatistic["email"],
            "counts" => $userStatistic["logon_count"], "time" => $userStatistic["seconds_spent_live"], "deactivated_time" => $userStatistic["deactivated_at"]);
    }
    while ($articleStatistic = $articlesStatistic->fetch(PDO::FETCH_ASSOC)) {
        array_push($personalArticleStatistic, array("article_id" => $articleStatistic["article_id"], "article_count" => $articleStatistic["article_count"], "astname" => $articleStatistic["description"]));
    }
    array_push($personalStatistic['articlesByUser'],$personalArticleStatistic);
    echo json_encode($personalStatistic);
} else if(isset($_POST['delCatEwz'])){
    $db = getDB();
    $delCat = $db->prepare("select * from cat where cat_name like '%ewz %' or description like '%ewz %'");
    $delCat->execute();
    $count=0;
    while($row = $delCat->fetch(PDO::FETCH_ASSOC)) {
        $prepare1 = $db->prepare("delete from cat_module_neu where cat_id=:cid");
        $prepare1->bindParam(':cid', $row['cat_id']);
        $prepare1->execute();

        $prepare2 = $db->prepare("delete from cat_module where cat_id=:cid");
        $prepare2->bindParam(':cid', $row['cat_id']);
        $prepare2->execute();

        $prepare3 = $db->prepare("delete from cat where cat_id=:cid");
        $prepare3->bindParam(':cid', $row['cat_id']);
        if($prepare3->execute()){$count++;}
    }
    echo json_encode($count);
} else {
    return false;
};
?>
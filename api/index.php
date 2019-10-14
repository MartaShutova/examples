<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once('../ajax/system/db.php');

// $request = explode('/', trim($_SERVER['PATH_INFO'],'/'));
// $_POST = json_decode(file_get_contents('php://input'),true);

// read products
function read($limit) {

    $db = getDB();
    $smtx = $db->prepare("select * from article order by article_id DESC");
    if (isset($limit) && $limit !== '') {
        $smtx = $db->prepare("select * from article order by article_id DESC LIMIT ".$limit);
    }
    $smtx -> execute();
 
    return $smtx;

}

// query products
$stmt = read('');
if (isset($_POST)) {
    if (isset($_POST['limit'])) {
        $stmt = read($_POST['limit']);
    }
}
$num = $stmt->rowCount();
 
// check if more than 0 record found
if($num>0){
 
    // products array
    $article_arr = array();
    $article_arr["records"] = array();
 
    // retrieve our table contents
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){

        // extract row
        // this will make $row['name'] to
        // just $name only
        extract($row);
 
        $article_item=array(
            "id" => $article_id,
            "status" => $article_status,
            "editor" => $editor,
            "headline" => html_entity_decode($headline),
            "created" => $created_at,
            "modified" => $last_modified
        );
 
        array_push($article_arr["records"], $article_item);

    }
 
    // set response code - 200 OK
    http_response_code(200);
 
    // show products data in json format
    echo json_encode($article_arr);

}
 
// no products found will be here

else {
 
    // set response code - 404 Not found
    http_response_code(404);
 
    // tell the user no products found
    echo json_encode(
        array("message" => "Nothing found.")
    );

}

?>
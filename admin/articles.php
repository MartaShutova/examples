<?php
include('../inc/header-admin-login.php');
$title = 'Admin';

require_once('../ajax/system/db.php');
require_once('../ajax/system/user.php');
require_once('../ajax/system/article.php');

$db=getDB();
$smtx = $db->prepare("select * from article order by article_id DESC");
$smtx -> execute();
?>
<main class="container">
    <div class="row">
        <div class="col-xs-12">
            <h1>Artikels</h1>
        </div>
    </div>
    <div id="response">

    </div>
    <div class="row">
        <div class="col-xs-12">
            <form id="delArticle">
                <table class="table table-bordered">
                    <tr>
                        <th>N</th>
                        <th>Article</th>
                        <th>Delete?</th>
                    </tr>
                <?php while($row = $smtx->fetch(PDO::FETCH_ASSOC)){ ?>
                    <tr id="tr<?php echo $row['article_id']; ?>">
                        <td><?php echo $row['article_id']; ?></td>
                        <td><?php echo $row['headline']; ?></td>
                        <td>
                            <a id="<?php echo $row['article_id']; ?>" class="delButton btn btn-primary">Delete?</a>
                        </td>
                    </tr>
                <?php }?>
                </table>
            </form>
        </div>
    </div>
</main>
<script>
    $( document ).ready(function(){
        $(".delButton" ).click(function(){
            if(confirm("Are you sure?")){
                var id = $(this).attr('id');
                $.ajax({
                    url : "../ajax/admin.php",
                    type: 'POST',
                    data : {"id": id, "deleteChosenArticle": true},
                }).success(function(data) {
                    var result = jQuery.parseJSON(data);
                    var response = result['text'];
                    if(response == 'deleted'){
                       $('#tr'+id).remove();
                    }else if(response == 'no'){
                        $('#response').html("<h2>Can't delete article!</h2>");
                    }
                })
            }
        })
    })
</script>

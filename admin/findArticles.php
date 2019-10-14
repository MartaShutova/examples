<?php
include('../inc/header-admin-login.php');
$title = 'Admin';

require_once('../ajax/system/db.php');
require_once('../ajax/system/user.php');
require_once('../ajax/system/article.php');
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


<?php
if(isset($_POST['changeText'])) {?>
        <div class="col-xs-12">
            <table class="table table-bordered">
                <tr>
                    <th>N</th>
                    <th>Article</th>
                    <th>Change?</th>
                </tr>
    <?php
    $inputText = $_POST['findText'];
    $findTextSpecial = "%".htmlentities($inputText, ENT_QUOTES)."%";
    $findText="%".$inputText."%";
    $db = getDB();
    $fulltext = $db->prepare("select art.*, article.headline from article_text as art INNER JOIN article on art.article_id=article.article_id where art.full_text like :text OR article.headline like :text or art.full_text like :textSpecial OR article.headline like :textSpecial;");
    $fulltext->bindParam(':text', $findText);
    $fulltext->bindParam(':textSpecial', $findTextSpecial);
    $fulltext->execute();
    while ($text = $fulltext->fetch(PDO::FETCH_ASSOC)) {?>
        <tr id="tr<?php echo $text['article_id']; ?>">
            <td><?php echo $text['article_id']; ?></td>
            <td>
                <p class="show" id="show<?php echo $text['article_id']; ?>"><?php echo $text['headline']; ?></p>
                <div id="full<?php echo $text['article_id']; ?>" class="full" style="display: none; width: 100%; min-height: inherit">
                    <textarea id="textarea<?php echo $text['article_id']; ?>" class="textarea" style="width: 100%; min-height: 250px;"><?php echo $text['full_text'];?></textarea>
                    <div style="width: 100%"><span style="margin: 10px auto; width: 20%;" id="check<?php echo $text['article_id']; ?>" class="checkButton btn btn-primary">Check result</span></div>
                    <div id="textoriginal<?php echo $text['article_id']; ?>" class="original" style="width: 100%;"><?php echo $text['full_text'];?></div>
                </div>
            </td>
            <td>
                <a id="change<?php echo $text['article_id']; ?>" class="changeButton btn btn-primary">Save changes?</a>
            </td>
        </tr>
        <?php
    };?></table>

        </div>
<?php
} else {
  echo "<h2>There is no articles.</h2>";
};
?>

    </div>
</main>
<script>
    $( document ).ready(function(){
        $(".checkButton" ).click(function(){
            var oldId = $(this).attr('id');
            var checkId = oldId.replace("check", "textarea");
            var resultId = oldId.replace("check", "textoriginal");
            var allText = $('#'+checkId).val();
            $('#'+resultId).html(allText);
        });
    });
    $( document ).ready(function(){
        $(".show" ).click(function(){
            $('div.full').css({'display':'none'});
            var id = $(this).attr('id');
            var newId = id.replace("show", "full");
            $('#'+newId).css({'display':'block'});
        });
    });
    $( document ).ready(function(){
        $(".changeButton" ).click(function(){
            if(confirm("Are you sure?")){
                var changeId = $(this).attr('id');
                changeId = changeId.replace('change','');
                var resultText = $('#textarea'+changeId).val();
                $.ajax({
                    url : "../ajax/admin.php",
                    type: 'POST',
                    data : {"id": changeId, "changeText": resultText, "changeFullText": true},
                }).success(function(data) {
                    var result = jQuery.parseJSON(data);
                    var response = result['text'];
                    $('#response').html("<h2>"+response+"</h2>");
                });
            }
        });
    });
</script>

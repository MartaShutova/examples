<?php
require_once 'vendor/autoload.php';
require_once('./ajax/system/db.php');
if(!isset($_SESSION)) session_start();
$article_id=$_GET['article'];
$phpWord = new \PhpOffice\PhpWord\PhpWord();
$phpWord->setDefaultFontName('Tahoma');
$phpWord->setDefaultFontSize(14);
$db = getDB();
if($article_id && $article_id != 0 && isset($_SESSION['user_id']) &&$_SESSION['user_id']!=0) {
    $stat = $db->prepare("insert into article_stat (article_id, user_id, article_stat_type_id, \"date\") VALUES (:aid, :uid, 3, :dat);");
    $stat->bindParam(':uid', $_SESSION['user_id']);
    $stat->bindParam(':aid', $article_id);
    $stat->bindParam(':dat', date('Y-m-d h:m:s'));
    $stat->execute();
}
$qry= "Select *, full_text, concat(u2.surname,' ',u2.lastname) as editor, concat(u1.surname,' ',u1.lastname) as modifier, source_id from article 
        left join article_text on article.article_id=article_text.article_id
         left join \"user\" as u2 on article.editor=u2.user_id
         left join \"user\" as u1 on article.last_modified_by=u1.user_id
         LEFT JOIN article_source on article.article_id=article_source.article_id
          where article.article_id=:aid";
$smt = $db->prepare($qry);
$smt->bindParam(':aid',$article_id);
$smt->execute();
$row = $smt->fetch(PDO::FETCH_ASSOC);
//source
$source = $row['source_id'];
$smt1 = $db->prepare("select \"name\", to_char(cast(source_date as date), 'DD.MM.YYYY') as \"sdate\", \"date_type\" as dtt, date_alternative, url from article_source left join \"source\" on \"source\".source_id=article_source.source_id where article_id=:aid");
$smt1->bindParam(':aid',$article_id);
$smt1->execute();
$first = true;
while ($row1 = $smt1->fetch(PDO::FETCH_ASSOC)) {
    if ($first){$source_string = '<small>Quelle: <a href="';
        $first = false;
    }else{
        $source_string .= ', <a href="';
    }
    $source_string .= $row1['url'].'">'.$row1['name'].'</a> (';
    if($row1['dtt']==2){
        $source_string .= $row1['date_alternative'];
    }
    if($row1['dtt']==3){
        $source_string .= $row1['date_alternative'];
    }
    if($row1['dtt']==4){
        $source_string .= 'Stand: '.$row1['date_alternative'];
    }
    if($row1['dtt']==5){
        $source_string .= 'Ausgabe: '.$row1['date_alternative'];
    }
    else{
        $source_string .= $row1['sdate'];
    }
    $source_string .= ') ';
}
$source_string .= '</small>';
$properties = $phpWord->getDocInfo();
$properties->setCreator($row['editor']);
$properties->setCompany($row1['name']);
$properties->setTitle($row['headline']);
$properties->setDescription($row['teaser']);
$properties->setCreated($row['created_at']);
$properties->setModified($row['last_modified']);
$sectionStyle = array(
    'orientation' => 'portrait',
    'marginTop' => 1000,
    'marginLeft' => 1000,
    'marginRight' => 1000,
    'colsNum' => 1,
    'pageNumberingStart' => 0,
    'borderBottomSize'=>100,
    'borderBottomColor'=>'C0C0C0'
);
$section = $phpWord->addSection($sectionStyle);
$fullText = $row['full_text'];
$fontStyle = array( 'name' => 'Tahoma', 'size' => 20,'color' => '#1F5C1F' ,'bold'=>false);
$phpWord->addTitleStyle(6,$fontStyle);
$section->addText('<div style="max-width: 80%; margin: 30px auto;"' );
$section->addText(date('d.m.Y',strtotime($row['last_modified'])), array('name' => 'Tahoma', 'size' => 9,'color' => '#000000' ,'bold'=>false));
$section->addText('<hr/>');
$section->addTitle($row['headline'],6);
$fullText = iconv('UTF-8', 'windows-1252', $fullText);
$section->addText(html_entity_decode($fullText), array('name' => 'Tahoma', 'size' => 12,'color' => '#000000' ,'bold'=>false));
$section->addText(html_entity_decode($source_string), array('name' => 'Tahoma', 'size' => 10,'color' => '#c9982b' ,'bold'=>false));
$section->addText('</div>');
header("Content-Description: File Transfer");
header('Content-Disposition: attachment; filename="html'.rand(10000,99999).'.html"');
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Transfer-Encoding: binary');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Expires: 0');
$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML');
$objWriter->save("php://output");
?>
<?php
require_once 'vendor/autoload.php';
require_once('./ajax/system/db.php');
if(!isset($_SESSION)) session_start();
$article_id=$_GET['article'];
$phpWord = new \PhpOffice\PhpWord\PhpWord();

$phpWord->setDefaultFontName('Tahoma');
$phpWord->setDefaultFontSize(14);
$image_path = array();
$db = getDB();
if($article_id && $article_id != 0 && isset($_SESSION['user_id']) && $_SESSION['user_id']!=0) {
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
$smt1 = $db->prepare("select \"name\", to_char(cast(source_date as date), 'DD.MM.YYYY') as \"sdate\", \"date_type\" as dtt, date_alternative, url from article_source left join \"source\" on \"source\".source_id=article_source.source_id where article_id=:aid");
$smt1->bindParam(':aid',$article_id);
$smt1->execute();
$first = true;
while ($row1 = $smt1->fetch(PDO::FETCH_ASSOC)) {
    if ($first){$source_string = 'Quelle: ';
        $first = false;
    }else{
        $source_string .= ', ';
    }
    $source_string .= $row1['name'].' (';
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
};
$properties = $phpWord->getDocInfo();
$properties->setCreator($row['editor']);
$properties->setCompany($row1['name']);
$properties->setTitle($row['headline']);
$properties->setDescription($row['teaser']);
//$properties->setCategory('My category');
//$properties->setLastModifiedBy('My name');
$properties->setCreated($row['created_at']);
$properties->setModified($row['last_modified']);
//$properties->setSubject('My subject');
//$properties->setKeywords('my, key, word');

$sectionStyle = array(
    'orientation' => 'portrait',
    'marginTop' => 1000,
    'marginLeft' => 1000,
    'marginRight' => 1000,
    'colsNum' => 1,
    'pageNumberingStart' => 0,
    'borderBottomSize'=>40,
    'borderBottomColor'=>'C0C0C0'
);
$section = $phpWord->addSection($sectionStyle);
$fullText = $row['full_text'];
$fontStyle = array( 'name' => 'Tahoma', 'size' => 20,'color' => '#1F5C1F' ,'bold'=>false);
$phpWord->addTitleStyle(6,$fontStyle);
$section->addText(date('d.m.Y',strtotime($row['last_modified'])), array('name' => 'Tahoma', 'size' => 9,'color' => '#000000' ,'bold'=>false));

$section->addTitle(htmlspecialchars(html_entity_decode($row['headline']), ENT_QUOTES),6);

$doc = new DOMDocument();
@$doc->loadHTML($fullText);
$tags = $doc->getElementsByTagName('img');
$tagsTable = $doc->getElementsByTagName('table');
if ($tagsTable->length>0){
    $tagsTr = $doc->getElementsByTagName('tr');
    $dataTable = [];
    foreach ($tagsTr as $i => $tr) {
        /** @var DOMElement $td */
        foreach ($tr->childNodes as $td) {
            if ($td instanceof DOMElement) {
                /** @var DOMElement $a */
                $row = [];
                foreach ($td->childNodes as $a) {
                    /** @var DOMAttr $attribute */
                    $row['content'] = $td->nodeValue;
                    if ($a->hasAttributes()) {
                        foreach ($a->attributes as $attribute) {
                            $row[$attribute->name] = $attribute->value;
                        }

                    }

                }
                $dataTable[$i][] = $row;
            }
        }
    }
    $fullText = preg_replace("'<table[^>]*?>.*?</table>'si","", $fullText);
}
foreach ($tags as $tag) {
    array_push($image_path, $tag->getAttribute('src'));
};

$fullText=strip_tags($fullText);
$fullText = iconv('UTF-8', 'windows-1252', $fullText);
$section->addText(htmlspecialchars(html_entity_decode($fullText), ENT_QUOTES), array('name' => 'Tahoma', 'size' => 12,'color' => '#000000' ,'bold'=>false));
if($dataTable){
    $phpWord->addTableStyle('tStyle',  array(
        'borderSize' => 6,
        'borderColor' => '999999',
        'cellMarginTop' => 40,
        'cellMarginRight' => 20,
        'cellMarginBottom' => 40,
        'cellMarginLeft' => 20,
        'bgColor' => 'd2f7d2',
        'unit',
    ), array(
        'borderSize' => 12,
        'borderColor' => '000000',
        'cellMargin' => 80,
        'bgColor' => '6c9b6c',
        'size' => 12,
    ));
    $table = $section->addTable('tStyle');
    $headTitleNum = array('valign' => 'center','borderSize' => 5);
    foreach ($dataTable as $dataTableRow){
        $table->addRow();
        foreach ($dataTableRow as $dataTableCell){
            $cell = $table->addCell(300, $headTitleNum)->addTextRun()->addText(htmlspecialchars(html_entity_decode($dataTableCell['content']), ENT_QUOTES), array('name' => 'Tahoma', 'align' => 'center', 'size' => 10,'color' => '#000000' ,'bold'=>false));
        }
    }
}
foreach($image_path as $image){
    if(preg_match('/^(.*base64,)/m', $image)) {
        $image = base64_decode(preg_replace('/^(.*base64,)/m', '', $image));
    }
    $section->addImage($image, array(
        'width'         => 300,
        'height'        => 'auto',
        'marginTop'     => -1,
        'marginLeft'    => -1,
        'wrappingStyle' => 'behind'
    ));
};

$section->addText(htmlspecialchars(html_entity_decode($source_string), ENT_QUOTES), array('name' => 'Tahoma', 'size' => 10,'color' => '#000000' ,'bold'=>false));
$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
header("Content-Description: File Transfer");
header('Content-Disposition: attachment; filename="document'.time().'.docx"');
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Transfer-Encoding: binary');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Expires: 0');
$objWriter->save("php://output");
?>
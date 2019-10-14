<?php
//require_once("./system/session.php");
//require_once("./system/user.php");
require_once('../ajax/system/db.php');
require_once('../ajax/system/user.php');
require_once '../vendor/autoload.php';

if(isset($_POST['exportArticles'])) {
    $row = array();
    foreach ($_POST as $articleId){
        if(!empty($articleId)){
            $row[]=$articleId;
        }
    }
    
    $phpWord = new \PhpOffice\PhpWord\PhpWord();
    $phpWord->setDefaultFontName('Tahoma');
    $phpWord->setDefaultFontSize(14);
    $properties = $phpWord->getDocInfo();
    $properties->setCreator('');
    $properties->setCompany('');
    $properties->setTitle('');
    $properties->setCreated(date('d-m-Y'));
    $properties->setSubject('My export to word');

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
    $fontStyle = array( 'name' => 'Tahoma', 'size' => 20,'color' => '#1F5C1F' ,'bold'=>false);
    $phpWord->addTitleStyle(6,$fontStyle);
    $db = getDB();

    $path = '../vendor/done/'.time().'/';
    mkdir($path, 0777, true);

    foreach ($row as $articleId) {
        $image_path = array();
        $qry = "Select *, full_text, concat(u2.surname,' ',u2.lastname) as editor, concat(u1.surname,' ',u1.lastname) as modifier, source_id from article
        left join article_text on article.article_id=article_text.article_id
        left join \"user\" as u2 on article.editor=u2.user_id
        left join \"user\" as u1 on article.last_modified_by=u1.user_id
        LEFT JOIN article_source on article.article_id=article_source.article_id
        where article.article_id=:aid";
        $smt = $db->prepare($qry);
        $smt->bindParam(':aid', $articleId);
        $smt->execute();
        $row = $smt->fetch(PDO::FETCH_ASSOC);
        $smtSource = $db->prepare("select \"name\", to_char(cast(source_date as date), 'DD.MM.YYYY') as \"sdate\", \"date_type\" as dtt, date_alternative, url from article_source left join \"source\" on \"source\".source_id=article_source.source_id where article_id=:aid");
        $smtSource->bindParam(':aid', $articleId);
        $smtSource->execute();
        $first = true;
        while ($rowSource = $smtSource->fetch(PDO::FETCH_ASSOC)) {
            if ($first) {
                $source_string = 'Quelle: ';
                $first = false;
            } else {
                $source_string .= ', ';
            }
            $source_string .= $rowSource['name'] . ' (';
            if ($rowSource['dtt'] == 2) {
                $source_string .= $rowSource['date_alternative'];
            }
            if ($rowSource['dtt'] == 3) {
                $source_string .= $rowSource['date_alternative'];
            }
            if ($rowSource['dtt'] == 4) {
                $source_string .= 'Stand: ' . $rowSource['date_alternative'];
            }
            if ($rowSource['dtt'] == 5) {
                $source_string .= 'Ausgabe: ' . $rowSource['date_alternative'];
            } else {
                $source_string .= $rowSource['sdate'];
            }
            $source_string .= ') ';
        };

        $fullText = $row['full_text'];
        $section->addText(date('d.m.Y', strtotime($row['last_modified'])), array('name' => 'Tahoma', 'size' => 9, 'color' => '#000000', 'bold' => false));
        $section->addTitle(htmlspecialchars(html_entity_decode($row['headline']), ENT_QUOTES), 6);

        $doc = new DOMDocument();
        @$doc->loadHTML($fullText);
        $tags = $doc->getElementsByTagName('img');
        foreach ($tags as $tag) {
            array_push($image_path, $tag->getAttribute('src'));
        };

        $fullText = strip_tags($fullText);
        $fullText = iconv('UTF-8', 'windows-1252', $fullText);
        $section->addText(htmlspecialchars(html_entity_decode($fullText), ENT_QUOTES), array('name' => 'Tahoma', 'size' => 12, 'color' => '#000000', 'bold' => false));

        foreach ($image_path as $image) {
            if(preg_match('/^(.*base64,)/m', $image)) {
                $image = base64_decode(preg_replace('/^(.*base64,)/m', '', $image));
            }
            $section->addImage($image, array(
                'width' => 300,
                'height' => 'auto',
                'marginTop' => -1,
                'marginLeft' => -1,
                'wrappingStyle' => 'behind'
            ));
        };

        $section->addText(htmlspecialchars(html_entity_decode($source_string), ENT_QUOTES), array('name' => 'Tahoma', 'size' => 10, 'color' => '#000000', 'bold' => false));
        $section->addPageBreak();

        // $filename='document'.time().rand(100,999).'.docx';
        // $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        // $objWriter->save($path.$filename);
    }

    $filename='document'.time().rand(100,999).'.docx';
    $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');

    header("Content-Description: File Transfer");
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Transfer-Encoding: binary');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Expires: 0');
    $objWriter->save("php://output");

    // $zipPath = '../vendor/done/';
    // $zip = new ZipArchive();
    // $filename = $zipPath.time().'.zip';
    // if ($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE) {
    //     fwrite(STDERR, 'Error while creating archive file');

    // }
    // $dir = opendir($path);

    // while ( $file = readdir($dir) ) {
    //     if(strlen($file)>2){
    //         $zip->addFile($path.$file,$file);
    //     }
    // }
    // $zip->close();
    // $filesZip = scandir($zipPath);
    // foreach ($filesZip as $fileZip){
    //     if(strpos($fileZip, '.zip')!==false){
    //         $zipName = $zipPath.$fileZip;

    //         if(file_exists($zipName)){
    //             if (headers_sent()) {
    //                 echo 'HTTP header already sent';
    //             } else {
    //                 if (!is_file($zipName)) {
    //                     header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
    //                     echo 'File not found';
    //                 } else {
    //                     if (!is_readable($zipName)) {
    //                         header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
    //                         echo 'File not readable';
    //                     } else {
    //                         header($_SERVER['SERVER_PROTOCOL'] . ' 200 OK');
    //                         header("Content-Type: application/zip");
    //                         header("Content-Transfer-Encoding: Binary");
    //                         header("Content-Length: " . filesize($zipName));
    //                         header("Content-Disposition: attachment; filename=\"" . basename($zipName) . "\"");
    //                         readfile($zipName);
    //                         exit;
    //                     }
    //                 }
    //             }
    //         }
    //     }
    // }

}

?>
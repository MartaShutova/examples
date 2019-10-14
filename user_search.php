<?php
require_once('db.php');
require_once('bookmark.php');
require_once('misc.php');
require_once('permission.php');
class user_search{
var $search;
var $text;
var $word;
var $topic;
var $subtopic;
var $customer_group;
var $region;
var $start;
var $endd;
var $source;
var $autoren;
var $letzterbearbeiter;
var $not;
var $textv;
var $wordv;
var $topicv;
var $subtopicv;
var $customer_groupv;
var $regionv;
var $startv;
var $endv;
var $sourcev;
var $autorenv;
var $letzterbearbeiterv;
var $notv;
var $html;
var $html_top;
var $wordcount;
var $hits;
var $cat_html;
var $subcat_html;
var $explicit;
var $explicitv;
var $db;
var $backend_search;

function build_qry($n){
	//$user = new user();
	//Quick Search
	$qry = "select distinct article.article_id, headline, article.is_pdf, article.pdf_link, to_char(cast(article.article_date as date), 'DD.MM.YYYY') as article_date, 
article.article_date as artikeldatum, article.last_modified as alm, case when article.article_id in (select article_id from bookmark where user_id=:uid) then 1 else 0 end as bookmarked, 
case when c.ak is not null then c.ak else 0 end as ac, case when d.aw is not null then d.aw else 0 end as aw,
case when teaser is not null or teaser like '' then teaser else left(regexp_replace(regexp_replace(full_text, E'<.*?>', '', 'g' ), E'&nbsp;', ' ', 'g'),120)end as teaser,
last_modified, article.last_modified_by, article.editor, article.deadline_ready, article_status from article
left join article_text on article.article_id=article_text.article_id
left join article_cat on article.article_id=article_cat.article_id
left join (select article_id, count(*) as ak from article_comment group by article_id) as c on article.article_id=c.article_id
left join (select article_id, count(*) as aw from article_rating group by article_id) as d on article.article_id=d.article_id
left join (select a.article_id as aid, \"name\", url from (select distinct article_id, min(article_source_id) as amin from article_source group by article_id) as a left outer join (select * from article_source) as ac on a.amin=ac.article_source_id left outer join (select source_id, \"name\" from \"source\") as x on ac.source_id=x.source_id) as h
on h.aid=article.article_id
left join article_customer_group on article_customer_group.article_id=article.article_id
left join article_hashtag on article.article_id=article_hashtag.article_id
left join article_source on article.article_id=article_source.article_id";
	$qry = add_article_permission($qry);
	if ($this->text & strlen($this->textv)>0){
		//$user->write_to_log("text: ja");
		if($n==1){
			$qry = "Select distinct * from (".$qry.") as hb where article_id in (select article.article_id from article left join article_text on article.article_id=article_text.article_id where (lower(headline) like concat('%',lower(:txt),'%') or lower(full_text) like concat('%',lower(:txt),'%') or lower(headline) like concat('%',lower(:txtH),'%') or lower(full_text) like concat('%',lower(:txtH),'%')) order by article.article_id)";
		}elseif($n==2){
			//Suchbegriffe zählen und umformatieren funktion und für jeden einzeln suchen. Hier auch Funktionsaufruf build qry_text
			$qry = "Select distinct * from (".$qry.") as hb where article_id in (select article.article_id from article left join article_text on article.article_id=article_text.article_id where ((replace(replace(replace(replace(lower(headline),'.',''),'!',''),':',''),' ','') like concat('%',lower(:txt),'%')) or (replace(replace(replace(replace(lower(full_text),'.',''),'!',''),':',''),' ','') like concat('%',lower(:txt),'%')) or (replace(replace(replace(replace(lower(headline),'.',''),'!',''),':',''),' ','') like concat('%',lower(:txtH),'%')) or (replace(replace(replace(replace(lower(full_text),'.',''),'!',''),':',''),' ','') like concat('%',lower(:txtH),'%')))) order by article.article_id)";
		}elseif($n==3 & $this->word){
			$i=0;
			$first=true;
			$qry = "Select distinct * from (".$qry.") as hb where article_id in (select article.article_id from article left join article_text on article.article_id=article_text.article_id ";
			while($i<$this->wordcount){
				if($first){$first=false; $qry .="where (";}else{$qry .=" and ";}
				$txt=":txt".$i;
                $txtH=":txtH".$i;
				$qry .= "((lower(headline) like concat('%',lower(".$txt."),'%') or lower(full_text) like concat('%',lower(".$txt."),'%') or lower(full_text) like concat('%',lower(".$txtH."),'%') or lower(headline) like concat('%',lower(".$txtH."),'%')))";
				$i++;
			}
			$qry .=") order by article.article_id) ";
		}elseif($n==4 & $this->word){
			$i=0;
			$first=true;
			$qry = "Select distinct * from (".$qry.") as hb where article_id in (select article.article_id from article left join article_text on article.article_id=article_text.article_id ";
			while($i<$this->wordcount){
				if($first){$first=false; $qry .="where (";}else{$qry .=" and ";}
				$txt=":txt".$i;
                $txtH=":txtH".$i;
				$qry .= "((replace(replace(replace(replace(lower(headline),'.',''),'!',''),':',''),' ','') like concat('%',lower(".$txt."),'%')) or (replace(replace(replace(replace(lower(full_text),'.',''),'!',''),':',''),' ','') like concat('%',lower(".$txt."),'%')) or (replace(replace(replace(replace(lower(full_text),'.',''),'!',''),':',''),' ','') like concat('%',lower(".$txtH."),'%'))  or (replace(replace(replace(replace(lower(headline),'.',''),'!',''),':',''),' ','') like concat('%',lower(".$txtH."),'%')))";
				$i++;
			}
			$qry .=") order by article.article_id) ";
		}elseif($n==8){
			$qry = "Select distinct * from (".$qry.") as hb where article_id in (select article.article_id from article left join article_text on article.article_id=article_text.article_id where (";
//			$qry .= "lower(headline) like concat('% ',lower(:txt),' %') or lower(full_text) like concat('% ',lower(:txt),' %')";
//			$qry .= " or lower(headline) like concat('% ',lower(:txt),':%') or lower(full_text) like concat('% ',lower(:txt),':%')";
//			$qry .= " or lower(headline) like concat('% ',lower(:txt),',%') or lower(full_text) like concat('% ',lower(:txt),',%')";
//			$qry .= " or lower(headline) like concat('% ',lower(:txt),'.%') or lower(full_text) like concat('% ',lower(:txt),'.%')";
//			$qry .= " or lower(headline) like concat('%-',lower(:txt),' %') or lower(full_text) like concat('%-',lower(:txt),' %')";
//			$qry .= " or lower(headline) like concat('% ',lower(:txt),'-%') or lower(full_text) like concat('% ',lower(:txt),'-%')";
//			$qry .= " or lower(headline) like concat('% ',lower(:txt),';%') or lower(full_text) like concat('% ',lower(:txt),';%')";
//			$qry .= " or lower(headline) like concat('% ',lower(:txt),'!%') or lower(full_text) like concat('% ',lower(:txt),'!%')";
//			$qry .= " or lower(headline) like concat('% ',lower(:txt),'?%') or lower(full_text) like concat('% ',lower(:txt),'?%')";
//			$qry .= " or lower(headline) like concat('% ',lower(:txt),')%') or lower(full_text) like concat('% ',lower(:txt),')%')";
//			$qry .= " or lower(headline) like concat('%(',lower(:txt),' %') or lower(full_text) like concat('( ',lower(:txt),' %')";
//			$qry .= " or lower(headline) like concat('%(',lower(:txt),':%') or lower(full_text) like concat('% ',lower(:txt),':%')";
//			$qry .= " or lower(headline) like concat('%(',lower(:txt),',%') or lower(full_text) like concat('% ',lower(:txt),',%')";
//			$qry .= " or lower(headline) like concat('%(',lower(:txt),'.%') or lower(full_text) like concat('% ',lower(:txt),'.%')";
//			$qry .= " or lower(headline) like concat('%(',lower(:txt),' %') or lower(full_text) like concat('%-',lower(:txt),' %')";
//			$qry .= " or lower(headline) like concat('%(',lower(:txt),'-%') or lower(full_text) like concat('% ',lower(:txt),'-%')";
//			$qry .= " or lower(headline) like concat('%(',lower(:txt),';%') or lower(full_text) like concat('% ',lower(:txt),';%')";
//			$qry .= " or lower(headline) like concat('%(',lower(:txt),'!%') or lower(full_text) like concat('% ',lower(:txt),'!%')";
//			$qry .= " or lower(headline) like concat('%(',lower(:txt),'?%') or lower(full_text) like concat('% ',lower(:txt),'?%')";
//			$qry .= " or lower(headline) like concat('%(',lower(:txt),')%') or lower(full_text) like concat('% ',lower(:txt),')%')";
//			$qry .= " or lower(headline) like concat('% ',lower(:txt),' %') or lower(full_text) like concat('% ',lower(:txt),' %')";
//			$qry .= " or lower(headline) like concat(lower(:txt),' %') or lower(full_text) like concat(lower(:txt),' %')";
//			$qry .= " or lower(headline) like concat(lower(:txt),':%') or lower(full_text) like concat(lower(:txt),':%')";
//			$qry .= " or lower(headline) like concat(lower(:txt),',%') or lower(full_text) like concat(lower(:txt),',%')";
//			$qry .= " or lower(headline) like concat(lower(:txt),'.%') or lower(full_text) like concat(lower(:txt),'.%')";
//			$qry .= " or lower(headline) like concat(lower(:txt),'-%') or lower(full_text) like concat(lower(:txt),'-%')";
//			$qry .= " or lower(headline) like concat(lower(:txt),';%') or lower(full_text) like concat(lower(:txt),';%')";
//			$qry .= " or lower(headline) like concat(lower(:txt),'!%') or lower(full_text) like concat(lower(:txt),'!%')";
//			$qry .= " or lower(headline) like concat(lower(:txt),'?%') or lower(full_text) like concat(lower(:txt),'?%')";
//			$qry .= " or lower(headline) like concat('% ',lower(:txt)) or lower(full_text) like concat('% ',lower(:txt))";
//			$qry .= " or lower(headline) like concat('%(',lower(:txt),')%') or lower(full_text) like concat('%(',lower(:txt),')%')";
//			$qry .= " or lower(headline) like concat('%\"',lower(:txt),'\"%') or lower(full_text) like concat('%\"',lower(:txt),'\"%')";
//
//            $qry .= " or lower(headline) like concat('% ',lower(:txtH),' %') or lower(full_text) like concat('% ',lower(:txtH),' %')";
//            $qry .= " or lower(headline) like concat('% ',lower(:txtH),':%') or lower(full_text) like concat('% ',lower(:txtH),':%')";
//            $qry .= " or lower(headline) like concat('% ',lower(:txtH),',%') or lower(full_text) like concat('% ',lower(:txtH),',%')";
//            $qry .= " or lower(headline) like concat('% ',lower(:txtH),'.%') or lower(full_text) like concat('% ',lower(:txtH),'.%')";
//            $qry .= " or lower(headline) like concat('%-',lower(:txtH),' %') or lower(full_text) like concat('%-',lower(:txtH),' %')";
//            $qry .= " or lower(headline) like concat('% ',lower(:txtH),'-%') or lower(full_text) like concat('% ',lower(:txtH),'-%')";
//            $qry .= " or lower(headline) like concat('% ',lower(:txtH),';%') or lower(full_text) like concat('% ',lower(:txtH),';%')";
//            $qry .= " or lower(headline) like concat('% ',lower(:txtH),'!%') or lower(full_text) like concat('% ',lower(:txtH),'!%')";
//            $qry .= " or lower(headline) like concat('% ',lower(:txtH),'?%') or lower(full_text) like concat('% ',lower(:txtH),'?%')";
//            $qry .= " or lower(headline) like concat('% ',lower(:txtH),')%') or lower(full_text) like concat('% ',lower(:txtH),')%')";
//            $qry .= " or lower(headline) like concat('%(',lower(:txtH),' %') or lower(full_text) like concat('( ',lower(:txtH),' %')";
//            $qry .= " or lower(headline) like concat('%(',lower(:txtH),':%') or lower(full_text) like concat('% ',lower(:txtH),':%')";
//            $qry .= " or lower(headline) like concat('%(',lower(:txtH),',%') or lower(full_text) like concat('% ',lower(:txtH),',%')";
//            $qry .= " or lower(headline) like concat('%(',lower(:txtH),'.%') or lower(full_text) like concat('% ',lower(:txtH),'.%')";
//            $qry .= " or lower(headline) like concat('%(',lower(:txtH),' %') or lower(full_text) like concat('%-',lower(:txtH),' %')";
//            $qry .= " or lower(headline) like concat('%(',lower(:txtH),'-%') or lower(full_text) like concat('% ',lower(:txtH),'-%')";
//            $qry .= " or lower(headline) like concat('%(',lower(:txtH),';%') or lower(full_text) like concat('% ',lower(:txtH),';%')";
//            $qry .= " or lower(headline) like concat('%(',lower(:txtH),'!%') or lower(full_text) like concat('% ',lower(:txtH),'!%')";
//            $qry .= " or lower(headline) like concat('%(',lower(:txtH),'?%') or lower(full_text) like concat('% ',lower(:txtH),'?%')";
//            $qry .= " or lower(headline) like concat('%(',lower(:txtH),')%') or lower(full_text) like concat('% ',lower(:txtH),')%')";
//            $qry .= " or lower(headline) like concat('% ',lower(:txtH),' %') or lower(full_text) like concat('% ',lower(:txtH),' %')";
//            $qry .= " or lower(headline) like concat(lower(:txtH),' %') or lower(full_text) like concat(lower(:txtH),' %')";
//            $qry .= " or lower(headline) like concat(lower(:txtH),':%') or lower(full_text) like concat(lower(:txtH),':%')";
//            $qry .= " or lower(headline) like concat(lower(:txtH),',%') or lower(full_text) like concat(lower(:txtH),',%')";
//            $qry .= " or lower(headline) like concat(lower(:txtH),'.%') or lower(full_text) like concat(lower(:txtH),'.%')";
//            $qry .= " or lower(headline) like concat(lower(:txtH),'-%') or lower(full_text) like concat(lower(:txtH),'-%')";
//            $qry .= " or lower(headline) like concat(lower(:txtH),';%') or lower(full_text) like concat(lower(:txtH),';%')";
//            $qry .= " or lower(headline) like concat(lower(:txtH),'!%') or lower(full_text) like concat(lower(:txtH),'!%')";
//            $qry .= " or lower(headline) like concat(lower(:txtH),'?%') or lower(full_text) like concat(lower(:txtH),'?%')";
//            $qry .= " or lower(headline) like concat('% ',lower(:txtH)) or lower(full_text) like concat('% ',lower(:txtH))";
//            $qry .= " or lower(headline) like concat('%(',lower(:txtH),')%') or lower(full_text) like concat('%(',lower(:txtH),')%')";
//            $qry .= " or lower(headline) like concat('%\"',lower(:txtH),'\"%') or lower(full_text) like concat('%\"',lower(:txtH),'\"%')";


            $qry .= " lower(headline) like concat('%',lower(:txt),'%') or lower(full_text) like concat('%',lower(:txt),'%')";
            $qry .= " or lower(headline) like concat('%',lower(:txtH),'%') or lower(full_text) like concat('%',lower(:txtH),'%')";

			$qry .= ") order by article.article_id)";
		}
	}
	if ($this->topic & $this->topicv>1 & (!$this->subtopic | $this->subtopicv==1)){
		//$user->write_to_log("topic: ja");
		$qry = "Select * from (".$qry.") as hc where article_id in (select distinct article_id from cat right join article_cat on cat.cat_id=article_cat.cat_id where mother_cat=:catid order by article_id)";
	}
	if ($this->subtopic & $this->subtopicv>1){
		//$user->write_to_log("subtopic: ja");
		$qry = "Select * from (".$qry.") as hd where article_id in (select distinct article_id from cat right join article_cat on cat.cat_id=article_cat.cat_id where cat.cat_id=:catid order by article_id)";
	}
	if ($this->customer_group & $this->customer_groupv>1){
		//$user->write_to_log("cg: ja");
		$qry = "Select * from (".$qry.") as he where article_id in (select distinct article_id from customer_group right join article_customer_group on customer_group.customer_group_id=article_customer_group.customer_group_id where customer_group.customer_group_id=:cgid order by article_id)";
	}
	if ($this->region & $this->regionv>1){
		//$user->write_to_log("region: ja");
		$qry = "Select * from (".$qry.") as hf where article_id in (select distinct article_id from region right join article_region on region.region_id=article_region.region_id where region.region_id=:rgid order by article_id)";
	}
	if ($this->start|$this->endd){
		//$user->write_to_log("datum: ja");
		$qry = "Select * from (".$qry.") as hg where article_id in (select article_id from article where article_date between cast(:st as date) and cast(:ed as date) order by article_id)";
	}
	if ($this->source & $this->sourcev>1){
		//$user->write_to_log("region: ja");
		$qry = "Select * from (".$qry.") as hh where article_id in (select distinct article_id from \"source\" right join article_source on \"source\".source_id=article_source.source_id where article_source.source_id=:sid order by article_id)";
	}
    if ($this->autoren & $this->autorenv>1){
        //$user->write_to_log("region: ja");
        $qry = "Select * from (".$qry.") as hi where article_id in (select distinct article_id from article where article.editor=:aid order by article_id)";
    }
    if ($this->letzterbearbeiter & $this->letzterbearbeiterv>1){
        //$user->write_to_log("region: ja");
        $qry = "Select * from (".$qry.") as hj where article_id in (select distinct article_id from article where article.last_modified_by=:lid order by article_id)";
    }
	if ($this->not){
		//$user->write_to_log("text: ja");
		$qry = "Select * from (".$qry.") as hb where article_id in (select article.article_id from article left join article_text on article.article_id=article_text.article_id where (lower(headline) not like concat('%',lower(:ntxt),'%') and lower(full_text) not like concat('%',lower(:ntxt),'%')) order by article.article_id)";
	}
	/*Hier die Restlichen Params
	if ($this->start|$this->endd){
		$qry = $qry." and article.last_modified between :st and :ed";
	}*/
	//nur freigegebene Artikel anzeigen
	
	$qry = "Select * from (".$qry.") as hx where article_id in (select article_id from article where article_status=7)";
	$qry .= " order by artikeldatum desc;";
	$this->search_stat();
	return $qry;
}
function check_words():void{
	if(stripos($this->textv, ' ')>0){
		$this->wordv=explode(" ",$this->textv);
		$this->word=true;
		$this->wordcount=count($this->wordv);
	}
} 
function initdb(){
	$this->db=getDB();
}
function replace_text($text){
	$text=str_replace('.','',$text);
	$text=str_replace('!','',$text);
	$text=str_replace('"','',$text);
	$text=str_replace(':','',$text);
	return $text;
}
function search_stat():void{
	/*Und hier könnte man später die Statistik einfügen*/
}
function search_it($n){
	$user=new user();
	$this->textv=trim($this->textv);
	//$user->write_to_log($this->textv);
	$qry = $this->build_qry($n);
	$textHTML = htmlentities($this->textv, ENT_QUOTES);
	//$current=print_r($qry, true);
	//$user->write_to_log($current);
	try{
	$this->db = getDB();
	$smt=$this->db->prepare($qry);
	$smt->bindParam('uid',$_SESSION['user_id']);
	$smt->bindParam('cid',$_SESSION['company_id']);
	if ($this->text){
		if($n==1 | $n==8){
			$smt->bindParam('txt',$this->textv, PDO::PARAM_STR);
            $smt->bindParam('txtH',$textHTML, PDO::PARAM_STR);
		}
		if($n==2){
			$textv=$this->replace_text($this->textv);
			$smt->bindParam('txt',$textv, PDO::PARAM_STR);
            $smt->bindParam('txtH',$textHTML, PDO::PARAM_STR);
		}if($n==3){
			for($i=0;$i<$this->wordcount;$i++){
				$param='txt'.$i;
                $paramH='txtH'.$i;
				$smt->bindParam($param,$this->wordv[$i], PDO::PARAM_STR);
                $smt->bindParam($paramH,htmlentities($this->wordv[$i], ENT_QUOTES), PDO::PARAM_STR);
				//$user->write_to_log($param.": ".$wd);
			}
		}if($n==4){
			for($i=0;$i<$this->wordcount;$i++){
				$param='txt'.$i;
                $paramH='txtH'.$i;
				$word=$this->replace_text($this->wordv[$i]);
				$smt->bindParam($param,$this->wordv[$i], PDO::PARAM_STR);
                $smt->bindParam($paramH,htmlentities($this->wordv[$i], ENT_QUOTES), PDO::PARAM_STR);
				//$user->write_to_log($param.": ".$word);
			}
		}
	}
	if ($this->topic & $this->topicv>1 & (!$this->subtopic | $this->subtopicv==1)){
		$smt->bindParam('catid',$this->topicv);
	}
	if ($this->subtopic & $this->subtopicv>1){
		$smt->bindParam('catid',$this->subtopicv);
	}
	if ($this->customer_group & $this->customer_groupv>1){
		$smt->bindParam('cgid',$this->customer_groupv);
	}
	if ($this->region & $this->regionv>1){
		$smt->bindParam('rgid',$this->regionv);
	}
	if ($this->source & $this->sourcev>1){
		$smt->bindParam('sid',$this->sourcev);
	}
	if ($this->start|$this->endd){
		if($this->start & $this->endd){
			$start=$this->format_date_sql($this->startv);
			$end=$this->format_date_sql($this->endv);
			$smt->bindParam('st',$start, PDO::PARAM_STR);
			$smt->bindParam('ed',$end, PDO::PARAM_STR);
		}
		if(!$this->start & $this->endd){
			$start='1900-01-01 00:00:00';
			$end=$this->format_date_sql($this->endv);
			$smt->bindParam('st',$start, PDO::PARAM_STR);
			$smt->bindParam('ed',$end, PDO::PARAM_STR);
		}
		else{
			$end='2099-12-31 23:59:59';
			$start=$this->format_date_sql($this->startv);
			$smt->bindParam('st',$start, PDO::PARAM_STR);
			$smt->bindParam('ed',$end, PDO::PARAM_STR);
		}
	}
	if ($this->not){
		$smt->bindParam('ntxt',$this->notv, PDO::PARAM_STR);
	}

	$smt->execute();
	$html = '';
	/*
	while ($row = $smt->fetch(PDO::FETCH_LAZY)) {
		$current=print_r($this->search, true);
		$user->write_to_log($current);
		if(!array_key_exists($row['article_id'],$this->search)){
			$this->search[$row['article_id']] = array(
                'headline' => $row['headline'],
				'teaser' => $row['teaser'],
				'ac' => $row['ac'],
				'aw' => $row['aw'],
				'bookmarked' => $row['bookmarked'],
				'article_date' => $row['article_date']
				);
				
			$this->hitcount++;
		}
	}
	*/
	while ($row = $smt->fetch(PDO::FETCH_OBJ)) {
		$current=print_r($this->search, true);
		if(!array_key_exists($row->article_id,$this->search)){
			$this->search[$row->article_id] = array(
                'headline' => $row->headline,
				'teaser' => $row->teaser,
				'ac' => $row->ac,
				'aw' => $row->aw,
				'bookmarked' => $row->bookmarked,
				'article_date' => $row->article_date,
				'is_pdf' => $row->is_pdf,
				'pdf_link' => $row->pdf_link
				);
			$this->hitcount++;
		}
	}
	//$current=print_r($this->search, true);
	//$user->write_to_log($current);
}catch(PDOException $e) {
$user->write_to_log($e->getMessage());
}
}
function build_html(){
	$bookmark = new bookmark();
	$bookmark->set_db();
	$html = '';
	$i=0;
	$blub = $this->hitcount-$this->explicitv;
	if(!$this->explicit & !empty($this->search)){
		$html .= '<li class="list-group-item">Ihr Suchbegriff wurde&nbsp;<b>'.$this->explicitv.'</b>&nbsp;mal exakt gefunden. Unten folgen&nbsp;<b>'.$blub.'</b>&nbsp;ähnliche Ergebnisse, die den Suchbegriff enthalten.</li>';
	}
	foreach ($this->search as $key => $item) {
		if($this->explicit & $i==$this->explicitv){
			
			$html .= '<li class="list-group-item">Ihr Suchbegriff wurde&nbsp;<b>'.$this->explicitv.'</b>&nbsp;mal exakt gefunden. Unten folgen&nbsp;<b>'.$blub.'</b>&nbsp;ähnliche Ergebnisse, die den Suchbegriff enthalten.</li>';
		}
		$html .= '<li class="list-group-item"><div class="article-infos"><div class="article-date">'.$item['article_date'].'</div>';
		$html .= '<div class="article-source">'.$bookmark->get_src_html($key, 9999).'</div>';
		//Bookmark
		if ($item['bookmarked']==1){
			$html .= '<a class="unmark" name="book_'.$key.'"><span class="glyphicon glyphicon-star" aria-hidden="true" title="Von Merkliste entfernen"></span></a>';
		}else{
			$html .= '<a class="bookmark" name="book_'.$key.'"><span class="glyphicon glyphicon-star-empty" aria-hidden="true" title="Zur Merkliste hinzufügen"></span></a>';
		}
		$html .= '</div><div class="article-text"><div class="article-user-feedback pull-right">';
		//Kommentare und wichtig
		$kommentare=get_comments($key);
				$wichtig=get_rates($key);
				//Ermittlung abgeschlossen
				if ($kommentare==1){
					$comment_count = '1 Kommentar';
				}else{
					$comment_count = $kommentare.' Kommentare';
				}	
				
		if ($kommentare==1){
								$html .= '<div class="user-feedback comments" title="1 Kommentar zu diesem Artikel"><span class="glyphicon glyphicon-comment" aria-hidden="true"></span> 1</div>';
								}elseif ($kommentare>1){
								$html .= '<div class="user-feedback comments" title="'.$kommentare.' Kommentare zu diesem Artikel"><span class="glyphicon glyphicon-comment" aria-hidden="true"></span> '.$kommentare.'</div>';
								}	
								if ($wichtig>0){
								$html .= '&nbsp;<div class="user-feedback important" title="'.$wichtig.' Mal als wichtig markiert"><span class="glyphicon glyphicon-flag" aria-hidden="true"></span> '.$wichtig.'</div>';
								}
		$html .= '</div>';
		//if($item['is_pdf']==1){$html .='<a href="'.$item['pdf_link'];}else{
			$html .='<a href="artikel.php?article='.$key;
			//}
		
		$html .= '" class="headline" target="_blank">'.$item['headline'].'</a><div class="teaser">'.htmlspecialchars_decode($item['teaser']).' </div></div></li>';
		$i++;
	}
	$this->html .= $html;
}
function search():void{
	$user=new user();
	try{
		$this->search=null;
		$this->search=[];
		$this->hitcount=0;
		//$user->write_to_log("1. Durchlauf");
		$this->search_it(8);
		if(!empty($this->search)){$this->explicit=true; $this->explicitv=count(array_keys($this->search));}else{$this->explicit=false; $this->explicitv=0;}
		$this->search_it(1);
		//Hier 2. Durchlauf
		if ($this->text){
				//$user->write_to_log("2. Durchlauf");
			$this->search_it(2);
				//$current=print_r($this->search, true);
				//$user->write_to_log("Recordset: ".$current);
			//Hier 3. Durchlauf
			$this->word=false;
			$this->check_words();
				$current=print_r($this->wordv, true);
				$user->write_to_log("Words: ".$current);
			if($this->word){
				//$user->write_to_log("3. Durchlauf");
				$this->search_it(3);
				//$current=print_r($this->search, true);
				//$user->write_to_log("Recordset: ".$current);
				//$user->write_to_log("4. Durchlauf");
				$this->search_it(4);
				//$current=print_r($this->search, true);
				//$user->write_to_log("Recordset: ".$current);
			}
		}
		$this->html = '';
		$this->build_html();
		$this->hits="Ihre Suche ergab <b>".$this->hitcount."</b> Treffer";
		//$user->write_to_log("Suche abgeschlossen");
	}catch(PDOException $e) {
		$user->write_to_log($e->getMessage());
}
}
function format_date_sql($date){
	$d=substr($date,0,2);
	$m=substr($date,3,2);
	$y=substr($date,6,4);
	return $y.'-'.$m.'-'.$d;
}
function get_topic_search(){
	$smt = $this->db->prepare("select cat_id, cat_name from cat where cat_id in (select distinct mother_cat from booked_module_neu right outer join cat_module_neu on booked_module_neu.module_id=cat_module_neu.module_id left join cat on cat_module_neu.cat_id=cat.cat_id where company_id=:cid order by mother_cat) order by cat_name;");
	$smt->bindParam(':cid',$_SESSION['company_id']);
	$smt->execute();
	$html = '<option value="1"';
	if($this->topic==1){$html .= 'selected';}
	$html .= '>Alle Themen</option>';
	while ($row = $smt->fetch(PDO::FETCH_OBJ)) {
		//hier noch selected
		$html .= '<option value="'.$row->cat_id.'"';
		if($this->topicv==$row->cat_id){$html .= 'selected';}
		$html .= '>'.$row->cat_name.'</option>';
					}
	return $html;
}
function get_subtopic_search(){
	if($this->topic){
		$smt = $this->db->prepare("select cat.cat_id, cat_name from booked_module_neu right outer join cat_module_neu on booked_module_neu.module_id=cat_module_neu.module_id left join cat on cat_module_neu.cat_id=cat.cat_id where company_id=:cid and mother_cat=:mcid order by cat_name;");
		$smt->bindParam(':cid',$_SESSION['company_id']);
		$smt->bindParam(':mcid',$this->topicv);
		$smt->execute();
		$html = '<option value="1"';
		if($this->subtopicv==1){$html .= 'selected';}
		$html .= '>Alle Unterthemen</option>';
		while ($row = $smt->fetch(PDO::FETCH_OBJ)) {
			$html .= '<option value="'.$row->cat_id.'"';
			if($this->subtopicv==$row->cat_id){$html .= 'selected';}
			$html .= '>'.$row->cat_name.'</option>';
		}
	}else{
		$html = '<option value="1"selected>Alle Unterthemen</option>';
	}
	return $html;
}
function get_cgroup_search(){
	$smt = $this->db->prepare("select customer_group_id, \"name\" from customer_group");
	$smt->execute();
	$html = "";
	while ($row = $smt->fetch(PDO::FETCH_OBJ)) {
					$html .= '<option value="'.$row->customer_group_id.'"';
					if($this->customer_groupv==$row->customer_group_id){$html .= 'selected';}
					$html .= '>'.$row->name.'</option>';
					}
	return $html;
}
function get_rg_search(){
	$smt = $this->db->prepare("select region_id, \"name\" from region");
	$smt->execute();
	$html = "";
	while ($row = $smt->fetch(PDO::FETCH_OBJ)) {
					$html .= '<option value="'.$row->region_id.'"';
					if($this->regionv==$row->region_id){$html .= 'selected';}
					$html .= '>'.$row->name.'</option>';
					}
	return $html;
}
function get_src_search(){
	$smt = $this->db->prepare("select source_id, \"name\" from \"source\" where source_id > 1 order by \"name\"");
	$smt->execute();
	$html = '<option value="1">Alle Quellen</option>';
	while ($row = $smt->fetch(PDO::FETCH_OBJ)) {
					$html .= '<option value="'.$row->source_id.'"';
					if($this->sourcev==$row->source_id){$html .= 'selected';}
					$html .= '>'.$row->name.'</option>';
					}
	$db = null;
	return $html;
}
function get_start_search(){
	if($this->start){
		return $this->startv;
	}else{
		return '';
	}
}
function get_end_search(){
	if($this->endd){
		return $this->endv;
	}else{
		return '';
	}
}
function get_not_search(){
	if($this->not){
		return 'value="'.$this->notv.'"';
	}else{
		return 'placeholder="Ausschlußkriterien"';
	}
}

}
?>

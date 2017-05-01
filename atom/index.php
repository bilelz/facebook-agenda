<?php header("Content-Type: text/xml; charset=UTF-8");
echo '<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">';

	$url = "http" . (($_SERVER['SERVER_PORT'] == 443) ? "s://" : "://") . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	$urlBase = "http" . (($_SERVER['SERVER_PORT'] == 443) ? "s://" : "://") . $_SERVER['HTTP_HOST'].'/fbcal/';

	if(isset($_GET["gamp"])){
		$css = file_get_contents('style.css');
		$css =  preg_replace('/\n/i', ' ', $css);
		$css =  preg_replace('/    /i', '', $css);
	} 


/*debut du cache*/
$cache = '../cache/atom.html';

$expire = time() -0*30 ; // now
 
// fresh is the magic word
if(file_exists($cache) && filemtime($cache) > $expire && !isset($_GET["fresh"]))
{
        readfile($cache);
}
else
{
        ob_start(); // ouverture du tampon

	// get FB pages list
	
	$json = file_get_contents('../manifest.json');
	$package = json_decode($json, true);

	$siteName = $package['name'];
	$siteDescription= $package['name'];
	$siteThemeColor = $package['theme_color'];
	$siteTwitter = $package['twitter'];
	$siteFB = $package['facebook'];

	$token = "188510571271418|6935eae5d8fb74dceb9a5d497204f14e";

	$urlFB = "https://graph.facebook.com/v2.8/".$siteFB."/likes?access_token=".$token;
	$json = file_get_contents($urlFB);
	$fbList = json_decode($json, true);
?>




   
<?php
	$date = new DateTime();
	$date->setTime(0, 0);
	$todayTimestamp=  $date->getTimestamp();
	

	$allEvents = array();


	$fbPageIds = $package['facebookID'];

	//listing of each FBPage
	foreach ($fbList["data"] as $page) {
		$fbPageIds = $fbPageIds.",".$page["id"];
	}



    // date range
    $today =  date('Y-m-d');
	
	//$fakedate, example =  2014-12-31
	if (isset($_GET['fakedate'])) {
		$today = $_GET['fakedate'];
	}
	
	
	if (isset($_GET['fakedate'])) {
		$fakedate = $_GET['fakedate'];
		//$faketime = mktime(0, 0, 0, substr($fakedate, 5, 7), substr($fakedate, 8, 2), substr($fakedate, 0, 4)); //$fakedate, example =  2014-12-31
		$faketime = strtotime($fakedate);
	} else {
		$faketime = time();
	}
	
    $lang = ($package["lang"]=="en-us")?"":$package["lang"]; // default (english): "", french = _fr-fr
	if($lang !=""){
		$lang = "_".$lang;
	}

	$dayNamesTab = array('Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi');
	$dayMinusNamesTab = array('Di', 'Lu', 'Ma', 'Me', 'Je', 'Ve', 'Sa');
	$monthNamesTab = array('Janvier', 'Fevrier', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Aout', 'Septembre', 'Octobre', 'Novembre', 'Decembre');	
	
	$message=array(	"this"=>"This",			"this_fr-fr"=>"Ce",
					"last"=>"Last",			"last_fr-fr"=>"Dernier",
					"today"=>"Today",		"today_fr-fr"=>"Aujourd'hui",	
					"tomorrow"=>"Tomorrow",	"tomorrow_fr-fr"=>"Demain",
					"week"=>"week",			"week_fr-fr"=>"semaine",
					"weekof"=>"week of",	"weekof_fr-fr"=>"semaine du",
					"andthisweekend"=>"and this week-end",	"andthisweekend_fr-fr"=>"et ce week-end",
					"event"=>"event",		"event_fr-fr"=>"evenement",
					"events"=>"events",		"events_fr-fr"=>"evenements",
					"forthe"=>"for the",	"forthe_fr-fr"=>"pour le",
					"forthis"=>"for this",	"forthis_fr-fr"=>"pour ce",
					"forthef"=>"for the",	"forthef_fr-fr"=>"pour la",
					"friday"=>"friday",		"friday_fr-fr"=>"vendredi");
					
	
	$messageDay=array(	"1"=>"monday",			"1_fr-fr"=>"lundi",
						"2"=>"tuesday",			"2_fr-fr"=>"mardi",
						"3"=>"wednesday",		"3_fr-fr"=>"mercredi",
						"4"=>"thursday",		"4_fr-fr"=>"jeudi",
						"5"=>"friday",			"5_fr-fr"=>"vendredi",
						"6"=>"saturday",		"6_fr-fr"=>"samedi",
						"7"=>"sunday",			"7_fr-fr"=>"dimanche");
	
	$messageMonth=array("1"=>"january",			"1_fr-fr"=>"janvier",
						"2"=>"febrary",			"2_fr-fr"=>"fevrier",
						"3"=>"mars",			"3_fr-fr"=>"mars",
						"4"=>"april",			"4_fr-fr"=>"avril",
						"5"=>"may",				"5_fr-fr"=>"mai",
						"6"=>"june",			"6_fr-fr"=>"juin",
						"7"=>"july",			"7_fr-fr"=>"juillet",
						"8"=>"august",			"8_fr-fr"=>"aout",
						"9"=>"september",		"9_fr-fr"=>"septembre",
						"10"=>"october",		"10_fr-fr"=>"octobre",
						"11"=>"november",		"11_fr-fr"=>"novembre",
						"12"=>"december",		"12_fr-fr"=>"decembre");
	
	$three_months_in_seconds = 60 * 60 * 24 * 28 * 3;
	$three_months_ago = date("Y-m-d", time() - $three_months_in_seconds);
	$three_months_from_today = date("Y-m-d", time() + $three_months_in_seconds);
	
	$only_today_in_seconds = 60 * 60 * 24 * 0;
	$only_today = date("Y-m-d", $faketime + $only_today_in_seconds);
	
	$one_day_in_seconds = 60 * 60 * 24 * 1;
	$one_day_from_today = date("Y-m-d", $faketime + $one_day_in_seconds);
	$tomorrowTime = strtotime($one_day_from_today);
	
	$three_day_in_seconds = 60 * 60 * 24 * 3;
	$three_day_from_today = date("Y-m-d", $faketime + $three_day_in_seconds);
	
	$seven_day_in_seconds = 60 * 60 * 24 * 7;
	$seven_day_from_today = date("Y-m-d", $faketime + $seven_day_in_seconds);
	
	$titleDay = $messageDay[date('N', $faketime).$lang];
	$titleMonth = $messageMonth[date('n', $faketime).$lang];
	$titleDayNumber = date("j", $faketime);
	$titleYear = date("Y", $faketime);
	$titleLabelDay = $titleDay." ".$titleDayNumber." ".$titleMonth." ".$titleYear;
	
	$todayFormattedRSS = gmdate('Y-m-d\TH:i:s\Z', $faketime);	
	
	$lundi = 1;
	$vendredi = 5;
	
	//echo "jour = ".date("w");
	if (date("w", $faketime) == $lundi) {// si on est lundi, on affiche les conferences de la semaine
		$untilDate = $seven_day_from_today;
		$labelTitle = $message['forthis'.$lang]." ".$message['weekof'.$lang]." ".$titleLabelDay;		
	} else if (date("w", $faketime) == $vendredi) {// si on est vendredi, on affiche les conferences du week-end
		$untilDate = $three_day_from_today;
		$labelTitle = $message['forthis'.$lang]." ".$titleLabelDay." ".$message['andthisweekend'.$lang];
	} else {// sinon on affiche juste les conferences du jour en cours
		$untilDate =  $one_day_from_today;
		$labelTitle = $message['forthis'.$lang]." ".$titleLabelDay;
	}

	$url= "https://graph.facebook.com/v2.8/events?ids=".$fbPageIds."&access_token=" . $token . "&fields=interested_count,cover,description,id,category,name,start_time,place&since=".$todayTimestamp.'&until='.$untilDate;

	$json = file_get_contents($url);
	$pageEventsResponse= json_decode($json, true);

	// own page event
	$pageEvents = $pageEventsResponse[$package["facebookID"]];
	if(count($pageEvents["data"])>0){
		$allEvents = array_merge($allEvents, $pageEvents["data"]);			
	}	

	// reading response for each page
	foreach ($fbList["data"] as $page) {
		$pageEvents = $pageEventsResponse[$page["id"]];

		if(count($pageEvents["data"])>0){
			$allEvents = array_merge($allEvents, $pageEvents["data"]);			
		}		
	}

	function sortByYear($a, $b) {
		$dA = strtotime (($a['start_time']));
		$dB = strtotime (($b['start_time']));

		return $dA - $dB;
	}

	function text2AlphaNum($string)
	{
		$result =  preg_replace('/ /i', '_', $string);
		$result =  preg_replace('/[^a-zA-Z_\-0-9]+/i', '', $result);
	    return $result;
	}

	usort($allEvents, 'sortByYear');
	$allEvents = array_unique($allEvents, SORT_REGULAR);


	setlocale(LC_TIME, 'fr', 'fr_FR', 'fr_FR.UTF-8');

    ?>

	<title><?php echo sizeof($allEvents )." ".$message['events'.$lang]." ".$labelTitle; ?></title>
    <link href="<?php echo $package['homepage']; ?>" rel="alternate" type="text/html"/>
    <link href="<?php echo $package['homepage']; ?>/atom" rel="self" type="application/atom+xml"/>
    
    <id><?php echo $package['homepage']; ?></id>
    <updated><?php echo $todayFormattedRSS; ?></updated>
    <category term="LifeStyle"/>
    <subtitle><?php echo $package['name'];  ?></subtitle>
    <author>
        <name><?php echo $package['name']; ?></name>
        <uri><?php echo $package['homepage']; ?></uri>
    </author>

    <?php

//$url = "http" . (($_SERVER['SERVER_PORT'] == 443) ? "s://" : "://") . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$homepage = $package["homepage"];


foreach ($allEvents as $obj) {
	$start_time = $obj['start_time'];
    $end_time = (isset($obj['end_time']))?$obj['end_time']:$obj['start_time'];
	$timestamp = strtotime ($obj['start_time']);
	$monthLabel = utf8_encode(strftime('%b', $timestamp));
	$dayNumber = date('j', $timestamp);
	$dayLabel = utf8_encode(strftime('%a', $timestamp));

    $eventStartTimeRSSFormatted = gmdate('Y-m-d\TH:i:s\Z', strtotime($start_time));
	$eventUpdateTimeRSSFormatted = gmdate('Y-m-d\TH:i:s\Z', strtotime($start_time));

	
	$dayList = utf8_encode(strftime('%A', $timestamp)).' '.$dayNumber.' '.utf8_encode(strftime('%B', $timestamp));

	$placeName = (isset($obj['place']['name']))?($obj['place']['name']):'';
    $placeQuery = "";
    if(isset($obj['place']) && isset($obj['place']['location'])) {
		$placeQuery =  $obj['place']['location']['latitude'].'+'.$obj['place']['location']['longitude'];
		$placeQueryImg =  $obj['place']['location']['latitude'].','.$obj['place']['location']['longitude'];
	}else if(isset($obj['place']) && isset($obj['place']['name'])){
		$placeQuery =   urlencode ($placeName);
		$placeQueryImg=   urlencode ($placeName);	
	}

	$image = $obj['cover']['source'];
    $title = htmlspecialchars($obj['name'], ENT_QUOTES, 'UTF-8');
	$description = (isset($obj['description']))?$obj['description']:$title;

	$reg_exUrl = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";
	$descriptionHTML = $description;
	if(preg_match($reg_exUrl, $descriptionHTML, $url)) {
		// make the urls hyper links
		$descriptionHTML =  preg_replace($reg_exUrl, '<a href="'.$url[0].'" target="_blank" rel="noopener">'.$url[0].'</a>', $descriptionHTML);
	} 
	$descriptionHTML = nl2br($descriptionHTML, true);
    
	$urlItem = $homepage.'/e/'.text2AlphaNum($title).'/'.$obj['id'];
	
?>

<entry> 
		<title>[<?php echo $dayList.' '. date('G:i',$timestamp); ?>] <?php echo $title;?></title>		
		<link href="<?php echo $urlItem; ?>" rel='alternate' type='text/html'/> 
		<id><?php echo $urlItem; ?></id> 
		<published><?php echo $eventStartTimeRSSFormatted; ?></published>
		<updated><?php echo $eventUpdateTimeRSSFormatted; ?></updated>
		<content type="html">
			&lt;a href=&quot;<?php echo $urlItem; ?>&quot;&gt;
			&lt;img src=&quot;<?php echo preg_replace('/&/i', '&amp;', $image);?>&quot; alt=&quot;<?php echo $title;?>&quot;/&gt;
			&lt;/a&gt;
			&lt;br/&gt; &lt;br/&gt;

			<?php echo htmlspecialchars($descriptionHTML, ENT_QUOTES, 'UTF-8');?> 
			&lt;br/&gt; &lt;br/&gt;

			&lt;br/&gt; &lt;br/&gt;
			&lt;a href=&quot;http://maps.google.com/maps?q=<?php echo $placeQuery;?>&quot;&gt;
				📍&amp;nbsp;<?php echo htmlspecialchars($placeName, ENT_QUOTES, 'UTF-8');?>
			&lt;/a&gt;&lt;br/&gt;

			&lt;a href=&quot;<?php echo $urlItem; ?>&quot;&gt;
			<?php echo $urlItem; ?>
			&lt;/a&gt;
		</content> 
		
		<link rel="enclosure"
          type="image/jpeg"
          title="<?php echo $title;?>"
          href="<?php echo preg_replace('/&/i', '&amp;', $image);?>"
          length="1234" />	
		
	</entry>

<?php } ?>

</feed><?php
	$page = ob_get_contents(); // copie du contenu du tampon dans une chaîne
	ob_end_clean(); // effacement du contenu du tampon et arrêt de son fonctionnement
	//minify this!
	//$page = preg_replace(array('/<!--(.*)-->/Uis',"/[[:blank:]]+/"),array('',' '),str_replace(array("\n","\r","\t"),'',$page));
	
	file_put_contents($cache, $page) ; // on écrit la chaîne précédemment récupérée ($page) dans un fichier ($cache) 
	echo $page ; // on affiche notre page :D 
}
?>
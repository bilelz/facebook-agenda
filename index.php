<?php
	header("Content-Type: text/html; charset=UTF-8");

	$generatetime = microtime();
	$generatetime = explode(' ', $generatetime);
	$generatetime = $generatetime[1] + $generatetime[0];
	$generatestart = $generatetime;

	$url = "http" . (($_SERVER['SERVER_PORT'] == 443) ? "s://" : "://") . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	$urlBase = "http" . (($_SERVER['SERVER_PORT'] == 443) ? "s://" : "://") . $_SERVER['HTTP_HOST'].'/fbcal/';

	if(isset($_GET["gamp"])){
		$css = file_get_contents('style.css');
		$css =  preg_replace('/\n/i', ' ', $css);
		$css =  preg_replace('/    /i', '', $css);
	} 


/*debut du cache*/
$cache = 'cache/index.html';
if(isset($_GET["gamp"])){
	$cache = 'cache/indexamp.html';
}

$expire = time() -60*30 ; // 30min
 
// fresh is the magic word
if(file_exists($cache) && filemtime($cache) > $expire && !isset($_GET["fresh"]))
{
	readfile($cache);
	$generatetime = microtime();
	$generatetime = explode(' ', $generatetime);
	$generatetime = $generatetime[1] + $generatetime[0];
	$generatefinish = $generatetime;
	$generatetotal_time = round(($generatefinish - $generatestart), 4);
	echo '<small>(⚡ '.$generatetotal_time.' seconds ⚡)</small>';
	echo'</body></html>';
}
else
{
        ob_start(); // ouverture du tampon

	function text2AlphaNum($string)
	{
		$result =  preg_replace('/ /i', '_', $string);
		$result =  preg_replace('/[^a-zA-Z_\-0-9]+/i', '', $result);
	    return $result;
	}

	// get FB pages list
	
	$json = file_get_contents('manifest.json');
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

<!DOCTYPE html>
<?php 
	if(isset($_GET["gamp"]))
		echo '<html AMP lang="en">';
	else
		echo '<html  lang="en">';
?>
    <head>
        <title><?php echo $siteName;?></title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">
        
		<?php if(!isset($_GET["gamp"])){ ?>    
			<link href="style.<?php echo str_replace('.','',$package['version']);?>.css" rel="stylesheet">   	
			<link rel="amphtml" href="./?amp=amp">
			<meta name="theme-color" content="<?php echo $siteThemeColor;?>">
			<link rel="apple-touch-icon" href="img/ic_launcher_192.png">
			<link rel="manifest" href="manifest.json">
		<?php }else{ ?>   
			<script async src="https://cdn.ampproject.org/v0.js"></script>
        	<link rel="canonical" href="./" />
			<style amp-boilerplate>body{-webkit-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-moz-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-ms-animation:-amp-start 8s steps(1,end) 0s 1 normal both;animation:-amp-start 8s steps(1,end) 0s 1 normal both}@-webkit-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-moz-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-ms-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-o-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}</style><noscript><style amp-boilerplate>body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}</style></noscript>
			
			<style amp-custom>
			<?php echo $css; ?>
			amp-img{ max-width: 100%;}
			</style>

		<?php }
		$url = "http" . (($_SERVER['SERVER_PORT'] == 443) ? "s://" : "://") . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		?>

		<meta name="twitter:card" content="summary_large_image">
		<meta name="twitter:site" content="<?php echo $url; ?>">
		<meta name="twitter:creator" content="@<?php echo $siteTwitter;?>">
		<meta name="twitter:title" content="<?php echo $siteName;?>">
		<meta name="twitter:description" content="<?php echo $siteDescription;?>">
		<meta name="twitter:image:src" content="<?php echo $url.'/img/ic_launcher_192.png' ?>">

		<meta property=”og:type” content="website"/> 
		<meta property="og:title" content="<?php echo $siteName;?>"/>
		<meta property="og:description" content="<?php echo $siteDescription;?>">
		<meta property="og:url" content="<?php echo $url; ?>"/>
		<meta property="og:image" content="<?php echo $url.'/img/ic_launcher_192.png' ?>"/> 
    </head>
    <body>

        <header>
<nav>
	<?php if(!isset($_GET["gamp"])){ ?> 
		<a href="./">&nbsp;📅 <?php echo $siteName;?></a>
	<?php }else{?>   
		<a href="./?amp=amp">&nbsp;📅 <?php echo $siteName;?></a>
	<?php }?>
</nav>
        </header>


<div id="eventList">
<?php
	$date = new DateTime();
	$date->setTime(0, 0);

	// pour recupérer les evenements ayant démarré depuis 1 mois
	$sinceDate = new DateTime();
	$sinceDate->setTime(0, 0);
	$sinceDate->sub(new DateInterval('P1M'));

	$todayTimestamp=  $date->getTimestamp();
	$sinceTimestamp = $sinceDate->getTimestamp();


	$allEvents = array();


	$fbPageIds = $package['facebookID'];

	//listing of each FBPage
	foreach ($fbList["data"] as $page) {
		$fbPageIds = $fbPageIds.",".$page["id"];
	}

	$url= "https://graph.facebook.com/v2.8/events?ids=".$fbPageIds."&access_token=" . $token . "&fields=interested_count,cover,description,id,category,name,start_time,end_time,place&since=".$sinceTimestamp;

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

// 	// suppression des évenements terminés aujourd'hui
// 	$allEventsAvailable = [];
// // echo '<div222222>'; print_r($allEvents); echo '</div2222222>';
// 	foreach ($allEvents as $event) {
// 		if(isset($event['end_time']) == false || strtotime ($event['end_time']) >= $todayTimestamp ){
// 			$allEventsAvailable = array_merge($allEventsAvailable, $event);
// 		}
		
// 	}

// 	// echo '<div> '; print_r($allEventsAvailable); echo '</div>';



	usort($allEvents, 'sortByYear');

	$allEvents = array_unique($allEvents, SORT_REGULAR);


	

	setlocale(LC_TIME, 'fr', 'fr_FR', 'fr_FR.UTF-8');

	// test if this event is added to our facebook page
	// if yes, this event will be tagged "editor choice"
	$urlOurEvents = "https://graph.facebook.com/v2.8/".$siteFB."/events?access_token=".$token;
	$json = file_get_contents($urlOurEvents);
	$fbOurEvents = json_decode($json, true);

$lastDayList = '';

foreach ($allEvents as $obj) {
  if(strtotime ($obj['end_time']) > $todayTimestamp ){ // on n'affiche que les évenements se terminant aprés aujourd'hui'
	$start_time = $obj['start_time'];
    $end_time = (isset($obj['end_time']))?$obj['end_time']:$obj['start_time'];
	$timestamp = strtotime ($obj['start_time']);
	$monthLabel = utf8_encode(strftime('%b', $timestamp));
	$dayNumber = date('j', $timestamp);
	$dayLabel = utf8_encode(strftime('%a', $timestamp));

	
	$dayList = utf8_encode(strftime('%A', $timestamp)).' '.$dayNumber.' '.utf8_encode(strftime('%B', $timestamp));

	$placeName = (isset($obj['place']['name']))?($obj['place']['name']):'';
	$image = $obj['cover']['source'];
    $title =  $obj['name'];
	$description = (isset($obj['description']))?$obj['description']:$title;
    
	if($lastDayList != $dayList){
		echo '<h3 class="dateHeader"><span data-timestamp="'.$timestamp.'"></span>, '.$dayList.'</h3>';
	}
	$lastDayList = $dayList;

	$urlItem = $package['homepage'].'/'.text2AlphaNum($obj['name']).'/'.$obj['id'];
	
	$isEditorChoice = false;
	foreach ($fbOurEvents["data"] as $ourEvent) {			
		if($ourEvent["id"] == $obj['id']){
			$isEditorChoice = true;
		}
	}
	
?>

<script type="application/ld+json">
{
	"@context": "http://schema.org",
	"@type": "Event",
	"url": "<?php echo $urlItem;?>",
	"description": "<?php echo $description?>",
	"name": "<?php echo $title?>",
	"startDate" :"<?php echo $start_time?>",
	"endDate" :"<?php echo $end_time?>",
	"location": {
		"@type": "Place",
		"address": {
			"@type": "PostalAddress",
			"addressLocality": "<?php echo $placeName?>"
			},
		"name": "<?php echo $placeName?>"
	},
	"image": {
		"@type": "ImageObject",
		"url": "<?php echo $urlBase.'img.php?url='.$image;?>",
		"height" : 720,
		"width": 720
	}
}
</script>  
 <article class="<?php if($isEditorChoice) echo 'editorChoice'; ?>">    
	<?php if(!isset($_GET["gamp"])){ ?> 
		<a href="e/<?php echo text2AlphaNum($obj['name']);?>/<?php echo $obj['id'];?>">
	<?php }else{?>   
		<a href="amp/<?php echo text2AlphaNum($obj['name']);?>/<?php echo $obj['id'];?>">
	<?php }?>

        <div class="header">
			<div class="editorChoiceBadge"> Choix de l'équipe </div>
			<?php if(!isset($_GET["gamp"])){ ?> 
            	<div class="headerBg lazyload" data-bg="<?php echo $obj['cover']['source']; ?>"></div>
			<?php }else{ 
				list($imgwidth, $imgheight, $type, $attr) = getimagesize($image);				
				?>   
				<amp-img src="<?php echo $image; ?>" alt="<?php echo $title; ?>"  layout="responsive"
				 class="lazyloaded" height="<?php echo $imgheight;?>" width="<?php echo $imgwidth;?>"></amp-img>
			<?php }?>
            <span class="title"><?php echo $obj['name']; ?></span>
        </div>
        <div class="listDatePlace">
            <span class="divDate" title="<?php echo $obj['start_time']; ?>">
                <span class="hour"><?php echo date('G:i',$timestamp);?></span>
            </span>
            <span class="listInfo">
                <span class="listWhere" >
                    📍&nbsp;<?php echo $placeName; ?>        
                </span>
            </span>
        </div> 
		</a>  
    </article>
<?php }
}
?>      

</div>


		<section id="newsletter"> 
			<form action="<?php echo $package['mailchimp']['urlForm'];?>" 
					method="post" id="mc-embedded-subscribe-form" 
					name="mc-embedded-subscribe-form" target="_blank" rel="noopener">
	
					<label for="mce-EMAIL" style="display: inline-block;vertical-align: sub;">Newsletter: </label>
					<input type="email" value="" name="EMAIL" class="required email form-control" id="mce-EMAIL" required="required" placeholder="Mail..."><button type="submit" name="subscribe" id="mc-embedded-subscribe">
						OK
					</button>
					<input type="hidden" name="<?php echo $package['mailchimp']['key'];?>" value=""/>
				</form>
		</section>
		<section id="socialPage">
				<a href="https://facebook.com/<?php echo $package['facebook'];?>" class="btnFB" target="_blank" rel="noopener">
				<svg height="1792" viewBox="0 0 1792 1792" width="1792" xmlns="http://www.w3.org/2000/svg"><path d="M1343 12v264h-157q-86 0-116 36t-30 108v189h293l-39 296h-254v759h-306v-759h-255v-296h255v-218q0-186 104-288.5t277-102.5q147 0 228 12z"/></svg>
				&nbsp;@<?php echo $package['facebook'];?></a>
				<a href="https://twitter.com/<?php echo $package['twitter'];?>" class="btnTW" target="_blank" rel="noopener">
				<svg height="1792" viewBox="0 0 1792 1792" width="1792" xmlns="http://www.w3.org/2000/svg"><path d="M1684 408q-67 98-162 167 1 14 1 42 0 130-38 259.5t-115.5 248.5-184.5 210.5-258 146-323 54.5q-271 0-496-145 35 4 78 4 225 0 401-138-105-2-188-64.5t-114-159.5q33 5 61 5 43 0 85-11-112-23-185.5-111.5t-73.5-205.5v-4q68 38 146 41-66-44-105-115t-39-154q0-88 44-163 121 149 294.5 238.5t371.5 99.5q-8-38-8-74 0-134 94.5-228.5t228.5-94.5q140 0 236 102 109-21 205-78-37 115-142 178 93-10 186-50z"/></svg>
				&nbsp;@<?php echo $package['twitter'];?></a>
				<a href="./atom" class="btnRSS">
				<svg height="50" viewBox="0 0 1792 1792" width="50" xmlns="http://www.w3.org/2000/svg"><path d="M576 1344q0 80-56 136t-136 56-136-56-56-136 56-136 136-56 136 56 56 136zm512 123q2 28-17 48-18 21-47 21h-135q-25 0-43-16.5t-20-41.5q-22-229-184.5-391.5t-391.5-184.5q-25-2-41.5-20t-16.5-43v-135q0-29 21-47 17-17 43-17h5q160 13 306 80.5t259 181.5q114 113 181.5 259t80.5 306zm512 2q2 27-18 47-18 20-46 20h-143q-26 0-44.5-17.5t-19.5-42.5q-12-215-101-408.5t-231.5-336-336-231.5-408.5-102q-25-1-42.5-19.5t-17.5-43.5v-143q0-28 20-46 18-18 44-18h3q262 13 501.5 120t425.5 294q187 186 294 425.5t120 501.5z"/></svg>
				&nbsp;RSS</a>
			</section>


	<?php if(!isset($_GET["gamp"])){ ?>
			<script>
				window.lazySizesConfig = window.lazySizesConfig || {};
				window.lazySizesConfig.loadMode = 1;
				window.lazySizesConfig.expand = -10;            
			</script>
			<script type="text/javascript" src="libs.<?php echo str_replace('.','',$package['version']);?>.js"></script> 
			<script type="text/javascript">
				window.onload = function(){
					if ('serviceWorker' in navigator) {
						navigator.serviceWorker.register('sw.js').then(function(registration) {
							console.log('ServiceWorker registration successful with scope: ', registration.scope);
						}).catch(function(err) {
							console.log('ServiceWorker registration failed: ', err);
						});
					}
				}
			</script> 
		<?php } ?>
<?php
	$page = ob_get_contents(); // copie du contenu du tampon dans une chaîne
	ob_end_clean(); // effacement du contenu du tampon et arrêt de son fonctionnement
	//minify this!
	$page = preg_replace(array('/<!--(.*)-->/Uis',"/[[:blank:]]+/"),array('',' '),str_replace(array("\n","\r","\t"),'',$page));
	
	file_put_contents($cache, $page) ; // on écrit la chaîne précédemment récupérée ($page) dans un fichier ($cache) 
	echo $page ; // on affiche notre page :D 

	$generatetime = microtime();
	$generatetime = explode(' ', $generatetime);
	$generatetime = $generatetime[1] + $generatetime[0];
	$generatefinish = $generatetime;
	$generatetotal_time = round(($generatefinish - $generatestart), 4);
	echo '<small>('.$generatetotal_time.' seconds)</small>';
	echo'</body></html>';
}
?>

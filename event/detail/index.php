<?php
	header("Content-Type: text/html; charset=UTF-8");

	if(isset($_GET["fb"]) == false){
		echo '<!DOCTYPE html><html><head></head><body>bad parameter</body></html>';
		exit;
	}

	$generatetime = microtime();
	$generatetime = explode(' ', $generatetime);
	$generatetime = $generatetime[1] + $generatetime[0];
	$generatestart = $generatetime;

	$eventID = $_GET["fb"];
/*debut du cache*/
$cache = '../../cache/event'.$eventID.'.html';
if(isset($_GET["gamp"])){
	$cache = '../../cache/event'.$eventID.'amp.html';
}

$expire = time() - 3600*24*7 ; 
 
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
	
	$token = "188510571271418|6935eae5d8fb74dceb9a5d497204f14e";
	$url= "https://graph.facebook.com/v2.8/".$eventID."?&access_token=" . $token . "&fields=admins,interested_count,cover,description,id,category,name,start_time,place";

	$json = file_get_contents($url);
	$obj = json_decode($json, true);
	setlocale(LC_TIME, 'fr', 'fr_FR', 'fr_FR.ISO8859-1');

	// get FB pages list for verification
	$json = file_get_contents('../../manifest.json');
	$package = json_decode($json, true);

	$siteName = $package['name'];
	$sitedescrition= $package['name'];
	$siteThemeColor = $package['theme_color'];
	$siteTwitter = $package['twitter'];
	$siteFB = $package['facebook'];
	$token = "188510571271418|6935eae5d8fb74dceb9a5d497204f14e";

	$urlFB = "https://graph.facebook.com/v2.8/".$siteFB."/likes?access_token=".$token;
	$json = file_get_contents($urlFB);
	$fbList = json_decode($json, true);

	$isAuthorisedPage = false;
    $pageId = 0;

	// test if is our own event
	foreach ($obj["admins"]["data"] as $admin) {
		if($package["facebookID"] == $admin["id"]){
			$pageId =$package["facebookID"];
			$isAuthorisedPage = true;
		}
	}

	// get event for each FBPage Liked by our facebook page
	// test if this event owner is liked by us
	foreach ($fbList["data"] as $page) {
			
		foreach ($obj["admins"]["data"] as $admin) {
			if($page["id"] == $admin["id"]){
				$pageId = $page["id"];
				$isAuthorisedPage = true;
			}
		}

	}

	// test if this event is added to our facebook page
	// if yes, this event will be tagged "editor choice"
	$urlOurEvents = "https://graph.facebook.com/v2.8/".$siteFB."/events?access_token=".$token;
	$json = file_get_contents($urlOurEvents);
	$fbOurEvents = json_decode($json, true);

	$isEditorChoice = false;
	foreach ($fbOurEvents["data"] as $ourEvent) {			
		if($ourEvent["id"] == $eventID){
			$isEditorChoice = true;
		}
	}


	if($isAuthorisedPage == false){
		echo '<!DOCTYPE html><html><head></head><body>this event is not allowed to be displayed here.'.json_encode($obj["admins"]["data"]).'</body></html>';
		exit;
	}


	$start_time = $obj['start_time'];
    $end_time = (isset($obj['end_time']))?$obj['end_time']:$obj['start_time'];
	$timestamp = strtotime ($obj['start_time']);
	$monthLabel = utf8_encode(strftime('%b', $timestamp));
	$monthLabelLong = utf8_encode(strftime('%B', $timestamp));
	$dayNumber = date('j', $timestamp);
	$dayLabel = utf8_encode(strftime('%a', $timestamp));
	$dayLabelLong = utf8_encode(strftime('%A', $timestamp));
	$placeName = (isset($obj['place']['name']))?($obj['place']['name']):'';
	if(isset($obj['place']) && isset($obj['place']['location'])) {
		$mapQuery =  $obj['place']['location']['latitude'].'+'.$obj['place']['location']['longitude'];
		$mapQueryImg =  $obj['place']['location']['latitude'].','.$obj['place']['location']['longitude'];
	}else if(isset($obj['place']) && isset($obj['place']['name'])){
		$mapQuery =   urlencode ($placeName);
		$mapQueryImg=   urlencode ($placeName);	
	}

	$image = $obj['cover']['source'];
	list($imgwidth, $imgheight, $type, $attr) = getimagesize($image);	
    $title =  htmlspecialchars($obj['name']);
	$description = (isset($obj['description']))?$obj['description']:$title;	
	$reg_exUrl = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";
	$descriptionHTML = $description;
	if(preg_match($reg_exUrl, $descriptionHTML, $url)) {
		// make the urls hyper links
		$descriptionHTML =  preg_replace($reg_exUrl, '<a href="'.$url[0].'" target="_blank" rel="noopener">'.$url[0].'</a>', $descriptionHTML);
	} 
	$descriptionHTML = nl2br($descriptionHTML, true);

    $url = "http" . (($_SERVER['SERVER_PORT'] == 443) ? "s://" : "://") . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	$urlBase = "http" . (($_SERVER['SERVER_PORT'] == 443) ? "s://" : "://") . $_SERVER['HTTP_HOST'].'/fbcal/';
	$urlBase = $package['homepage'];

	$url = $package['homepage'].'/e/'.text2AlphaNum($obj['name']).'/'.$eventID;


	if(isset($_GET["gamp"])){
		$css = file_get_contents('../../style.css');
		$css =  preg_replace('/\n/i', ' ', $css);
		$css =  preg_replace('/    /i', '', $css);
	} 

	
?>

<!DOCTYPE html>
<?php 
	if(isset($_GET["gamp"]))
		echo '<html AMP lang="en">';
	else
		echo '<html  lang="en">';
?>
    <head>
        <title><?php echo $obj['name']; ?> - <?php echo $siteName;?></title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">
		<link rel="canonical" href="../../e/<?php echo text2AlphaNum($obj['name']).'/'.$eventID; ?>" />

<!-- Origin Trial Token, feature = Web Share, origin = https://caldev.io, expires = 2017-03-21 -->
<meta http-equiv="origin-trial" data-feature="Web Share" data-expires="2017-03-21" content="AuRi1pI5RzVJ8sgnKZUrd0f710cNzHstQDgMl01NIVuTRztnFPanZJTs15wfzr7gdaqmA4ACnlgdxPm5VqodsQAAAABLeyJvcmlnaW4iOiJodHRwczovL2NhbGRldi5pbzo0NDMiLCJmZWF0dXJlIjoiV2ViU2hhcmUiLCJleHBpcnkiOjE0OTAxMTMxMTJ9">
        
		<?php if(!isset($_GET["gamp"])){ ?>    
			<link href="../../style.<?php echo str_replace('.','',$package['version']);?>.css" rel="stylesheet"> 
			<link rel="amphtml" href="../../amp/<?php echo text2AlphaNum($obj['name']).'/'.$eventID; ?>">
			<meta name="theme-color" content="<?php echo $siteThemeColor;?>">
			<link rel="apple-touch-icon" href="../../img/ic_launcher_192.png">
			<link rel="manifest" href="../../manifest.json">
		<?php }else{ ?>   
			<script async src="https://cdn.ampproject.org/v0.js"></script>
			<style amp-boilerplate>body{-webkit-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-moz-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-ms-animation:-amp-start 8s steps(1,end) 0s 1 normal both;animation:-amp-start 8s steps(1,end) 0s 1 normal both}@-webkit-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-moz-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-ms-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-o-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}</style><noscript><style amp-boilerplate>body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}</style></noscript>
			

			<style amp-custom>
			<?php echo $css; ?>
			amp-img{
				max-width: 100%;
			}
			</style>
		<?php }?>

		<meta name="twitter:card" content="summary_large_image">
		<meta name="twitter:site" content="<?php echo $url; ?>">
		<meta name="twitter:creator" content="<?php echo $siteTwitter;?>">
		<meta name="twitter:title" content="<?php echo $title; ?> - <?php echo $siteName;?>">
		<meta name="twitter:description" content="<?php echo htmlspecialchars($description)?>">
		<meta name="twitter:image:src" content="<?php echo $urlBase.'/img.php?url='.$image ?>">

		<meta property=”og:type” content="website"/> 
		<meta property="og:title" content="<?php echo $title; ?> - <?php echo $siteName;?>"/>
		<meta property="og:description" content="<?php echo htmlspecialchars($description)?>">
		<meta property="og:url" content="<?php echo $url; ?>"/>
		<meta property="og:image" content="<?php echo $urlBase.'/img.php?url='.$image ?>"/>
		<meta property="og:image:width" content="<?php echo $imgwidth ?>"/>
		<meta property="og:image:height" content="<?php echo $imgheight ?>"/>


		<script type="application/ld+json">
			{
				"@context": "http://schema.org",
				"@type": "Event",
				"url": "<?php echo $url;?>",
				"description": "<?php echo htmlspecialchars($description)?>",
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
					"url": "<?php echo $urlBase.'/img.php?url='.$image;?>",
					"height" : 720,
					"width": 720
				}
			}
			</script>  
 
    </head>
    <body data-page='event'>

    <header>
		<nav>    
			<a href="../../">&nbsp;
				<svg height="128px" id="Layer_1" style="enable-background:new 0 0 128 128;height: 24px;width: 24px;vertical-align: initial;fill: white;" version="1.1" viewBox="0 0 128 128" width="128px" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g style="
"><line style="fill:none;stroke: white;stroke-width:12;stroke-linecap:square;stroke-miterlimit:10;" x1="87.5" x2="40.5" y1="111" y2="64"></line><line style="fill:none;stroke: #ffffff;stroke-width:12;stroke-linecap:square;stroke-miterlimit:10;" x1="40.5" x2="87.5" y1="64" y2="17"></line></g></svg>
			 <?php echo $siteName;?></a>			
		</nav>
     </header>
<div id="detail">
 <article class="<?php if($isEditorChoice) echo 'editorChoice'; ?>">    
        <div class="header">
			<div class="editorChoiceBadge"> Choix de l'équipe </div>
			<?php if(!isset($_GET["gamp"])){ ?> 
            	<div class="headerBg lazyload" data-bg="<?php echo $obj['cover']['source']; ?>"></div>
			<?php }else{ 
							
				?>   
				<amp-img src="<?php echo $image; ?>" alt="<?php echo $title; ?>"  layout="responsive"
				 class="lazyload" height="<?php echo $imgheight;?>" width="<?php echo $imgwidth;?>"></amp-img>
			<?php }?>
            <span class="title">
				<h1><?php echo $obj['name']; ?></h1>
				<div data-timestamp="<?php echo $timestamp;?>"></div>
				<span class="divDate" title="<?php echo $obj['start_time']; ?>">
					<span class="day"><?php echo $dayLabelLong;?></span>
					<span class="dayNumber"><?php echo $dayNumber;?></span>
					<span class="month"><?php echo $monthLabelLong;?></span>
					<span class="dayMoment">
                    <?php echo date('G:i',$timestamp);?>
               		</span>
				</span>
				
			</span>
			
        </div>
<!--        <div class="listDatePlace">
            <span class="divDate" title="<?php echo $obj['start_time']; ?>">
                <span class="day"><?php echo $dayLabel;?></span>
                <span class="dayNumber"><?php echo $dayNumber;?></span>
                <span class="month"><?php echo $monthLabel;?></span>
            </span>
            <span class="listInfo">
                <span class="dayMoment">
                    🕒&nbsp;<span data-timestamp="<?php echo $timestamp;?>"></span> @<?php echo date('G:i',$timestamp);?>
                </span>
                <span class="listWhere">						
                    📍<a class="listLocation" href="https://maps.google.com/maps?q=<?php echo $mapQuery;?>" target="_blank" rel="noopener">&nbsp;<?php echo $placeName; ?></a>        
                </span>
            </span>
        </div>-->

		<div id="shareBtn">
			Partager
			<button data-share-api>Web share</button>
			<a  class="btnFB" href="http://www.facebook.com/sharer/sharer.php?u=<?php echo $url;?>" target="_blank" rel="noopener">
				<svg height="1792" viewBox="0 0 1792 1792" width="1792" xmlns="http://www.w3.org/2000/svg"><path d="M1343 12v264h-157q-86 0-116 36t-30 108v189h293l-39 296h-254v759h-306v-759h-255v-296h255v-218q0-186 104-288.5t277-102.5q147 0 228 12z"/></svg>
			</a>
			<a  class="btnTW" href="https://twitter.com/intent/tweet?text=<?php echo $title; ?>&url=<?php echo $url;?>&via=<?php echo $siteTwitter;?>" target="_blank" rel="noopener">
				<svg height="1792" viewBox="0 0 1792 1792" width="1792" xmlns="http://www.w3.org/2000/svg"><path d="M1684 408q-67 98-162 167 1 14 1 42 0 130-38 259.5t-115.5 248.5-184.5 210.5-258 146-323 54.5q-271 0-496-145 35 4 78 4 225 0 401-138-105-2-188-64.5t-114-159.5q33 5 61 5 43 0 85-11-112-23-185.5-111.5t-73.5-205.5v-4q68 38 146 41-66-44-105-115t-39-154q0-88 44-163 121 149 294.5 238.5t371.5 99.5q-8-38-8-74 0-134 94.5-228.5t228.5-94.5q140 0 236 102 109-21 205-78-37 115-142 178 93-10 186-50z"/></svg>
			</a>
			<a  class="btn btn-default shareBtn shareWhat" href="whatsapp://send?text=<?php echo $url;?>" title="Whatsapp">
				<svg height="1792" viewBox="0 0 1792 1792" width="1792" xmlns="http://www.w3.org/2000/svg"><path d="M1113 974q13 0 97.5 44t89.5 53q2 5 2 15 0 33-17 76-16 39-71 65.5t-102 26.5q-57 0-190-62-98-45-170-118t-148-185q-72-107-71-194v-8q3-91 74-158 24-22 52-22 6 0 18 1.5t19 1.5q19 0 26.5 6.5t15.5 27.5q8 20 33 88t25 75q0 21-34.5 57.5t-34.5 46.5q0 7 5 15 34 73 102 137 56 53 151 101 12 7 22 7 15 0 54-48.5t52-48.5zm-203 530q127 0 243.5-50t200.5-134 134-200.5 50-243.5-50-243.5-134-200.5-200.5-134-243.5-50-243.5 50-200.5 134-134 200.5-50 243.5q0 203 120 368l-79 233 242-77q158 104 345 104zm0-1382q153 0 292.5 60t240.5 161 161 240.5 60 292.5-60 292.5-161 240.5-240.5 161-292.5 60q-195 0-365-94l-417 134 136-405q-108-178-108-389 0-153 60-292.5t161-240.5 240.5-161 292.5-60z"/></svg>
			</a>
			<a  class="btn btn-default shareBtn shareViber" href="viber://forward?text=<?php echo $url;?>" title="Viber">
				<svg enable-background="new 0 0 24 24" height="24px" id="Layer_1" version="1.1" viewBox="0 0 24 24" width="24px" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g><g><g><g><g><path d="M18.011,23.883c-0.485,0-0.805-0.1-1.381-0.337c-5.06-2.087-9.083-5.185-12.298-9.472       c-1.67-2.229-2.958-4.571-3.827-6.965c-0.568-1.569-0.583-2.36-0.058-3.259c0.278-0.468,1.155-1.268,1.655-1.644       c1.023-0.761,1.476-1.023,1.938-1.12c0.343-0.072,0.9-0.023,1.264,0.109c0.18,0.062,0.421,0.188,0.565,0.281       c0.776,0.515,2.701,2.955,3.379,4.282c0.44,0.855,0.569,1.514,0.404,2.074C9.477,8.458,9.12,8.795,8.286,9.467       C7.934,9.751,7.735,9.938,7.703,9.975c-0.01,0.022-0.077,0.226-0.077,0.358c0.001,0.398,0.3,1.318,0.778,2.103       c0.357,0.584,1,1.339,1.637,1.922c0.756,0.691,1.419,1.161,2.154,1.525c0.784,0.391,1.279,0.528,1.505,0.422       c0.05-0.021,0.093-0.044,0.123-0.061c0.022-0.028,0.054-0.066,0.092-0.112l0.414-0.512c0.583-0.735,0.821-0.989,1.41-1.19       c0.669-0.23,1.388-0.17,2.062,0.175c0.414,0.214,1.298,0.763,1.857,1.152c0.671,0.471,2.312,1.789,2.597,2.132       c0.532,0.654,0.637,1.495,0.299,2.369c-0.311,0.798-1.385,2.146-2.211,2.773c-0.742,0.563-1.346,0.808-2.083,0.844       C18.163,23.88,18.083,23.883,18.011,23.883z M4.419,1.728c-0.119,0-0.197,0.01-0.241,0.02c-0.342,0.072-0.764,0.324-1.673,1       C1.954,3.163,1.207,3.893,1.028,4.193C0.661,4.822,0.59,5.36,1.139,6.879c0.846,2.33,2.103,4.615,3.733,6.791       c3.14,4.187,7.07,7.214,12.016,9.253c0.52,0.214,0.756,0.285,1.123,0.285c0.062,0,0.132-0.002,0.213-0.006       c0.607-0.03,1.07-0.221,1.71-0.707c0.721-0.549,1.726-1.8,1.989-2.48c0.251-0.647,0.185-1.235-0.19-1.696       c-0.208-0.251-1.751-1.509-2.461-2.008c-0.541-0.376-1.39-0.903-1.78-1.105c-0.507-0.259-1.036-0.307-1.533-0.138       c-0.397,0.137-0.532,0.255-1.103,0.974l-0.611,0.735c-0.046,0.033-0.155,0.095-0.264,0.144       c-0.433,0.201-1.034,0.093-2.087-0.432c-0.792-0.393-1.504-0.896-2.308-1.631c-0.681-0.623-1.37-1.436-1.759-2.07       c-0.496-0.816-0.874-1.871-0.876-2.452c0-0.247,0.1-0.6,0.218-0.771C7.252,9.461,7.51,9.227,7.862,8.942       c0.836-0.675,1.029-0.893,1.142-1.295c0.113-0.387,0-0.888-0.356-1.58C7.987,4.773,6.126,2.458,5.499,2.042       c-0.103-0.067-0.298-0.168-0.42-0.21C4.901,1.768,4.644,1.728,4.419,1.728z"/></g></g></g></g><g><g><g><path d="M23.2,11.825C22.25,0.375,12.345,0.849,12.237,0.858l-0.058-0.735c0.041-0.004,4.247-0.228,7.645,2.637      c2.4,2.025,3.784,5.055,4.111,9.005L23.2,11.825z"/></g></g></g><g><g><g><path d="M21.327,12.467c-0.82-9.881-9.363-9.463-9.449-9.454l-0.063-0.735c0.038,0,3.686-0.191,6.629,2.263      c2.113,1.763,3.331,4.409,3.618,7.866L21.327,12.467z"/></g></g></g><g><g><g><path d="M18.711,12.467c-0.618-7.447-7.046-7.119-7.112-7.12L11.52,4.614C11.547,4.61,14.28,4.45,16.56,6.28      c1.691,1.357,2.662,3.419,2.887,6.126L18.711,12.467z"/></g></g></g></g></svg>
			</a>
			<a  class="btn btn-default shareBtn shareMail" href="mailto:?subject=<?php echo $title; ?>&amp;body=<?php echo $title; ?>%0D%0A%0D%0A<?php echo $url;?>">
				<svg contentScriptType="text/ecmascript" contentStyleType="text/css" enable-background="new 0 0 2048 2048" height="2048px" id="Layer_1" preserveAspectRatio="xMidYMid meet" version="1.1" viewBox="0.0 0 1792.0 2048" width="1792.0px" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" zoomAndPan="magnify"><path d="M1664,1632V864c-21.333,24-44.333,46-69,66c-178.667,137.333-320.667,250-426,338c-34,28.667-61.667,51-83,67  s-50.167,32.167-86.5,48.5C963.167,1399.833,929,1408,897,1408h-1h-1c-32,0-66.167-8.167-102.5-24.5S727.333,1351,706,1335  s-49-38.333-83-67c-105.333-88-247.333-200.667-426-338c-24.667-20-47.667-42-69-66v768c0,8.667,3.167,16.167,9.5,22.5  s13.833,9.5,22.5,9.5h1472c8.667,0,16.167-3.167,22.5-9.5S1664,1640.667,1664,1632z M1664,581v-11v-13.5c0,0-0.167-4.333-0.5-13  s-1.333-12.833-3-12.5s-3.5-2.667-5.5-9s-5-8.833-9-7.5s-8.667,0.5-14-2.5H160c-8.667,0-16.167,3.167-22.5,9.5S128,535.333,128,544  c0,112,49,206.667,147,284c128.667,101.333,262.333,207,401,317c4,3.333,15.667,13.167,35,29.5s34.667,28.833,46,37.5  s26.167,19.167,44.5,31.5s35.167,21.5,50.5,27.5s29.667,9,43,9h1h1c13.333,0,27.667-3,43-9s32.167-15.167,50.5-27.5  s33.167-22.833,44.5-31.5c11.333-8.667,26.667-21.167,46-37.5s31-26.167,35-29.5c138.667-110,272.333-215.667,401-317  c36-28.667,69.5-67.167,100.5-115.5S1664,620.333,1664,581z M1792,544v1088c0,44-15.667,81.667-47,113s-69,47-113,47H160  c-44,0-81.667-15.667-113-47s-47-69-47-113V544c0-44,15.667-81.667,47-113s69-47,113-47h1472c44,0,81.667,15.667,113,47  S1792,500,1792,544z"/></svg>
			</a>
			<a  class="btn btn-default shareBtn shareWWW" href="<?php echo $url;?>">
				<svg height="1792" viewBox="0 0 1792 1792" width="1792" xmlns="http://www.w3.org/2000/svg"><path d="M896 128q209 0 385.5 103t279.5 279.5 103 385.5-103 385.5-279.5 279.5-385.5 103-385.5-103-279.5-279.5-103-385.5 103-385.5 279.5-279.5 385.5-103zm274 521q-2 1-9.5 9.5t-13.5 9.5q2 0 4.5-5t5-11 3.5-7q6-7 22-15 14-6 52-12 34-8 51 11-2-2 9.5-13t14.5-12q3-2 15-4.5t15-7.5l2-22q-12 1-17.5-7t-6.5-21q0 2-6 8 0-7-4.5-8t-11.5 1-9 1q-10-3-15-7.5t-8-16.5-4-15q-2-5-9.5-10.5t-9.5-10.5q-1-2-2.5-5.5t-3-6.5-4-5.5-5.5-2.5-7 5-7.5 10-4.5 5q-3-2-6-1.5t-4.5 1-4.5 3-5 3.5q-3 2-8.5 3t-8.5 2q15-5-1-11-10-4-16-3 9-4 7.5-12t-8.5-14h5q-1-4-8.5-8.5t-17.5-8.5-13-6q-8-5-34-9.5t-33-.5q-5 6-4.5 10.5t4 14 3.5 12.5q1 6-5.5 13t-6.5 12q0 7 14 15.5t10 21.5q-3 8-16 16t-16 12q-5 8-1.5 18.5t10.5 16.5q2 2 1.5 4t-3.5 4.5-5.5 4-6.5 3.5l-3 2q-11 5-20.5-6t-13.5-26q-7-25-16-30-23-8-29 1-5-13-41-26-25-9-58-4 6-1 0-15-7-15-19-12 3-6 4-17.5t1-13.5q3-13 12-23 1-1 7-8.5t9.5-13.5.5-6q35 4 50-11 5-5 11.5-17t10.5-17q9-6 14-5.5t14.5 5.5 14.5 5q14 1 15.5-11t-7.5-20q12 1 3-17-5-7-8-9-12-4-27 5-8 4 2 8-1-1-9.5 10.5t-16.5 17.5-16-5q-1-1-5.5-13.5t-9.5-13.5q-8 0-16 15 3-8-11-15t-24-8q19-12-8-27-7-4-20.5-5t-19.5 4q-5 7-5.5 11.5t5 8 10.5 5.5 11.5 4 8.5 3q14 10 8 14-2 1-8.5 3.5t-11.5 4.5-6 4q-3 4 0 14t-2 14q-5-5-9-17.5t-7-16.5q7 9-25 6l-10-1q-4 0-16 2t-20.5 1-13.5-8q-4-8 0-20 1-4 4-2-4-3-11-9.5t-10-8.5q-46 15-94 41 6 1 12-1 5-2 13-6.5t10-5.5q34-14 42-7l5-5q14 16 20 25-7-4-30-1-20 6-22 12 7 12 5 18-4-3-11.5-10t-14.5-11-15-5q-16 0-22 1-146 80-235 222 7 7 12 8 4 1 5 9t2.5 11 11.5-3q9 8 3 19 1-1 44 27 19 17 21 21 3 11-10 18-1-2-9-9t-9-4q-3 5 .5 18.5t10.5 12.5q-7 0-9.5 16t-2.5 35.5-1 23.5l2 1q-3 12 5.5 34.5t21.5 19.5q-13 3 20 43 6 8 8 9 3 2 12 7.5t15 10 10 10.5q4 5 10 22.5t14 23.5q-2 6 9.5 20t10.5 23q-1 0-2.5 1t-2.5 1q3 7 15.5 14t15.5 13q1 3 2 10t3 11 8 2q2-20-24-62-15-25-17-29-3-5-5.5-15.5t-4.5-14.5q2 0 6 1.5t8.5 3.5 7.5 4 2 3q-3 7 2 17.5t12 18.5 17 19 12 13q6 6 14 19.5t0 13.5q9 0 20 10t17 20q5 8 8 26t5 24q2 7 8.5 13.5t12.5 9.5l16 8 13 7q5 2 18.5 10.5t21.5 11.5q10 4 16 4t14.5-2.5 13.5-3.5q15-2 29 15t21 21q36 19 55 11-2 1 .5 7.5t8 15.5 9 14.5 5.5 8.5q5 6 18 15t18 15q6-4 7-9-3 8 7 20t18 10q14-3 14-32-31 15-49-18 0-1-2.5-5.5t-4-8.5-2.5-8.5 0-7.5 5-3q9 0 10-3.5t-2-12.5-4-13q-1-8-11-20t-12-15q-5 9-16 8t-16-9q0 1-1.5 5.5t-1.5 6.5q-13 0-15-1 1-3 2.5-17.5t3.5-22.5q1-4 5.5-12t7.5-14.5 4-12.5-4.5-9.5-17.5-2.5q-19 1-26 20-1 3-3 10.5t-5 11.5-9 7q-7 3-24 2t-24-5q-13-8-22.5-29t-9.5-37q0-10 2.5-26.5t3-25-5.5-24.5q3-2 9-9.5t10-10.5q2-1 4.5-1.5t4.5 0 4-1.5 3-6q-1-1-4-3-3-3-4-3 7 3 28.5-1.5t27.5 1.5q15 11 22-2 0-1-2.5-9.5t-.5-13.5q5 27 29 9 3 3 15.5 5t17.5 5q3 2 7 5.5t5.5 4.5 5-.5 8.5-6.5q10 14 12 24 11 40 19 44 7 3 11 2t4.5-9.5 0-14-1.5-12.5l-1-8v-18l-1-8q-15-3-18.5-12t1.5-18.5 15-18.5q1-1 8-3.5t15.5-6.5 12.5-8q21-19 15-35 7 0 11-9-1 0-5-3t-7.5-5-4.5-2q9-5 2-16 5-3 7.5-11t7.5-10q9 12 21 2 7-8 1-16 5-7 20.5-10.5t18.5-9.5q7 2 8-2t1-12 3-12q4-5 15-9t13-5l17-11q3-4 0-4 18 2 31-11 10-11-6-20 3-6-3-9.5t-15-5.5q3-1 11.5-.5t10.5-1.5q15-10-7-16-17-5-43 12zm-163 877q206-36 351-189-3-3-12.5-4.5t-12.5-3.5q-18-7-24-8 1-7-2.5-13t-8-9-12.5-8-11-7q-2-2-7-6t-7-5.5-7.5-4.5-8.5-2-10 1l-3 1q-3 1-5.5 2.5t-5.5 3-4 3 0 2.5q-21-17-36-22-5-1-11-5.5t-10.5-7-10-1.5-11.5 7q-5 5-6 15t-2 13q-7-5 0-17.5t2-18.5q-3-6-10.5-4.5t-12 4.5-11.5 8.5-9 6.5-8.5 5.5-8.5 7.5q-3 4-6 12t-5 11q-2-4-11.5-6.5t-9.5-5.5q2 10 4 35t5 38q7 31-12 48-27 25-29 40-4 22 12 26 0 7-8 20.5t-7 21.5q0 6 2 16z"/></svg>
			</a>

		
		</div>
			<?php if(isset($obj['place'])){ ?>
				<?php if(!isset($_GET["gamp"])){ ?> 
					<div  class="lazyload"
					id="map" data-bg="https://maps.google.com/maps/api/staticmap?center=<?php echo $mapQueryImg;?>&markers=<?php echo $mapQueryImg;?>&zoom=12&size=1000x400&maptype=roadmap&sensor=false&language=&scale=2&key=AIzaSyBlTSh8VUIwlp-itff0sh7X5-1rQQkQA50">
						<a href="https://maps.google.com/maps?q=<?php echo $mapQuery;?>" target="_blank" rel="noopener"><?php echo $obj['place']['name']?> ➡️</a>
					</div>
				<?php }else{?>
					<amp-img src="https://maps.google.com/maps/api/staticmap?center=<?php echo $mapQueryImg;?>&markers=<?php echo $mapQueryImg;?>&zoom=12&size=1000x400&maptype=roadmap&sensor=false&language=&scale=2&key=AIzaSyBlTSh8VUIwlp-itff0sh7X5-1rQQkQA50" alt="<?php echo $obj['place']['name']?>"  layout="responsive"
					class="lazyload" height="400" width="1000"></amp-img>	
					<a href="https://maps.google.com/maps?q=<?php echo $mapQuery;?>" target="_blank" rel="noopener" class="mapLink"><?php echo $obj['place']['name']?> ➡️</a>		
				<?php }?> 
			<?php }?>      
		 
        <p class="description">
			<?php echo $descriptionHTML; ?>
        </p>
        <p class="description descriptionImage">
            <a href="https://www.facebook.com/events/<?php echo $obj['id']; ?>" target="_blank" rel="noopener">
				
				<?php if(!isset($_GET["gamp"])){ ?> 
					<img data-src="<?php echo $obj['cover']['source']; ?>" class="lazyload" alt="<?php echo $obj['name']; ?>"/>
				<?php }else{
					list($imgwidth, $imgheight, $type, $attr) = getimagesize($image);				
				?>   
				<amp-img src="<?php echo $image; ?>" alt="<?php echo $title; ?>"  layout="responsive"
				 class="lazyload" height="<?php echo $imgheight;?>" width="<?php echo $imgwidth;?>"></amp-img>
				<?php }?>
				<br/> En savoir plus...
            </a>
        </p>
		<br/>

		<?php if(!isset($_GET["gamp"])){ ?> 
            <div id="fb-root"></div>
            <script>(function(d, s, id) {
            var js, fjs = d.getElementsByTagName(s)[0];
            if (d.getElementById(id)) return;
            js = d.createElement(s); js.id = id;
            js.src = "//connect.facebook.net/fr_FR/sdk.js#xfbml=1&version=v2.8&appId=188510571271418";
            fjs.parentNode.insertBefore(js, fjs);
            }(document, 'script', 'facebook-jssdk'));</script>
            <div class="fb-page" data-href="https://www.facebook.com/<?php echo $pageId;?>/" style="text-align: center;    display: block; width: 100%;"data-tabs="timeline" data-small-header="false" data-adapt-container-width="true" data-hide-cover="false" data-show-facepile="false"><blockquote cite="https://www.facebook.com/<?php echo $pageId;?>/" class="fb-xfbml-parse-ignore"><a href="https://www.facebook.com/<?php echo $pageId;?>/">facebook.com/<?php echo $pageId;?></a></blockquote></div>
        <?php }?>
    </article>
</div>

		<?php if(!isset($_GET["gamp"])){ ?>
			  
			<script>
				window.lazySizesConfig = window.lazySizesConfig || {};
				window.lazySizesConfig.loadMode = 1;
				window.lazySizesConfig.expand = -10;            
			</script>
			<script type="text/javascript" src="../../libs.<?php echo str_replace('.','',$package['version']);?>.js"></script> 
			<script type="text/javascript">
				window.onload = function(){
					if ('serviceWorker' in navigator) {
						navigator.serviceWorker.register('../../sw.js').then(function(registration) {
							console.log('ServiceWorker registration successful with scope: ', registration.scope);
						}).catch(function(err) {
							console.log('ServiceWorker registration failed: ', err);
						});
					}
										
					(function (d, s, id) {
						var js, fjs = d.getElementsByTagName(s)[0];
						if (d.getElementById(id)) { return; }
						js = d.createElement(s); js.id = id;
						js.src = "https://connect.facebook.net/en_US/sdk.js";
						fjs.parentNode.insertBefore(js, fjs);
					} (document, 'script', 'facebook-jssdk'));
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

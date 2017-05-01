<?php
    $url = $_GET['url'];
    list($width, $height, $type, $attr) = getimagesize($url);
	header("Content-Type: ".image_type_to_mime_type($type)."; charset=UTF-8");
    $img = file_get_contents($url);
    echo $img;
?>
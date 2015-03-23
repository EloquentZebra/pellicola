<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">

<html>

<!--
	Author: Dmitri Popov
	License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt
	Source code: https://github.com/dmpop/mejiro
-->

	<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width" />
	<link href='http://fonts.googleapis.com/css?family=Fira+Sans&subset=cyrillic,latin' rel='stylesheet' type='text/css'>
	<link rel="shortcut icon" href="favicon.ico" />

	<?php

	// User-defined settings
	$title = "Mejiro";
	$tagline = "No-frills open source photo grid";
	$footer="Powered by <a href='https://github.com/dmpop/mejiro'>Mejiro</a> &mdash; pastebin for your photos";
	$expire = false; // Set to true to enable the expiration feature
	$days = 15; // Expiration period
	$stats = true; // Enable web statistics (requires CrazyStat)
	$reverse_order = false; // Set to true to show thumbnails in the reverse order (oldest ot newest)
	$google_maps=false; //Set to true to use Google Maps instead of OpenStreetMap
	$password='m0nk3y'; //Upload password
	// ---------------------
	?>

	<style>
		body { font: 15px/25px 'Fira Sans', sans-serif; text-align: justify; background-color: #777777; }
		a { color: #e3e3e3; }
		a.title { text-decoration: none; color: #ffffff; }
		a.superscript { position: relative; top: -0.7em; font-size: 51%; text-decoration: none; }
		h1 { color: #e3e3e3; font: 29px/50% 'Fira Sans', sans-serif; font-weight: 400; text-align: center; margin-top: 13px; margin-bottom: 7px; line-height: 100%; text-shadow: 1px 1px 1px #585858; letter-spacing: 5px; }
		p { width: 800px; text-align: justify; }
		p.box { border-style: dotted; border-radius: 9px; width: 790px; border-width: 1px; font-size: 13px; padding: 5px; color: #e3e3e3; margin-bottom: 0px; text-align: center; }
		p.center { font-size: 15px; padding: 1px; text-align: center; }
		img { vertical-align: text-bottom; padding-right: 1px; }
		img.thumbnail { max-width: 132px; max-height: 88px; width: auto; height: auto; vertical-align: text-bottom; padding-right: 1px; }
		#content { margin: 0px auto; width: 800px; color: #e3e3e3; }
		.text { text-align: center; padding: 0px; color: inherit; float: left; }
		.center { height: auto; text-align: center; padding: 0px; margin-left: auto; margin-right: auto; }
		.footer { text-align: center; font-family: monospace; font-size: 11px; }
	</style>

	<?php
	
	//Supress all error messages
	//error_reporting (E_ALL ^ E_NOTICE);

	// Detect browser language
	$language = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);

	/*
	* Returns an array of latitude and longitude from the image file
	* @param image $file
	* @return multitype:number |boolean
	* http://stackoverflow.com/questions/5449282/reading-geotag-data-from-image-in-php
	*/
	function read_gps_location($file){
		if (is_file($file)) {
			$info = exif_read_data($file);
			if (isset($info['GPSLatitude']) && isset($info['GPSLongitude']) &&
				isset($info['GPSLatitudeRef']) && isset($info['GPSLongitudeRef']) &&
				in_array($info['GPSLatitudeRef'], array('E','W','N','S')) && in_array($info['GPSLongitudeRef'], array('E','W','N','S'))) {

				$GPSLatitudeRef  = strtolower(trim($info['GPSLatitudeRef']));
				$GPSLongitudeRef = strtolower(trim($info['GPSLongitudeRef']));

				$lat_degrees_a = explode('/',$info['GPSLatitude'][0]);
				$lat_minutes_a = explode('/',$info['GPSLatitude'][1]);
				$lat_seconds_a = explode('/',$info['GPSLatitude'][2]);
				$lon_degrees_a = explode('/',$info['GPSLongitude'][0]);
				$lon_minutes_a = explode('/',$info['GPSLongitude'][1]);
				$lon_seconds_a = explode('/',$info['GPSLongitude'][2]);

				$lat_degrees = $lat_degrees_a[0] / $lat_degrees_a[1];
				$lat_minutes = $lat_minutes_a[0] / $lat_minutes_a[1];
				$lat_seconds = $lat_seconds_a[0] / $lat_seconds_a[1];
				$lon_degrees = $lon_degrees_a[0] / $lon_degrees_a[1];
				$lon_minutes = $lon_minutes_a[0] / $lon_minutes_a[1];
				$lon_seconds = $lon_seconds_a[0] / $lon_seconds_a[1];

				$lat = (float) $lat_degrees+((($lat_minutes*60)+($lat_seconds))/3600);
				$lon = (float) $lon_degrees+((($lon_minutes*60)+($lon_seconds))/3600);

				// If the latitude is South, make it negative
				// If the longitude is west, make it negative
				$GPSLatitudeRef  == 's' ? $lat *= -1 : '';
				$GPSLongitudeRef == 'w' ? $lon *= -1 : '';

				return array(
					'lat' => $lat,
					'lon' => $lon
				);
			}
		}
		return false;
	}

	// Create the required directories if they don't exist
		if (!file_exists('photos')) {
		mkdir('photos', 0744, true);
	}
	if (!file_exists('photos/thumbs')) {
		mkdir('photos/thumbs', 0744, true);
	}

	// Get file info
	$files = glob("photos/*.{jpg,jeg,JPG,JPEG}", GLOB_BRACE);
	$fileCount = count($files);

	function createThumb($original, $thumb, $thumbWidth)
	{
		// Load image
		$img = @imagecreatefromjpeg($original);
		if(!$img) return false; // we couldn't read the image, abort

		// Get image size
		$width = imagesx($img);
		$height = imagesy($img);

		// Calculate thumbnail size
		$new_width  = $thumbWidth;
		$new_height = floor($height * ($thumbWidth / $width));

		// Create a new temporary image
		$tmp_img = imagecreatetruecolor($new_width, $new_height);

		// Copy and resize old image into new image
		imagecopyresampled($tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

		// Save thumbnail into a file
		$ok = @imagejpeg($tmp_img, $thumb);

		// Cleanup
		imagedestroy($img);
		imagedestroy($tmp_img);

		// Return bool true if thumbnail creation worked
		return $ok;
	}

	// Generate any missing thumbnails and check expiration
	for($i = 0; $i < $fileCount; $i++) {
		$file  = $files[$i];
		$thumb = "photos/thumbs/".basename($file);

		if(!file_exists($thumb)) {
			if(createThumb($file, $thumb, 800)) {
				// This is a new file, update last modification date for expiration feature
				touch($file);
			} else {
				// We couldn't create a thumbnail, remove the image from our list
				unset($files[$i]);
			}
		}

		if($expire && (time() - filemtime($file) >= $days * 24 * 60 * 60) ) {
			unlink($file);
			unlink($thumb);
			unset($files[$i]);
		}
	}

	// Update count (we might have removed some files)
	$fileCount = count($files);

	echo "<title>$title ($fileCount)</title>";
	echo "</head>";
	echo "<body>";
	echo "<div id='content'>";

	// The $r parameter is used to empty the /photos/thumbs directory.
	$rebuild = (isset($_GET['r']) ? $_GET['r'] : null);
	if (isset($rebuild)) {
		$files = glob('photos/thumbs/*');
			foreach($files as $file){
				unlink($file);
			}
		exit("Thumbnails have been deleted. <a href='".basename($_SERVER['PHP_SELF'])."'>Reload the page</a> to rebuild thumbnails.");
		}

	// The $t parameter is used to show the thumbnails
	$view = (isset($_GET['t']) ? $_GET['t'] : null);
	if (!isset($view)) {
		echo "<h1>".$title."</h1>";
		echo "<p class ='center'>".$tagline."</p>";
		echo "<p class='center'>";
		// Check whether the reversed order option is enabled and sort the array accordingly
		if($reverse_order) {
			rsort($files);
		}
		for ($i=($fileCount-1); $i>=0; $i--) {
			$file = $files[$i];
			$thumb = "photos/thumbs/".basename($file);
			$filepath = pathinfo($file);
			echo '<a href="index.php?t&p='.$file.'"><img class="thumbnail" src="'.$thumb.'" alt="'.$filepath['filename'].'" title="'.$filepath['filename'].'"></a>';
		}
		echo "</p>";
	}

	// The $p parameter is used to show an individual photo
	$file = (isset($_GET['p']) ? $_GET['p'] : null);
	if (isset($file)) {
		$key = array_search($file, $files); // Determine the array key of the current item (we need this for generating the Next and Previous links)
		$thumb = "photos/thumbs/".basename($file);
		$exif = exif_read_data($file, 0, true);
		$filepath = pathinfo($file);
		//Check if the related RAW file exists and link to it.
		$rawfile=glob('photos/'.$filepath['filename'].'.{ARW,NEF,CR2}', GLOB_BRACE);
		if (!empty($rawfile)) {
			echo "<h1>".$filepath['filename']." <a class='superscript' href=".$rawfile[0].">RAW</a></h1>";
		}
		else {
			echo "<h1>".$filepath['filename']."</h1>";
		}
		echo "<p>";
		// Check whether the localized description file matching the browser language exists
		// added @ to file_get_contents as docs say this is optional.
		if (file_exists('photos/'.$language.'-'.$filepath['filename'].'.txt')) {
			echo @file_get_contents('photos/'.$language.'-'.$filepath['filename'].'.txt');
			// If the localized description file doesn't exist, use the default one
			} else {
			echo @file_get_contents('photos/'.$filepath['filename'].'.txt');
		}
		echo $exif['COMPUTED']['UserComment'];
		echo "</p>";
		echo '<a href="'.$file.'"><img class="dropshadow" src="'.$thumb.'" alt=""></a>';
		$gps = read_gps_location($file);

		$fstop = explode("/", $exif['EXIF']['FNumber']);
		$fstop = $fstop[0] / $fstop[1];
		if (empty($fstop[0]) || empty($fstop[1]) ) {
			$fstop = "";
		} else {
			$fstop = $fstop[0] / $fstop[1];
			$fstop = "&fnof;/".$fstop." &bull; ";
		}
		$exposuretime=$exif['EXIF']['ExposureTime'];
		if (empty($exposuretime)) {
			$exposuretime="";
		} else {
			$exposuretime=$exposuretime." &bull; ";
		}
		$iso=$exif['EXIF']['ISOSpeedRatings'];
		if (empty($iso)) {
			$iso="";
		} else {
			$iso=$iso." &bull; ";
		}
		$datetime=$exif['EXIF']['DateTimeOriginal'];
		if (empty($datetime)) {
			$datetime="";
		} else {
			$datetime=$datetime." &bull; ";
		}
		// Parse IPTC metadata and extract keywords
		// http://stackoverflow.com/questions/9050856/finding-keywords-in-image-data
		$size = getimagesize($file, $info);
		if(isset($info['APP13'])) {
			$iptc = iptcparse($info['APP13']);
				if(isset($iptc['2#025'])) {
					$keywords = $iptc['2#025'];
				} else {
					$keywords = array();
				}
		}
		$keyword = implode(", ", $keywords);

		//Generate map URL. Choose between Google Maps and OpenStreetmap
		if ($google_maps){
			$map_url = " <a href='http://maps.google.com/maps?q=".$gps[lat].",".$gps[lon]."' target='_blank'>Map</a>";
		} else {
			$map_url = " <a href='http://www.openstreetmap.org/index.html?mlat=".$gps[lat]."&mlon=".$gps[lon]."&zoom=18' target='_blank'>Map</a>";
		}

		// Disable the Map link if the photo has no geographical coordinates
		if (empty($gps[lat])) {
			      echo "<p class='box'>".$fstop.$exposuretime.$iso.$datetime."<br />".$keyword."</p>";
		}
		else {
		        echo "<p class='box'>".$fstop.$exposuretime.$iso.$datetime.$map_url."<br />".$keyword."</p>";
		}

		// Disable the Next link if this is the last photo
		if (empty($files[$key+1])) {
		echo "<p class='center'><a href='".basename($_SERVER['PHP_SELF'])."' accesskey='h'><img class='thumbnail' src=photos/thumbs/".basename(max($files))."></a><a href='".basename($_SERVER['PHP_SELF'])."?t&p=".$files[$key-1]."' accesskey='p'><img class='thumbnail' src=photos/thumbs/".basename($files[$key-1])."></a></p>";
		}
		// Disable the Previous link if this is the first photo
		elseif (empty($files[$key-1])) {
			echo "<p class='center'><a href='".basename($_SERVER['PHP_SELF'])."' accesskey='h'><img class='thumbnail' src=photos/thumbs/".basename(max($files))."></a><a href='".basename($_SERVER['PHP_SELF'])."?t&p=".$files[$key+1]."' accesskey='n'><img class='thumbnail' src=photos/thumbs/".basename($files[$key+1])."></a></p>";
		}
		else {
		echo "<p class='center'><a href='".basename($_SERVER['PHP_SELF'])."' accesskey='h'><img class='thumbnail' src=photos/thumbs/".basename(max($files))."></a><a href='".basename($_SERVER['PHP_SELF'])."?t&p=".$files[$key+1]."' accesskey='n'><img class='thumbnail' src=photos/thumbs/".basename($files[$key+1])."></a><a href='".basename($_SERVER['PHP_SELF'])."?t&p=".$files[$key-1]."' accesskey='p'><img class='thumbnail' src=photos/thumbs/".basename($files[$key-1])."></a></p>";
		}
	}

	// The $h parameter is used to show options
	$help = (isset($_GET['h']) ? $_GET['h'] : null);
	if (isset($help)) {
		echo '<p class="box"><a href="'.$_SERVER['PHP_SELF'].'?r">Rebuild thumbnails</a><br /><a href="'.$_SERVER['PHP_SELF'].'?u">Show upload form</a></p>';
	}

	// Upload form adapted from http://sebsauvage.net/wiki/doku.php?id=php:filehosting
	// The $u parameter is used to show the upload form
	$upload = (isset($_GET['u']) ? $_GET['u'] : null);
	if (isset($upload)) {
	$scriptname = basename($_SERVER["SCRIPT_NAME"]).'?u';
	if (isset($_FILES['filetoupload']) && isset($_POST['password'])){
			sleep(3); // Reduce brute-force attack effectiveness
		if ($_POST['password']!=$password) { print '<br /><p class="box">Wrong password! <a href="'.basename($_SERVER['PHP_SELF']).'">Back</a></p>'; header($_SERVER['PHP_SELF']); exit(); }
			$filename = 'photos/'.basename( $_FILES['filetoupload']['name']);
			if (file_exists($filename)) { print '<br /><p class="box">This file already exists. <a href="'.basename($_SERVER['PHP_SELF']).'">Back</a></p>'; header($_SERVER['PHP_SELF']); exit(); }
			if(move_uploaded_file($_FILES['filetoupload']['tmp_name'], $filename)){ $serverport=''; if ($_SERVER["SERVER_PORT"]!='80') { $serverport=':'.$_SERVER["SERVER_PORT"]; }
	$fileurl='http://'.$_SERVER["SERVER_NAME"].$serverport.dirname($_SERVER["SCRIPT_NAME"]).'/photos/'.basename($_FILES['filetoupload']['name']);
	print '<br /><p class="box">Upload successful. <a href="'.basename($_SERVER['PHP_SELF']).'">Reload</a> the page to finish.</p>';
	}
	else { echo '<br /><p class="box">There was an error uploading the file, please try again!</p>'; }
	}
	print <<<EOD
	<p><div class='center'><form method="post" action="$scriptname" enctype="multipart/form-data">
	File: <input type="file" name="filetoupload" size="60">
	<input type="hidden" name="MAX_FILE_SIZE" value="256000000"> Password: <input type="password" name="password"> <input type="submit" value="Upload">
	</form></div></p>
EOD;
}

	echo '<div class="footer">'.$footer.' | <a href="'.$_SERVER['PHP_SELF'].'?h">Options</a></div>';

	if ($stats) {
	echo '<center>';
	@include_once("../crazystat/src/include.php");
	echo '</center>';
	}

	?>
	</div>
	</body>
</html>

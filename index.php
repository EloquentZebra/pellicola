<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">

<html>

	<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<link href='http://fonts.googleapis.com/css?family=Asap:400,700,400italic,700italic' rel='stylesheet' type='text/css'>
	<link href='http://fonts.googleapis.com/css?family=Maven+Pro:400,500,700,900' rel='stylesheet' type='text/css'>
	<link rel="shortcut icon" href="favicon.ico" />

	<style>
		body {
		background-image: url('background.png');
		font: 13px/175% 'Asap', sans-serif;
		text-align: justify;
		background-color: #4a525a;
		}
		h1 {
		color: #cfcfcf;
		font: 41px 'Maven Pro', sans-serif;
		font-weight: 500;
		text-align: center;
		margin-top: 51px;
		margin-bottom: 11px;
		padding-bottom: 11px;
		padding-left: 0px;
		border-style: dashed;
		border-top: none;
		border-left: none;
		border-right: none;
		border-bottom: thick dotted;
		text-shadow: 1px 1px 1px #333;
		letter-spacing: 26px;
	}
	a {
		color: #e3e3e3;
	}
	a.title {
		text-decoration: none;
	}
	h2 {
	color: #e3e3e3;
		font: 29px/50% 'Maven Pro', sans-serif;
		font-weight: 400;
		text-align: left;
		margin-top: 39px;
		margin-bottom: 7px;
		line-height: 100%;
		text-shadow: 1px 1px 1px #101010;
		letter-spacing: 5px;
	}
	p.box {
		border-style: dashed;
		width: 489px;
		border-width: 1px;
		font-size: 12px;
		padding: 5px;
		color: #e3e3e3;
		margin-bottom: 0px;
	}
	p {
		width: 500px;
		text-align: justify;
	}
	img.dropshadow {
		box-shadow: 5px 5px 25px -2px #333;
	}
	img {
		vertical-align: text-bottom;
	}
	#content {
		background-image: url('../images/background.png');
		position: absolute;
		top: 10%;
		left: 50%;
		margin-top: -75px;
		margin-left: -250px;
		width: 500px;
		height: auto;
		color: #e3e3e3;
	}
	.text {
		width: 430px;
		height: auto;
		text-align: left;
		padding: 0px;
		margin: 0px;
		margin-right: 20px;
		color: inherit;
		float: left;
	}
	.center {
		width: 430px;
		height: auto;
		text-align: center;
		padding: 0px;
		margin-left: auto;
		margin-right: auto;
	}
	.footer {
		width: 515px;
		text-align: center;
		font-family: monospace;
		font-size: 11px;
		margin: 0px;
		margin-top: 15px;
	}
	</style>

	<?php

	// User-defined settings

	$title = 'Photocrumbs';
	$tagline=" -- Uncomplicated photo publishing --";
	$basedir='photos/';
	$footer='Powered by <a href="https://github.com/dmpop/photocrumbs">Photocrumbs</a>';

	function createThumbs( $pathToImages, $pathToThumbs, $thumbWidth )
	{
		// open the directory
		$dir = opendir( $pathToImages );

		// loop through it, looking for any/all JPG files:
		while (false !== ($fname = readdir( $dir ))) {
			// parse path for the extension
			$info = pathinfo($pathToImages . $fname);
			// continue only if this is a JPEG image
			if ( strtolower($info['extension']) == 'jpg' )
			{

				// load image and get image size
				$img = imagecreatefromjpeg( "{$pathToImages}{$fname}" );
				$width = imagesx( $img );
				$height = imagesy( $img );

				// calculate thumbnail size
				$new_width = $thumbWidth;
				$new_height = floor( $height * ( $thumbWidth / $width ) );

				// create a new temporary image
				$tmp_img = imagecreatetruecolor( $new_width, $new_height );

				// copy and resize old image into new image
				imagecopyresized( $tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height );

				// save thumbnail into a file
				imagejpeg( $tmp_img, "{$pathToThumbs}{$fname}" );
			}
		}
		// close the directory
		closedir( $dir );
	}
	// call createThumb function and pass to it as parameters the path
	// to the directory that contains images, the path to the directory
	// in which thumbnails will be placed and the thumbnail's width.
	// We are assuming that the path will be a relative path working
	// both in the filesystem, and through the web for links
	createThumbs($basedir,$basedir."thumbs/",500);

	echo "<title>$title</title>";
	echo "</head>";
	echo "<body>";

	echo "<div id='content'><h1>$title</h1>";
	echo "<div class='center'>$tagline</div>";

	$dir=$basedir."/";
	$files = glob($dir.'*.jpg', GLOB_BRACE);
	$thumbs = glob($dir.'thumbs/*.jpg', GLOB_BRACE);
	$fileCount = count(glob($dir.'*.jpg'));

	for ($i=($fileCount-1); $i>=0; $i--)  {
	$exif = exif_read_data($files[$i], 0, true);
	$filepath = pathinfo($files[$i]);
	echo "<h2>".$filepath['filename']."</h2>";
	echo "<p>";
	include $dir.$filepath['filename'].'.php';
	echo "</p>";
    echo '<a href="'.$files[$i].'"><img class="dropshadow" src="'.$thumbs[$i].'" alt=""></a>';
    $Fnumber = explode("/", $exif['EXIF']['FNumber']);
    $Fnumber = $Fnumber[0] / $Fnumber[1];
    echo "<p class='box'>Aperture: f/".$Fnumber." Shutter speed: " .$exif['EXIF']['ExposureTime']. " ISO: ".$exif['EXIF']['ISOSpeedRatings']. " Date: ".$exif['EXIF']['DateTimeOriginal']."</p>";
    }

    echo "<div class='footer'>$footer</div>";

	?>
	</div>
	</body>
</html>

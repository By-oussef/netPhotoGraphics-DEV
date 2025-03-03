<?php

/**
 * @package plugins/text_watermark
 */
define('OFFSET_PATH', 3);
require('../../zp-core/admin-functions.php');
$string = sanitize(@$_GET['text_watermark_text'], 3);

if (!empty($string)) {

	if (isset($_GET['transient'])) {
		header("Content-type: image/png");
		$filename = NULL;
	} else {
		$filename = dirname(dirname(__FILE__)) . '/watermarks/' . seoFriendly($string) . '.png';
	}
	$len = strlen($string);
	$font = gl_imageLoadFont(sanitize(@$_GET['text_watermark_font'], 3));
	$fw = gl_imageFontWidth($font);
	$fh = gl_imageFontHeight($font);
	$image = gl_createImage($fw * $len, $fh);
	$color = sanitize(@$_GET['text_watermark_color'], 3);
	$cr = hexdec(substr($color, 1, 2));
	$cg = hexdec(substr($color, 3, 2));
	$cb = hexdec(substr($color, 5, 2));
	$back = gl_colorAllocate($image, 255 - $cr, 255 - $cg, 255 - $cb);
	if (!is_null($filename)) {
		gl_imageColorTransparent($image, $back);
	}
	gl_imageFill($image, 0, 0, $back);
	$ink = gl_colorAllocate($image, $cr, $cg, $cb);
	$l = 0;
	for ($i = 0; $i < $len; $i++) {
		gl_writeString($image, $font, $l, 0, substr($string, $i, 1), $ink);
		$l = $l + $fw;
	}
	gl_imageOutputt($image, 'png', $filename);
}
?>

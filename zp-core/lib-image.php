<?php

/**
 * image processing functions
 * @package core
 *
 */
// force UTF-8 Ø

class imageProcessing {

	/**
	 * If in debug mode, prints the given error message and continues; otherwise redirects
	 * to the given error message image and exits; designed for a production gallery.
	 * @param $errormessage string the error message to print if $_GET['debug'] is set.
	 * @param $errorimg string the filename of the error image to display for production. Defaults
	 *   to 'err-imagegeneral.png'. Images should be located in /zen/images .
	 */
	static function error($status_text, $errormessage, $errorimg = 'err-imagegeneral.png') {
		global $newfilename, $album, $image;
		$debug = isset($_GET['debug']);
		$err = sprintf(gettext('Image Processing Error: %s'), $errormessage);
		if ($debug) {
			echo '<strong>' . $err . '</strong>';
		} else {
			if (DEBUG_IMAGE) {
				$msg = $err . "\n\t\t" . sprintf(gettext('Request URI: [%s]'), getRequestURI())
								. "\n\t\t" . 'PHP_SELF: [' . sanitize($_SERVER['PHP_SELF'], 3) . ']';
				if ($newfilename) {
					$msg .= "\n\t\t" . sprintf(gettext('Cache: [%s]'), '/' . CACHEFOLDER . '/' . sanitize($newfilename, 3));
				}
				if ($image || $album) {
					$msg .= "\n\t\t" . sprintf(gettext('Image: [%s]'), sanitize($album . '/' . $image, 3));
				}
				debugLog($msg);
			}
			if (!isset($_GET['returncheckmark'])) {
				header("HTTP/1.0 $status_text");
				header("Status: $status_text");
				header('Location: ' . FULLWEBPATH . '/' . CORE_FOLDER . '/images/' . $errorimg);
			}
		}
		exit();
	}

	/**
	 * Prints debug information from the arguments to i.php.
	 *
	 * @param string $album alubm name
	 * @param string $image image name
	 * @param array $args size/crop arguments
	 * @param string $imgfile the filename of the image
	 */
	static function debug($album, $image, $args, $imgfile) {
		list($size, $width, $height, $cw, $ch, $cx, $cy, $quality, $thumb, $crop, $thumbStandin, $passedWM, $adminrequest, $effects) = $args;
		echo "Album: [ " . html_encode($album) . " ], Image: [ " . html_encode($image) . " ]<br /><br />";
		if (file_exists($imgfile)) {
			echo "Image filesize: " . filesize($imgfile);
		} else {
			echo "Image file not found.";
		}
		echo '<br /><br />';
		echo "<strong>" . gettext("Debug") . " <code>i.php</code> | " . gettext("Arguments:") . "</strong><br />\n\n"
		?>
		<ul>
			<li><?php echo gettext("size ="); ?>   <strong> <?php echo $size ?> </strong></li>
			<li><?php echo gettext("width =") ?>   <strong> <?php echo $width ?> </strong></li>
			<li><?php echo gettext("height =") ?>  <strong> <?php echo $height ?> </strong></li>
			<li><?php echo gettext("cw =") ?>      <strong> <?php echo $cw ?> </strong></li>
			<li><?php echo gettext("ch =") ?>      <strong> <?php echo $ch ?> </strong></li>
			<li><?php echo gettext("cx =") ?>      <strong> <?php echo $cx ?> </strong></li>
			<li><?php echo gettext("cy =") ?>      <strong> <?php echo $cy ?> </strong></li>
			<li><?php echo gettext("quality =") ?> <strong> <?php echo $quality ?> </strong></li>
			<li><?php echo gettext("thumb =") ?>   <strong> <?php echo $thumb ?> </strong></li>
			<li><?php echo gettext("crop =") ?>    <strong> <?php echo $crop ?> </strong></li>
			<li><?php echo gettext("thumbStandin =") ?>    <strong> <?php echo $thumbStandin ?> </strong></li>
			<li><?php echo gettext("watermark =") ?>    <strong> <?php echo $passedWM ?> </strong></li>
			<li><?php echo gettext("adminrequest =") ?>    <strong> <?php echo $adminrequest ?> </strong></li>
			<li><?php echo gettext("effects =") ?>    <strong> <?php echo $effects ?> </strong></li>
			<li><?php echo gettext("return_checkmark =") ?>    <strong> <?php echo isset($_GET['returncheckmark']) ?> </strong></li>
		</ul>
		<?php
	}

	/**
	 * Calculates proportional width and height
	 * Used internally by imageProcessing::cache
	 *
	 * Returns array containing the new width and height
	 *
	 * @param int $size
	 * @param int $width
	 * @param int $height
	 * @param int $w
	 * @param int $h
	 * @param int $thumb
	 * @param int $image_use_side
	 * @param int $dim
	 * @return array
	 */
	protected static function propSizes($size, $width, $height, $w, $h, $thumb, $image_use_side, $dim) {
		$hprop = round(($h / $w) * $dim);
		$wprop = round(($w / $h) * $dim);
		if ($size) {
			if ((($thumb || ($image_use_side == 'longest')) && $h > $w) || ($image_use_side == 'height') || ($image_use_side == 'shortest' && $h < $w)) {
				$newh = $dim; // height is the size and width is proportional
				$neww = $wprop;
			} else {
				$neww = $dim; // width is the size and height is proportional
				$newh = $hprop;
			}
		} else { // length and/or width is set, size is NULL (Thumbs work the same as image in this case)
			if ($height) {
				$newh = $height; // height is supplied, use it
			} else {
				$newh = $hprop; // height not supplied, use the proprotional
			}
			if ($width) {
				$neww = $width; // width is supplied, use it
			} else {
				$neww = $wprop; // width is not supplied, use the proportional
			}
		}
		if (DEBUG_IMAGE) {
					debugLog("imageProcessing::propSizes(\$size=$size, \$width=$width, \$height=$height, \$w=$w, \$h=$h, \$thumb=$thumb, \$image_use_side=$image_use_side, \$dim=$dim):: \$wprop=$wprop; \$hprop=$hprop; \$neww=$neww; \$newh=$newh");
		}
		return array((int) $neww, (int) $newh);
	}

	/**
	 * self::iptc_make_tag() function by Thies C. Arntzen
	 * @param $rec
	 * @param $data
	 * @param $value
	 */
	static function iptc_make_tag($rec, $data, $value) {
		$length = strlen($value);
		$retval = chr(0x1C) . chr($rec) . chr($data);
		if ($length < 0x8000) {
			$retval .= chr($length >> 8) . chr($length & 0xFF);
		} else {
			$retval .= chr(0x80) . chr(0x04) . chr(($length >> 24) & 0xFF) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
		}
		return $retval . $value;
	}

	/**
	 * Creates the cache folder version of the image, including watermarking
	 *
	 * @param string $newfilename the name of the file when it is in the cache
	 * @param string $imgfile the image name
	 * @param array $args the cropping arguments
	 * @param bool $allow_watermark set to true if image may be watermarked
	 * @param string $theme the current theme
	 * @param string $album the album containing the image
	 */
	static function cache($newfilename, $imgfile, $args, $allow_watermark = false, $theme, $album) {
		global $_gallery;
		try {
			@list($size, $width, $height, $cw, $ch, $cx, $cy, $quality, $thumb, $crop, $thumbstandin, $passedWM, $adminrequest, $effects) = $args;
			// Set the config variables for convenience.
			$image_use_side = getOption('image_use_side');
			$upscale = getOption('image_allow_upscale');
			$allowscale = true;
			$sharpenthumbs = getOption('thumb_sharpen');
			$sharpenimages = getOption('image_sharpen');
			$id = $im = NULL;

			$watermark_image = false;
			if ($passedWM) {
				if ($passedWM != NO_WATERMARK) {
					$watermark_image = getWatermarkPath($passedWM);
					if (!file_exists($watermark_image)) {
						$watermark_image = false;
					}
				}
			} else {
				if ($allow_watermark) {
					if ($thumb || $thumbstandin) {
						$watermark_image = getAlbumInherited($album, 'watermark_thumb', $id);
						if (empty($watermark_image)) {
							$watermark_image = THUMB_WATERMARK;
						}
					} else {
						$watermark_image = getAlbumInherited($album, 'watermark', $id);
						if (empty($watermark_image)) {
							$watermark_image = IMAGE_WATERMARK;
						}
					}
					if ($watermark_image) {
						if ($watermark_image != NO_WATERMARK) {
							$watermark_image = getWatermarkPath($watermark_image);
							if (!file_exists($watermark_image)) {
								$watermark_image = false;
							}
						}
					}
				}
			}
			if (!$effects) {
				if ($thumb && getOption('thumb_gray')) {
					$effects = 'gray';
				} else if (getOption('image_gray')) {
					$effects = 'gray';
				}
			}
			$newfile = SERVERCACHE . $newfilename;
			mkdir_recursive(dirname($newfile), FOLDER_MOD);
			if (DEBUG_IMAGE) {
							debugLog("imageProcessing::cache(\$imgfile=" . basename($imgfile) . ", \$newfilename=$newfilename, \$allow_watermark=$allow_watermark, \$theme=$theme) \$size=$size, \$width=$width, \$height=$height, \$cw=$cw, \$ch=$ch, \$cx=" . (is_null($cx) ? 'NULL' : $cx) . ", \$cy=" . (is_null($cy) ? 'NULL' : $cy) . ", \$quality=$quality, \$thumb=$thumb, \$crop=$crop \$image_use_side=$image_use_side; \$upscale=$upscale);");
			}
			// Check for the source image.
			if (!file_exists($imgfile) || !is_readable($imgfile)) {
				self::error('404 Not Found', sprintf(gettext('Image %s not found or is unreadable.'), filesystemToInternal($imgfile)), 'err-imagenotfound.png');
			}
			$rotate = false;
			if (gl_imageCanRotate()) {
				$rotate = self::getRotation($imgfile);
			}
			$s = getSuffix($imgfile);
			if (function_exists('exif_thumbnail') && getOption('use_embedded_thumb') && ($s == 'jpg' || $s == 'jpeg')) {
				$im = exif_thumbnail($imgfile, $tw, $th, $tt);
				if ($im) {
					if ($size) {
						$big_enough = $tw >= $size && $th >= $size;
					} else {
						$big_enough = $tw >= $width && $th >= $height;
					}
					if ($big_enough) {
						$im = gl_imageFromString($im);
						if (DEBUG_IMAGE && $im) {
													debugLog(sprintf(gettext('Using %1$ux%2$u %3$s thumbnail image.'), $tw, $th, image_type_to_mime_type($tt)));
						}
					} else {
						$im = false;
					}
				} else {
					$im = false;
				}
			}
			if (!$im) {
				$im = gl_imageGet($imgfile);
			}
			if (!$im) {
				self::error('404 Not Found', sprintf(gettext('Image %s not renderable (imageGet).'), filesystemToInternal($imgfile)), 'err-failimage.png');
			}
			if ($rotate) {
				if (DEBUG_IMAGE) {
									debugLog("self::cache:rotate->$rotate");
				}
				$im = gl_rotateImage($im, $rotate);
				if (!$im) {
					self::error('404 Not Found', sprintf(gettext('Image %s not rotatable.'), filesystemToInternal($imgfile)), 'err-failimage.png');
				}
			}
			$w = gl_imageWidth($im);
			$h = gl_imageHeight($im);
			// Give the sizing dimension to $dim
			$ratio_in = '';
			$ratio_out = '';
			$crop = ($crop || $cw != 0 || $ch != 0);
			if (!empty($size)) {
				$dim = $size;
				if ($crop) {
					if (!$ch) {
											$ch = $size;
					}
					if (!$cw) {
											$cw = $size;
					}
					$width = $cw;
					$height = $ch;
					$size = false;
				} else {
					$width = $height = false;
				}
			} else if (!empty($width) && !empty($height)) {
				$ratio_in = $h / $w;
				$ratio_out = $height / $width;
				if ($ratio_in > $ratio_out) { // image is taller than desired, $height is the determining factor
					$thumb = true;
					$dim = $width;
					if (!$ch) {
											$ch = $height;
					}
				} else { // image is wider than desired, $width is the determining factor
					$dim = $height;
					if (!$cw) {
											$cw = $width;
					}
				}
			} else if (!empty($width)) {
				$dim = $width;
				$size = $height = false;
			} else if (!empty($height)) {
				$dim = $height;
				$size = $width = false;
			} else {
				// There's a problem up there somewhere...
				self::error('404 Not Found', sprintf(gettext('Unknown error processing %s! Please report to the <a href="' . GITHUB . '/issues">developers</a>'), filesystemToInternal($imgfile)), 'err-imagegeneral.png');
			}

			$sizes = self::propSizes($size, $width, $height, $w, $h, $thumb, $image_use_side, $dim);
			list($neww, $newh) = $sizes;

			if (DEBUG_IMAGE) {
							debugLog("self::cache:" . basename($imgfile) . ": \$size=$size, \$width=$width, \$height=$height, \$w=$w; \$h=$h; \$cw=$cw, " .
								"\$ch=$ch, \$cx=$cx, \$cy=$cy, \$quality=$quality, \$thumb=$thumb, \$crop=$crop, \$newh=$newh, \$neww=$neww, \$dim=$dim, " .
								"\$ratio_in=$ratio_in, \$ratio_out=$ratio_out \$upscale=$upscale \$rotate=$rotate \$effects=$effects");
			}

			if (!$upscale && $newh >= $h && $neww >= $w) { // image is the same size or smaller than the request
				$neww = $w;
				$newh = $h;
				$allowscale = false;
				if ($crop) {
					if ($width > $neww) {
						$width = $neww;
					}
					if ($height > $newh) {
						$height = $newh;
					}
				}
				if (DEBUG_IMAGE) {
									debugLog("imageProcessing::cache:no upscale " . basename($imgfile) . ":  \$newh=$newh, \$neww=$neww, \$crop=$crop, \$thumb=$thumb, \$rotate=$rotate, watermark=" . $watermark_image);
				}
			}

			// Crop the image if requested.
			if ($crop) {
				if ($cw > $ch) {
					$ir = $ch / $cw;
				} else {
					$ir = $cw / $ch;
				}
				if ($size) {
					$neww = $size;
					$newh = $ir * $size;
				} else {
					$neww = $width;
					$newh = $height;
					if ($neww > $newh) {
						if ($newh === false) {
							$newh = $ir * $neww;
						}
					} else {
						if ($neww === false) {
							$neww = $ir * $newh;
						}
					}
				}
				$sizes = self::propSizes($size, $neww, $newh, $w, $h, $thumb, $image_use_side, $dim);
				list($neww, $newh) = $sizes;

				if (is_null($cx) && is_null($cy)) { // scale crop to max of image
					// set crop scale factor
					$cf = 1;
					if ($cw) {
											$cf = min($cf, $cw / $neww);
					}
					if ($ch) {
											$cf = min($cf, $ch / $newh);
					}
					//	set the image area of the crop (use the most image possible)
					if (!$cw || $w / $cw * $ch > $h) {
						$cw = round($h / $ch * $cw * $cf);
						$ch = round($h * $cf);
						$cx = round(($w - $cw) / 2);
					} else {
						$ch = round($w / $cw * $ch * $cf);
						$cw = round($w * $cf);
						$cy = round(($h - $ch) / 2);
					}
				} else { // custom crop
					if (!$cw || $cw > $w) {
											$cw = $w;
					}
					if (!$ch || $ch > $h) {
											$ch = $h;
					}
				}
				// force the crop to be within the image
				if ($cw + $cx > $w) {
									$cx = $w - $cw;
				}
				if ($cx < 0) {
					$cw = $cw + $cx;
					$cx = 0;
				}
				if ($ch + $cy > $h) {
									$cy = $h - $ch;
				}
				if ($cy < 0) {
					$ch = $ch + $cy;
					$cy = 0;
				}
				if (DEBUG_IMAGE) {
					debugLog("imageProcessing::cache:crop " . basename($imgfile) . ":\$size=$size, \$width=$width, \$height=$height, \$cw=$cw, \$ch=$ch, \$cx=$cx, \$cy=$cy, \$quality=$quality, \$thumb=$thumb, \$crop=$crop, \$rotate=$rotate");
				}
				$newim = gl_createImage($neww, $newh, ($suffix = getSuffix($newfilename)) != 'gif');
				switch ($suffix) {
					case 'gif':
						$newim = Gl_imageResizeTransparent($newim, $neww, $newh);
						break;
					case 'png':
					case 'webp':
						$newim = gl_imageResizeAlpha($newim, $neww, $newh);
						break;
				}
				if (!gl_resampleImage($newim, $im, 0, 0, $cx, $cy, $neww, $newh, $cw, $ch)) {
					self::error('404 Not Found', sprintf(gettext('Image %s not renderable (resample).'), filesystemToInternal($imgfile)), 'err-failimage.png', $imgfile, $album, $newfilename);
				}
			} else {
				if ($newh >= $h && $neww >= $w && !$rotate && !$effects && !$watermark_image && (!$upscale || $newh == $h && $neww == $w)) {
					// we can just use the original!
					if (SYMLINK && @symlink($imgfile, $newfile)) {
						if (DEBUG_IMAGE) {
													debugLog("imageProcessing::cache:symlink original " . basename($imgfile) . ":\$size=$size, \$width=$width, \$height=$height, \$dim=$dim, \$neww=$neww; \$newh=$newh; \$quality=$quality, \$thumb=$thumb, \$crop=$crop, \$rotate=$rotate; \$allowscale=$allowscale;");
						}
						clearstatcache();
						return true;
					} else if (@copy($imgfile, $newfile)) {
						if (DEBUG_IMAGE) {
													debugLog("imageProcessing::cache:copy original " . basename($imgfile) . ":\$size=$size, \$width=$width, \$height=$height, \$dim=$dim, \$neww=$neww; \$newh=$newh; \$quality=$quality, \$thumb=$thumb, \$crop=$crop, \$rotate=$rotate; \$allowscale=$allowscale;");
						}
						clearstatcache();
						return true;
					}
				}
				if ($allowscale) {
					$sizes = self::propSizes($size, $width, $height, $w, $h, $thumb, $image_use_side, $dim);
					list($neww, $newh) = $sizes;
				}
				if (DEBUG_IMAGE) {
					debugLog("self::cache:no crop " . basename($imgfile) . ":\$size=$size, \$width=$width, \$height=$height, \$dim=$dim, \$neww=$neww; \$newh=$newh; \$quality=$quality, \$thumb=$thumb, \$crop=$crop, \$rotate=$rotate; \$allowscale=$allowscale;");
				}
				$newim = gl_createImage($neww, $newh, ($suffix = getSuffix($newfilename)) != 'gif');
				switch ($suffix) {
					case 'gif':
						$newim = Gl_imageResizeTransparent($newim, $neww, $newh);
						break;
					case 'png':
					case 'webp':
						$newim = gl_imageResizeAlpha($newim, $neww, $newh);
						break;
				}
				if (!gl_resampleImage($newim, $im, 0, 0, 0, 0, $neww, $newh, $w, $h)) {
					self::error('404 Not Found', sprintf(gettext('Image %s not renderable (resample).'), filesystemToInternal($imgfile)), 'err-failimage.png');
				}
				if (($thumb && $sharpenthumbs) || (!$thumb && $sharpenimages)) {
					if (!gl_imageUnsharpMask($newim, getOption('sharpen_amount'), getOption('sharpen_radius'), getOption('sharpen_threshold'))) {
						self::error('404 Not Found', sprintf(gettext('Image %s not renderable (unsharp).'), filesystemToInternal($imgfile)), 'err-failimage.png');
					}
				}
			}

			$imgEffects = explode(',', $effects);
			if (in_array('gray', $imgEffects)) {
				gl_imageGray($newim);
			}

			if ($watermark_image) {
				$newim = self::watermarkImage($newim, $watermark_image, $imgfile);
			}

			// Create the cached file (with lots of compatibility)...
			@chmod($newfile, 0777);
			if (gl_imageOutputt($newim, getSuffix($newfile), $newfile, $quality)) { //	successful save of cached image
				if (getOption('ImbedIPTC') && getSuffix($newfilename) == 'jpg' && GRAPHICS_LIBRARY != 'Imagick') { // the imbed function works only with JPEG images
					global $_images_classes; //	because we are doing the require in a function!
					require_once(dirname(__FILE__) . '/functions.php'); //	it is ok to increase memory footprint now since the image processing is complete
					$iptc = array(
							'1#090' => chr(0x1b) . chr(0x25) . chr(0x47), //	character set is UTF-8
							'2#115' => $_gallery->getTitle() //	source
					);
					$iptc_data = gl_imageIPTC($imgfile);
					if ($iptc_data) {
						$iptc_data = iptcparse($iptc_data);
						if ($iptc_data) {
													$iptc = array_merge($iptc_data, $iptc);
						}
					}
					$imgfile = str_replace(ALBUM_FOLDER_SERVERPATH, '', $imgfile);
					$imagename = basename($imgfile);
					$albumname = dirname($imgfile);
					$image = newImage(newAlbum($albumname), $imagename);
					$copyright = $image->getCopyright();
					if (empty($copyright)) {
						$copyright = getOption('default_copyright');
					}
					if (!empty($copyright)) {
						$iptc['2#116'] = $copyright;
					}
					$credit = $image->getCredit();
					if (!empty($credit)) {
						$iptc['2#110'] = $credit;
					}
					$iptc_result = '';
					foreach ($iptc as $tag => $string) {
						$tag_parts = explode('#', $tag);
						if (is_array($string)) {
							foreach ($string as $element) {
								$iptc_result .= self::iptc_make_tag($tag_parts[0], $tag_parts[1], $element);
							}
						} else {
							$iptc_result .= self::iptc_make_tag($tag_parts[0], $tag_parts[1], $string);
						}
					}
					$content = iptcembed($iptc_result, $newfile);
					$fw = fopen($newfile, 'w');
					fwrite($fw, $content);
					fclose($fw);
					clearstatcache();
				}
				@chmod($newfile, FILE_MOD);
				if (DEBUG_IMAGE) {
									debugLog('Finished:' . basename($imgfile));
				}
			} else {
				if (DEBUG_IMAGE) {
									debugLog('imageProcessing::cache: failed to create ' . $newfile);
				}
				self::error('404 Not Found', sprintf(gettext('imageProcessing::cache: failed to create %s'), $newfile), 'err-failimage.png');
			}
			@chmod($newfile, FILE_MOD);
			gl_imageKill($newim);
			gl_imageKill($im);
		} catch (Exception $e) {
			debugLog('imageProcessing::cache(' . $newfilename . ') exception: ' . $e->getMessage());
			self::error('404 Not Found', sprintf(gettext('imageProcessing::cache(%1$s) exception: %2$s'), $newfilename, $e->getMessage()), 'err-failimage.png');
			return false;
		}
		clearstatcache();
		return true;
	}

	static function watermarkImage($newim, $watermark_image, $imgfile) {
		$offset_h = getOption('watermark_h_offset') / 100;
		$offset_w = getOption('watermark_w_offset') / 100;
		$percent = getOption('watermark_scale') / 100;
		$watermark = gl_imageGet($watermark_image);
		if (!$watermark) {
			self::error('404 Not Found', sprintf(gettext('Watermark %s not renderable.'), $watermark_image), 'err-failimage.png');
		}
		$watermark_width = gl_imageWidth($watermark);
		$watermark_height = gl_imageHeight($watermark);
		$imw = gl_imageWidth($newim);
		$imh = gl_imageHeight($newim);
		$r = sqrt(($imw * $imh * $percent) / ($watermark_width * $watermark_height));
		if (!getOption('watermark_allow_upscale')) {
			$r = min(1, $r);
		}
		$nw = round($watermark_width * $r);
		$nh = round($watermark_height * $r);
		$watermark_new = false;
		if (($nw != $watermark_width) || ($nh != $watermark_height)) {
			$watermark_new = gl_imageResizeAlpha($watermark, $nw, $nh);
			if (!gl_resampleImage($watermark_new, $watermark, 0, 0, 0, 0, $nw, $nh, $watermark_width, $watermark_height)) {
				self::error('404 Not Found', sprintf(gettext('Watermark %s not resizeable.'), $watermark_image), 'err-failimage.png');
			}
		}
		// Position Overlay in Bottom Right
		$dest_x = max(0, floor(($imw - $nw) * $offset_w));
		$dest_y = max(0, floor(($imh - $nh) * $offset_h));
		if (DEBUG_IMAGE) {
					debugLog("Watermark:" . basename($imgfile) . ": \$offset_h=$offset_h, \$offset_w=$offset_w, \$watermark_height=$watermark_height, \$watermark_width=$watermark_width, \$imw=$imw, \$imh=$imh, \$percent=$percent, \$r=$r, \$nw=$nw, \$nh=$nh, \$dest_x=$dest_x, \$dest_y=$dest_y");
		}
		if (!gl_copyCanvas($newim, $watermark_new, $dest_x, $dest_y, 0, 0, $nw, $nh)) {
			self::error('404 Not Found', sprintf(gettext('Image %s not renderable (copycanvas).'), filesystemToInternal($imgfile)), 'err-failimage.png', $imgfile, $album, $newfilename);
		}

		if ($watermark_new != $watermark) {
			gl_imageKill($watermark_new);
		}
		gl_imageKill($watermark);
		return $newim;
	}

	/* Determines the rotation of the image looking EXIF information.
	 *
	 * @param string $imgfile the image name
	 * @return false when the image should not be rotated, or the degrees the
	 *         image should be rotated otherwise.
	 *
	 * PHP GD do not support flips so when a flip is needed we make a
	 * rotation that get close to that flip. But I don't think any camera will
	 * fill a flipped value in the tag.
	 */

	static function getRotation($img) {
		if (is_object($img)) {
			$rotation = $img->get('rotation');
		} else {
			$result = NULL;
			$rotation = 0;
			if (strpos($img, ALBUM_FOLDER_SERVERPATH) === 0) { // then we have possible image object
				$imgfile = substr(filesystemToInternal($img), strlen(ALBUM_FOLDER_SERVERPATH));
				$album = trim(dirname($imgfile), '/');
				$image = basename($imgfile);
				$a = query_single_row($sql = 'SELECT `id` FROM ' . prefix('albums') . ' WHERE `folder`=' . db_quote($album));
				if ($a) {
					$result = query_single_row($sql = 'SELECT rotation FROM ' . prefix('images') . '  WHERE `albumid`=' . $a['id'] . ' AND `filename`=' . db_quote($image));
				}
			}
			if (is_array($result)) {
				if (array_key_exists('rotation', $result)) {
					$rotation = $result['rotation'];
				}
			} else {
				//try the file directly as this might be an image not in the database
				if (function_exists('exif_read_data') && in_array(getSuffix($img), array('jpg', 'jpeg', 'tif', 'tiff'))) {
					$result = @exif_read_data($img);
					if (is_array($result) && array_key_exists('Orientation', $result)) {
						$rotation = $result['Orientation'];
					}
				}
			}
		}
		switch (substr(trim($rotation, '!'), 0, 1)) {
			case 0:
			case 1: // none
			case 2: return 0; // mirrored
			case 3: // upside-down
			case 4: return 180; // upside-down mirrored
			case 5: // 90 CW mirrored
			case 6: return 90; // 90 CCW
			case 7: // 90 CCW mirrored
			case 8: return 270; // 90 CW
		}
		return 0;
	}

}

<?php

/**
 * Library for image handling using the Imagick library of functions
 *
 * Requires Imagick 3.0.0+ and ImageMagick 6.3.8+
 *
 * @package core
 */
// force UTF-8 Ø

define('IMAGICK_REQUIRED_VERSION', '3.0.0');
define('IMAGEMAGICK_REQUIRED_VERSION', '6.3.8');

$_imagick_version = phpversion('imagick');
$_imagick_version_pass = version_compare($_imagick_version, IMAGICK_REQUIRED_VERSION, '>=');

$_imagemagick_version = '';
$_imagemagick_version_pass = false;

$_imagick_present = extension_loaded('imagick') && $_imagick_version_pass;

if ($_imagick_present) {
	@$_imagemagick_version = Imagick::getVersion();
	preg_match('/\d+(\.\d+)*/', $_imagemagick_version['versionString'], $matches);

	$_imagemagick_version['versionNumber'] = $matches[0];
	$_imagemagick_version_pass = version_compare($_imagemagick_version['versionNumber'], IMAGEMAGICK_REQUIRED_VERSION, '>=');

	$_imagick_present &= $_imagick_version_pass;
	unset($matches);
}

$_graphics_optionhandlers += array('lib_Imagick_Options' => new lib_Imagick_Options());

/**
 * Option class for lib-Imagick
 */
class lib_Imagick_Options {

	public static $ignore_size = 0;

	function __construct() {
		setOptionDefault('use_imagick', NULL);
		setOptionDefault('magick_max_height', self::$ignore_size);
		setOptionDefault('magick_max_width', self::$ignore_size);

		if (!sanitize_numeric(getOption('magick_max_height'))) {
			setOption('magick_max_height', self::$ignore_size);
		}

		if (!sanitize_numeric(getOption('magick_max_width'))) {
			setOption('magick_max_width', self::$ignore_size);
		}
	}

	/**
	 * Standard option interface
	 *
	 * @return array
	 */
	function getOptionsSupported() {
		global $_imagick_present, $_graphics_optionhandlers;
		if ($disabled = $this->canLoadMsg()) {
			setOption('use_imagick', 0, true);
		}
		$imagickOptions = array(
				gettext('Enable Imagick') => array(
						'key' => 'use_imagick',
						'type' => OPTION_TYPE_CHECKBOX,
						'order' => 0,
						'disabled' => $disabled,
						'desc' => ($disabled) ? '<p class="notebox">' . $disabled . '</p>' : gettext('Your PHP has support for Imagick. Check this option if you wish to use the Imagick graphics library.')
				)
		);

		if (!$disabled) {
			$imagickOptions += array(
					gettext('Max height') => array(
							'key' => 'magick_max_height',
							'type' => OPTION_TYPE_TEXTBOX,
							'order' => 1,
							'desc' => sprintf(gettext('The maximum height used by the site for processed images. Set to %d for unconstrained. Default is <strong>%d</strong>'), self::$ignore_size, self::$ignore_size)
					),
					gettext('Max width') => array(
							'key' => 'magick_max_width',
							'type' => OPTION_TYPE_TEXTBOX,
							'order' => 2,
							'desc' => sprintf(gettext('The maximum width used by the site for processed images. Set to %d for unconstrained. Default is <strong>%d</strong>.'), self::$ignore_size, self::$ignore_size)
					),
					gettext('Chroma sampling') => array(
							'key' => 'magick_sampling_factor',
							'type' => OPTION_TYPE_ORDERED_SELECTOR,
							'null_selection' => '',
							'selections' => array(
									gettext('no sampling') => '1x1 1x1 1x1',
									gettext('horizontally halved') => '4x1 2x1 2x1',
									gettext('vertically halved') => '1x4 1x2 1x2',
									gettext('horizontally and vertically halved') => '4x4 2x2 2x2'
							),
							'order' => 3,
							'desc' => gettext('Select a Chroma sampling pattern. Leave empty for the image default.')
					)
			);
		}

		return $imagickOptions;
	}

	function canLoadMsg() {
		global $_imagick_version_pass, $_imagemagick_version_pass;

		if (extension_loaded('imagick')) {
			if (!$_imagick_version_pass) {
				return sprintf(gettext('The <strong><em>Imagick</em></strong> library version must be <strong>%s</strong> or later.'), IMAGICK_REQUIRED_VERSION);
			}

			if (!$_imagemagick_version_pass) {
				return sprintf(gettext('The <strong><em>ImageMagick</em></strong> binary version must be <strong>%s</strong> or later.'), IMAGEMAGICK_REQUIRED_VERSION);
			}
		} else {
			return gettext('The <strong><em>Imagick</em></strong> extension is not available.');
		}

		return '';
	}

}

/**
 * Image manipulation functions using the Imagick library
 */
if ($_imagick_present && (getOption('use_imagick') || !extension_loaded('gd'))) {
	$_lib_Imagick_info = array();
	$_lib_Imagick_info['Library'] = 'Imagick';
	$_lib_Imagick_info['Library_desc'] = sprintf(gettext('PHP Imagick library <em>%s</em>') . '<br /><em>%s</em>', $_imagick_version, $_imagemagick_version['versionString']);

	$_imagick_format_whitelist = array(
			'BMP' => 'jpg', 'BMP2' => 'jpg', 'BMP3' => 'jpg',
			'GIF' => 'gif', 'GIF87' => 'gif',
			'JPG' => 'jpg', 'JPEG' => 'jpg',
			'PNG' => 'png', 'PNG8' => 'png', 'PNG24' => 'png', 'PNG32' => 'png',
			'WEBP' => 'webp',
			'TIFF' => 'jpg', 'TIFF64' => 'jpg'
	);

	$_imagick = new Imagick();
	$_imagick_formats = $_imagick->queryFormats();

	foreach ($_imagick_formats as $format) {
		if (array_key_exists($format, $_imagick_format_whitelist)) {
			$_lib_Imagick_info[$format] = $_imagick_format_whitelist[$format];
		}
	}
	// set chroma sampling from option if exists
	$_chromaSampling = getOption('magick_sampling_factor');
	if (!empty($_chromaSampling)) {
		$_imagick->setSamplingFactors(explode(' ', $_chromaSampling));
	}

	unset($_chromaSampling);
	unset($_imagick_format_whitelist);
	unset($_imagick_formats);
	unset($_imagick);
	unset($format);

	if (DEBUG_IMAGE) {
		debugLog('Loading ' . $_lib_Imagick_info['Library']);
	}

	/**
	 * Takes an image filename and returns an Imagick image object
	 *
	 * @param string $imgfile the full path and filename of the image to load
	 * @return Imagick
	 */
	function gl_imageGet($imgfile) {
		global $_lib_Imagick_info;

		if (array_key_exists(strtoupper(getSuffix($imgfile)), $_lib_Imagick_info)) {
			$image = new Imagick();

			$maxHeight = getOption('magick_max_height');
			$maxWidth = getOption('magick_max_width');

			if ($maxHeight > lib_Imagick_Options::$ignore_size && $maxWidth > lib_Imagick_Options::$ignore_size) {
				$image->setOption('jpeg:size', $maxWidth . 'x' . $maxHeight);
			}

			$image->readImage(imgSrcURI($imgfile));

			//Generic CMYK to RGB conversion
			if ($image->getImageColorspace() == Imagick::COLORSPACE_CMYK) {
				$image->transformimagecolorspace(Imagick::COLORSPACE_SRGB);
			}

			return $image;
		}

		return false;
	}

	/**
	 * Outputs an image resource as a given type
	 *
	 * @param Imagick $im
	 * @param string $type
	 * @param string $filename
	 * @param int $qual
	 * @return bool
	 */
	function gl_imageOutputt($im, $type, $filename = NULL, $qual = 75) {
		$interlace = getOption('image_interlace');
		$qual = max(min($qual, 100), 0);

		$im->setImageFormat($type);

		switch ($type) {
			case 'gif':
				$im->setImageCompression(Imagick::COMPRESSION_LZW);
				$im->setImageCompressionQuality($qual);

				if ($interlace) {
					$im->setInterlaceScheme(Imagick::INTERLACE_GIF);
				}

				break;

			case 'jpeg':
			case 'jpg':
				$im->setImageCompression(Imagick::COMPRESSION_JPEG);
				$im->setImageCompressionQuality($qual);

				if ($interlace) {
					$im->setInterlaceScheme(Imagick::INTERLACE_JPEG);
				}

				break;

			case 'png':
				$im->setImageCompression(Imagick::COMPRESSION_ZIP);
				$im->setImageCompressionQuality($qual);

				if ($interlace) {
					$im->setInterlaceScheme(Imagick::INTERLACE_PNG);
				}

				break;
		}

		$im->optimizeImageLayers();

		if ($filename == NULL) {
			header('Content-Type: image/' . $type);

			return print $im->getImagesBlob();
		}

		return $im->writeImages(imgSrcURI($filename), true);
	}

	/**
	 * Creates a true color image
	 *
	 * @param int $w the width of the image
	 * @param int $h the height of the image
	 * @param bool $truecolor True to create a true color image, false for usage with palette images like gifs
	 * @return Imagick
	 */
	function gl_createImage($w, $h, $truecolor = true) {
		$im = new Imagick();
		$im->newImage($w, $h, 'none');
		if ($truecolor) {
			$im->setImageType(Imagick::IMGTYPE_TRUECOLORMATTE);
		} else {
			$imagetype = $im->getImageType();
			$im->setImageType($imagetype);
		}
		return $im;
	}

	/**
	 * Fills an image area
	 *
	 * @param Imagick $image
	 * @param int $x
	 * @param int $y
	 * @param color $color
	 * @return bool
	 */
	function gl_imageFill($image, $x, $y, $color) {
		$target = $image->getImagePixelColor($x, $y);

		return $image->floodFillPaintImage($color, 1, $target, $x, $y, false);
	}

	/**
	 * Sets the transparency color
	 *
	 * @param Imagick $image
	 * @param color $color
	 * @return bool
	 */
	function gl_imageColorTransparent($image, $color) {
		return $image->transparentPaintImage($color, 0.0, 1, false);
	}

	/**
	 * removes metadata (except ICC profile) from an image.
	 * @param object $img
	 */
	function gl_stripMetadata($img) {
		$profiles = $img->getImageProfiles("icc", true);
		$img->stripImage();
		if (!empty($profiles)) {
			$img->profileImage("icc", $profiles['icc']);
		}
		return $img;
	}

	/**
	 * Copies an image canvas
	 *
	 * @param Imagick $imgCanvas destination canvas
	 * @param Imagick $img source canvas
	 * @param int $dest_x destination x
	 * @param int $dest_y destination y
	 * @param int $src_x source x
	 * @param int $src_y source y
	 * @param int $w width
	 * @param int $h height
	 * @return bool
	 */
	function gl_copyCanvas($imgCanvas, $img, $dest_x, $dest_y, $src_x, $src_y, $w, $h) {
		$img->cropImage($w, $h, $src_x, $src_y);

		$result = true;

		$imgCanvas = $imgCanvas->coalesceImages();

		foreach ($imgCanvas as $frame) {
			$result &= $frame->compositeImage($img, Imagick::COMPOSITE_OVER, $dest_x, $dest_y);
		}

		return $result;
	}

	/**
	 * Resamples an image to a new copy
	 *
	 * @param Imagick $dst_image
	 * @param Imagick $src_image
	 * @param int $dst_x
	 * @param int $dst_y
	 * @param int $src_x
	 * @param int $src_y
	 * @param int $dst_w
	 * @param int $dst_h
	 * @param int $src_w
	 * @param int $src_h
	 * @return bool
	 */
	function gl_resampleImage($dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h) {
		foreach ($src_image->getImageProfiles() as $name => $profile) {
			$dst_image->profileImage($name, $profile);
		}

		$result = true;

		$src_image = $src_image->coalesceImages();

		foreach ($src_image as $frame) {
			$frame->cropImage($src_w, $src_h, $src_x, $src_y);
			$frame->setImagePage(0, 0, 0, 0);
		}

		$src_image = $src_image->coalesceImages();

		foreach ($src_image as $frame) {
			$frame->resizeImage($dst_w, $dst_h, Imagick::FILTER_LANCZOS, 1);

			$dst_image->setImageDelay($frame->getImageDelay());
			$result &= $dst_image->compositeImage($frame, Imagick::COMPOSITE_OVER, $dst_x, $dst_y);

			if ($dst_image->getNumberImages() < $src_image->getNumberImages()) {
				$result &= $dst_image->addImage(gl_createImage($dst_image->getImageWidth(), $dst_image->getImageHeight()));
			}

			if (!$result) {
				break;
			}
		}

		return $result;
	}

	/**
	 * Sharpens an image using an Unsharp Mask filter.
	 *
	 * @param Imagick $img the image to sharpen
	 * @param int $amount the strength of the sharpening effect
	 * @param int $radius the pixel radius of the sharpening mask
	 * @param int $threshold the color difference threshold required for sharpening
	 * @return Imagick
	 */
	function gl_imageUnsharpMask($img, $amount, $radius, $threshold) {
		$img->unsharpMaskImage($radius, 0.1, $amount, $threshold);

		return $img;
	}

	/**
	 * Resize a file with transparency to given dimensions and still retain the alpha channel information
	 *
	 * @param Imagick $src
	 * @param int $w
	 * @param int $h
	 * @return Imagick
	 */
	function gl_imageResizeAlpha($src, $w, $h) {
		$src->scaleImage($w, $h);
		return $src;
	}

	/**
	 * Uses gl_imageResizeAlpha() internally as Imagick does not make a difference
	 *
	 * @param type $src
	 * @param type $w
	 * @param type $h
	 * @return type
	 */
	function Gl_imageResizeTransparent($src, $w, $h) {
		return gl_imageResizeAlpha($src, $w, $h);
	}

	/**
	 * Returns true if Imagick library is configured with image rotation support
	 *
	 * @return bool
	 */
	function gl_imageCanRotate() {
		return true;
	}

	/**
	 * Rotates an image resource according to its Orientation setting
	 *
	 * @param Imagick $im
	 * @param int $rotate
	 * @return Imagick
	 */
	function gl_rotateImage($im, $rotate) {
		$im->rotateImage('none', $rotate);
		return $im;
	}

	/**
	 * Returns the image height and width
	 *
	 * @param string $filename
	 * @param array $imageinfo
	 * @return array
	 */
	function gl_imageDims($filename) {
		$ping = new Imagick();

		if ($ping->pingImage(imgSrcURI($filename))) {
			return array('width' => $ping->getImageWidth(), 'height' => $ping->getImageHeight());
		}

		return false;
	}

	/**
	 * Returns the IPTC data of an image
	 *
	 * @param string $filename
	 * @return string
	 */
	function gl_imageIPTC($filename) {
		$ping = new Imagick();

		if ($ping->pingImage(imgSrcURI($filename))) {
			try {
				return $ping->getImageProfile('iptc');
			} catch (ImagickException $e) {
				// IPTC profile does not exist
			}
		}

		return false;
	}

	/**
	 * Returns the width of an image resource
	 *
	 * @param Imagick $im
	 * @return int
	 */
	function gl_imageWidth($im) {
		return $im->getImageWidth();
	}

	/**
	 * Returns the height of an image resource
	 *
	 * @param Imagick $im
	 * @return int
	 */
	function gl_imageHeight($im) {
		return $im->getImageHeight();
	}

	/**
	 * Does a copy merge of two image resources
	 *
	 * @param Imagick $dst_im
	 * @param Imagick $src_im
	 * @param int $dst_x
	 * @param int $dst_y
	 * @param int $src_x
	 * @param int $src_y
	 * @param int $src_w
	 * @param int $src_h
	 * @param int $pct
	 * @return bool
	 */
	function gl_imageMerge($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct) {
		$src_im->cropImage($src_w, $src_h, $src_x, $src_y);
		$src_im->setImageOpacity($pct / 100);

		return $dst_im->compositeImage($src_im, Imagick::COMPOSITE_OVER, $dst_x, $dst_y);
	}

	/**
	 * Creates a grayscale image
	 *
	 * @param Imagick $image The image to grayscale
	 * @return Imagick
	 */
	function gl_imageGray($image) {
		$image->setType(Imagick::IMGTYPE_GRAYSCALE);
		$image->setImageColorspace(Imagick::COLORSPACE_GRAY);
		$image->setImageProperty('exif:ColorSpace', Imagick::IMGTYPE_GRAYSCALE);

		return $image;
	}

	/**
	 * Destroys an image resource
	 *
	 * @param Imagick $im
	 * @return bool
	 */
	function gl_imageKill($im) {
		return $im->destroy();
	}

	/**
	 * Returns an RGB color identifier
	 *
	 * @param Imagick $image
	 * @param int $red
	 * @param int $green
	 * @param int $blue
	 * @return ImagickPixel
	 */
	function gl_colorAllocate($image, $red, $green, $blue) {
		return new ImagickPixel("rgb($red, $green, $blue)");
	}

	/**
	 * Renders a string into the image
	 *
	 * @param Imagick $image
	 * @param ImagickDraw $font
	 * @param int $x
	 * @param int $y
	 * @param string $string
	 * @param ImagickPixel $color
	 * @return bool
	 */
	function gl_writeString($image, $font, $x, $y, $string, $color, $angle = 0) {
		$font->setStrokeColor($color);

		return $image->annotateImage($font, $x, $y + $image->getImageHeight() / 2, $angle, $string);
	}

	/**
	 * Creates a rectangle
	 *
	 * @param Imagick $image
	 * @param int $x1
	 * @param int $y1
	 * @param int $x2
	 * @param int $y2
	 * @param ImagickPixel $color
	 * @return bool
	 */
	function gl_drawRectangle($image, $x1, $y1, $x2, $y2, $color) {
		return $image->borderImage($color, 1, 1);
	}

	/**
	 * Returns array of graphics library info
	 *
	 * @return array
	 */
	function gl_graphicsLibInfo() {
		global $_lib_Imagick_info;

		return $_lib_Imagick_info;
	}

	/**
	 * Returns a list of available fonts
	 *
	 * @return array
	 */
	function gl_getFonts() {
		global $_imagick_fontlist;

		if (!is_array($_imagick_fontlist)) {
			@$_imagick_fontlist = Imagick::queryFonts();
			$_imagick_fontlist = array('system' => '') + array_combine($_imagick_fontlist, $_imagick_fontlist);

			$paths = array(SERVERPATH . '/' . USER_PLUGIN_FOLDER . '/imagick_fonts', CORE_SERVERPATH . 'FreeSerif');
			foreach ($paths as $basefile) {
				if (is_dir($basefile)) {
					chdir($basefile);
					$filelist = safe_glob('*.ttf');

					foreach ($filelist as $file) {
						$key = filesystemToInternal(str_replace('.ttf', '', $file));
						$_imagick_fontlist[$key] = getcwd() . '/' . $file;
					}
				}
			}
			chdir(dirname(__FILE__));
		}

		return $_imagick_fontlist;
	}

	/**
	 * Loads a font and returns an object with the font loaded
	 *
	 * @param string $font
	 * @return ImagickDraw
	 */
	function gl_imageLoadFont($font = NULL, $size = 18) {
		$draw = new ImagickDraw();

		if (!empty($font)) {
			$draw->setFont($font);
		}

		$draw->setFontSize($size);

		return $draw;
	}

	/**
	 * Returns the font width in pixels
	 *
	 * @param ImagickDraw $font
	 * @return int
	 */
	function gl_imageFontWidth($font) {
		$temp = new Imagick();
		$metrics = $temp->queryFontMetrics($font, "The quick brown fox jumps over the lazy dog");
		$temp->destroy();

		return $metrics['characterWidth'];
	}

	/**
	 * Returns the font height in pixels
	 *
	 * @param ImagickDraw $font
	 * @return int
	 */
	function gl_imageFontHeight($font) {
		$temp = new Imagick();
		$metrics = $temp->queryFontMetrics($font, "The quick brown fox jumps over the lazy dog");
		$temp->destroy();

		return $metrics['characterHeight'];
	}

	/**
	 * Creates an image from an image stream
	 *
	 * @param $string
	 * @return Imagick
	 */
	function gl_imageFromString($string) {
		$im = new Imagick();

		$maxHeight = getOption('magick_max_height');
		$maxWidth = getOption('magick_max_width');

		if ($maxHeight > lib_Imagick_Options::$ignore_size && $maxWidth > lib_Imagick_Options::$ignore_size) {
			$im->setOption('jpeg:size', $maxWidth . 'x' . $maxHeight);
		}

		$im->readImageBlob($string);

		return $im;
	}

}
?>

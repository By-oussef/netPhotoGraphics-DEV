<?php

/*
 * Provides dynamic translations of exif strings
 *
 * The data extracted from image metadata is created with "tags" that can later
 * be translated into the current active language when displayed.
 *
 * @Copyright 2015, 2018 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
 * for use with ZenPhotoGrapphics CMS software.
 */

function exifTranslate($source) {
	preg_match('/\!.*\!/', $source, $matches);
	if (isset($matches[0])) {
		$sw = $matches[0];
	} else {
		return $source;
	}
	switch ($sw) {
		default: return $source;
		case '!1-area-focusing!' : return str_replace($sw, gettext("1-area-focusing"), $source);
		case '!1-area-focusing (high speed)!' : return str_replace($sw, gettext("1-area-focusing (High speed)"), $source);
		case '!10s!' : return str_replace($sw, gettext("10s"), $source);
		case '!1: normal (0 deg)!' : return str_replace($sw, gettext('Normal (0 deg)'), $source);
		case '!1st curtain sync!' : return str_replace($sw, gettext('1st curtain sync'), $source);
		case '!2: mirrored!' : return str_replace($sw, gettext('Mirrored'), $source);
		case '!2nd(rear)-curtain sync used!' : return str_replace($sw, gettext('2nd(rear)-curtain sync used'), $source);
		case '!3-area-focusing (high speed)!' : return str_replace($sw, gettext("3-area-focusing (High speed)"), $source);
		case '!3: upside-down!' : return str_replace($sw, gettext('Upside-down'), $source);
		case '!4: upside-down mirrored!' : return str_replace($sw, gettext('Upside-down Mirrored'), $source);
		case '!5: 90 deg ccw mirrored!' : return str_replace($sw, gettext('90 deg CCW Mirrored'), $source);
		case '!6: 90 deg cw!' : return str_replace($sw, gettext('90 deg CW'), $source);
		case '!7: 90 deg cw mirrored!' : return str_replace($sw, gettext('90 deg CW Mirrored'), $source);
		case '!8: 90 deg ccw!' : return str_replace($sw, gettext('90 deg CCW'), $source);
		case '!ae good!' : return str_replace($sw, gettext("AE Good"), $source);
		case '!af non d!' : return str_replace($sw, gettext("AF non D"), $source);
		case '!ai focus!' : return str_replace($sw, gettext("AI Focus"), $source);
		case '!ai servo!' : return str_replace($sw, gettext("AI Servo"), $source);
		case '!aperture priority!' : return str_replace($sw, gettext('Aperture Priority'), $source);
		case '!aperture priority ae!' : return str_replace($sw, gettext("Aperture Priority AE"), $source);
		case '!auto!' : return str_replace($sw, gettext("Auto"), $source);
		case '!auto + red eye reduction!' : return str_replace($sw, gettext("Auto + Red Eye Reduction"), $source);
		case '!auto focus good!' : return str_replace($sw, gettext("Auto Focus Good"), $source);
		case '!auto selected!' : return str_replace($sw, gettext("Auto Selected"), $source);
		case '!auto; continuous!' : return str_replace($sw, gettext("Auto; Continuous"), $source);
		case '!auto; focus button!' : return str_replace($sw, gettext("Auto; Focus button"), $source);
		case '!auto-dep!' : return str_replace($sw, gettext("Auto-DEP"), $source);
		case '!av!' : return str_replace($sw, gettext("Av"), $source);
		case '!average!' : return str_replace($sw, gettext('Average'), $source);
		case '!baby!' : return str_replace($sw, gettext("Baby"), $source);
		case '!black & white!' : return str_replace($sw, gettext("Black & White"), $source);
		case '!black and white!' : return str_replace($sw, gettext("Black and White"), $source);
		case '!bright+!' : return str_replace($sw, gettext("Bright+"), $source);
		case '!bright-!' : return str_replace($sw, gettext("Bright-"), $source);
		case '!bulb!' : return str_replace($sw, gettext('Bulb'), $source);
		case '!center!' : return str_replace($sw, gettext("Center"), $source);
		case '!center of pixel array!' : return str_replace($sw, gettext('Center of Pixel Array'), $source);
		case '!center weighted average!' : return str_replace($sw, gettext('Center Weighted Average'), $source);
		case '!center-weighted!' : return str_replace($sw, gettext("Center-weighted"), $source);
		case '!centimeter!' : return str_replace($sw, gettext('Centimeter'), $source);
		case '!Millimeter!' : return str_replace($sw, gettext('Millimeter'), $source);
		case '!Micrometer!' : return str_replace($sw, gettext('Micrometer'), $source);
		case '!chroma saturation high!' : return str_replace($sw, gettext("Chroma Saturation High"), $source);
		case '!chroma saturation low(org)!' : return str_replace($sw, gettext("Chroma Saturation Low(ORG)"), $source);
		case '!chroma saturation normal(std)!' : return str_replace($sw, gettext("Chroma Saturation Normal(STD)"), $source);
		case '!close-up (macro)!' : return str_replace($sw, gettext("Close-up (Macro)"), $source);
		case '!cloudy!' : return str_replace($sw, gettext("Cloudy"), $source);
		case '!color!' : return str_replace($sw, gettext("Color"), $source);
		case '!color sequential area sensor!' : return str_replace($sw, gettext('Color Sequential Area Sensor'), $source);
		case '!color sequential linear sensor!' : return str_replace($sw, gettext('Color Sequential Linear Sensor'), $source);
		case '!compulsory flash!' : return str_replace($sw, gettext('Compulsory Flash'), $source);
		case '!compulsory flash, light detected!' : return str_replace($sw, gettext('Compulsory Flash; Return light detected'), $source);
		case '!compulsory flash, light not detected!' : return str_replace($sw, gettext('Compulsory Flash; Return light not detected'), $source);
		case '!continuous!' : return str_replace($sw, gettext("Continuous"), $source);
		case '!contrast high(hard)!' : return str_replace($sw, gettext("Contrast High(HARD)"), $source);
		case '!contrast low(org)!' : return str_replace($sw, gettext("Contrast Low(ORG)"), $source);
		case '!contrast normal(std)!' : return str_replace($sw, gettext("Contrast Normal(STD)"), $source);
		case '!contrast+!' : return str_replace($sw, gettext("Contrast+"), $source);
		case '!contrast-!' : return str_replace($sw, gettext("Contrast-"), $source);
		case '!cool!' : return str_replace($sw, gettext("Cool"), $source);
		case '!custom!' : return str_replace($sw, gettext("Custom"), $source);
		case '!datum point!' : return str_replace($sw, gettext('Datum Point'), $source);
		case '!daylight!' : return str_replace($sw, gettext('Daylight'), $source);
		case '!daylightcolor-fluorescence!' : return str_replace($sw, gettext("DaylightColor-fluorescence"), $source);
		case '!daywhitecolor-fluorescence!' : return str_replace($sw, gettext("DaywhiteColor-fluorescence"), $source);
		case '!did not fire!' : return str_replace($sw, gettext("Did Not Fire"), $source);
		case '!digital still camera!' : return str_replace($sw, gettext('Digital Still Camera'), $source);
		case '!directly photographed!' : return str_replace($sw, gettext('Directly Photographed'), $source);
		case '!easyshoot!' : return str_replace($sw, gettext("EasyShoot"), $source);
		case '!evaluative!' : return str_replace($sw, gettext("Evaluative"), $source);
		case '!external!' : return str_replace($sw, gettext("External"), $source);
		case '!external e-ttl!' : return str_replace($sw, gettext('External E-TTL'), $source);
		case '!external flash!' : return str_replace($sw, gettext("External Flash"), $source);
		case '!fast shutter!' : return str_replace($sw, gettext("Fast Shutter"), $source);
		case '!fine!' : return str_replace($sw, gettext("Fine"), $source);
		case '!fireworks!' : return str_replace($sw, gettext("Fireworks"), $source);
		case '!fisheye!' : return str_replace($sw, gettext("Fisheye"), $source);
		case '!flash!' : return str_replace($sw, gettext('Flash'), $source);
		case '!flash did not fire!' : return str_replace($sw, gettext("Flash Did Not Fire"), $source);
		case '!flash fired!' : return str_replace($sw, gettext("Flash Fired"), $source);
		case '!flash; auto-mode!' : return str_replace($sw, gettext('Flash; Auto-Mode'), $source);
		case '!flash, auto-mode, light detected!' : return str_replace($sw, gettext('Flash; Auto-Mode; Return light detected'), $source);
		case '!flash, auto-mode, light not detected!' : return str_replace($sw, gettext('Flash; Auto-Mode; Return light not detected'), $source);
		case '!flash, strobe, light detected!' : return str_replace($sw, gettext('Flash; strobe; Return light detected'), $source);
		case '!flash, strobe, light not detected!' : return str_replace($sw, gettext('Flash; strobe; Return light not detected'), $source);
		case '!fluorescence!' : return str_replace($sw, gettext("Fluorescence"), $source);
		case '!fluorescent!' : return str_replace($sw, gettext('Fluorescent'), $source);
		case '!food!' : return str_replace($sw, gettext("Food"), $source);
		case '!fp sync used!' : return str_replace($sw, gettext('FP sync used'), $source);
		case '!full auto!' : return str_replace($sw, gettext("Full Auto"), $source);
		case '!halogen!' : return str_replace($sw, gettext("Halogen"), $source);
		case '!hard!' : return str_replace($sw, gettext("Hard"), $source);
		case '!high!' : return str_replace($sw, gettext("High"), $source);
		case '!high sensitivity!' : return str_replace($sw, gettext("High Sensitivity"), $source);
		case '!horizontal (normal)!' : return str_replace($sw, gettext("Horizontal (normal)"), $source);
		case '!incandescence!' : return str_replace($sw, gettext("Incandescence"), $source);
		case '!inch!' : return str_replace($sw, gettext('Inch'), $source);
		case '!infinite!' : return str_replace($sw, gettext("Infinite"), $source);
		case '!internal flash!' : return str_replace($sw, gettext('Internal Flash'), $source);
		case '!iso speed!' : return str_replace($sw, gettext('ISO Speed'), $source);
		case '!iso studio tungsten!' : return str_replace($sw, gettext('ISO Studio Tungsten'), $source);
		case '!jpeg compression!' : return str_replace($sw, gettext('Jpeg Compression'), $source);
		case '!landscape!' : return str_replace($sw, gettext('Landscape'), $source);
		case '!large!' : return str_replace($sw, gettext("Large"), $source);
		case '!left!' : return str_replace($sw, gettext("Left"), $source);
		case '!locked (pan mode)!' : return str_replace($sw, gettext("Locked (Pan Mode)"), $source);
		case '!low!' : return str_replace($sw, gettext("Low"), $source);
		case '!low/high quality!' : return str_replace($sw, gettext("Low/High Quality"), $source);
		case '!macro!' : return str_replace($sw, gettext("Macro"), $source);
		case '!macro/close-up!' : return str_replace($sw, gettext("Macro/Close-Up"), $source);
		case '!manual!' : return str_replace($sw, gettext('Manual'), $source);
		case '!manual exposure!' : return str_replace($sw, gettext("Manual Exposure"), $source);
		case '!manual focus!' : return str_replace($sw, gettext("Manual Focus"), $source);
		case '!medium!' : return str_replace($sw, gettext("Medium"), $source);
		case '!mode 1!' : return str_replace($sw, gettext("Mode 1"), $source);
		case '!mode 2!' : return str_replace($sw, gettext("Mode 2"), $source);
		case '!monochrome!' : return str_replace($sw, gettext('Monochrome'), $source);
		case '!multi-spot!' : return str_replace($sw, gettext('Multi-Spot'), $source);
		case '!natural!' : return str_replace($sw, gettext("Natural"), $source);
		case '!night!' : return str_replace($sw, gettext("Night"), $source);
		case '!night portrait!' : return str_replace($sw, gettext("Night Portrait"), $source);
		case '!night scenery!' : return str_replace($sw, gettext("Night Scenery"), $source);
		case '!no!' : return str_replace($sw, gettext("No"), $source);
		case '!no compression!' : return str_replace($sw, gettext('No Compression'), $source);
		case '!no flash!' : return str_replace($sw, gettext('No Flash'), $source);
		case '!no unit!' : return str_replace($sw, gettext('No Unit'), $source);
		case '!no warning!' : return str_replace($sw, gettext("No Warning"), $source);
		case '!none!' : return str_replace($sw, gettext("None"), $source);
		case '!normal!' : return str_replace($sw, gettext("Normal"), $source);
		case '!not defined!' : return str_replace($sw, gettext('Not defined'), $source);
		case '!off!' : return str_replace($sw, gettext("Off"), $source);
		case '!on!' : return str_replace($sw, gettext("On"), $source);
		case '!on + red eye reduction!' : return str_replace($sw, gettext("On + Red Eye Reduction"), $source);
		case '!on camera!' : return str_replace($sw, gettext("On Camera"), $source);
		case '!one chip color area sensor!' : return str_replace($sw, gettext('One Chip Color Area Sensor'), $source);
		case '!one-shot!' : return str_replace($sw, gettext("One-Shot"), $source);
		case '!other!' : return str_replace($sw, gettext('Other'), $source);
		case '!out of focus!' : return str_replace($sw, gettext("Out of Focus"), $source);
		case '!over exposure!' : return str_replace($sw, gettext("Over Exposure"), $source);
		case '!pan focus!' : return str_replace($sw, gettext("Pan Focus"), $source);
		case '!panning!' : return str_replace($sw, gettext("Panning"), $source);
		case '!partial!' : return str_replace($sw, gettext('Partial'), $source);
		case '!party!' : return str_replace($sw, gettext("Party"), $source);
		case '!pattern!' : return str_replace($sw, gettext('Pattern'), $source);
		case '!pet!' : return str_replace($sw, gettext("Pet"), $source);
		case '!pixels!' : return str_replace($sw, gettext('pixels'), $source);
		case '!portrait!' : return str_replace($sw, gettext('Portrait'), $source);
		case '!preset!' : return str_replace($sw, gettext("Preset"), $source);
		case '!program!' : return str_replace($sw, gettext('Program'), $source);
		case '!program action!' : return str_replace($sw, gettext('Program Action'), $source);
		case '!program ae!' : return str_replace($sw, gettext("Program AE"), $source);
		case '!program creative!' : return str_replace($sw, gettext('Program Creative'), $source);
		case '!raw!' : return str_replace($sw, gettext("RAW"), $source);
		case '!recommended exposure index!' : return str_replace($sw, gettext('Recommended Exposure Index'), $source);
		case '!recommended exposure index and iso speed!' : return str_replace($sw, gettext('Recommended Exposure Index and ISO Speed'), $source);
		case '!red eye!' : return str_replace($sw, gettext('Red Eye'), $source);
		case '!red eye reduction!' : return str_replace($sw, gettext("Red Eye Reduction"), $source);
		case '!red eye; auto-mode!' : return str_replace($sw, gettext('Red Eye; Auto-Mode'), $source);
		case '!red eye, auto-mode, light detected!' : return str_replace($sw, gettext('Red Eye; Auto-Mode; Return light detected'), $source);
		case '!red eye, light not detected!' : return str_replace($sw, gettext('Red Eye; Auto-Mode; Return light not detected'), $source);
		case '!red eye, compulsory flash!' : return str_replace($sw, gettext('Red Eye; Compulsory Flash'), $source);
		case '!red eye, compulsory flash, light detected!' : return str_replace($sw, gettext('Red Eye; Compulsory Flash; Return light detected'), $source);
		case '!red eye, compulsory flash, light not detected!' : return str_replace($sw, gettext('Red Eye; Compulsory Flash; Return light not detected'), $source);
		case '!red eye, light detected!' : return str_replace($sw, gettext('Red Eye; Return light detected'), $source);
		case '!red eye, light not detected!' : return str_replace($sw, gettext('Red Eye; Return light not detected'), $source);
		case '!red-eye reduction!' : return str_replace($sw, gettext("Red-Eye Reduction"), $source);
		case '!rgb!' : return str_replace($sw, gettext('RGB'), $source);
		case '!right!' : return str_replace($sw, gettext("Right"), $source);
		case '!rotate 270 cw!' : return str_replace($sw, gettext("Rotate 270 CW"), $source);
		case '!rotate 90 cw!' : return str_replace($sw, gettext("Rotate 90 CW"), $source);
		case '!scenery!' : return str_replace($sw, gettext("Scenery"), $source);
		case '!sec!' : return str_replace($sw, gettext('sec'), $source);
		case '!sepia!' : return str_replace($sw, gettext("Sepia"), $source);
		case '!shutter priority!' : return str_replace($sw, gettext('Shutter Priority'), $source);
		case '!simple!' : return str_replace($sw, gettext("Simple"), $source);
		case '!single!' : return str_replace($sw, gettext("Single"), $source);
		case '!single/timer!' : return str_replace($sw, gettext("Single/Timer"), $source);
		case '!slow shutter!' : return str_replace($sw, gettext("Slow Shutter"), $source);
		case '!slow synchro!' : return str_replace($sw, gettext("Slow Synchro"), $source);
		case '!small!' : return str_replace($sw, gettext("Small"), $source);
		case '!snow!' : return str_replace($sw, gettext("Snow"), $source);
		case '!soft!' : return str_replace($sw, gettext("Soft"), $source);
		case '!speedlight!' : return str_replace($sw, gettext("SpeedLight"), $source);
		case '!sport!' : return str_replace($sw, gettext("Sport"), $source);
		case '!sports!' : return str_replace($sw, gettext("Sports"), $source);
		case '!spot!' : return str_replace($sw, gettext('Spot'), $source);
		case '!spot-focusing!' : return str_replace($sw, gettext("Spot-focusing"), $source);
		case '!srgb!' : return str_replace($sw, gettext('sRGB'), $source);
		case '!standard!' : return str_replace($sw, gettext("Standard"), $source);
		case '!standard light a!' : return str_replace($sw, gettext('Standard Light A'), $source);
		case '!standard light b!' : return str_replace($sw, gettext('Standard Light B'), $source);
		case '!standard light c!' : return str_replace($sw, gettext('Standard Light C'), $source);
		case '!standard output sensitivity!' : return str_replace($sw, gettext('Standard Output Sensitivity'), $source);
		case '!standard output sensitivity and iso speed!' : return str_replace($sw, gettext('Standard Output Sensitivity and ISO Speed'), $source);
		case '!standard output sensitivity and recommended exposure index!' : return str_replace($sw, gettext('Standard Output Sensitivity and Recommended Exposure Index'), $source);
		case '!standard output sensitivity; recommended exposure index and iso speed!' : return str_replace($sw, gettext('Standard Output Sensitivity; Recommended Exposure Index and ISO Speed'), $source);
		case '!sunny!' : return str_replace($sw, gettext("Sunny"), $source);
		case '!superfine!' : return str_replace($sw, gettext("Superfine"), $source);
		case '!sxga basic!' : return str_replace($sw, gettext("SXGA Basic"), $source);
		case '!sxga fine!' : return str_replace($sw, gettext("SXGA Fine"), $source);
		case '!sxga normal!' : return str_replace($sw, gettext("SXGA Normal"), $source);
		case '!the file could not be found.!' : return str_replace($sw, gettext('The file could not be found.'), $source);
		case '!three chip color area sensor!' : return str_replace($sw, gettext('Three Chip Color Area Sensor'), $source);
		case '!trilinear sensor!' : return str_replace($sw, gettext('Trilinear Sensor'), $source);
		case '!tungsten!' : return str_replace($sw, gettext('Tungsten'), $source);
		case '!tv!' : return str_replace($sw, gettext("Tv"), $source);
		case '!two chip color area sensor!' : return str_replace($sw, gettext('Two Chip Color Area Sensor'), $source);
		case '!uncalibrated!' : return str_replace($sw, gettext('Uncalibrated'), $source);
		case '!underwater!' : return str_replace($sw, gettext("Underwater"), $source);
		case '!unknown!' : return str_replace($sw, gettext('Unknown'), $source);
		case '!version!' : return str_replace($sw, gettext('version'), $source);
		case '!very high!' : return str_replace($sw, gettext("Very High"), $source);
		case '!vga basic!' : return str_replace($sw, gettext("VGA Basic"), $source);
		case '!vga fine!' : return str_replace($sw, gettext("VGA Fine"), $source);
		case '!vga normal!' : return str_replace($sw, gettext("VGA Normal"), $source);
		case '!warm!' : return str_replace($sw, gettext("Warm"), $source);
		case '!warning!' : return str_replace($sw, gettext("Warning"), $source);
		case '!white-fluorescence!' : return str_replace($sw, gettext("White-fluorescence"), $source);
		case '!ycbcr!' : return str_replace($sw, gettext('YCbCr'), $source);
		case '!yes!' : return str_replace($sw, gettext("Yes"), $source);
	}
}

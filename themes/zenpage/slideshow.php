<?php
// force UTF-8 Ø
if (!defined('WEBPATH'))
	die();
if (function_exists('printSlideShow')) {
	?>
	<!DOCTYPE html>
	<html>
		<head>
			<?php
			zp_apply_filter('theme_head');

			scriptLoader($_zp_themeroot . '/style.css');
			?>
		</head>
		<body>
				<?php zp_apply_filter('theme_body_open'); ?>
			<div id="slideshowpage">
			<?php printSlideShow(true, true); ?>
			</div>
	<?php zp_apply_filter('theme_body_close'); ?>

		</body>
	</html>
	<?php
} else {
	include(CORE_SERVERPATH . '404.php');
}
?>
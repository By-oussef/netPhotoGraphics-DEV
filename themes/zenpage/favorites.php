<?php
// force UTF-8 Ø

if (!defined('WEBPATH')) {
	die();
}
if (class_exists('favorites')) {
	?>
	<!DOCTYPE html>
	<html>
		<head>
			<?php
			npgFilters::apply('theme_head');

			scriptLoader($_themeroot . '/style.css');
			?>
		</head>
		<body>
			<?php npgFilters::apply('theme_body_open'); ?>

			<div id="main">

				<div id="header">
					<h1><?php printGalleryTitle(); ?></h1>
					<?php
					if (getOption('Allow_search')) {
						printSearchForm(NULL, 'search', NULL, gettext('Search gallery'));
					}
					?>
				</div>

				<div id="content">

					<div id="breadcrumb">
						<h2><a href="<?php echo html_encode(getGalleryIndexURL()); ?>" title="<?php echo gettext('Index'); ?>"><?php echo gettext("Index"); ?></a> » <?php printAlbumTitle(); ?></strong></h2>
					</div>

					<div id="content-left">
						<div><?php printAlbumDesc(); ?></div>


						<?php printPageListWithNav("« " . gettext("prev"), gettext("next") . " »"); ?>
						<div id="albums">
							<?php while (next_album()): ?>
								<div class="album">
									<div class="thumb">
										<a href="<?php echo html_encode(getAlbumURL()); ?>" title="<?php echo gettext('View album:'); ?> <?php printBareAlbumTitle(); ?>"><?php printCustomAlbumThumbImage(getBareAlbumTitle(), NULL, 95, 95, 95, 95); ?></a>
									</div>
									<div class="albumdesc">
										<h3><a href="<?php echo html_encode(getAlbumURL()); ?>" title="<?php echo gettext('View album:'); ?> <?php printBareAlbumTitle(); ?>"><?php printAlbumTitle(); ?></a></h3>
										<?php printAlbumDate(""); ?>
										<div><?php echo html_encodeTagged(shortenContent(getAlbumDesc(), 45, '...')); ?></div>
										<br />
										<?php printAddToFavorites($_current_album, '', gettext('Remove')); ?>
									</div>
									<p style="clear: both; "></p>
								</div>
							<?php endwhile; ?>
						</div>

						<div id="images">
							<?php while (next_image()): ?>
								<div class="image">
									<div class="imagethumb"><a href="<?php echo html_encode(getImageURL()); ?>" title="<?php printBareImageTitle(); ?>"><?php printImageThumb(getBareImageTitle()); ?></a>
										<?php printAddToFavorites($_current_image, '', gettext('Remove')); ?>
									</div>
								</div>
							<?php endwhile; ?>

						</div>
						<p style="clear: both; "></p>
						<?php
						@call_user_func('printSlideShowLink');
						printPageListWithNav("« " . gettext("prev"), gettext("next") . " »");
						printTags('links', gettext('<strong>Tags:</strong>') . ' ', 'taglist', ', ');
						?>
						<br style="clear:both;" /><br />
						<?php
						@call_user_func('printRating');
						@call_user_func('printCommentForm');
						?>
					</div><!-- content left-->



					<div id="sidebar">
						<?php include("sidebar.php"); ?>
					</div><!-- sidebar -->



					<div id="footer">
						<?php include("footer.php"); ?>
					</div>

				</div><!-- content -->

			</div><!-- main -->
			<?php
			npgFilters::apply('theme_body_close');
			?>
		</body>
	</html>
	<?php
} else {
	include(CORE_SERVERPATH . '404.php');
}
?>
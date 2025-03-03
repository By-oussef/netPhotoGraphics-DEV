<?php
// force UTF-8 Ø

if (!defined('WEBPATH')) {
	die();
}
?>
<!DOCTYPE html>
<html>
	<head>
		<?php
		npgFilters::apply('theme_head');

		scriptLoader($_themeroot . '/style.css');

		if (class_exists('RSS')) {
					printRSSHeaderLink('Album', getAlbumTitle());
		}
		?>
	</head>
	<body>
		<?php npgFilters::apply('theme_body_open'); ?>

		<div id="main">

			<div id="header">
				<h1><?php printGalleryTitle(); ?></h1>
				<?php
				if (getOption('Allow_search')) {
					$album_list = array('albums' => array($_current_album->name), 'pages' => '0', 'news' => '0');
					printSearchForm(NULL, 'search', gettext('Search within album'), gettext('Search'), NULL, NULL, $album_list);
				}
				?>
			</div>

			<div id="content">

				<div id="breadcrumb">
					<h2><a href="<?php echo html_encode(getGalleryIndexURL()); ?>" title="<?php echo gettext('Index'); ?>"><?php echo gettext("Index"); ?></a><?php printParentBreadcrumb(" » ", " » ", ""); ?> » <strong><?php printAlbumTitle(); ?></strong></h2>
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
									<?php
									if (function_exists('printAddToFavorites')) {
										echo "<br />";
										printAddToFavorites($_current_album);
									}
									?>
								</div>
								<p style="clear: both; "></p>
							</div>
						<?php endwhile; ?>
					</div>

					<div id="images">
						<?php while (next_image()): ?>
							<div class="image">
								<div class="imagethumb"><a href="<?php echo html_encode(getImageURL()); ?>" title="<?php printBareImageTitle(); ?>"><?php printImageThumb(getBareImageTitle()); ?></a></div>
							</div>
						<?php endwhile; ?>

					</div>
					<p style="clear: both; "></p>
					<?php
					printPageListWithNav("« " . gettext("prev"), gettext("next") . " »");
					printTags('links', gettext('<strong>Tags:</strong>') . ' ', 'taglist', ', ');
					?>
					<br style="clear:both;" /><br />
					<?php
					if (function_exists('printSlideShowLink')) {
						echo '<span id="slideshowlink">';
						printSlideShowLink();
						echo '</span>';
					}
					?>
					<br style="clear:both;" />
					<?php
					if (function_exists('printAddToFavorites')) {
											printAddToFavorites($_current_album);
					}
					@call_user_func('printRating');
					simpleMap::printMap();

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
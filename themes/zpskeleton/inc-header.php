<?php
if (!defined('WEBPATH')) {
	die();
}
$zpskel_social = function_exists('printAddThis');
?>
<!DOCTYPE html>
<html>
	<head>
		<?php npgFilters::apply('theme_head'); ?>
		<?php
		// Set some things depending on what page we are on...
		// Define some symbols
		$prev = "&#9656;";
		switch ($_gallery_page) {
			case 'index.php':
				if ($_current_page > 1) {
					$metatitle = getBareGalleryTitle() . " ($_current_page)";
				} else {
					$metatitle = getBareGalleryTitle();
				}
				$zpskel_metatitle = $metatitle;
				$zpskel_metadesc = truncate_string(getBareGalleryDesc(), 150, '...');
				if ((!$zenpage) || ($zenpage_homepage == 'none')) {
									$galleryactive = true;
				}
				break;
			case 'gallery.php':
				$zpskel_metatitle = getBareGalleryTitle();
				$zpskel_metadesc = truncate_string(getBareGalleryDesc(), 150, '...');
				$galleryactive = true;
				break;
			case 'album.php':
			case 'favorites.php';
				if ($_current_page > 1) {
					$metatitle = getBareAlbumTitle() . " ($_current_page)";
				} else {
					$metatitle = getBareAlbumTitle();
				}
				$zpskel_metatitle = $metatitle . getTitleBreadcrumb() . ' | ' . getBareGalleryTitle();
				$zpskel_metadesc = truncate_string(getBareAlbumDesc(), 150, '...');
				$galleryactive = true;
				if (extensionEnabled('rss')) {
					if (getOption('RSS_album_image')) {
						printRSSHeaderLink('Collection', getBareAlbumTitle() . ' - ' . gettext('Latest Images'), $lang = '') . "\n";
					}
					if ((function_exists('printCommentForm')) && (getOption('RSS_comments'))) {
						printRSSHeaderLink('Comments-album', getBareAlbumTitle() . ' - ' . gettext('Latest Comments'), $lang = '') . "\n";
					}
				}
				break;
			case 'image.php':
				$zpskel_metatitle = getBareImageTitle() . ' | ' . getBareAlbumTitle() . getTitleBreadcrumb() . ' | ' . getBareGalleryTitle();
				$zpskel_metadesc = truncate_string(getBareImageDesc(), 150, '...');
				$galleryactive = true;
				if ((extensionEnabled('rss')) && (function_exists('printCommentForm')) && (getOption('RSS_comments'))) {
					printRSSHeaderLink('Comments-image', getBareImageTitle() . ' - ' . gettext('Latest Comments'), $lang = '') . "\n";
				}
				break;
			case 'archive.php':
				$zpskel_metatitle = gettext("Archive View") . ' | ' . getBareGalleryTitle();
				$zpskel_metadesc = truncate_string(getBareGalleryDesc(), 150, '...');
				$galleryactive = true;
				break;
			case 'summary.php':
				$zpskel_metatitle = gettext("Daily summary") . ' | ' . getBareGalleryTitle();
				$zpskel_metadesc = truncate_string(getBareGalleryDesc(), 150, '...');
				$galleryactive = true;
				break;
			case 'search.php':
				$zpskel_metatitle = gettext('Search') . ' | ' . html_encode(getSearchWords()) . ' | ' . getBareGalleryTitle();
				$zpskel_metadesc = truncate_string(getBareGalleryDesc(), 150, '...');
				break;
			case 'pages.php':
				$zpskel_metatitle = getBarePageTitle() . ' | ' . getBareGalleryTitle();
				$zpskel_metadesc = truncate_string(getBare(getPageContent()), 150, '...');
				break;
			case 'news.php':
				if (is_NewsArticle()) {
					$zpskel_metatitle = NEWS_LABEL . ' | ' . getBareNewsTitle() . ' | ' . getBareGalleryTitle();
					$zpskel_metadesc = truncate_string(getBare(getNewsContent()), 150, '...');
				} else if ($_CMS_current_category) {
					$zpskel_metatitle = NEWS_LABEL . ' | ' . $_CMS_current_category->getTitle() . ' | ' . getBareGalleryTitle();
					$zpskel_metadesc = truncate_string(getBare(getNewsCategoryDesc()), 150, '...');
				} else if (getCurrentNewsArchive()) {
					$zpskel_metatitle = NEWS_LABEL . ' | ' . getCurrentNewsArchive() . ' | ' . getBareGalleryTitle();
					$zpskel_metadesc = truncate_string(getBareGalleryDesc(), 150, '...');
				} else {
					$zpskel_metatitle = NEWS_LABEL . ' | ' . getBareGalleryTitle();
					$zpskel_metadesc = truncate_string(getBareGalleryDesc(), 150, '...');
				}
				break;
			case 'contact.php':
				$zpskel_metatitle = gettext('Contact') . ' | ' . getBareGalleryTitle();
				$zpskel_metadesc = truncate_string(getBareGalleryDesc(), 150, '...');
				break;
			case 'login.php':
				$zpskel_metatitle = gettext('Login') . ' | ' . getBareGalleryTitle();
				$zpskel_metadesc = truncate_string(getBareGalleryDesc(), 150, '...');
				break;
			case 'register.php':
				$zpskel_metatitle = gettext('Register') . ' | ' . getBareGalleryTitle();
				$zpskel_metadesc = truncate_string(getBareGalleryDesc(), 150, '...');
				break;
			case 'password.php':
				$zpskel_metatitle = gettext('Password Required') . ' | ' . getBareGalleryTitle();
				$zpskel_metadesc = truncate_string(getBareGalleryDesc(), 150, '...');
				break;
			case '404.php':
				$zpskel_metatitle = gettext('404 Not Found...') . ' | ' . getBareGalleryTitle();
				$zpskel_metadesc = truncate_string(getBareGalleryDesc(), 150, '...');
				$galleryactive = true;
				break;
			default:
				$zpskel_metatitle = getBareGalleryTitle() . getBareGalleryTitle();
				$zpskel_metadesc = truncate_string(getBareGalleryDesc(), 150, '...');
				break;
		}
		// Finish out header RSS links for inc-header.php
		if (extensionEnabled('rss')) {
			if (getOption('RSS_album_image')) {
				printRSSHeaderLink('Gallery', gettext('Latest Images')) . "\n";
			}
			if (getOption('RSS_album_image')) {
				printRSSHeaderLink('AlbumsRSS', gettext('Latest Albums')) . "\n";
			}
			if ($zenpage) {
				if (getOption('RSS_articles')) {
					printRSSHeaderLink('News', '', NEWS_LABEL) . "\n";
				}
				if ((function_exists('printCommentForm')) && (getOption('RSS_article_comments'))) {
					printRSSHeaderLink('Comments-all', '', gettext('Latest Comments')) . "\n";
				}
			} else {
				if ((function_exists('printCommentForm')) && (getOption('RSS_comments'))) {
					printRSSHeaderLink('Comments', gettext('Latest Comments')) . "\n";
				}
			}
		}
		scriptLoader($_themeroot . '/js/zpskeleton.js');
		?>
		<meta name="description" content="<?php echo $zpskel_metadesc; ?>" />
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
		<?php
		scriptLoader($_themeroot . '/css/style.css');
		if (!$zpskel_ismobile) {
			scriptLoader($_themeroot . '/css/prettyPhoto.css');
			scriptLoader($_themeroot . '/js/jquery.prettyPhoto.min.js');
			?>
			<script type="text/javascript" charset="utf-8">
				$(document).ready(function () {
					$("a[rel^='slideshow']").prettyPhoto({
						slideshow: 5000, /* false OR interval time in ms */
						autoplay_slideshow: true, /* true/false */
						social_tools: false
					});
				});
			</script>
		<?php } ?>
		<?php if ($_gallery_page == "search.php") {
	printZDSearchToggleJS();
}
?>
		<link rel="shortcut icon" href="<?php echo $_themeroot; ?>/images/favicon.ico">
		<link rel="apple-touch-icon" href="<?php echo $_themeroot; ?>/images/apple-touch-icon.png">
		<link rel="apple-touch-icon" sizes="72x72" href="<?php echo $_themeroot; ?>/images/apple-touch-icon-72x72.png">
		<link rel="apple-touch-icon" sizes="114x114" href="<?php echo $_themeroot; ?>/images/apple-touch-icon-114x114.png">
		<?php if (getOption('zpskel_customcss') != null) { ?>
			<style>
	<?php echo getOption('zpskel_customcss'); ?>
			</style>
		<?php } ?>
	</head>
	<body>
		<?php npgFilters::apply('theme_body_open'); ?>
		<div class="wrapper topbar">
			<div class="container">
				<div class="sixteen columns">
					<h3 class="logo"><a href="<?php echo getGalleryIndexURL(); ?>"><?php printGalleryTitle(); ?></a></h3>
					<ul id="nav">
						<li class="menu">
							<a class="menu" href="#"><?php echo gettext('Menu'); ?></a>
							<ul class="menu-dropdown">
								<?php if (($zenpage) && ($zenpage_homepage != 'none')) { ?>
									<li <?php if ($_gallery_page == "index.php") { ?>class="active" <?php } ?>><a href="<?php echo html_encode(getGalleryIndexURL()); ?>" title="<?php echo gettext('Home'); ?>"><?php echo gettext('Home'); ?></a></li>
									<li <?php if ((!empty($galleryactive)) && ($_gallery_page != "index.php")) { ?>class="active" <?php } ?>><?php printCustomPageURL(gettext('Gallery'), "gallery"); ?></li>
								<?php } else { ?>
									<li <?php if (!empty($galleryactive)) { ?>class="active" <?php } ?>><a href="<?php echo html_encode(getGalleryIndexURL()); ?>" title="<?php echo gettext('Gallery'); ?>"><?php echo gettext('Gallery'); ?></a></li>
								<?php } ?>
									<?php if ($zpskel_archive) { ?><li <?php if ($_gallery_page == "archive.php") { ?>class="active" <?php } ?>><a href="<?php echo getCustomPageURL('archive'); ?>" title="<?php echo gettext('Archive View'); ?>"><?php echo gettext('Archive'); ?></a></li><?php } ?>
									<?php if (extensionEnabled('daily-summary')) { ?>
									<li <?php if ($_gallery_page == "summary.php") { ?>class="active" <?php } ?>>
										<a href="<?php echo getCustomPageURL('summary'); ?>"><?php echo gettext('Daily summary'); ?></a>
									</li>
								<?php } ?>
								<?php if ((function_exists('getNewsIndexURL')) && ($zpskel_usenews)) { ?>
									<li <?php if ($_gallery_page == "news.php") { ?>class="active" <?php } ?>>
										<a href="<?php echo getNewsIndexURL(); ?>"><?php echo NEWS_LABEL; ?></a>
									</li>
								<?php } ?>

								<?php if (function_exists('printContactForm')) { ?><li <?php if ($_gallery_page == "contact.php") { ?>class="active" <?php } ?>><?php printCustomPageURL(gettext('Contact'), "contact"); ?></li><?php } ?>
								<?php if (function_exists('printPageMenu')) { ?>
									<li class="divider"></li>
									<?php printPageMenu('list-top', '', 'active', '', 'active', '', true, false); ?>
								<?php } ?>
							</ul>

							<?php
							if (function_exists('printFavoritesURL')) {
								?>
								<ul class="menu-dropdown">
									<?php
									printFavoritesURL(NULL, '<li>', '</li><li>', '</li>');
									?>
								</ul>
								<?php
							}
							?>
						</li>
					</ul>
				</div>
			</div>
		</div>
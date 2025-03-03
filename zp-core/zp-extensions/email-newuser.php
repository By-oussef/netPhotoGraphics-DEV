<?php

/**
 * Sends new users an e-mail message urging the user to change his password.
 * It contains a link allowing him to do a password reset.
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/email-newuser
 * @pluginCategory users
 */
$plugin_is_filter = 5 | ADMIN_PLUGIN;
$plugin_description = gettext("Emails a password reset request to a newly created user.");


npgFilters::register('save_user_complete', 'email_new_user::save');
npgFilters::register('edit_admin_custom', 'email_new_user::edit_admin', 9999);

class email_new_user {

	static function save($savemsg, $userobj, $what) {
		global $_gallery;
		if ($what == 'new' && ($mail = $userobj->getEmail())) {
			$ref = npg_Authority::getResetTicket($adm = $userobj->getUser(), $userobj->getPass());
			$msg = "\n" . sprintf(gettext('You are receiving this e-mail because a user code (%1$s) has been created for you on the %2$s gallery.'), $adm, $_gallery->getTitle()) .
							"\n" . sprintf(gettext('To set your User password visit: %s'), getAdminLink('admin-tabs/users.php') . '?ticket=$ref&user=$adm') .
							"\n" . gettext("This ticket will automatically expire in 3 days.");
			$err_msg = npgFunctions::mail(gettext("User created"), $msg, array($mail));
			if (!empty($err_msg)) {
				$savemsg .= $err_msg;
			}
		}
		return $savemsg;
	}

	static function edit_admin($html, $userobj, $i, $background, $current) {
		if ($userobj->getValid()) {
			$user = $userobj->getUser();
			if (empty($user)) {
				$result = '<div class="user_left">' . "\n"
								. '<p class="notebox">'
								. gettext('New users will be mailed a password set link')
								. '</p>' . "\n"
								. '</div>' . "\n"
								. '<br class="clearall">' . "\n";
				$html = $result . $html;
			}
		}
		return $html;
	}

}

?>
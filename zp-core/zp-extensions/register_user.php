<?php
/**
 * Support for allowing visitors to register to access your site. Users registering
 * are verified via an e-mail to insure the validity of the e-mail address they provide.
 * Options are provided for setting the required registration details and the default
 * user rights that will be granted.
 *
 * Place a call on <var>printRegistrationForm()</var> where you want the form to appear.
 * Probably the best use is to create a new <i>custom page</i> script just for handling these
 * user registrations. Then put a link to that script on your index page so that people
 * who wish to register will click on the link and be taken to the registration page.
 *
 * When successfully registered, a new User will be created with no logon rights. An e-mail
 * will be sent to the user with a link to activate the user ID. When he clicks on that link
 * he will be taken to the registration page and the verification process will be completed.
 * At this point the user ID rights are set to the value of the plugin default user rights option
 * and an email is sent to the Gallery admin announcing the new registration.
 *
 * <b>NOTE:</b> If you change the rights of a user pending verification you have verified the user!
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/register_user
 * @pluginCategory users
 */
$plugin_is_filter = 5 | FEATURE_PLUGIN;
$plugin_description = gettext("Provides a means for placing a user registration form on your theme pages.");

$option_interface = 'register_user';

$_conf_vars['special_pages']['register_user'] = array('define' => '_REGISTER_USER_', 'rewrite' => getOption('register_user_link'),
		'option' => 'register_user_link', 'default' => '_PAGE_/register');
$_conf_vars['special_pages'][] = array('definition' => '%REGISTER_USER%', 'rewrite' => '_REGISTER_USER_');

$_conf_vars['special_pages'][] = array('rewrite' => '%REGISTER_USER%', 'rule' => '^%REWRITE%/*$		index.php?p=' . 'register' . ' [NC,L,QSA]');

/**
 * Plugin class
 *
 */
class register_user {

	function __construct() {
		global $_authority;
		if (OFFSET_PATH == 2) {
			setOptionDefault('register_user_page_tip', getAllTranslations('Click here to register for this site.'));
			setOptionDefault('register_user_page_link', getAllTranslations('Register'));
			setOptionDefault('register_user_captcha', 0);
			setOptionDefault('register_user_email_is_id', 1);
			setOptionDefault('register_user_create_album', 0);
			setOptionDefault('register_user_text', getAllTranslations('You have received this email because you registered with the user id %3$s on this site.' . "\n" . 'To complete your registration visit %1$s.'));
			setOptionDefault('register_user_accepted', getAllTranslations('Your registration information has been accepted. An email has been sent to you to verify your email address.'));
		}
	}

	function getOptionsSupported() {
		global $_authority, $_captcha;
		$options = array(
				gettext('Link text') => array('key' => 'register_user_page_link', 'type' => OPTION_TYPE_TEXTAREA,
						'order' => 2,
						'desc' => gettext('Default text for the register user link.')),
				gettext('Hint text') => array('key' => 'register_user_page_tip', 'type' => OPTION_TYPE_TEXTAREA,
						'order' => 2.5,
						'desc' => gettext('Default hint text for the register user link.')),
				gettext('Registration text') => array('key' => 'register_user_text', 'type' => OPTION_TYPE_TEXTAREA,
						'order' => 3,
						'desc' => gettext('Registration confirmation text.')),
				gettext('Accepted text') => array('key' => 'register_user_text', 'type' => OPTION_TYPE_TEXTAREA,
						'order' => 3.5,
						'desc' => gettext('Registration accepted text.')),
				gettext('User album') => array('key' => 'register_user_create_album', 'type' => OPTION_TYPE_CHECKBOX,
						'order' => 6,
						'desc' => gettext('If checked, an album will be created and assigned to the user.')),
				gettext('Email ID') => array('key' => 'register_user_email_is_id', 'type' => OPTION_TYPE_CHECKBOX,
						'order' => 4,
						'desc' => gettext('If checked, The use’s e-mail address will be used as his User ID.')),
				gettext('CAPTCHA') => array('key' => 'register_user_captcha', 'type' => OPTION_TYPE_CHECKBOX,
						'order' => 5,
						'desc' => ($_captcha->name) ? gettext('If checked, the form will include a Captcha verification.') : '<span class="notebox">' . gettext('No captcha handler is enabled.') . '</span>'),
		);
		if (extensionEnabled('userAddressFields')) {
			$options[gettext('Address fields')] = array('key' => 'register_user_address_info', 'type' => OPTION_TYPE_RADIO,
					'order' => 4.5,
					'buttons' => array(gettext('Omit') => 0, gettext('Show') => 1, gettext('Require') => 'required'),
					'desc' => gettext('If <em>Address fields</em> are shown or required, the form will include positions for address information. If required, the user must supply data in each address field.'));
		}

		if (class_exists('user_groups')) {
			$admins = $_authority->getAdministrators('groups');
			$defaultrights = ALL_RIGHTS;
			$ordered = array();
			foreach ($admins as $key => $admin) {
				$ordered[$admin['user']] = $admin['user'];
				if ($admin['rights'] < $defaultrights && $admin['rights'] >= NO_RIGHTS) {
					$nullselection = $admin['user'];
					$defaultrights = $admin['rights'];
				}
			}
			if (!empty($nullselection)) {
				if (is_numeric(getOption('register_user_user_rights'))) {
					setOption('register_user_user_rights', $nullselection);
				} else {
					setOptionDefault('register_user_user_rights', $nullselection);
				}
			}
			$options[gettext('Default user group')] = array('key' => 'register_user_user_rights', 'type' => OPTION_TYPE_SELECTOR,
					'order' => 1,
					'selections' => $ordered,
					'desc' => gettext("Initial group assignment for the new user."));
		} else {
			if (is_numeric(getOption('register_user_user_rights'))) {
				setOptionDefault('register_user_user_rights', NO_RIGHTS);
			} else {
				setOption('register_user_user_rights', NO_RIGHTS);
			}
			$options[gettext('Default rights')] = array('key' => 'register_user_user_rights', 'type' => OPTION_TYPE_CUSTOM,
					'order' => 1,
					'desc' => gettext("Initial rights for the new user. (If no rights are set, approval of the user will be required.)"));
		}
		return $options;
	}

	function handleOption($option, $currentValue) {
		global $_gallery;
		switch ($option) {
			case 'register_user_user_rights':
				printAdminRightsTable(0, '', '', getOption('register_user_user_rights'));
				break;
		}
	}

	static function handleOptionSave($themename, $themealbum) {
		if (isset($_POST['user'][0])) {
			setOption('register_user_user_rights', processRights(0));
		}
		return false;
	}

	/**
	 * Processes the post of an address
	 *
	 * @param int $i sequence number of the comment
	 * @return array
	 */
	static function getUserInfo($i) {
		$result = array();
		if (isset($_POST[$i . '-comment_form_website'])) {
					$result['website'] = sanitize($_POST[$i . '-comment_form_website'], 1);
		}
		if (isset($_POST[$i . '-comment_form_street'])) {
					$result['street'] = sanitize($_POST[$i . '-comment_form_street'], 1);
		}
		if (isset($_POST[$i . '-comment_form_city'])) {
					$result['city'] = sanitize($_POST[$i . '-comment_form_city'], 1);
		}
		if (isset($_POST[$i . '-comment_form_state'])) {
					$result['state'] = sanitize($_POST[$i . '-comment_form_state'], 1);
		}
		if (isset($_POST[$i . '-comment_form_country'])) {
					$result['country'] = sanitize($_POST[$i . '-comment_form_country'], 1);
		}
		if (isset($_POST[$i . '-comment_form_postal'])) {
					$result['postal'] = sanitize($_POST[$i . '-comment_form_postal'], 1);
		}
		return $result;
	}

	static function getLink() {
		return npgFilters::apply('getLink', rewrite_path(_REGISTER_USER_ . '/', '/index.php?p=register'), 'register.php', NULL);
	}

	static function post_processor() {
		global $admin_e, $admin_n, $user, $_authority, $_captcha, $_gallery, $_notify, $_link, $_message;
		//Handle registration
		if (isset($_POST['username']) && !empty($_POST['username'])) {
			$_notify = 'honeypot'; // honey pot check
		}
		if (getOption('register_user_captcha')) {
			if (isset($_POST['code'])) {
				$code = sanitize($_POST['code'], 3);
				$code_ok = sanitize($_POST['code_h'], 3);
			} else {
				$code = '';
				$code_ok = '';
			}
			if (!$_captcha->checkCaptcha($code, $code_ok)) {
				$_notify = 'invalidcaptcha';
			}
		}
		$admin_n = trim(sanitize($_POST['admin_name']));
		if (empty($admin_n)) {
			$_notify = 'incomplete';
		}
		if (isset($_POST['admin_email'])) {
			$admin_e = trim(sanitize($_POST['admin_email']));
		} else {
			$admin_e = trim(sanitize($_POST['user'], 0));
		}
		if (!npgFunctions::is_valid_email($admin_e)) {
			$_notify = 'invalidemail';
		}

		$pass = trim(sanitize($_POST['pass'], 0));
		$user = trim(sanitize($_POST['user'], 0));
		if (empty($pass)) {
			$_notify = 'empty';
		} else if (!empty($user) && !(empty($admin_n)) && !empty($admin_e)) {
			if (isset($_POST['disclose_password']) || $pass == trim(sanitize(@$_POST['pass_r']))) {
				$currentadmin = $_authority->getAnAdmin(array('`user`=' => $user, '`valid`>' => 0));
				if (is_object($currentadmin)) {
					$_notify = 'exists';
				} else {
					if ($_authority->getAnAdmin(array('`email`=' => $admin_e, '`valid`=' => '1'))) {
						$_notify = 'dup_email';
					}
				}
				if (empty($_notify)) {
					$userobj = $_authority->newAdministrator('');
					$userobj->transient = false;
					$userobj->setUser($user);
					$userobj->setPass($pass);
					$userobj->setName($admin_n);
					$userobj->setEmail($admin_e);
					$userobj->setRights(0);
					$userobj->setObjects(NULL);
					$userobj->setGroup('');
					$userobj->setLanguage(i18n::getUserLocale());
					if (extensionEnabled('userAddressFields')) {
						$addresses = getOption('register_user_address_info');
						$userinfo = register_user::getUserInfo(0);
						$_comment_form_save_post = serialize($userinfo);
						if ($addresses == 'required') {
							if (!isset($userinfo['street']) || empty($userinfo['street'])) {
								$userobj->transient = true;
								$userobj->msg .= ' ' . gettext('You must supply the street field.');
							}
							if (!isset($userinfo['city']) || empty($userinfo['city'])) {
								$userobj->transient = true;
								$userobj->msg .= ' ' . gettext('You must supply the city field.');
							}
							if (!isset($userinfo['state']) || empty($userinfo['state'])) {
								$userobj->transient = true;
								$userobj->msg .= ' ' . gettext('You must supply the state field.');
							}
							if (!isset($userinfo['country']) || empty($userinfo['country'])) {
								$userobj->transient = true;
								$userobj->msg .= ' ' . gettext('You must supply the country field.');
							}
							if (!isset($userinfo['postal']) || empty($userinfo['postal'])) {
								$userobj->transient = true;
								$userobj->msg .= ' ' . gettext('You must supply the postal code field.');
							}
						}
						setNPGCookie('reister_user_form_addresses', $_comment_form_save_post, false);
						userAddressFields::setCustomDataset($userobj, $userinfo);
					}

					npgFilters::apply('register_user_registered', $userobj);
					if ($userobj->transient) {
						if (empty($_notify)) {
							$_notify = 'filter';
						}
					} else {
						recordPolicyACK($userobj);
						$userobj->save();
						if (MOD_REWRITE) {
							$verify = '?verify=';
						} else {
							$verify = '&verify=';
						}
						$_link = FULLHOSTPATH . register_user::getLink() . $verify . bin2hex(serialize(array('user' => $user, 'email' => $admin_e)));
						$_message = sprintf(get_language_string(getOption('register_user_text')), $_link, $admin_n, $user, $pass);
						$_notify = npgFunctions::mail(get_language_string(gettext('Registration confirmation')), $_message, array($user => $admin_e));
						if (empty($_notify)) {
							$_notify = 'accepted';
						}
					}
				}
			} else {
				$_notify = 'mismatch';
			}
		} else {
			$_notify = 'incomplete';
		}
	}

}

/**
 * Parses the verification and registration if they have occurred
 * places the user registration form
 *
 * @param string $thanks the message shown on successful registration
 */
function printRegistrationForm($thanks = NULL) {
	global $admin_e, $admin_n, $user, $_authority, $_captcha, $_gallery, $_notify, $_link, $_message;
	require_once(CORE_SERVERPATH . 'admin-functions.php');
	$userobj = NULL;
	// handle any postings
	if (isset($_GET['verify'])) {
		$currentadmins = $_authority->getAdministrators();
		$params = unserialize(pack("H*", trim(sanitize($_GET['verify']), '.')));
		// expung the verify query string as it will cause us to come back here if login fails.
		unset($_GET['verify']);
		$_link = explode('?', getRequestURI());
		$p = array();
		if (isset($_link[1])) {
			$p = explode('&', $_link[1]);
			foreach ($p as $k => $v) {
				if (strpos($v, 'verify=') === 0) {
					unset($p[$k]);
				}
			}
			unset($p['verify']);
		}
		$_SERVER['REQUEST_URI'] = $_link[0];
		if (!empty($p)) {
			$_SERVER['REQUEST_URI'] .= '?' . implode('&', $p);
		}

		$userobj = $_authority->getAnAdmin(array('`user`=' => $params['user'], '`valid`=' => 1));
		if ($userobj && $userobj->getEmail() == $params['email']) {
			if (!$userobj->getRights()) {
				$userobj->setCredentials(array('registered', 'user', 'email'));
				$rights = getOption('register_user_user_rights');
				$group = NULL;
				if (!is_numeric($rights)) { //  a group or template
					$admin = $_authority->getAnAdmin(array('`user`=' => $rights, '`valid`=' => 0));
					if ($admin) {
						$userobj->setObjects($admin->getObjects());
						if ($admin->getName() != 'template') {
							$group = $rights;
						}
						$rights = $admin->getRights();
					} else {
						$rights = NO_RIGHTS;
					}
				}
				$userobj->setRights($rights | NO_RIGHTS);
				$userobj->setGroup($group);
				npgFilters::apply('register_user_verified', $userobj);
				if (getOption('register_user_notify')) {
					$_notify = npgFunctions::mail(gettext('netPhotoGraphics Gallery registration'), sprintf(gettext('%1$s (%2$s) has registered for the gallery providing an e-mail address of %3$s.'), $userobj->getName(), $userobj->getUser(), $userobj->getEmail()));
				}
				if (empty($_notify)) {
					if (getOption('register_user_create_album')) {
						$userobj->createPrimealbum();
					}
					$_notify = 'verified';
					$_POST['user'] = $userobj->getUser();
				}
				$userobj->save();
			} else {
				$_notify = 'already_verified';
			}
		} else {
			$_notify = 'not_verified'; // User ID no longer exists
		}
	}

	if (isset($_GET['login'])) { //presumably the user failed to login....
		$_notify = 'loginfailed';
	}

	if (npg_loggedin()) {
		if (isset($_GET['login'])) {
			echo '<meta http-equiv="refresh" content="1; url=' . WEBPATH . '/">';
		} else {
			echo '<div class="errorbox fade-message">';
			echo '<h2>' . gettext("you are already logged in.") . '</h2>';
			echo '</div>';
		}
		return;
	}
	if (isset($_GET['login'])) { //presumably the user failed to login....
		$_notify = 'loginfailed';
	}
	if (!empty($_notify)) {
		switch ($_notify) {
			case'verified':
				if (is_null($thanks)) {
									$thanks = gettext("Thank you for registering.");
				}
				?>
				<div class="messagebox fade-message">
					<p><?php echo $thanks; ?></p>
					<p><?php echo gettext('You may now log onto the site and verify your personal information.'); ?></p>
				</div>
			<?php
			case 'already_verified':
			case 'loginfailed':
				$_link = getRequestURI();
				if (strpos($_link, '?') === false) {
					$_SERVER['REQUEST_URI'] = $_link . '?login=true';
				} else {
					$_SERVER['REQUEST_URI'] = $_link . '&login=true';
				}
				require_once(CORE_SERVERPATH . PLUGIN_FOLDER . '/user_login-out.php');
				printPasswordForm(NULL, true, false, FULLWEBPATH);
				$_notify = 'success';
				break;
			case 'honeypot': //pretend it was accepted
			case 'accepted':
				?>
				<div class="messagebox fade-message">
					<p><?php echo gettext(get_language_string(getOption('register_user_accepted'))); ?></p>
				</div>
				<?php
				if ($_notify != 'honeypot') {
									$_notify = 'success';
				}
				// of course honeypot catches are no success!
				break;
			case 'exists':
				?>
				<div class="errorbox fade-message">
					<h2><?php echo gettext("Registration failed."); ?></h2>
					<p><?php printf(gettext('The user ID <em>%s</em> is already in use.'), $user); ?></p>
				</div>
				<?php
				break;
			case 'dup_email':
				?>
				<div class="errorbox fade-message">
					<h2><?php echo gettext("Registration failed."); ?></h2>
					<p><?php printf(gettext('A user with the e-mail <em>%s</em> already exists.'), $admin_e); ?></p>
				</div>
				<?php
				break;
			case 'empty':
				?>
				<div class="errorbox fade-message">
					<h2><?php echo gettext("Registration failed."); ?></h2>
					<p><?php echo gettext('Passwords may not be empty.'); ?></p>
				</div>
				<?php
				break;
			case 'mismatch':
				?>
				<div class="errorbox fade-message">
					<h2><?php echo gettext("Registration failed."); ?></h2>
					<p><?php echo gettext('Your passwords did not match.'); ?></p>
				</div>
				<?php
				break;
			case 'incomplete':
				?>
				<div class="errorbox fade-message">
					<h2><?php echo gettext("Registration failed."); ?></h2>
					<p><?php echo gettext('You have not filled in all the fields.'); ?></p>
				</div>
				<?php
				break;
			case 'notverified':
				?>
				<div class="errorbox fade-message">
					<h2><?php echo gettext("Registration failed."); ?></h2>
					<p><?php echo gettext('Invalid verification link.'); ?></p>
				</div>
				<?php
				break;
			case 'invalidemail':
				?>
				<div class="errorbox fade-message">
					<h2><?php echo gettext("Registration failed."); ?></h2>
					<p><?php echo gettext('Enter a valid email address.'); ?></p>
				</div>
				<?php
				break;
			case 'invalidcaptcha':
				?>
				<div class="errorbox fade-message">
					<h2><?php echo gettext("Registration failed."); ?></h2>
					<p><?php echo gettext('CAPTCHA verification failed.'); ?></p>
				</div>
				<?php
				break;
			case 'not_verified':
				?>
				<div class="errorbox fade-message">
					<h2><?php echo gettext("Verification failed."); ?></h2>
					<p><?php echo gettext('Your registration request could not be completed.'); ?></p>
				</div>
				<?php
				break;
			case 'filter':
				?>
				<div class="errorbox fade-message">
					<h2><?php echo gettext("Registration failed."); ?></h2>
					<p>
						<?php
						if (is_object($userobj) && !empty($userobj->msg)) {
							echo $userobj->msg;
						} else {
							echo gettext('Your registration attempt failed a <code>register_user_registered</code> filter check.');
						}
						?>
					</p>
				</div>
				<?php
				break;
			default:
				?>
				<div class="errorbox fade-message">
					<h2><?php echo gettext("Registration failed."); ?></h2>
					<p><?php echo $_notify; ?></p>
				</div>
				<?php
				break;
		}
	}
	if ($_notify != 'success') {
		$form = getPlugin('register_user/register_user_form.php', true);
		require_once($form);
	}
}

/**
 * prints the link to the register user page
 *
 * @param string $_linktext text for the link
 * @param string $prev text to insert before the URL
 * @param string $next text to follow the URL
 * @param string $class optional class
 */
function printRegisterURL($_linktext = NULL, $prev = '', $next = '', $class = NULL, $hint = NULL) {
	if (!npg_loggedin()) {
		if (!is_null($class)) {
			$class = 'class="' . $class . '"';
		}
		if (is_null($_linktext)) {
			$_linktext = get_language_string(getOption('register_user_page_link'));
		}
		if (is_null($hint)) {
			$hint = get_language_string(getOption('register_user_page_tip'));
		}
		echo $prev;
		?>
		<a href="<?php echo html_encode(register_user::getLink()); ?>"<?php echo $class; ?> title="<?php echo html_encode($hint); ?>" id="register_link"><?php echo $_linktext; ?> </a>
		<?php
		echo $next;
	}
}

if (isset($_POST['register_user'])) {
	npgFilters::register('load_theme_script', 'register_user::post_processor');
}
?>
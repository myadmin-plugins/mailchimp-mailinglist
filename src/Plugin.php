<?php

namespace Detain\MyAdminMailchimp;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class Plugin
 *
 * @package Detain\MyAdminMailchimp
 */
class Plugin {

	public static $name = 'Mailchimp Plugin';
	public static $description = 'Allows handling of Mailchimp emails and honeypots';
	public static $help = '';
	public static $type = 'plugin';

	/**
	 * Plugin constructor.
	 */
	public function __construct() {
	}

	/**
	 * @return array
	 */
	public static function getHooks() {
		return [
			'system.settings' => [__CLASS__, 'getSettings'],
			//'ui.menu' => [__CLASS__, 'getMenu'],
		];
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getMenu(GenericEvent $event) {
		$menu = $event->getSubject();
		if ($GLOBALS['tf']->ima == 'admin') {
			function_requirements('has_acl');
					if (has_acl('client_billing'))
							$menu->add_link('admin', 'choice=none.abuse_admin', '//my.interserver.net/bower_components/webhostinghub-glyphs-icons/icons/development-16/Black/icon-spam.png', 'Mailchimp');
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getRequirements(GenericEvent $event) {
		$loader = $event->getSubject();
		$loader->add_requirement('class.Mailchimp', '/../vendor/detain/myadmin-mailchimp-mailinglist/src/Mailchimp.php');
		$loader->add_requirement('deactivate_kcare', '/../vendor/detain/myadmin-mailchimp-mailinglist/src/abuse.inc.php');
		$loader->add_requirement('deactivate_abuse', '/../vendor/detain/myadmin-mailchimp-mailinglist/src/abuse.inc.php');
		$loader->add_requirement('get_abuse_licenses', '/../vendor/detain/myadmin-mailchimp-mailinglist/src/abuse.inc.php');
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getSettings(GenericEvent $event) {
		$settings = $event->getSubject();
		$settings->add_text_setting('Accounts', 'MailChimp', 'mailchimp_apiid', 'API ID', 'API ID', (defined('MAILCHIMP_APIID') ? MAILCHIMP_APIID : ''));
		$settings->add_text_setting('Accounts', 'MailChimp', 'mailchimp_listid', 'List ID', 'List ID', (defined('MAILCHIMP_LISTID') ? MAILCHIMP_LISTID : ''));
	}

}

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
			'account.activated' => [__CLASS__, 'doAccountActivated'],
			//'ui.menu' => [__CLASS__, 'getMenu'],
		];
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function doAccountActivated(GenericEvent $event) {
		$account = $event->getSubject();
		if (defined('MAILCHIMP_ENABLE') && MAILCHIMP_ENABLE == 1) {
			self::doSetup($account->getAccountId());
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getSettings(GenericEvent $event) {
		$settings = $event->getSubject();
		$settings->add_dropdown_setting('Accounts', 'MailChimp', 'mailchimp_enable', 'Enable MailChimp', 'Enable/Disable MailChimp Mailing on Account Signup', (defined('MAILCHIMP_ENABLE') ? MAILCHIMP_ENABLE : '0'), ['0', '1'], ['No', 'Yes']);
		$settings->add_text_setting('Accounts', 'MailChimp', 'mailchimp_apiid', 'API ID', 'API ID', (defined('MAILCHIMP_APIID') ? MAILCHIMP_APIID : ''));
		$settings->add_text_setting('Accounts', 'MailChimp', 'mailchimp_listid', 'List ID', 'List ID', (defined('MAILCHIMP_LISTID') ? MAILCHIMP_LISTID : ''));
	}

	/**
	 * @param int $custid
	 */
	public static function doSetup($custid) {
		myadmin_log('accounts', 'info', "mailchimp_setup($custid) Called", __LINE__, __FILE__);
		$module = get_module_name('default');
		$GLOBALS['tf']->accounts->set_db_module($module);
		$GLOBALS['tf']->history->set_db_module($module);
		$data = $GLOBALS['tf']->accounts->read($custid);
		$lid = $data['account_lid'];
		$contacts = [];
		list($first, $last) = explode(' ', $data['name']);
		/*
		$contact = array(
		'email' => $lid,
		'firstName' => $first,
		//			'lastName' =>  $last,
		//			'street' => $data['address'],
		//			'city' => $data['city'],
		//			'state' => mb_substr($data['state'], 0, 10),
		//			'postalCode' => $data['zip'],
		//			'phone' => $data['phone'],
		'status' => 'normal',
		);
		if (isset($data['company'])) {
		$contact['business'] = $data['company'];
		}
		$contacts[] = $contact;
		$json = json_encode($contacts);
		*/
		$merge_vars = ['FNAME' => $first,'LNAME' => $last,'GROUPINGS' => [['id' => 2249, 'groups' => 'Company News,Special Promotions,Network and Datacenter Updates']]];

		$MailChimp = new MailChimp(MAILCHIMP_APIID);
		$result = $MailChimp->post('lists/'.MAILCHIMP_LISTID.'/members', ['email_address' => $lid, 'status' => 'normal']);
		myadmin_log('mailchimp', 'info', 'mailchimp->post(lists/'.MAILCHIMP_LISTID.'/members, [email_address => '.$lid.', status => normal]) returned '.json_encode($result), __LINE__, __FILE__);
		$subscriber_hash = $MailChimp->subscriberHash($lid);
		$result = $MailChimp->patch('lists/'.MAILCHIMP_LISTID.'/members/'.$subscriber_hash, ['merge_fields' => $merge_vars]);
		myadmin_log('mailchimp', 'info', 'mailchimp->patch(lists/'.MAILCHIMP_LISTID.'/members/'.$subscriber_hash.', [merge_fields => $merge_vars]) returned '.json_encode($result), __LINE__, __FILE__);
	}
}

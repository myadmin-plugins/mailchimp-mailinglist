<?php

namespace Detain\MyAdminMailchimp;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class Plugin
 *
 * @package Detain\MyAdminMailchimp
 */
class Plugin
{
	public static $name = 'Mailchimp Plugin';
	public static $description = 'Allows handling of Mailchimp based Mailing List Subscriptions';
	public static $help = '';
	public static $type = 'plugin';

	/**
	 * Plugin constructor.
	 */
	public function __construct()
	{
	}

	/**
	 * @return array
	 */
	public static function getHooks()
	{
		return [
			'system.settings' => [__CLASS__, 'getSettings'],
			'account.activated' => [__CLASS__, 'doAccountActivated'],
			'mailinglist.subscribe' => [__CLASS__, 'doMailinglistSubscribe'],
			//'ui.menu' => [__CLASS__, 'getMenu'],
		];
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function doAccountActivated(GenericEvent $event)
	{
		$account = $event->getSubject();
		if (defined('MAILCHIMP_ENABLE') && MAILCHIMP_ENABLE == 1) {
			self::doSetup($account->getId());
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function doMailinglistSubscribe(GenericEvent $event)
	{
		$email = $event->getSubject();
		if (defined('MAILCHIMP_ENABLE') && MAILCHIMP_ENABLE == 1) {
			self::doEmailSetup($email);
		}
	}
	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
    public static function getSettings(GenericEvent $event)
    {
        /**
         * @var \MyAdmin\Settings $settings
         **/
        $settings = $event->getSubject();
		$settings->add_dropdown_setting(_('Accounts'), _('MailChimp'), 'mailchimp_enable', _('Enable MailChimp'), _('Enable/Disable MailChimp Mailing on Account Signup'), (defined('MAILCHIMP_ENABLE') ? MAILCHIMP_ENABLE : '0'), ['0', '1'], ['No', 'Yes']);
		$settings->add_text_setting(_('Accounts'), _('MailChimp'), 'mailchimp_apiid', _('API ID'), _('API ID'), (defined('MAILCHIMP_APIID') ? MAILCHIMP_APIID : ''));
		$settings->add_text_setting(_('Accounts'), _('MailChimp'), 'mailchimp_listid', _('List ID'), _('List ID'), (defined('MAILCHIMP_LISTID') ? MAILCHIMP_LISTID : ''));
	}

	/**
	 * @param $accountId
	 */
	public static function doSetup($accountId)
	{
		myadmin_log('accounts', 'info', "mailchimp_setup($accountId) Called", __LINE__, __FILE__);
		$module = get_module_name('default');
		$data = $GLOBALS['tf']->accounts->read($accountId);
		$email = $data['account_lid'];
		list($first, $last) = explode(' ', $data['name']);
		$merge_vars = [
			'FNAME' => $first,
			'LNAME' => $last
		];
		self::doEmailSetup($email, $merge_vars);
	}

	/**
	 * @param                  $email
	 * @param array|bool|false $params
	 */
	public static function doEmailSetup($email, $params = false)
	{
		myadmin_log('accounts', 'info', "mailchimp_setup($email) Called", __LINE__, __FILE__);
		$contacts = [];
		$merge_vars = [
			'GROUPINGS' => [
				[
					'id' => 2249,
					'groups' => 'Company News,Special Promotions,Network and Datacenter Updates'
				]
			]
		];
		if ($params !== false) {
			$merge_vars = array_merge($merge_vars, $params);
		}
		$MailChimp = new MailChimp(MAILCHIMP_APIID);
		$result = $MailChimp->post('lists/'.MAILCHIMP_LISTID.'/members', ['email_address' => $email, 'status' => 'normal']);
		myadmin_log('mailchimp', 'info', 'mailchimp->post(lists/'.MAILCHIMP_LISTID.'/members, [email_address => '.$email.', status => normal]) returned '.json_encode($result), __LINE__, __FILE__);
		$subscriber_hash = $MailChimp->subscriberHash($email);
		$result = $MailChimp->patch('lists/'.MAILCHIMP_LISTID.'/members/'.$subscriber_hash, ['merge_fields' => $merge_vars]);
		myadmin_log('mailchimp', 'info', 'mailchimp->patch(lists/'.MAILCHIMP_LISTID.'/members/'.$subscriber_hash.', [merge_fields => $merge_vars]) returned '.json_encode($result), __LINE__, __FILE__);
	}
}

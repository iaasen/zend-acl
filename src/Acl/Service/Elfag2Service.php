<?php
/**
 * Created by PhpStorm.
 * User: iaase
 * Date: 03.12.2017
 * Time: 18.52
 */

namespace Acl\Service;


use Acl\Model\Group;
use Acl\Model\User;
use Zend\Mail\Transport\Smtp AS TransportSmtp;
use Zend\Mail\Message AS MailMessage;

class Elfag2Service
{
	/** @var UserService */
	protected $userService;
	/** @var GroupTable */
	protected $groupTable;
	/** @var TransportSmtp */
	protected $mailTransport;

	public function __construct($userService, $groupTable, $mailTransport)
	{
		$this->userService = $userService;
		$this->groupTable = $groupTable;
		$this->mailTransport = $mailTransport;
	}

	/**
	 * @param \stdClass $tokenData
	 * @throws \Exception
	 */
	public function createUserIfNeeded(\stdClass $tokenData) {
		$user = $this->userService->getUserByIdentityAndLogintype($tokenData->user_email, 'elfag2');
		if(!$user) {
			/** @var User $user */
			$user = clone $this->userService->getUserObjectPrototype();
			$user->exchangeArray([
				'logintype' => 'elfag2',
				'username' => $tokenData->user_email,
				'name' => $tokenData->user_display_name,
				'email' => $tokenData->user_email,
				'email_confirmed' => true,
				'ludens_id' => $tokenData->user_id,

			]);
			//$user = $this->userService->getUserByUsername($user->username, 'elfag2');
		}
		$user->ludens_company = $tokenData->company;
		$this->userService->saveUser($user);
	}

	/**
	 * @param User $user
	 * @param Group $group
	 * @throws \Exception
	 */
	public function connectUserToGroup(User $user, Group $group) {
		// Give access
		$this->userService->addUserToGroup($user, $group);
		$user->setAccessLevel($group, 5);
		$this->userService->saveUserAccess($user, $group);

		// Update user
		$user->current_group = $group->group;
		$this->userService->saveUser($user);

		// Update group
		$group->ludens_id = $user->ludens_company->id;
		$this->groupTable->save($group);
	}

	/**
	 * @param User $user
	 * @throws \Exception
	 */
	public function createGroupFromUser(User $user) {
		// Create group
		/** @var Group $group */
		$group = clone $this->groupTable->getObjectPrototype();
		$group->exchangeArray([
			'group' => 'ludens-' . $user->ludens_company->id,
			'name' => $user->ludens_company->name,
			'ludens_id' => $user->ludens_company->id,
		]);
		$this->groupTable->save($group);

		// Give access
		$this->userService->addUserToGroup($user, $group);
		$user->setAccessLevel($group, 5);
		$this->userService->saveUserAccess($user, $group);

		// Update user
		$user->current_group = $group->group;
		$this->userService->saveUser($user);
	}

	public function sendEmailAboutMissingGroup(User $user) {
		$mail = new MailMessage();
		$mail->setEncoding("UTF-8");
		$mail->setFrom('reklamasjon@oppned.com');
		$mail->addTo('support@prosjektkalkulator.no');
		$mail->setSubject($user->name . ' mangler kobling til ' . $user->ludens_company->name);
		$mail->setBody(
			'Bruker:' . PHP_EOL .
			iconv('utf-8', 'iso8859-1', print_r($user->getArrayCopy(), true)) . PHP_EOL . PHP_EOL
		);
		$this->mailTransport->send($mail);
	}
}
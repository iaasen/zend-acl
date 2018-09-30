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
use Iaasen\Exception\NotFoundException;
use Iaasen\Messenger\Email;
use Iaasen\Messenger\EmailService;

class Elfag2Service
{
	/** @var UserService */
	protected $userService;
	/** @var GroupTable */
	protected $groupTable;
	/** @var EmailService */
	protected $emailService;

	public function __construct(
		UserService $userService,
		GroupTable $groupTable,
		EmailService $emailService
	)
	{
		$this->userService = $userService;
		$this->groupTable = $groupTable;
		$this->emailService = $emailService;
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
				'email' => $tokenData->user_email,
				'email_confirmed' => true,
			]);
		}
		$user->exchangeArray([
			'name' => $tokenData->user_display_name,
			'ludens_id' => $tokenData->user_id,
			'ludens_permissions' => $tokenData->permissions,
		]);
		$user->ludens_company = $tokenData->company;
		$this->userService->saveUser($user);
	}

	/**
	 * Connects group if it exists then updates access right
	 * @param User $user
	 * @param Group $group
	 * @throws \Exception
	 */
	public function connectUserToGroup(User $user, ?Group $group = null) {
		if(!$group) {
			if(isset($user->ludens_company->id))
				$group = $this->groupTable->getGroupByLudensId($user->ludens_company->id);
			if(!$group && isset($user->ludens_company->org_number)) {
				try {
					$group = $this->groupTable->getGroupByOrgNumber($user->ludens_company->org_number);
				}
				catch (NotFoundException $e) {};
			}
			if(!$group && isset($user->ludens_company->id)) {
				$group = $this->groupTable->getGroupByName('ludens-' . $user->ludens_company->id);
			}
		}

		if(in_array($user->username, ['ann-kristin@elfag.no', 'geir.syversen@onninen.com'])) {
			// Special access for system owners
			$group = $this->groupTable->getGroupByName('elfag');
			$this->userService->addUserToGroup($user, $group);
			$user->setAccessLevel($group, 3);
			$this->userService->saveUserAccess($user, $group);

			$group = $this->groupTable->getGroupByName('ingvar');
			$this->userService->addUserToGroup($user, $group);
			$user->setAccessLevel($group, 3);
			$this->userService->saveUserAccess($user, $group);

			$group = $this->groupTable->getGroupByName('global');
			$this->userService->addUserToGroup($user, $group);
			$user->setAccessLevel($group, 3);
			$this->userService->saveUserAccess($user, $group);
		}
		elseif($group) {
			// Give access
			$this->userService->addUserToGroup($user, $group);
			$user->setAccessLevel($group, ($user->ludens_permissions) ? 3 : 0);
			$this->userService->saveUserAccess($user, $group);

			// Update group
			$group->ludens_id = $user->ludens_company->id;
			$group->org_number = $user->ludens_company->org_number;
			$this->groupTable->save($group);
		}
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
			'org_number' => $user->ludens_company->org_number,
		]);
		$this->groupTable->save($group);

		// Give access
		$this->userService->addUserToGroup($user, $group);
		$this->connectUserToGroup($user, $group);

		// Update user
		$user->current_group = $group->group;
		$this->userService->saveUser($user);
	}

	public function sendEmailAboutMissingGroup(User $user) {
		$email = new Email();
		$email->setTo('support@prosjektkalkulator.no');
		$email->subject = $user->name . ' mangler kobling til ' . $user->ludens_company->name;
		$email->body =
			'Bruker:' . PHP_EOL .
			iconv('utf-8', 'iso8859-1', print_r($user->getArrayCopy(), true)) . PHP_EOL . PHP_EOL;

		$this->emailService->send($email);
	}
}
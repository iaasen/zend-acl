<?php
/**
 * Created by PhpStorm.
 * User: iaase
 * Date: 03.12.2017
 * Time: 18.52
 */

namespace Acl\Service;


use Acl\Model\User;

class Elfag2Service
{
	/** @var UserService */
	protected $userService;

	public function __construct($userService)
	{
		$this->userService = $userService;
	}

	/**
	 * @param \stdClass $tokenData
	 * @throws \Exception
	 */
	public function createUserIfNeeded(\stdClass $tokenData) {
		$user = $this->userService->getUserByUsername($tokenData->user_email, 'elfag2');
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
}
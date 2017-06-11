<?php
/**
 * Created by PhpStorm.
 * User: iaase
 * Date: 05.06.2017
 * Time: 22.47
 */

namespace Acl\Service;


use Zend\ServiceManager\ServiceManager;

class UserServiceFactory
{

	/**
	 * @param ServiceManager $sm
	 * @return UserService
	 */
	public function __invoke($sm)
	{
		$authService = $sm->get(AuthService::class);
		$userTable = $sm->get(UserTable::class);
		$groupTable = $sm->get(GroupTable::class);
		$accessTable = $sm->get(AccessTable::class);
		$request = $sm->get('Request');
		return new UserService(
			$authService,
			$userTable,
			$groupTable,
			$accessTable,
			$request
		);
	}

}
<?php
/**
 * User: ingvar
 * Date: 11.08.2016
 * Time: 19.04
 */

namespace Acl\Controller;


class UserControllerFactory
{

	/**
	 * @param \Zend\Mvc\Controller\ControllerManager $serviceLocator
	 * @return UserController
	 */
	public function __invoke($serviceLocator)
	{
	    $userTable = $serviceLocator->getServiceLocator()->get(\Acl\Service\UserTable::class);
		$currentUser = $userTable->getCurrentUser();
		$groupTable = $serviceLocator->getServiceLocator()->get(\Acl\Service\GroupTable::class);

		return new UserController($currentUser, $userTable, $groupTable);
	}

}
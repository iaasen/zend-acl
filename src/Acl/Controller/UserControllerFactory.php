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
		$currentUser = $serviceLocator->getServiceLocator()->get('UserTable')->getCurrentUser();
		$groupTable = $serviceLocator->getServiceLocator()->get('GroupTable');

		return new UserController($currentUser, $groupTable);
	}

}
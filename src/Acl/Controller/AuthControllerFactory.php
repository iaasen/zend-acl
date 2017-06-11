<?php
/**
 * User: ingvar
 * Date: 28.11.2016
 * Time: 20.59
 */

namespace Acl\Controller;


use Zend\Mvc\Controller\ControllerManager;

class AuthControllerFactory
{
	/**
	 * @param ControllerManager $controllerManager
	 * @return AuthController
	 */
	public function __invoke($controllerManager)
	{
		$sm = $controllerManager->getServiceLocator();
		$authService = $sm->get(\Acl\Service\AuthService::class);
		$loginForm = $sm->get(\Acl\Form\LoginForm::class);
		$userTable = $sm->get(\Acl\Service\UserTable::class);
		$userService = $sm->get(\Acl\Service\UserService::class);
		$sessionStorage = $sm->get(\Acl\Model\AclStorage::class);
		return new AuthController($authService, $loginForm, $userService, $userTable, $sessionStorage);
	}

}
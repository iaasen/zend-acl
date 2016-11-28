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
		$authService = $controllerManager->getServiceLocator()->get(\Acl\Service\AuthService::class);
		$loginForm = $controllerManager->getServiceLocator()->get(\Acl\Form\LoginForm::class);
		$userTable = $controllerManager->getServiceLocator()->get('Acl\Service\UserTable');
		$sessionStorage = $controllerManager->getServiceLocator()->get(\Acl\Model\AclStorage::class);
		return new AuthController($authService, $loginForm, $userTable, $sessionStorage);
	}

}
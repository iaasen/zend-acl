<?php
/**
 * User: ingvar
 * Date: 27.11.2016
 * Time: 16.36
 */

namespace Acl\Service;


use Acl\Model\AuthElfagAdapter;
use Zend\ServiceManager\ServiceManager;

class AuthServiceFactory
{
	/**
	 * @param ServiceManager $serviceManager
	 * @return AuthService
	 */
	public function __invoke($serviceManager)
	{
		$authLocal = $serviceManager->get('Acl\AuthLocalAdapter');
		$authElfag = new AuthElfagAdapter();

		$authService = new AuthService();
		$authService->addAdapter($authLocal);
		$authService->addAdapter($authElfag);
		$authService->setStorage($serviceManager->get('Acl\Model\AclStorage'));
		//$authService->setEventManager($serviceManager->get('EventManager'));
		return $authService;
	}

}
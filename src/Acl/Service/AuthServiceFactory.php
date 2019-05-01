<?php
/**
 * User: ingvar
 * Date: 27.11.2016
 * Time: 16.36
 */

namespace Acl\Service;


use Zend\ServiceManager\ServiceManager;

class AuthServiceFactory
{
	/**
	 * @param ServiceManager $serviceManager
	 * @return AuthService
	 */
	public function __invoke($serviceManager)
	{
		$authLocal = $serviceManager->get(\Acl\Adapter\AuthLocalAdapter::class);
		$authElfag2 = $serviceManager->get(\Acl\Adapter\AuthElfag2Adapter::class);
		//$authElfag = $serviceManager->get(\Acl\Adapter\AuthElfagAdapter::class);

		$authService = new AuthService();
		$authService->addAdapter($authLocal);
		$authService->addAdapter($authElfag2);
		//$authService->addAdapter($authElfag);
		$authService->setStorage($serviceManager->get('Acl\Model\AclStorage'));
		//$authService->setEventManager($serviceManager->get('EventManager'));
		return $authService;
	}

}
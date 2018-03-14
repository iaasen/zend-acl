<?php
/**
 * User: ingvar
 * Date: 11.08.2016
 * Time: 19.04
 */

namespace Acl\Controller;


use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class UserControllerFactory implements FactoryInterface
{

	/**
	 * Create an object
	 *
	 * @param  ContainerInterface $container
	 * @param  string $requestedName
	 * @param  null|array $options
	 * @return UserController
	 * @throws \Psr\Container\ContainerExceptionInterface
	 * @throws \Psr\Container\NotFoundExceptionInterface
	 */
	public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
	{
		if(!$container instanceof ContainerInterface) $container = $container->getServiceLocator();

		$userTable = $container->get(\Acl\Service\UserTable::class);
		$groupTable = $container->get(\Acl\Service\GroupTable::class);
		$userService = $container->get(\Acl\Service\UserService::class);
		$elfag2Service = $container->get(\Acl\Service\Elfag2Service::class);
		$currentUser = $userService->getCurrentUser();
		$authLocalAdapter = $container->get(\Acl\Adapter\AuthLocalAdapter::class);

		return new UserController($currentUser, $userTable, $groupTable, $userService, $elfag2Service, $authLocalAdapter);

	}
}
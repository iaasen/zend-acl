<?php
/**
 * User: ingvar
 * Date: 11.08.2016
 * Time: 19.04
 */

namespace Acl\Controller;


use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;

class UserControllerFactory implements FactoryInterface
{

//	/**
//	 * @param \Zend\Mvc\Controller\ControllerManager $serviceLocator
//	 * @return UserController
//	 */
//	public function __invoke($serviceLocator)
//	{
//	}

	/**
	 * Create an object
	 *
	 * @param  ContainerInterface $container
	 * @param  string $requestedName
	 * @param  null|array $options
	 * @return UserController
	 * @throws ServiceNotFoundException if unable to resolve the service.
	 * @throws ServiceNotCreatedException if an exception is raised when
	 *     creating a service.
	 * @throws ContainerException if any other error occurs
	 */
	public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
	{
		if(!$container instanceof ContainerInterface) $container = $container->getServiceLocator();

		$userTable = $container->get(\Acl\Service\UserTable::class);
		$groupTable = $container->get(\Acl\Service\GroupTable::class);
		$userService = $container->get(\Acl\Service\UserService::class);
		$currentUser = $userService->getCurrentUser();

		return new UserController($currentUser, $userTable, $groupTable, $userService);

	}
}
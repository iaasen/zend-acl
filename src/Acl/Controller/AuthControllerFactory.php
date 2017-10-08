<?php
/**
 * User: ingvar
 * Date: 28.11.2016
 * Time: 20.59
 */

namespace Acl\Controller;


use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Zend\Mvc\Controller\ControllerManager;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;

class AuthControllerFactory implements FactoryInterface
{

//	/**
//	 * @param ControllerManager $controllerManager
//	 * @return AuthController
//	 */
//	public function __invoke($controllerManager)
//	{
//	}

	/**
	 * Create an object
	 *
	 * @param  ContainerInterface $container
	 * @param  string $requestedName
	 * @param  null|array $options
	 * @return object
	 * @throws ServiceNotFoundException if unable to resolve the service.
	 * @throws ServiceNotCreatedException if an exception is raised when
	 *     creating a service.
	 * @throws ContainerException if any other error occurs
	 */
	public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
	{
		if($container instanceof ControllerManager) $container = $container->getServiceLocator();

		$authService = $container->get(\Acl\Service\AuthService::class);
		$loginForm = $container->get(\Acl\Form\LoginForm::class);
		$userTable = $container->get(\Acl\Service\UserTable::class);
		$userService = $container->get(\Acl\Service\UserService::class);
		$sessionStorage = $container->get(\Acl\Model\AclStorage::class);
		return new AuthController($authService, $loginForm, $userService, $userTable, $sessionStorage);

	}
}
<?php
/**
 * User: ingvar
 * Date: 28.11.2016
 * Time: 20.59
 */

namespace Acl\Controller;


use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class AuthControllerFactory implements FactoryInterface
{
	/**
	 * @param ContainerInterface $container
	 * @param string $requestedName
	 * @param array|null $options
	 * @return AuthController
	 */
	public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
	{
		$authService = $container->get(\Acl\Service\AuthService::class);
		$loginForm = $container->get(\Acl\Form\LoginForm::class);
		$userTable = $container->get(\Acl\Service\UserTable::class);
		$userService = $container->get(\Acl\Service\UserService::class);
		$sessionStorage = $container->get(\Acl\Model\AclStorage::class);
		$elfag2Service = $container->get(\Acl\Service\Elfag2Service::class);

		return new AuthController(
			$authService,
			$loginForm,
			$userService,
			$userTable,
			$sessionStorage,
			$elfag2Service
		);

	}
}
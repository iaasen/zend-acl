<?php
/**
 * Created by PhpStorm.
 * User: iaase
 * Date: 09.06.2018
 * Time: 21:15
 */

namespace Acl\Model;


use Acl\Service\UserService;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class CurrentUserFactory implements FactoryInterface
{
	/**
	 * @param ContainerInterface $container
	 * @param string $requestedName
	 * @param array|null $options
	 * @return CurrentUser
	 */
	public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
	{
		/** @var UserService $userService */
		$userService = $container->get(UserService::class);
		return $userService->getCurrentUser();

	}
}
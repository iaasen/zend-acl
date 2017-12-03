<?php
/**
 * Created by PhpStorm.
 * User: iaase
 * Date: 03.12.2017
 * Time: 18.53
 */

namespace Acl\Service;


use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class Elfag2ServiceFactory implements FactoryInterface
{

	/**
	 * Create an object
	 *
	 * @param  ContainerInterface $container
	 * @param  string $requestedName
	 * @param  null|array $options
	 * @return Elfag2Service
	 * @throws \Psr\Container\ContainerExceptionInterface
	 * @throws \Psr\Container\NotFoundExceptionInterface
	 */
	public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
	{
		$userService = $container->get(UserService::class);
		return new Elfag2Service($userService);
	}
}
<?php
/**
 * Created by PhpStorm.
 * User: iaase
 * Date: 11.06.2017
 * Time: 20.52
 */

namespace Acl\View\Helper;


use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\View\HelperPluginManager;

class GroupSelectionWidgetFactory implements FactoryInterface
{
//	/**
//	 * @param \Laminas\View\HelperPluginManager $helperManager
//	 * @return GroupSelectionWidget
//	 */
//	public function __invoke($helperManager)
//	{
//	}

	/**
	 * Create an object
	 *
	 * @param  ContainerInterface $container
	 * @param  string $requestedName
	 * @param  null|array $options
	 * @return GroupSelectionWidget
	 * @throws ServiceNotFoundException if unable to resolve the service.
	 * @throws ServiceNotCreatedException if an exception is raised when
	 *     creating a service.
	 * @throws ContainerException if any other error occurs
	 */
	public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
	{
		if($container instanceof HelperPluginManager) $container = $container->getServiceLocator();
		$userService = $container->get(\Acl\Service\UserService::class);

		return new GroupSelectionWidget($userService);

	}
}
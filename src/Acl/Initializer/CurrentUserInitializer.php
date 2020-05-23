<?php
/**
 * Created by PhpStorm.
 * User: iaase
 * Date: 09.06.2018
 * Time: 17:06
 */

namespace Acl\Initializer;


use Acl\Service\UserService;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Initializer\InitializerInterface;

class CurrentUserInitializer implements InitializerInterface
{

	/**
	 * Initialize the given instance
	 *
	 * @param  ContainerInterface $container
	 * @param  object $instance
	 * @return void
	 */
	public function __invoke(ContainerInterface $container, $instance)
	{
		if($instance instanceof CurrentUserAwareInterface) {
			$currentUser = $container->get(UserService::class)->getCurrentUser();
			$instance->setCurrentUser($currentUser);
		}
	}
}
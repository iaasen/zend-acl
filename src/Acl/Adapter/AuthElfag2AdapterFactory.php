<?php
/**
 * Created by PhpStorm.
 * User: iaase
 * Date: 06.11.2017
 * Time: 22.42
 */

namespace Acl\Adapter;


use Laminas\ServiceManager\ServiceManager;

class AuthElfag2AdapterFactory
{
	/**
	 * @param ServiceManager $container
	 * @return AuthElfag2Adapter
	 */
	public function __invoke($container)
	{
		$transport = $container->get(\Acl\Service\Elfag2Transport::class);
		return new AuthElfag2Adapter($transport);
	}

}
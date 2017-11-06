<?php
/**
 * Created by PhpStorm.
 * User: iaase
 * Date: 06.11.2017
 * Time: 22.35
 */

namespace Acl\Adapter;


use Zend\ServiceManager\ServiceManager;

class AuthLocalAdapterFactory
{
	/**
	 * @param ServiceManager $container
	 * @return AuthLocalAdapter
	 */
	public function __invoke($container)
	{
		$dbAdapter = $container->get('Db\Acl');
		return new AuthLocalAdapter($dbAdapter, 'users', 'username', 'password');
	}

}
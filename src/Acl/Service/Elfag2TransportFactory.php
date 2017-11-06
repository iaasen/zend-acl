<?php
/**
 * Created by PhpStorm.
 * User: iaase
 * Date: 06.11.2017
 * Time: 22.50
 */

namespace Acl\Service;


use Zend\ServiceManager\ServiceManager;

class Elfag2TransportFactory
{
	/**
	 * @param ServiceManager $container
	 * @return Elfag2Transport
	 */
	public function __invoke($container)
	{
		$config = $container->get('Config')['elfag2'];
		return new Elfag2Transport($config);
	}
}
<?php
/**
 * User: ingvar
 * Date: 28.11.2016
 * Time: 21.33
 */

namespace Acl\Model;


use Zend\ServiceManager\ServiceManager;

class AclStorageFactory
{
	/**
	 * @param ServiceManager $serviceManager
	 * @return AclStorage
	 */
	public function __invoke($serviceManager)
	{
		return new AclStorage('acl_auth');
	}

}
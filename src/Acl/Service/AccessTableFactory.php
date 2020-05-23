<?php
/**
 * Created by PhpStorm.
 * User: iaase
 * Date: 06.06.2017
 * Time: 00.02
 */

namespace Acl\Service;


use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\ServiceManager\ServiceManager;

class AccessTableFactory
{
	/**
	 * @param ServiceManager $sm
	 * @return AccessTable
	 */
	public function __invoke($sm)
	{
		/** @var \Laminas\Db\Adapter\Adapter $dbAdapter */
		$dbAdapter = $sm->get('Db\Acl');
		$resultSetPrototype = new ResultSet();
		$resultSetPrototype->setArrayObjectPrototype($sm->get(\Acl\Model\Access::class));
		$primaryGateway = new TableGateway('users_has_groups', $dbAdapter, null, $resultSetPrototype);
		return new AccessTable(null, $primaryGateway);
	}

}
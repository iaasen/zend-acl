<?php
/**
 * User: ingvar
 * Date: 28.11.2016
 * Time: 21.23
 */

namespace Acl\Service;


use Acl\Model\User;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\Feature\RowGatewayFeature;
use Zend\Db\TableGateway\TableGateway;
use Zend\ServiceManager\ServiceManager;

class UserTableFactory
{
	/**
	 * @param ServiceManager $serviceManager
	 * @return UserTable
	 */
	public function __invoke($serviceManager)
	{
		$dbAdapter = $serviceManager->get('Db\Acl');
		$resultSetPrototype = new ResultSet();
		$resultSetPrototype->setArrayObjectPrototype($serviceManager->get(\Acl\Model\User::class));
		//$primaryGateway = new TableGateway('users', $dbAdapter, new RowGatewayFeature('id'), $resultSetPrototype);
		$primaryGateway = new TableGateway('users', $dbAdapter, null, $resultSetPrototype);
		return new UserTable($primaryGateway);
	}

}

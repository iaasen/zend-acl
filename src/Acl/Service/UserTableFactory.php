<?php
/**
 * User: ingvar
 * Date: 28.11.2016
 * Time: 21.23
 */

namespace Acl\Service;


use Zend\Db\ResultSet\ResultSet;
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
		/** @var \Zend\Db\Adapter\Adapter $dbAdapter */
		$dbAdapter = $serviceManager->get('Db\Acl');
		$resultSetPrototype = new ResultSet();
		$userPrototype = $serviceManager->get(\Acl\Model\User::class);
		$resultSetPrototype->setArrayObjectPrototype($userPrototype);
		//$primaryGateway = new TableGateway('users', $dbAdapter, new RowGatewayFeature('id'), $resultSetPrototype);
		$primaryGateway = new TableGateway('users', $dbAdapter, null, $resultSetPrototype);

		$groupTable = $serviceManager->get(\Acl\Service\GroupTable::class);
		$authService = $serviceManager->get(\Acl\Service\AuthService::class);

		return new UserTable($primaryGateway, $groupTable, $authService);
	}

}

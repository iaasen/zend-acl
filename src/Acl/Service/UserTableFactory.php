<?php
/**
 * User: ingvar
 * Date: 28.11.2016
 * Time: 21.23
 */

namespace Acl\Service;


use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\ServiceManager\ServiceManager;

class UserTableFactory
{
	/**
	 * @param ServiceManager $serviceManager
	 * @return UserTable
	 */
	public function __invoke($serviceManager)
	{
		/** @var \Laminas\Db\Adapter\Adapter $dbAdapter */
		$dbAdapter = $serviceManager->get('Db\Acl');
		$resultSetPrototype = new ResultSet();
		$userPrototype = $serviceManager->get(\Acl\Model\User::class);
		$resultSetPrototype->setArrayObjectPrototype($userPrototype);
		//$primaryGateway = new TableGateway('users', $dbAdapter, new RowGatewayFeature('id'), $resultSetPrototype);
		$primaryGateway = new TableGateway('users', $dbAdapter, null, $resultSetPrototype);

		//$groupTable = $serviceManager->get(\Acl\Service\GroupTable::class);
		$authService = $serviceManager->get(\Acl\Service\AuthService::class);

		//$request = $serviceManager->get('Request');

		return new UserTable(null, $primaryGateway, $authService);
	}

}

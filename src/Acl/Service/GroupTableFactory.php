<?php
/**
 * User: Ingvar
 * Date: 17.04.2016
 * Time: 20.04
 */

namespace Acl\Service;

use Acl\Service\GroupTable;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\ServiceManager\ServiceManager;

class GroupTableFactory
{
	/**
	 * @param ServiceManager $serviceManager
	 * @return GroupTable
	 */
	public function __invoke($serviceManager)
	{
		$dbAdapter = $serviceManager->get('Db\Acl');
		$resultSetPrototype = new ResultSet();
		$resultSetPrototype->setArrayObjectPrototype($serviceManager->get(\Acl\Model\Group::class));
		$primaryGateway = new TableGateway('groups', $dbAdapter, null, $resultSetPrototype);
		$authService = $serviceManager->get(\Acl\Service\AuthService::class);
		//$userTable = $serviceManager->get(\Acl\Service\UserTable::class);
		//$currentUser = $userTable->getCurrentUser;
		return new GroupTable(null, $primaryGateway, ['authService' => $authService]);
	}
}
<?php

namespace Acl\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Sql\Select;
use General\Message;

class ClientTable extends DbTable {
	//protected $serviceLocator;
	protected static $currentUser;
	protected $tableGateway;
	protected $groupTable;

	public function __construct(TableGateway $_tableGateway) {
		$this->tableGateway = $_tableGateway;
	}

// 	public function fetchAll() {
// 		$resultSet = $this->tableGateway->select();
// 		return $resultSet;
// 	}

	public function find($id) {
		return $this->getUser((int) $id);
	}
	
	
	/**
	 * Get all users the current user has admin rights to
	 */
	public function getUsers() {
		$groups = $this->groupTable->getGroupIds();
		$groups = implode(', ', $groups);
		$groups = rtrim($groups, ', ');
		
		//$select = new Select();
		$rowSet = $this->tableGateway->select(
			function(Select $select) use ($groups) {
				$select->join('users_has_groups', 'users_has_groups.users_id = users.id');
				$select->where("users_has_groups.groups_id IN($groups)");
				$select->group('id');
				//echo $select->getSqlString(new \Zend\Db\Adapter\Platform\Mysql());
			}
		);

		$users = array();
		for($i = 0; $i < $rowSet->count(); $i++) {
			$users[] = $rowSet->current();
			$rowSet->next();
		}

		for($i = 0; $i < count($users); $i++) {
			$this->getUserAccess($users[$i]);
		}
		
		return $users;
	}
	


	/**
	 * @param mixed $user
	 * @throws \Exception
	 * @return User $user
	 * 
	 * Give clint_id or id to retrieve user from database.
	 */
	public function getClient($client) {
		if(!strlen($client)) {
			return false;
		}
		elseif(is_int($client)) {
			$rowSet = parent::find($client);
			$user = $rowSet;
		}
		else {
			$rowSet = $this->fetchAll(array('client_id' => $client));
			
			if(count($rowSet) > 1)
				throw new \Exception('Multiple clients with same client_id. Something is wrong.');
				
			if(count($rowSet) == 0) {
				Message::create(3, 'Client not found');
				return false;
			}
				
			$user = $rowSet[0];
		}
		
		$this->getUserAccess($client);
		
		return $user;
	}
	
	public function save($client) {
		if($this->accessToSave($client) == false) return false;
		
		$data = $client->databaseSaveArray();
		
		unset($data->id);
		unset($data->timestamp_created);
		
		$id = (int) $client->id;
		if ($id == 0) {
			$this->tableGateway->insert($data);
			$id = (int) $this->tableGateway->getLastInsertValue();
		}
		else {
			if ($this->find($id)) {
				$this->tableGateway->update($data, array (
					'id' => $id,
				));
			}
			else {
				throw new \Exception('id does not exist');
			}
		}
		return $id;
	}
	
	/**
	 * Not implemented
	 * 
	 * @param Client $client
	 * @return boolean
	 */
	public function accessToSave($client) {
		return false;
	}

	public function getAuthService() {
		return $this->serviceLocator->get('AuthService');
	}
	
// 	public function getGroupTable() {
// 		return $this->groupTable;
// 	}
	
// 	public function setGroupTable($table) {
// 		$this->groupTable = $table;
// 	}
}
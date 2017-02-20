<?php

namespace Acl\Service;

use Acl\Model\User;
use Acl\Model\Group;

class GroupTable extends DbTable {

	public function find($id) {
		return $this->getGroup((int) $id);
	}
	
	
	
	/**
	 * Get available groups to current user.
	 * 
	 * @return Group[]
	 */
	public function getGroups($all = false) {
		// Get Username
		$currentUser = $this->getAuthService()->getIdentity();
		// Setup SQL statement
		$select = new \Zend\Db\Sql\Select();
		$select->from($this->primaryGateway->table); // groups
		$select->join('users_has_groups', 'users_has_groups.groups_id = groups.id', array());
		$select->join('users', 'users.id = users_has_groups.users_id', array());
		$select->group('groups.id');
		
		if(!$all) {
			$select->where(array (
				'users.username' => $currentUser 
			));
		}
		//echo $select->getSqlString(new \Zend\Db\Adapter\Platform\Mysql());
//  		$sql = new Sql($this->tableGateway->getAdapter());
//  		$statement = $sql->prepareStatementForSqlObject($select);
//  		$results = $statement->execute();
		
		$results = $this->primaryGateway->selectWith($select);
		//~r($results->current());
		$groups = array ();
		foreach($results as $elem) {
			$groups[] = $elem;
		}
		//~r($groups);
		return $groups;
	}
	
	/**
	 * 
	 * @param mixed $id (int) Table id or (string) group name
	 * @return Group|false
	 * @throws \Exception
	 */
	public function getGroup($id) {
		if(is_int($id)) {
			$group = parent::find($id);
		}
		else {
			$rowSet = $this->fetchAll(['group' => $id]);
			if(count($rowSet) > 1)
				throw new \Exception('Multiple groups with same name. Something is wrong.');
			if(count($rowSet) == 0)
				return false;
			$group = $rowSet[0];
		}
		$currentUser = $this->getCurrentUser();

		if($currentUser->getAccessLevel($group) > 0) return $group;
//		if(isset($currentUser->access[$group->group]) || $currentUser->superuser)
//			return $group;
		else return false;
	}
	
	
	/**
	 * 
	 * @param string $group
	 * @return int|bool
	 */
	public function getGroupId($group) {
		$group = $this->fetchAll(array('group' => $group));
		if($group) return $group[0]->id;
		else return false;
	}
	


	public function getGroupsArray() {
		$data = $this->getGroups();
		
		$groups = array ();
		foreach($data as $elem) {
			$groups[$elem['id']] = $elem['group'];
		}
		
		return $groups;
	}

	public function getGroupIds() {
		$groups = $this->getGroups();
		$group_ids = array ();
		foreach($groups as $group) {
			array_push($group_ids, $group->id);
		}
		
		return $group_ids;
	}


	/**
	 * @param Group $model
	 * @return false|int
	 * @throws \Exception
	 */
	public function save($model) {
		//$activeUser = $this->getAuthService()->getIdentity();
		$activeUser = $this->getCurrentUser();
		
		if (isset($activeUser->access[$model->group])) {
			$data = $model->databaseSaveArray();
			unset($data->id);
			
			$id = (int) $model->id;
			if ($id == 0) {
				$this->primaryGateway->insert($data);
				$id = (int) $this->primaryGateway->getLastInsertValue();
			}
			else {
				if ($this->find($id)) {
					$this->primaryGateway->update($data, array (
						'id' => $id 
					));
				}
				else {
					throw new \Exception('id does not exist');
				}
			}
			return $id;
		}
		else
			return false;
	}

	/**
	 * @return AuthService
	 */
	public function getAuthService() {
		/** @var AuthService $authService */
		$authService = $this->getServiceLocator()->get('AuthService');
		return $authService;
	}

	/**
	 * @return User
	 */
	public function getCurrentUser() {
		return $this->getServiceLocator()->get('UserTable')->getCurrentUser();
	}
	
}
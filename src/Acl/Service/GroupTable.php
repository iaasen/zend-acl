<?php

namespace Acl\Service;

use Acl\Model\User;
use Acl\Model\Group;
use Oppned\AbstractTable;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\TableGateway;

class GroupTable extends AbstractTable {
	/** @var  \Acl\Service\AuthService */
	protected $authService;

//	public function __construct($currentUser, TableGateway $primaryGateway, $authService)
//	{
//		$this->authService = $authService;
//		parent::__construct($currentUser, $primaryGateway);
//	}

	public function find($id) {
		return $this->getGroupById((int) $id);
	}


	/**
	 * Get available groups to current user.
	 * @deprecated Use UserService::getGroupsByCurrentUser()
	 * @param bool $all
	 * @return Group[]
	 */
	public function getGroups($all = false) {
		throw new \DomainException('Method is deprecated. Use UserService::getGroupsByCurrentUser()');
	}

	/**
	 * @param int $id
	 * @return Group|false
	 */
	public function getGroupById($id) {
		return parent::find($id);
	}

	/**
	 * @param string $name
	 * @return Group|false
	 * @throws \Exception
	 */
	public function getGroupByName($name) {
		$rowSet = $this->fetchAll(['group' => $name]);
		if(count($rowSet) > 1)
			throw new \Exception('Multiple groups with same name. Something is wrong.');
		if(count($rowSet) == 0)
			return false;
		return $rowSet[0];
	}
	
	/**
	 * 
	 * @param mixed $id (int) Table id or (string) group name
	 * @deprecated Use getGroupById() or getGroupByName()
	 */
	public function getGroup($id) {
		throw new \DomainException('Method is deprecated. Use getGroupById() or getGroupByName()');
	}


	/**
	 *
	 * @param string $name
	 * @return false|int
	 */
	public function getGroupId($name) {
		$group = $this->getGroupByName($name);
		if($group) return $group->id;
		else return false;
	}

	public function getGroupsArray() {
		throw new \DomainException('Method is deprecated.');
	}

	public function getGroupsByUserId($id) {
		// Setup SQL statement
		$select = new Select();
		$select->from($this->primaryGateway->table); // groups
		$select->join('users_has_groups', 'users_has_groups.groups_id = groups.id', []);
		$select->group('groups.id');
		$select->where->equalTo('users_has_groups.user_id', $id);
		$results = $this->primaryGateway->selectWith($select);

		return $this->convertRowSetToArray($results);
	}

	/**
	 * @deprecated Use AccessTable::getAccessesByUserId()
	 */
	public function getAccessByUserId($id) {
		throw new \DomainException('Method is deprecated. Use AccessTable::getAccessesByUserId()');
	}

	/**
	 * @deprecated
	 */
	public function getGroupIds() {
		throw new \DomainException('Method is deprecated.');
	}

	/**
	 * Get all users the current user has admin rights to
	 * @deprecated
	 */
	public function getUsersByGroup($group = null) {
		throw new \DomainException('Method is deprecated.');
	}



	/**
	 * @param Group $model
	 * @return false|int
	 * @throws \Exception
	 */
	public function save($model) {
		if (isset($this->currentUser->access[$model->group])) {
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
}
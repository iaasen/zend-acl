<?php

namespace Acl\Service;

use Acl\Model\Group;
use Acl\Model\User;
use Oppned\AbstractTable;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Where;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Oppned\Message;
use Zend\Stdlib\RequestInterface;

class UserTable extends AbstractTable {
//	/** @var  User */
//	protected static $currentUser;
//	/** @var  string */
//	protected static $currentIdentity;

//	/** @var  \Acl\Service\GroupTable */
//	protected $groupTable;
	/** @var  \Acl\Service\AuthService */
	protected $authService;
//	/** @var  RequestInterface */
//	protected $request;

	public function __construct($currentUser, TableGateway $primaryGateway, $authService) {
		//$this->groupTable = $groupTable;
		$this->authService = $authService;
		//$this->request = $request;
		parent::__construct($currentUser, $primaryGateway);
	}

	public function find($id) {
		return $this->getUser((int) $id);
	}

	/**
	 * Get all users the current user has admin rights to
	 * @deprecated Use UserService::getUsersByCurrentGroup()
	 */
	public function getUsers() {
		throw new \DomainException('Method is deprecated. Use UserService::getUsersByCurrentGroup()');
	}

	/**
	 * @deprecated
	 */
	public function getUsersByGroupId($id) {
		throw new \DomainException('Method is deprecated. Use UserService::getUsersByGroupId()');
	}

	/**
	 * Get logged in user
	 * @deprecated Use UserService::getCurrentUser() in stead
	 */
	public function getCurrentUser() {
		throw new \DomainException("Method deprecated. Use UserService::getCurrentUser()");
	}

	/**
	 * @param string $identity
	 * @throws \Exception
	 * @return User|false $user
	 * 
	 * Give username or id to retrieve user from database.
	 */
	public function getUser($identity) {
		if(!strlen($identity)) {
			return false;
		}
		elseif(is_int($identity)) {
			$rowSet = parent::find($identity);
			$identity = $rowSet;
		}
		else {
			$rowSet = $this->fetchAll(array('username' => $identity));
			if(count($rowSet) > 1)
				throw new \Exception('Multiple users with same username. Something is wrong.');
				
			if(count($rowSet) == 0) {
				Message::create(3, 'User not found');
				return false;
			}
				
			$identity = $rowSet[0];
		}
		return $identity;
	}

	public function getUserByEmail($email) {
		$rowSet = $this->fetchAll(array('email' => $email));
		if(count($rowSet) > 1)
			throw new \Exception('Multiple users with same e-mail. Something is wrong.');
		if(count($rowSet) == 0)
			return false;
		$user = $rowSet[0];
		$user = $this->getUserAccess($user);
		return $user;
	}
	
	public function getUniqueUsername($start, $group = null) {
		if($group == null) {
			$currentUser = $this->getCurrentUser();
			$group = $currentUser->current_group;
		}
		if(!$this->accessToView($group, 'group')) return false;
		
		if(!count($this->fetchAll(array('username' => $start)))) return $start;
		
		if(preg_match('/^(.*)(\d+)$/', $start, $matches)) {
			$start = $matches[1];
			$counter = $matches[2];
		}
		else {
			$counter = 1;
		}
		  
		for($i = $counter; ; $i++) {
			if(!count($this->fetchAll(array('username' => $start . $i))))
				return $start . $i;
		}
	}
	
	public function getUserAccess(User $user) {
		$select = new \Zend\Db\Sql\Select();
		$select->from('users');
		$select->join('users_has_groups', 'users_has_groups.users_id = users.id');
		$select->join('groups', 'groups.id = users_has_groups.groups_id');
		$select->where(array (
			'users.id' => $user->id
		));
		//echo $select->getSqlString(new \Zend\Db\Adapter\Platform\Mysql());
		
		$sql = new Sql($this->primaryGateway->getAdapter());
		$statement = $sql->prepareStatementForSqlObject($select);
		$results = $statement->execute();
		foreach($results AS $result) {
			$user->updateAccess($result['group'], $result);
		}
		return $user;
	}

	public function getUserId() {
		$user = $this->getUser($this->authService->getIdentity());
		
		return $user->id;
	}

	/**
	 * @param User $user
	 * @return User|false
	 */
	public function create($user) {
		$access = false;
		foreach($this->getCurrentUser()->access AS $current) {
			if($current['access_level'] > 4) {
				$access = true;
				break;
			}
		}
		if(!$access) return false;
		
		// Check if email is in use
		$check = $this->getUserByEmail($user->email);
		if($check) return false;
		// Check if email is set
		if(!strlen($user->email)) return false;
		
		$savedata = $user->databaseSaveArray();
		unset($savedata['id']);
		
		$success = $this->primaryGateway->insert($savedata);
		if(!$success) return false;
		else {
			$id = (int) $this->primaryGateway->getLastInsertValue();
			return $this->getUser($id); 
		}
	}

	/**
	 * @param User $user
	 * @return bool|int
	 * @throws \Exception
	 */
	public function save($user) {
		//get the trace
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
		if($trace[1]['class'] != UserService::class) throw new \Exception('Save can only be done by UserService');

		// Existing user
		$data = $user->databaseSaveArray();
		$currentIdentity = $this->authService->getIdentity();

		if($user->logintype == 'soap') {
			$storedUser = $user;
		}
		else {
			if($user->id) { // Existing user
				$storedUser = $this->find($user->id);
			}
			else { // New user
				$storedUser = $user;
			}

			$storedData = $storedUser->databaseSaveArray();
			foreach($data AS $key => $value) {
				if($value === $storedData[$key]) continue;
				switch($key) {
					case 'name':
						$storedUser->name = $value;
						break;
					case 'email':
						$storedUser->email = $value;
						break;
					case 'password':
						if($currentIdentity == $user->username) {
							$storedUser->password = $user->password;
							Message::create(1, 'Password changed');
						}
						break;
					case 'current_group':
						if(array_key_exists($value, $storedUser->access)) {
							$storedUser->current_group = $value;
						}
						break;
				}
			}
			$data = $storedUser->databaseSaveArray();
		}
		// Save to database
		unset($data['id']);
		unset($data['created']);
		
		$id = (int) $storedUser->id;
		if ($id == 0) {
			$data['created'] = date("Y-m-d H:i:s");
			$this->primaryGateway->insert($data);
			$id = (int) $this->primaryGateway->getLastInsertValue();
		}
		else {
			if ($this->find($id)) {
				$this->primaryGateway->update($data, array (
					'id' => $id,
				));
			}
			else {
				throw new \Exception('id does not exist');
			}
		}
		return $id;
	}
	
	public function addUserToGroup($user, $group) {
		if($group instanceof Group) $group = $group->group;
	
		$user = $this->getUser($user->id);
	
		// User already member of the group
		if(isset($user->access[$group])) return true;
	
	
		$group = $this->groupTable->getGroup($group);
		// Add user
		if($group !== false) {
			$insert = new \Zend\Db\Sql\Insert();
			$insert->into('users_has_groups');
			$insert->values(array(
				'users_id' => $user->id,
				'groups_id' => $group->id
			));
			//echo $insert->getSqlString(new \Zend\Db\Adapter\Platform\Mysql());
			$sql = new Sql($this->primaryGateway->getAdapter());
			$statement = $sql->prepareStatementForSqlObject($insert);
			$statement->execute();
			return true;
	
		}
		// No access to group, or group does not exist.
		return false;
	
	}
	
	/**
	 * 
	 * @param User $user
	 * @param string $group
	 * @return boolean
	 */
	public function saveUserAccess(User $user, $group) {
		if($group instanceof Group) $group = $group->group;
		if(!$this->accessToSaveAccess($user, $group)) return false;
		
		$access = $user->getAccessLevel($group);
		$groupid = $this->groupTable->getGroupId($group);
		
		if($this->find($user->id)) {
			$update = new \Zend\Db\Sql\Update();
			$update->table('users_has_groups');
			$update->where(array (
				'users_id' => $user->id,
				'groups_id' => $groupid,
			));
			$update->set(array('access_level' => $access));
			//echo $update->getSqlString(new \Zend\Db\Adapter\Platform\Mysql());
			
			$sql = new Sql($this->primaryGateway->getAdapter());
			$statement = $sql->prepareStatementForSqlObject($update);

			$statement->execute();
			return true;
		}
		return false;
	}
	
	public function accessToView($mixed, $object = 'user') {
		$currentUser = $this->getCurrentUser();
		
		if($object == 'user') {
			if(count(array_intersect_key($mixed->access, $currentUser->access))) return true;
		}
		elseif($object == 'group') {
			if(isset($currentUser->access[$mixed])) return true;
		}
		return false;
	}

	/**
	 * @deprecated
	 */
	protected function accessToSave($user) {
		throw new \DomainException('Method decrepated. Use UserService::saveUser()');
	}
	
	/**
	 * 
	 * @param User $user
	 * @param mixed $group
	 */
	public function accessToSaveAccess($user, $group) {
		if($group instanceof Group) $group = $group->group;
		$currentUser = $this->getCurrentUser();
		
		// Allow system to set access to newly created user
		if($currentUser->created > (time() - 3600)) return true;
		
		// Not member of the same group
		if(!isset($currentUser->access[$group])) {
			Message::create(3, 'Kan ikke endre tilgangsnvå, bruker tilhører ikke samme firma som deg');
			return false;
		}
		
		// Access level too low
		if($currentUser->access[$group]['access_level'] < 4) { // 4 = Admin
			Message::create(3, 'Kan ikke endre tilgangsnivå, du er ikke administrator');
			return false;
		}
		
		// Not higher level than object
		$checkUser = $this->getUser((int) $user->id);
		if($currentUser->getAccessLevel($group) <= $checkUser->getAccessLevel($group)) {
			Message::create(3, 'Kan ikke endre tilgangsnivå, du må ha høyere tilgang enn bruker du vil endre');
			return false;
		}
		
		// Can't set higher than own access level
		if($currentUser->getAccessLevel($group) <= $user->getAccessLevel($group)) {
			Message::create(3, 'Kan ikke endre tilgangsniva, du må sette lavere enn du har selv');
			return false;
		}
		
		return true;
	}
	
	public function accessToCreateUser($group) {
		if($group instanceof Group) $group = $group->group;
		
		// Not member of the group
		if(!isset($this->getCurrentUser()->access[$group])) {
			Message::create(3, 'Cannot create user, you are not a member of this group');
			return false;
		}
		
		// Access level too low
		if($this->getCurrentUser()->access[$group]['access_level'] < 4) { // 4 = Admin
			Message::create(3, 'Cannot create user, you access level is too low');
			return false;
		}

		return true;
	}
	

// 	public function find($id) {
// 		$id = ( int ) $id;
// 		$rowset = $this->tableGateway->select(array (
// 			'id' => $id 
// 		));
// 		$row = $rowset->current();
// 		if (! $row) {
// 			throw new \Exception("Could not find row $id");
// 		}
// 		return $row;
// 	}

	/**
	 * @return \Acl\Service\AuthService
	 */
//	public function getAuthService() {
//		return $this->serviceLocator->get('AuthService');
//	}
	
//	public function getGroupTable() {
//		return $this->groupTable;
//	}
//
//	public function setGroupTable($table) {
//		$this->groupTable = $table;
//	}
}

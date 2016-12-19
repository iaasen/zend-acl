<?php

namespace Acl\Service;

use Acl\Model\Group;
use Acl\Model\User;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Oppned\Message;

class UserTable extends DbTable {
	/** @var  User */
	protected static $currentUser;
	/** @var  string */
	protected static $currentIdentity;

	/** @var  \Acl\Service\GroupTable */
	protected $groupTable;
	/** @var  \Acl\Service\AuthService */
	protected $authService;

	public function __construct(TableGateway $primaryGateway, $groupTable, $authService) {
		$this->groupTable = $groupTable;
		$this->authService = $authService;
		parent::__construct($primaryGateway);
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
		$rowSet = $this->primaryGateway->select(
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
	 * Get logged in user
	 */
	public function getCurrentUser() {
		$identity = $this->authService->getIdentity();
		if($identity == self::$currentIdentity) {
			return self::$currentUser;
		}

		$user = $this->getUser($identity);
		if(!$user) {
			$user = new User();
			$user->username = $identity;
			if(substr($identity, 0, 6) == 'elfag-') {
				$user->logintype = 'elfag';
				$user->updateAccess($identity, array('access_level' => 5));
			}
		}
		self::$currentUser = &$user;
		self::$currentIdentity = &$identity;

		return self::$currentUser;


//
//		if(self::$currentUser == null) {
//			$identity = $this->authService->getIdentity();
//			if($identity) {
//				$user = $this->getUser($identity);
//				if(!$user) {
//					 $user = new User();
//					 $user->username = $identity;
//					 if(substr($identity, 0, 6) == 'elfag-') {
//					 	$user->logintype = 'elfag';
//					 	$user->updateAccess($identity, array('access_level' => 5));
//					 }
//				}
//				self::$currentUser = $user;
//			}
//			else self::$currentUser = null;
//		}
//		return self::$currentUser;
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
			//r($rowSet);
			if(count($rowSet) > 1)
				throw new \Exception('Multiple users with same username. Something is wrong.');
				
			if(count($rowSet) == 0) {
				Message::create(3, 'User not found');
				return false;
			}
				
			$identity = $rowSet[0];
		}
		
		$this->getUserAccess($identity);
		
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
		if($this->accessToSave($user) == false) return false;
		
		// Existing user
		$data = $user->databaseSaveArray();
		$currentUser = $this->getCurrentUser();
		
		
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
					case 'password':
						if($currentUser->id == $user->id) {
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
	
	protected function accessToSave($user) {
		$currentUser = $this->getCurrentUser();
		
		if($user->id) { // Existing user
			// Same user
			if($user->id == $currentUser->id) {
				return true;
			}
			$user = $this->find($user->id);
		}
		else { // New user
			if($user->username == $currentUser->username) return true;
		}
		
		// Administrator of a soap-user
		if($user->logintype == 'soap') {
			$groups = array_intersect_key($user->access, $currentUser->access);
			foreach($groups AS $key => $value) {
				if($currentUser->getAccessLevel($key) >= 4) return true;
			}
		}
		return false;
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
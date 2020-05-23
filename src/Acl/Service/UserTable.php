<?php

namespace Acl\Service;

use Acl\Model\Group;
use Acl\Model\User;
use Iaasen\Service\AbstractTable;
use Laminas\Db\Sql\Sql;
use Laminas\Db\TableGateway\TableGateway;
use Oppned\Message;

class UserTable extends AbstractTable {
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

	/**
	 * @param int $id
	 * @return User|false
	 * @throws \Exception
	 */
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
	 * Give username or id to retrieve user from database.
	 * @param string $identity
	 * @return User|false
	 * @throws \Exception
	 */
	public function getUser($identity) {
		if(!strlen($identity)) {
			return false;
		}
		elseif(is_numeric($identity)) {
			$rowSet = parent::find($identity);
			$identity = $rowSet;
		}
		else {
			$rowSet = $this->fetchAll(['username' => $identity]);
			if(count($rowSet) > 1)
				throw new \DomainException('Multiple users with same username. Something is wrong.');
				
			if(count($rowSet) == 0) {
				Message::create(3, 'User not found');
				return false;
			}
				
			$identity = $rowSet[0];
		}
		return $identity;
	}

	/**
	 * @param string $identity
	 * @param string $loginType
	 * @return User|false
	 * @throws \Exception
	 */
	public function getUserByIdentityAndLogintype(string $identity, string $loginType) {
		if(!strlen($identity)) return false;
		$rowSet = $this->fetchAll(['username' => $identity, 'logintype' => $loginType]);
		if(count($rowSet) > 1)
			throw new \DomainException('Multiple users with same username. Something is wrong.');

		if(count($rowSet) == 0) {
			Message::create(3, 'User not found');
			return false;
		}

		return $rowSet[0];
	}

	/**
	 * @param string $email
	 * @return User|false
	 * @throws \Exception
	 */
	public function getUserByEmail(string $email) {
		$rowSet = $this->fetchAll(array('email' => $email));
		if(count($rowSet) > 1)
			throw new \Exception('Multiple users with same e-mail. Something is wrong.');
		if(count($rowSet) == 0)
			return false;
		$user = $rowSet[0];
		$user = $this->getUserAccess($user);
		return $user;
	}

	/**
	 * @param string $start
	 * @param null|string $group
	 * @return bool|string
	 */
	public function getUniqueUsername($start, $group = null) {
		if($group == null) {
			$currentUser = $this->currentUser;
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
			if(!count($this->fetchAll(['username' => $start . $i])))
				return $start . $i;
		}
		return false;
	}
	
	public function getUserAccess(User $user) {
		$select = new \Laminas\Db\Sql\Select();
		$select->from('users');
		$select->join('users_has_groups', 'users_has_groups.users_id = users.id');
		$select->join('groups', 'groups.id = users_has_groups.groups_id');
		$select->where(array (
			'users.id' => $user->id
		));
		//echo $select->getSqlString(new \Laminas\Db\Adapter\Platform\Mysql());
		
		$sql = new Sql($this->primaryGateway->getAdapter());
		$statement = $sql->prepareStatementForSqlObject($select);
		$results = $statement->execute();
		foreach($results AS $result) {
			$user->setAccess($result['group'], $result);
		}
		return $user;
	}

	/**
	 * @return int
	 * @throws \Exception
	 */
	public function getUserId() {
		$user = $this->getUser($this->authService->getIdentity());
		return $user->id;
	}

	/**
	 * @param User $user
	 * @return User|false
	 * @throws \Exception
	 */
	public function create($user) {
		$access = false;
		foreach($this->currentUser->access AS $current) {
			if($current['access_level'] > 4) {
				$access = true;
				break;
			}
		}
		if(!$access) return false;

		// Check if email is set
		if(!strlen($user->email)) return false;
		// Check if email is in use
		$check = $this->getUserByEmail($user->email);
		if($check) return false;

		$userId =  parent::save($user);
		return $this->find($userId);
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
		return parent::save($user);
	}

	/**
	 * @deprecated
	 */
	public function addUserToGroup($user, $group) {
		throw new \DomainException('Method deprecated. Use UserService::addUserToGroup()');
	}
	
	/**
	 * @deprecated
	 */
	public function saveUserAccess(User $user, $group) {
		throw new \DomainException('Method deprecated. Use UserService::saveUserAccess()');
	}
	
	public function accessToView($mixed, $object = 'user') {
		$currentUser = $this->currentUser;
		
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
	 * @deprecated
	 */
	public function accessToSaveAccess($user, $group) {
		throw new \DomainException('Method decrepated. Is moved to UserService::accessToSaveAccess()');
	}

	/**
	 * @deprecated Should be in UserService
	 * @todo Move create user to UserService
	 * @param $group
	 * @return bool
	 */
	public function accessToCreateUser($group) {
		if($group instanceof Group) $group = $group->group;
		
		// Not member of the group
		if(!isset($this->currentUser->access[$group])) {
			Message::create(3, 'Cannot create user, you are not a member of this group');
			return false;
		}
		
		// Access level too low
		if($this->currentUser->access[$group]['access_level'] < 4) { // 4 = Admin
			Message::create(3, 'Cannot create user, you access level is too low');
			return false;
		}

		return true;
	}
}

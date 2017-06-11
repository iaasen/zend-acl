<?php
/**
 * Created by PhpStorm.
 * User: iaase
 * Date: 05.06.2017
 * Time: 22.47
 */

namespace Acl\Service;


use Acl\Model\User;
use Zend\Db\Sql\AbstractPreparableSql;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Zend\Http\PhpEnvironment\Request;

class UserService
{
	/** @var  \Acl\Service\AuthService */
	protected $authService;
	/** @var  \Acl\Service\UserTable */
	protected $userTable;
	/** @var  \Acl\Service\GroupTable */
	protected $groupTable;
	/** @var  \Acl\Service\AccessTable */
	protected $accessTable;
	/** @var  Request */
	protected $request;

	/** @var  \Acl\Model\User */
	protected static $currentUser;
	/** @var  string */
	protected static $currentIdentity;
	/** @var  \Acl\Model\Group */
	protected static $currentGroup;


	public function __construct($authService, $userTable, $groupTable, $accessTable, $request)
	{
		$this->authService = $authService;
		$this->userTable = $userTable;
		$this->groupTable = $groupTable;
		$this->accessTable = $accessTable;
		$this->request = $request;
	}

	public function getCurrentGroup() {
		if(self::$currentGroup) {
			return self::$currentGroup;
		}
		self::$currentGroup = $this->groupTable->getGroupByName($this->getCurrentUser()->current_group);
		return self::$currentGroup;
	}

	public function getCurrentUser() {
		// Console user
		if($this->request instanceof \Zend\Console\Request) {
			$user = new User();
			$user->username = 'console';
			$user->logintype = 'console';
			self::$currentUser = &$user;
			self::$currentIdentity = 'console';
			return self::$currentUser;
		}

		$identity = $this->authService->getIdentity();
		if($identity == self::$currentIdentity) {
			return self::$currentUser;
		}

		$user = $this->getUserByUsername($identity);
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
	}

	public function getUserById($id) {
		$user = $this->userTable->getUser($id);
		return $this->populateUser($user);
	}

	public function getUserByUsername($username) {
		$user = $this->userTable->getUser($username);
		return $this->populateUser($user);
	}

	/**
	 * @param User $user
	 * @return User
	 */
	protected function populateUser($user) {
		$accesses = $this->accessTable->getAccessesByUserId($user->id);
		foreach($accesses AS $access) {
			$group = $this->groupTable->getGroupById($access->groups_id);
			$user->updateAccess($group->group, $access);
		}
		return $user;
	}

	public function getUsersByCurrentGroup() {
		$group = $this->getCurrentUser()->current_group;
		return $this->getUsersByGroup($group);
	}

	/**
	 * Get all users who have access to group
	 * @param string $group
	 * @return User[]
	 */
	public function getUsersByGroup($group) {
		$group = $this->groupTable->getGroupByName($group);
		return $this->getUsersByGroupId($group->id);
	}

	/**
	 * @param int $groupId
	 * @param bool $populate
	 * @return array
	 */
	public function getUsersByGroupId($groupId, $populate = true) {
		$accesses = $this->accessTable->getAccessesByGroupId($groupId);
		$users = [];
		foreach($accesses AS $access) {
			$user = $this->userTable->getUser($access->users_id);
			if($populate) $user = $this->populateUser($user);
			$users[] = $user;
		}
		return $users;
	}

	public function getGroupsByCurrentUser() {
		return $this->getGroupsByUserId($this->getCurrentUser()->id);
	}

	public function getGroupsByUserId($id) {
		return $this->groupTable->getGroupsByUserId($id);
	}

	public function getGroupById($id) {
		return $this->groupTable->getGroupById($id);
	}

	public function getGroupByName($name) {
		return $this->groupTable->getGroupByName($name);
	}

	/**
	 * @param User $user
	 * @return bool|int
	 */
	public function saveUser($user) {
		$currentUser = $this->getCurrentUser();

		if($user->id) { // Existing user
			if($user->id != $currentUser->id) return false; // Not same user
		}
		else { // New user
			if($user->username != $currentUser->username) return false; // Not same user
		}

		// Administrator of a soap-user
		if($user->logintype == 'soap') {
			$groups = array_intersect_key($user->access, $currentUser->access);
			foreach($groups AS $key => $value) {
				if($currentUser->getAccessLevel($key) <= 4) return false;
			}
		}
		return $this->userTable->save($user);
	}
}
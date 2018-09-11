<?php

namespace Acl\Model;
use Iaasen\Entity\DateTime;
use Iaasen\Model\AbstractModel;


/**
 * Class User
 * @package Acl\Model
 * @property int $id
 * @property string $logintype
 * @property string $username
 * @property string $password
 * @property string $name
 * @property string $email
 * @property bool $email_confirmed
 * @property int $ludens_id
 * @property int $ludens_permissions
 * @property \stdClass $ludens_company
 * @property bool $superuser
 * @property string $current_group
 * @property \DateTime $last_login
 */
class User extends AbstractModel {
	public static $access_level = [
		0 => 'None',
		1 => 'View',
		2 => 'Edit own', // Create and edit own
		3 => 'Edit all', // Edit others
		4 => 'Admin',
		5 => 'Master'
	];

	public static $login_types = [
		'console' => 'Console',
		'default' => 'Local',
		'elfag' => 'Gamle elfag.no',
		'elfag2' => 'Elfag intranett',
		'soap' => 'Visma',
		'token' => 'Application/Website'
	];

	/** @var int */
	protected $id;
	/** @var string */
	protected $logintype;
	/** @var string */
	protected $username;
	/** @var string */
	protected $password;
	/** @var string */
	protected $name;
	/** @var string */
	protected $email;
	/** @var bool */
	protected $email_confirmed;
	/** @var int */
	protected $ludens_id;
	/** @var int */
	protected $ludens_permissions;
	/** @var mixed[] */
	protected $ludens_company;
	/** @var bool */
	protected $superuser;
	/** @var string */
	protected $current_group;
	/** @var DateTime */
	protected $last_login;

	/** @var \Acl\Model\Access[] */
	public $access = [];

	/**
	 * User constructor.
	 * @param array $data
	 * @throws \Exception
	 */
	public function __construct(array $data = []) {
		parent::__construct();

		foreach($data AS $key => $value) {
			parent::__set($key, $value);
		}

		//Translation strings
		_('Local');
		_('Application/Website');
	}


	/**
	 * @param Group|string $group
	 * @param Access $access
	 */
	public function setAccess($group, $access) {
		if($group instanceof Group) $group = $group->group;
		$this->access[$group] = $access;
	}

	/**
	 * @param Group|string $group
	 * @param int $accessLevel
	 */
	public function setAccessLevel($group, $accessLevel) {
		if($group instanceof Group) $group = $group->group;
		if(!isset($this->access[$group])) {
			$this->access[$group] = new Access();
		}
		$this->access[$group]->access_level = $accessLevel;
	}

	/**
	 * How many companies has the user access to?
	 */
	public function countGroupAccesses() {
		$count = 0;
		foreach($this->access AS $access) {
			if($access->access_level > 0) $count++;
		}
		return $count;
	}

	/**
	 * @param Group|string $group
	 * @return int
	 */
	public function getAccessLevel($group = null) {
		// Console
		if($this->logintype == 'console') return 6;

		if(!$group) $group = $this->current_group;
		if(!$group) return 0;

		if(is_object($group)) $groupName = $group->group;
		else $groupName = $group;

		if(isset($this->access[$groupName]) && isset($this->access[$groupName]->access_level)) {
			return $this->access[$groupName]->access_level;
		}
		return 0;
	}

	public function getAccessLevelName($group = null) {
		return self::$access_level[$this->getAccessLevel($group)];
	}
	
	public function getValueOptions() {
		$options = array();
		$options[0] = ['value' => 0, 'label' => _('None')];
		$options[1] = ['value' => 1, 'label' => _('View')];
		$options[2] = ['value' => 2, 'label' => _('Edit own')];
		$options[3] = ['value' => 3, 'label' => _('Edit all')];
		$options[4] = ['value' => 4, 'label' => _('Admin')];
		$options[5] = ['value' => 5, 'label' => _('Master')];
		return $options;
	}

	/**
	 * Called by $form->bind()
	 *
	 * @return array $_data Arraycopy of the datafields
	 * @throws \Exception
	 */
	public function getArrayCopy() {
		$data = parent::getArrayCopy();
		unset($data['access']);
		return $data;
	}

	/**
	 * Used by DbTable to format modeldata for the database.
	 *
	 * @return array $_data
	 */
	public function databaseSaveArray() {
		$data = parent::databaseSaveArray();
		unset($data['access']);
		return $data;
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 * @throws \Exception
	 */
	public function __set($name, $value) {
		switch ($name) {
			case 'old_password':
			case 'new_password':
			case 'new_password_confirm':
			case 'submit';
				break;
			case 'id':
				$this->$name = (int) $value;
				if($value == 0) $this->$name = null;
				break;
			default :
				parent::__set($name, $value);
				break;
		}
	}

	public function __get($name) {
		switch ($name) {
			case 'access' :
				return $this->access;
				break;
			default :
				return parent::__get($name);
				break;
		}
	}

	public function __clone() {
		parent::__clone();
		$this->last_login = null;
	}
}

<?php

namespace Acl\Model;


/**
 * Class User
 * @package Acl\Model
 * @property int $id
 * @property string $logintype
 * @property string $username
 * @property string $password
 * @property string $name
 * @property string $email
 * @property string $email_confirmed
 * @property bool $superuser
 * @property string $current_group
 */
class User {
//	public static $access_level = array (
//		0 => 'None',
//		1 => 'View',
//		2 => 'Edit own', // Create and edit own
//		3 => 'Edit all', // Edit others
//		4 => 'Admin',
//		5 => 'Master'
//	);
	public static $login_types = array(
		'console' => 'Console',
		'default' => 'Local',
		'elfag' => 'elfag.no',
		'soap' => 'Visma',
		'token' => 'Application/Website'
	);
	protected $data = array (
		'id' => null,
		'logintype' => 'default',
		'username' => null,
		'password' => null,
		'name' => null,
		'email' => null,
		'email_confirmed' => null,
		'superuser' => false,
		'current_group' => null,
		'created' => null,
		'last_login' => null 
	);
	public $access = array ();

	public function __construct(array $data = null) {
		$this->created = time();
		if (! is_null($data)) {
			foreach($data as $name => $value) {
				$this->{$name} = $value;
			}
		}
		
		//Translation strings
		_('Local');
		_('Application/Website');
	}


	public function updateAccess($group, $access) {
		if($group instanceof Group) $group = $group->group;
		$this->access[$group] = $access;
	}

	/**
	 * @param Group|string $group
	 * @return int
	 */
	public function getAccessLevel($group = null) {
		// Console
		if($this->logintype == 'console') return 6;

		if(!$group) $group = $this->current_group;

		if(is_object($group)) $groupName = $group->group;
		else $groupName = $group;

		if(isset($this->access[$groupName]) && isset($this->access[$groupName]->access_level)) {
			return $this->access[$groupName]->access_level;
		}
		return 0;
	}
	
	public function getValueOptions() {
		$options = array();
// 		foreach(self::$access_level AS $key => $value) {
// 			$options[$key] = array('value' => $key, 'label' => $value);
// 		}
		$options[0] = array('value' => 0, 'label' => _('None'));
		$options[1] = array('value' => 1, 'label' => _('View'));
		$options[2] = array('value' => 2, 'label' => _('Edit own'));
		$options[3] = array('value' => 3, 'label' => _('Edit all'));
		$options[4] = array('value' => 4, 'label' => _('Admin'));
		$options[5] = array('value' => 5, 'label' => _('Master'));
		return $options;
	}

	/**
	 * Called when object is created from database by TabelGateway?
	 * Called when form is validated.
	 */
	public function exchangeArray($data) {
		foreach($data as $key => $value) {
			try {
				switch ($key) {
					case 'id' :
						$this->$key = (int) $value;
						break;
					case 'created' :
					case 'last_login' :
						$this->$key = strtotime($value);
						break;
					case 'superuser' :
					case 'email_confirmed' :
						$this->$key = ($value) ? true : false;
						break;
					default :
						$this->$key = ($value) ? $value : null;
						break;
				}
			}
			catch(\Exception $e) {
			}
		}
	}

	/**
	 * Called by $form->bind()
	 *
	 * @return array $_data Arraycopy of the datafields
	 */
	public function getArrayCopy() {
		$_data = array ();
		foreach($this->data as $key => $value) {
			if (strlen($value))
				switch ($key) {
					case 'created' :
					case 'last_login' :
						$_data[$key] = date("d.m.Y", $value);
						break;
					default :
						$_data[$key] = $value;
						break;
				}
			else {
				$_data[$key] = null;
			}
		}
		return $_data;
	}

	/**
	 * Used by DbTable to format modeldata for the database.
	 *
	 * @return array $_data
	 */
	public function databaseSaveArray() {
		$_data = array ();
		foreach($this->data as $key => $value) {
			switch ($key) {
				case 'created' :
				case 'last_login' :
					$_data[$key] = (strlen($value)) ? date("Y-m-d H:i:s", $value) : null;
					break;
				case 'superuser' :
					$_data[$key] = ( bool ) $value;
					break;
				default :
					if (strlen($value))
						$_data[$key] = $value;
					else
						$_data[$key] = null;
					break;
			}
		}
		return $_data;
	}

	public function __set($name, $value) {
		switch ($name) {
			case 'id':
				$this->data[$name] = (int) $value;
				if($value == 0) $this->data[$name] = null;
				break;
			default :
				if (array_key_exists($name, $this->data)) {
					$this->data[$name] = (strlen($value)) ? $value : null;
				}
				else {
					throw new \Exception("You cannot set '$name' on " . get_class($this));
				}
				break;
		}
	}

	public function __get($name) {
		switch ($name) {
			case 'access' :
				return $this->_access;
				break;
			default :
				if (array_key_exists($name, $this->data)) {
					return $this->data[$name];
				}
				break;
		}
		return false;
	}

	public function __isset($name) {
		return isset($this->data[$name]);
	}

	public function __unset($name) {
		if (isset($this->data[$name])) {
			unset($this->data[$name]);
			return true;
		}
		return false;
	}

	public function __clone() {
		$this->id = null;
		$this->created = time();
		$this->last_login = null;
	}
}
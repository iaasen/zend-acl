<?php

namespace Acl\Model;

class Client {
	public static $access_level = array (
		0 => 'None',
		//1 => 'View',
		//2 => 'Edit own', // Create and edit own
		//3 => 'Edit all', // Edit others
		//4 => 'Admin',
		5 => 'Master' 
	);
	protected $data = array (
		'id' => null,
		'client_id' => null,
		'client_secret' => null,
		'name' => null,
		'timestamp_created' => null,
		'timestamp_updated' => null,
		'last_login' => null 
	);
	public $access = array ();

	public function __construct(array $data = null) {
		$this->timestamp_created = time();
		$this->timestamp_updated = time();
		
		if (! is_null($data)) {
			foreach($data as $name => $value) {
				$this->{$name} = $value;
			}
		}
	}
	
	/**
	 * Called when object is created from database by TableGateway?
	 * Called when form is validated.
	 */
	public function exchangeArray($data) {
		foreach($data as $key => $value) {
			try {
				switch ($key) {
					case 'id' :
						$this->$key = (int) $value;
						break;
					case 'timestamp_created' :
					case 'timestamp_updated' :
					case 'last_login' :
						$this->$key = strtotime($value);
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
					case 'timestamp_created' :
					case 'timestamp_updated' :
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
				case 'timestamp_created' :
				case 'timestamp_updated' :
				case 'last_login' :
					$_data[$key] = (strlen($value)) ? date("Y-m-d H:i:s", $value) : null;
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
			default :
				if (array_key_exists($name, $this->data)) {
					$this->data[$name] = $value;
				}
				else {
					throw new \Exception("You cannot set '$name' on " . get_class($this));
				}
				break;
		}
	}

	public function __get($name) {
		switch ($name) {
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
		$this->timestamp_created = time();
		$this->timestamp_updated = time();
		$this->last_login = null;
	}
}
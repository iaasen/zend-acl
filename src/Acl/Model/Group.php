<?php

namespace Acl\Model;

class Group {
	protected $data = array (
		'id' => null,
		'group' => null,
		'name' => null,
		'onninen_customer_id' => null,
		'elfag_membership_number' => null,
		'created' => null,
	);
	
	public function __construct(array $data = null) {
		$this->created = time();
		
		if (! is_null($data)) {
			foreach($data as $name => $value) {
				$this->{$name} = $value;
			}
		}
	}
	
	/**
	 * Called when object is created from database by TabelGateway?
	 * Called when form is validated.
	 */
	public function exchangeArray($data) {
		foreach($data as $key => $value) {
			try {
				switch ($key) {
					case 'created' :
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
					case 'created' :
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
			if (strlen($value)) {
				switch ($key) {
					case 'created' :
						$_data[$key] = date("Y-m-d H:i:s", $value);
						break;
					default :
						$_data[$key] = $value;
						break;
				}
			}
			else {
				$_data[$key] = null;
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
			case 'name':
				return (strlen($this->data[$name])) ? $this->data[$name] : $this->data['group'];
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
	}
}
<?php

namespace Acl\Model;
use Iaasen\Model\AbstractModel;

/**
 * Class Group
 * @package Acl\Model
 * @property int $id
 * @property string $group
 * @property string $name
 * @property string $onninen_customer_id
 * @property string $elfag_membership_number
 */
class Group extends AbstractModel {
	/** @var int */
	protected $id;
	/** @var string */
	protected $group;
	/** @var string */
	protected $name;
	/** @var int */
	protected $ludens_id;
	/** @var string */
	protected $elfag_membership_number;
	/** @var string */
	protected $onninen_customer_id;
	/** @var bool */
	protected $active = true;

	/**
	 * Group constructor.
	 * @param array $data
	 * @throws \Exception
	 */
	public function __construct(array $data = []) {
		foreach($data AS $key => $value) {
			parent::__set($key, $value);
		}
		parent::__construct();
	}

	/**
	 * @param string $name
	 * @return mixed
	 * @throws \Exception
	 */
	public function __get($name) {
		switch ($name) {
			case 'name':
				return (strlen($this->$name)) ? $this->$name : $this->group;
				break;
			default :
				return parent::__get($name);
				break;
		}
	}


//	/**
//	 * Called when object is created from database by TableGateway?
//	 * Called when form is validated.
//	 */
//	public function exchangeArray($data) {
//		foreach($data as $key => $value) {
//			try {
//				switch ($key) {
//					case 'created' :
//						$this->$key = strtotime($value);
//						break;
//					default :
//						$this->$key = ($value) ? $value : null;
//						break;
//				}
//			}
//			catch(\Exception $e) {
//			}
//		}
//	}
	
//	/**
//	 * Called by $form->bind()
//	 *
//	 * @return array $_data Arraycopy of the datafields
//	 */
//	public function getArrayCopy() {
//		$_data = array ();
//		foreach($this->data as $key => $value) {
//			if (strlen($value))
//				switch ($key) {
//					case 'created' :
//						$_data[$key] = date("d.m.Y", $value);
//						break;
//					default :
//						$_data[$key] = $value;
//						break;
//				}
//			else {
//				$_data[$key] = null;
//			}
//		}
//		return $_data;
//	}
	
//	/**
//	 * Used by DbTable to format modeldata for the database.
//	 *
//	 * @return array $_data
//	 */
//	public function databaseSaveArray() {
//		$_data = array ();
//		foreach($this->data as $key => $value) {
//			if (strlen($value)) {
//				switch ($key) {
//					case 'created' :
//						$_data[$key] = date("Y-m-d H:i:s", $value);
//						break;
//					default :
//						$_data[$key] = $value;
//						break;
//				}
//			}
//			else {
//				$_data[$key] = null;
//			}
//		}
//		return $_data;
//	}

//	public function __set($name, $value) {
//		switch ($name) {
//			default :
//				if (array_key_exists($name, $this->data)) {
//					$this->data[$name] = $value;
//				}
//				else {
//					throw new \Exception("You cannot set '$name' on " . get_class($this));
//				}
//				break;
//		}
//	}

//	public function __isset($name) {
//		return isset($this->data[$name]);
//	}
	
//	public function __unset($name) {
//		if (isset($this->data[$name])) {
//			unset($this->data[$name]);
//			return true;
//		}
//		return false;
//	}
	
//	public function __clone() {
//		$this->id = null;
//		$this->created = time();
//	}
}
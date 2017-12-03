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
}
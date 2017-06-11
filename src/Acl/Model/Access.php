<?php
/**
 * Created by PhpStorm.
 * User: iaase
 * Date: 05.06.2017
 * Time: 23.57
 */

namespace Acl\Model;


use Oppned\AbstractModel;

/**
 * Class Access
 * @package Acl\Model
 * @property int $users_id
 * @property int $groups_id
 * @property int $access_level
 * @property bool $onnshop
 */
class Access extends AbstractModel
{
	/** @var  int */
	protected $users_id;
	/** @var  int */
	protected $groups_id;
	/** @var int */
	protected $access_level = 0;
	/** @var bool */
	protected $onnshop = false;

	public static $accessLevels = [
		0 => 'None',
		1 => 'View',
		2 => 'Edit own', // Create and edit own
		3 => 'Edit all', // Edit others
		4 => 'Admin',
		5 => 'Master'
	];

	/**
	 * AbstractModel tries to set 'id'. Ignoring id as this model does not have it
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value) {
		if($name !== 'id') parent::__set($name, $value);
	}
}
<?php
/**
 * Created by PhpStorm.
 * User: iaase
 * Date: 06.06.2017
 * Time: 00.02
 */

namespace Acl\Service;


use Acl\Model\Access;
use Oppned\AbstractTable;

class AccessTable extends AbstractTable
{
	/**
	 * @param $userId
	 * @param $groupId
	 * @return Access
	 */
	public function getAccess($userId, $groupId) {
		return parent::fetchAll([
			'users_id' => $userId,
			'groups_id' => $groupId,
		])[0];
	}

	/**
	 * @param int $userId
	 * @return Access[]
	 */
	public function getAccessesByUserId($userId) {
		return parent::fetchAll([
			'users_id' => $userId,
		]);
	}

	/**
	 * @param int $groupId
	 * @return Access[]
	 */
	public function getAccessesByGroupId($groupId) {
		return parent::fetchAll([
			'groups_id' => $groupId,
		]);
	}

	public function save($model) {
		return parent::save($model);
	}

}
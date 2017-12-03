<?php
/**
 * Created by PhpStorm.
 * User: iaase
 * Date: 06.06.2017
 * Time: 00.02
 */

namespace Acl\Service;


use Acl\Model\Access;
use Iaasen\Service\AbstractTable;

class AccessTable extends AbstractTable
{
	/**
	 * @param $userId
	 * @param $groupId
	 * @return Access|false
	 */
	public function getAccess($userId, $groupId) {
		$access =  parent::fetchAll([
			'users_id' => $userId,
			'groups_id' => $groupId,
		]);
		if($access && count($access)) return $access[0];
		return false;
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

	/**
	 * @param Access $model
	 * @return bool
	 * @throws \Exception
	 */
	public function save($model) {
		$data = $model->databaseSaveArray();
		$primaryKeys = [
			'users_id' => $model->users_id,
			'groups_id' => $model->groups_id,
		];

		if(isset($data['timestamp_updated'])) $data['timestamp_updated'] = date("Y-m-d H:i:s", time());

		if($this->getAccess($model->users_id, $model->groups_id)) {
			unset($data['users_id']);
			unset($data['groups_id']);
			$this->primaryGateway->update($data, $primaryKeys);
		}
		else {
			$this->primaryGateway->insert($data);
		}
		return true;
	}

}
<?php
namespace Acl\Service;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;



abstract class DbTable implements ServiceLocatorAwareInterface
{
	/** @var TableGateway */
	protected $primaryGateway;
	protected $serviceLocator;
	
	public function __construct(TableGateway $primaryGateway)
	{
		$this->primaryGateway = $primaryGateway;
	}
	
	protected function fetchAll($where = null, $order = array())
	{
		$rowSet = $this->primaryGateway->select(
				function(Select $select) use ($where, $order) {
					$select->where($where);
					$select->order($order);
				}
		);
		
		$objects = array();
		for($i = 0; $i < $rowSet->count(); $i++) {
			$objects[] = $rowSet->current();
			$rowSet->next();
		}
		return $objects;
	}
	
	protected function find($id)
	{
		$id  = (int) $id;
		$rowset = $this->primaryGateway->select(array('id' => $id));
		$row = $rowset->current();
		if (!$row) {
			throw new \Exception("Could not find row $id");
		}
		return $row;
	}
	
	protected function save($model) {
		$data = $model->databaseSaveArray();
		unset($data->id);
		unset($data->timestamp_created);
		if(isset($data->timestamp_updated)) $data->timestamp_updated = date("Y-m-d H:i:s", time());
		
		$id = (int)$model->id;
		if ($id == 0) {
			$this->primaryGateway->insert($data);
			$id = $this->primaryGateway->getLastInsertValue();
		} else {
			if ($this->find($id)) {
				$this->primaryGateway->update($data, array('id' => $id));
			} else {
				throw new \Exception('Form id does not exist');
			}
		}
		return $id;
	}
		
	protected function delete($id)
	{
		if(is_object($id)) {
			$id = $id->id;
		}
		$result = $this->primaryGateway->delete(array('id' => $id));
		return (bool) $result;
	}
	
	protected function query($select, $outputSqlString = false) {
		if($outputSqlString) echo $select->getSqlString($this->primaryGateway->getAdapter()->getPlatform());
		$sql = new Sql($this->primaryGateway->getAdapter());
		$statement = $sql->prepareStatementForSqlObject($select);
		return $statement->execute();
	}
	
	
// 	public function getTable($table) {
// 		if (!isset($this->tables[$table])) {
// 			$sm = $this->getServiceLocator();
// 			$table = ucfirst ($table);
// 			$this->tables[$table] = $sm->get ($table . 'Table');
// 		}
// 		return $this->tables [$table];
// 	}
	
	public function setServiceLocator(ServiceLocatorInterface $serviceLocator) {
		$this->serviceLocator = $serviceLocator;
	}
	
	public function getServiceLocator() {
		return $this->serviceLocator;
	}



}

<?php

namespace Acl\Model;

use Zend\Authentication\Adapter\DbTable\AbstractAdapter AS AbstractDbAdapter;
use Zend\Authentication\Result;
use Zend\Crypt\Password\Bcrypt;
use General\Message;

class AuthSoapAdapter extends AbstractDbAdapter {


	/**
	 * Performs an authentication attempt
	 *
	 * @return \Zend\Authentication\Result
	 * @throws \Zend\Authentication\Adapter\Exception\ExceptionInterface If authentication cannot be performed
	 */
	public function authenticateValidateResult($resultIdentity) {
		$bcrypt = new Bcrypt();
		
		if ($bcrypt->verify($this->credential, $resultIdentity[$this->credentialColumn])) {
			$this->authenticateResultInfo['code'] = Result::SUCCESS;
			$this->authenticateResultInfo['messages'][] = 'Authentication successful.';
		} else {
			$this->authenticateResultInfo['code'] = Result::FAILURE_CREDENTIAL_INVALID;
			$this->authenticateResultInfo['messages'][] = 'Supplied credential is invalid.';
		}
		return $this->authenticateCreateAuthResult();
	}
	
	protected function authenticateCreateSelect() {
		$select = clone $this->getDbSelect();
		$select->from($this->tableName);
		$select->columns(array($this->credentialColumn));
		$select->where->equalTo($this->identityColumn, $this->identity);
		$select->where->equalTo('logintype', 'soap');
		//echo $select->getSqlString($this->zendDb->getPlatform());
		return $select;
	}
}

<?php

namespace Acl\Adapter;

use Laminas\Authentication\Adapter\DbTable\AbstractAdapter AS AbstractDbAdapter;
use Laminas\Authentication\Result;
use Laminas\Crypt\Password\Bcrypt;

class AuthLocalAdapter extends AbstractDbAdapter {


	/**
	 * Performs an authentication attempt
	 *
	 * @return \Laminas\Authentication\Result
	 * @throws \Laminas\Authentication\Adapter\Exception\ExceptionInterface If authentication cannot be performed
	 */
	public function authenticateValidateResult($resultIdentity) {
		$bcrypt = new Bcrypt();
		
		if ($bcrypt->verify($this->credential, $resultIdentity[$this->credentialColumn])) {
			$this->authenticateResultInfo['code'] = Result::SUCCESS;
			$this->authenticateResultInfo['messages'][] = 'Authentication successful';
		} else {
			$this->authenticateResultInfo['code'] = Result::FAILURE_CREDENTIAL_INVALID;
			$this->authenticateResultInfo['messages'][] = 'Supplied credential is invalid';
		}
		return $this->authenticateCreateAuthResult();
	}
	
	protected function authenticateCreateSelect() {
		$select = clone $this->getDbSelect();
		$select->from($this->tableName);
		$select->columns(array($this->credentialColumn));
		$select->where->equalTo($this->identityColumn, $this->identity);
		$select->where->equalTo('logintype', 'default');
		//echo $select->getSqlString($this->zendDb->getPlatform());
		return $select;
	}
}

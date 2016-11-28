<?php 

namespace Acl\Service;

use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Adapter\AdapterInterface;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerInterface;

class AuthService extends AuthenticationService {
	
	public $adapter = array();
	public $identity;
	public $credential;

	/** @var  \Zend\EventManager\EventManager */
	protected $eventManager;
	
	public function setAdapter(AdapterInterface $adapter) {
		$this->adapter = array($adapter);
		return $this;
	}
	
	public function addAdapter(AdapterInterface $adapter) {
		$this->adapter[] = $adapter;
		return $this;
	}
	
	public function setIdentity($identity) {
		$this->identity = trim($identity);
	}
	
	public function setCredential($credential) {
		$this->credential = trim($credential);
	}


	/**
	 * authenticate is extended to allow for multiple adapters
	 * identity/credential is set for each adapter;
	 * @see \Zend\Authentication\AuthenticationService::authenticate()
	 */
	public function authenticate(AdapterInterface $adapter = null) {
		if($adapter) $adapters = array($adapter);
		else $adapters = $this->adapter;
		
		foreach($adapters AS $adapter) {
			$adapter->setIdentity($this->identity);
			$adapter->setCredential($this->credential);
			
			$result = $adapter->authenticate();
			
			if ($this->hasIdentity()) {
				$this->clearIdentity();
			}
			
			if($result->isValid()) {
				$this->getStorage()->write($result->getIdentity());
				//$this->eventManager->trigger('login_successful', null, ['identity' => $result->getIdentity()]);
				return $result;
			}
		}
		return $result;
	}

}
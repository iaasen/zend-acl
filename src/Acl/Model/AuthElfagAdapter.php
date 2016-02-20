<?php

namespace Acl\Model;

use Zend\Authentication\Adapter\AbstractAdapter;
use Zend\Authentication\Result;

class AuthElfagAdapter extends AbstractAdapter {

	protected $identityPrefix = 'elfag-';
	protected $url = 'http://elfag.no/intranett/checkLogin.php';
	
	/**
	 * Sets username and password for authentication
	 *
	 * @return void
	 */
	public function __construct($identity = null, $credential = null) {
		if($identity) $this->identity = trim($identity);
		if($credential) $this->credential = trim($credential);
	}

	/**
	 * Performs an authentication attempt
	 *
	 * @return \Zend\Authentication\Result
	 * @throws \Zend\Authentication\Adapter\Exception\ExceptionInterface If authentication cannot be performed
	 */
	public function authenticate() {
		$ch = curl_init($this->url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array('u' => $this->identity, 'm' => md5($this->identity . ':' . $this->credential)));
		$response = strip_tags(curl_exec($ch));
	
		$response = json_decode($response);

		if(isset($response->status) && $response->status == 'success') {
			return new Result(Result::SUCCESS, $this->identityPrefix . $this->identity, array("Authentication successful."));
		}
		else {
			if($response->message == 'no user') return new Result(Result::FAILURE_IDENTITY_NOT_FOUND, null, array("User not found."));
			if($response->message == 'no match') return new Result(Result::FAILURE_CREDENTIAL_INVALID, null, array("Supplied credential is invalid."));
		}
		return new Result(Result::FAILURE_UNCATEGORIZED, null, array("Unexpected error."));
	}
	
}
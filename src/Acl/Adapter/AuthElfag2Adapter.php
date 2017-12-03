<?php
/**
 * Created by PhpStorm.
 * User: iaase
 * Date: 06.11.2017
 * Time: 22.40
 */

namespace Acl\Adapter;


use GuzzleHttp\Exception\ClientException;
use Iaasen\Transport\HttpTransportInterface;
use Zend\Authentication\Adapter\AbstractAdapter;
use Zend\Authentication\Result;

class AuthElfag2Adapter extends AbstractAdapter
{

	protected $httpTransport;

	public function __construct(HttpTransportInterface $httpClient)
	{
		$this->httpTransport = $httpClient;
	}

	/**
	 * Performs an authentication attempt
	 * @return Result
	 * @throws \Exception
	 */
	public function authenticate()
	{
		try {
			$tokenData = $this->httpTransport->sendPostWithJson('token', ['username' => $this->identity, 'password' => $this->credential]);
			$tokenData = \GuzzleHttp\json_decode($tokenData);
			if(strlen($tokenData->token)) return new Result(Result::SUCCESS, $tokenData->user_email);
			else return new Result(Result::FAILURE, null);
		}
		catch(ClientException $e) {
			$response = json_decode($e->getResponse()->getBody()->__toString());
			if($response->code == '[jwt_auth] incorrect_password') return new Result(Result::FAILURE_CREDENTIAL_INVALID, null);
			elseif($response->code == '[jwt_auth] empty_password') return new Result(Result::FAILURE_CREDENTIAL_INVALID, null);
			elseif($response->code == '[jwt_auth] invalid_email') return new Result(Result::FAILURE_IDENTITY_NOT_FOUND, null);
			elseif($response->code == '[jwt_auth] invalid_username') return new Result(Result::FAILURE_IDENTITY_NOT_FOUND, null);
			else return new Result(Result::FAILURE_UNCATEGORIZED, null);
		}
	}
}
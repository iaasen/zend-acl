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
use Laminas\Authentication\Result;
use Laminas\EventManager\EventManager;
use Laminas\EventManager\EventManagerAwareInterface;
use Laminas\EventManager\EventManagerInterface;

class AuthElfag2Adapter extends AbstractAuthAdapter implements EventManagerAwareInterface
{

	protected $httpTransport;
	/** @var EventManagerInterface */
	protected $eventManager;

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
			if(strlen($tokenData->token)) {
				$this->getEventManager()->trigger('user_elfag2_logged_in', get_class($this), ["tokenData" => $tokenData]);
				return new Result(Result::SUCCESS, $tokenData->user_email);
			}
			else return new Result(Result::FAILURE, null);
		}
		catch(ClientException $e) {
			$response = json_decode($e->getResponse()->getBody()->__toString());
			if($response->code == '[jwt_auth] incorrect_password') return new Result(Result::FAILURE_CREDENTIAL_INVALID, null);
			elseif($response->code == '[jwt_auth] empty_password') return new Result(Result::FAILURE_CREDENTIAL_INVALID, null);
			elseif($response->code == '[jwt_auth] invalid_email') return new Result(Result::FAILURE_IDENTITY_NOT_FOUND, null);
			elseif($response->code == '[jwt_auth] invalid_username') return new Result(Result::FAILURE_IDENTITY_NOT_FOUND, null);
			else return new Result(Result::FAILURE, null);
		}
	}

	/**
	 * Inject an EventManager instance
	 *
	 * @param  EventManagerInterface $eventManager
	 * @return void
	 */
	public function setEventManager(EventManagerInterface $eventManager)
	{
		$eventManager->setIdentifiers([__CLASS__, get_class($this)]);
		$this->eventManager = $eventManager;
	}


	/**
	 * Retrieve the event manager
	 *
	 * Lazy-loads an EventManager instance if none registered.
	 *
	 * @return EventManagerInterface
	 */
	public function getEventManager()
	{
		if (!$this->eventManager) {
			$this->setEventManager(new EventManager());
		}
		return $this->eventManager;
	}
}
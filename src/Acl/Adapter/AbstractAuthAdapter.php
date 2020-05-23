<?php
/**
 * Created by PhpStorm.
 * User: iaase
 * Date: 10.12.2017
 * Time: 22.59
 */

namespace Acl\Adapter;


use Iaasen\Messenger\SessionMessenger;
use Laminas\Authentication\Adapter\AbstractAdapter;


abstract class AbstractAuthAdapter extends AbstractAdapter
{
	/** @var  SessionMessenger */
	protected $flashMessenger;

	/**
	 * @return SessionMessenger
	 */
	protected function getFlashMessenger() {
		if(!$this->flashMessenger) $this->flashMessenger = new SessionMessenger();
		return $this->flashMessenger;
	}
}
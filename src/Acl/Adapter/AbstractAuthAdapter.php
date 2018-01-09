<?php
/**
 * Created by PhpStorm.
 * User: iaase
 * Date: 10.12.2017
 * Time: 22.59
 */

namespace Acl\Adapter;


use Zend\Authentication\Adapter\AbstractAdapter;
use Zend\Mvc\Plugin\FlashMessenger\FlashMessenger;

abstract class AbstractAuthAdapter extends AbstractAdapter
{
	/** @var  FlashMessenger */
	protected $flashMessenger;

	/**
	 * @return FlashMessenger
	 */
	protected function getFlashMessenger() {
		if(!$this->flashMessenger) $this->flashMessenger = new FlashMessenger();
		return $this->flashMessenger;
	}
}
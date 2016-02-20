<?php
namespace Acl\Model;

use Zend\Authentication\Storage;

class AclStorage extends Storage\Session {
	public function setRememberMe($_rememberMe = 0, $time = 1209600) {
		if($_rememberMe == 1) {
			$this->session->getManager()->rememberMe($time);
		}
	}
	
	public function forgetMe() {
		$this->session->getManager()->forgetMe();
	}
}
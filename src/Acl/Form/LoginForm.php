<?php
namespace Acl\Form;

//use Zend\Captcha\AdapterInterface as CaptchaAdapter;
//use Zend\Form\Element;
use Zend\Form\Form;

class LoginForm extends Form {
	public function __construct($name = null) {
		parent::__construct('user');
		
		$this->setAttribute('method', 'post');
		
		$this->add(array(
			'name' => 'redirect',
			'type' => 'Hidden',
		));
		
		$this->add(array(
			'name'		=> 'username',
			'options'	=> array(
				'label'	=> 'Brukernavn',
			),
			'attributes' => array(
				'autofocus' => 'autofocus',
			),
			'type'		=> 'Text',
		));
		
		$this->add(array(
			'name'		=> 'password',
			'options'	=> array(
				'label' => 'Passord',
			),
			'type'		=> 'Password',
		));
		
		$this->add(array(
			'name'		=> 'rememberMe',
			'options'	=> array(
				'label' => 'Husk meg',
			),
			'type'		=> 'Checkbox',
		));

		$this->add(array(
			'name'		=> 'submit',
			'options'	=> array(
				'label' => 'Logg inn',
			),
			'attributes'	=> array(
				'value' => 'Logg inn',
			),
			'type'		=> 'Submit',
		));
	}
}
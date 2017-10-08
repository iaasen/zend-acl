<?php

namespace Acl\Form;

use Zend\Form\Form;
use Zend\Hydrator\ArraySerializable;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\Stdlib\Hydrator\ArraySerializable as ArrayHydrator;
use Zend\InputFilter\InputFilter;
use Acl\Model\User;

class EditUserForm extends Form implements InputFilterProviderInterface {

	public function __construct($user) {
		// we want to ignore the name passed
		parent::__construct('useredit');
		$this->setAttribute('method', 'post');
		$this->setAttribute('autocomplete', 'off');
		$this->setHydrator(new ArraySerializable());
		$this->setInputFilter(new InputFilter());
		$this->setObject(new User());
		
		$this->add(array (
			'name' => 'id',
			'type' => 'Hidden'
		));
		$this->add(array (
			'name' => 'username', // Slash to avoid Firefox autofilling password
			'type' => 'Text',
			'options' => array (
				'label' => 'Brukernavn'
			) 
		));
		$this->add(array (
			'name' => 'name',
			'type' => 'Text',
			'attributes' => array (
				'autofocus' => 'autofocus'
			),
			'options' => array (
				'label' => 'Navn'
			) 
		));
		$this->add(array (
			'name' => 'old_password',
			'type' => 'Password',
// 			'attributes' => array(
// 				'placeholder' => ($user->password) ? 'Skriv nytt passord her' : ''
// 			),
			'options' => array (
				'label' => 'Gammelt passord',
			),
		));
		$this->add(array (
			'name' => 'new_password',
			'type' => 'Password',
// 			'attributes' => array(
// 				'placeholder' => ($user->password) ? 'Skriv nytt passord her' : ''
// 			),
			'options' => array (
				'label' => 'Nytt passord',
			),
		));
		$this->add(array (
			'name' => 'new_password_confirm',
			'type' => 'Password',
// 			'attributes' => array(
// 				'placeholder' => ($user->password) ? 'Bekreft nytt passord her' : ''
// 			),
			'options' => array (
				'label' => 'Bekreft nytt passord',
			),
		));
		$this->add(array (
			'name' => 'email',
			'type' => 'Text',
			'options' => array (
				'label' => 'E-post'
			) 
		));
		$this->add(array (
			'name' => 'access_level',
			'type' => 'Select',
			'options' => array (
				'label' => 'Tilgangsnivå',
				'value_options' => $user->getValueOptions(),
// 				'value_options' => array(
// 					array('value' => 0, 'label' => 'Ingen'),
// 					array('value' => 1, 'label' => 'Les'),
// 					array('value' => 2, 'label' => 'Rediger egne'),
// 					array('value' => 3, 'label' => 'Rediger alle'),
// 					array('value' => 4, 'label' => 'Administrator'),
// 					array('value' => 5, 'label' => 'Superbruker')
// 				)
			)
		));
		
		$this->add(array (
				'name' => 'submit',
				'type' => 'Submit',
				'attributes' => array (
						'value' => 'Lagre',
						'id' => 'submitbutton'
				),
				'options' => array(
						'label' => 'Lagre',
				),
		));
	}

	public function getInputFilterSpecification() {
		return array (
			'id' => array (
				'required' => false,
				'filters' => array (
					array (
						'name' => 'Int' 
					) 
				) 
			),
			'username' => array (
				'required' => false,
				'filters' => array (
					array (
						'name' => 'StripTags' 
					),
					array (
						'name' => 'StringTrim' 
					) 
				),
				'validators' => array (
					array (
						'name' => 'StringLength',
						'options' => array (
							'encoding' => 'UTF-8',
							'min' => 1,
							'max' => 100 
						) 
					) 
				) 
			),
			'new_password' => array (
				'required' => false,
				'filters' => array (
					array (
						'name' => 'StringTrim' 
					) 
				),
				'validators' => array (
					array (
						'name' => 'StringLength',
						'options' => array (
							'encoding' => 'UTF-8',
							'min' => 1,
							'max' => 100 
						) 
					) 
				) 
			),
			'new_password_confirm' => array (
				'required' => false,
				'filters' => array (
					array (
						'name' => 'StringTrim' 
					) 
				),
				'validators' => array (
					array (
						'name' => 'Identical',
						'options' => array(
							'token' => 'new_password'
						)
					),
					array (
						'name' => 'StringLength',
						'options' => array (
							'encoding' => 'UTF-8',
							'min' => 1,
							'max' => 100 
						) 
					) 
				) 
			),
			'name' => array (
				'required' => false,
				'filters' => array (
					array (
						'name' => 'StripTags' 
					),
					array (
						'name' => 'StringTrim' 
					) 
				),
				'validators' => array (
					array (
						'name' => 'StringLength',
						'options' => array (
							'encoding' => 'UTF-8',
							'min' => 1,
							'max' => 100 
						) 
					) 
				) 
			),
			'email' => array (
				'required' => false,
				'filters' => array (
					array (
						'name' => 'StripTags' 
					),
					array (
						'name' => 'StringTrim' 
					) 
				),
				'validators' => array (
					array (
						'name' => 'StringLength',
						'options' => array (
							'encoding' => 'UTF-8',
							'min' => 1,
							'max' => 100 
						) 
					) 
				) 
			),
			'access_level' => array (
				'required' => false,
				'filters' => array (
					array (
						'name' => 'Int'
					)
				)
			),
		);
	}
}
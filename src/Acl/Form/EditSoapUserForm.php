<?php

namespace Acl\Form;

use Laminas\Form\Form;
use Laminas\Hydrator\ArraySerializableHydrator;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\InputFilter\InputFilter;
use Acl\Model\User;

class EditSoapUserForm extends Form implements InputFilterProviderInterface {

	public function __construct($user) {
		parent::__construct('soapuseredit');
		$this->setAttribute('method', 'post');
		$this->setAttribute('autocomplete', 'off');
		$this->setHydrator(new ArraySerializableHydrator());
		$this->setInputFilter(new InputFilter());
		$this->setObject(new User());
		
		$this->add(array (
			'name' => 'id',
			'type' => 'Hidden'
		));
		$this->add(array (
			'name' => 'name',
			'type' => 'Text',
			'attributes' => array (
				'autofocus' => 'autofocus'
			),
			'options' => array (
				'label' => _('Description')
			) 
		));
		$this->add(array (
			'name' => 'username',
			'type' => 'Text',
			'options' => array (
				'label' => _('Username'),
			) 
		));
		$this->add(array (
			'name' => 'new_password',
			'type' => 'Text',
// 			'attributes' => array(
// 				'placeholder' => ($user->password) ? 'Skriv nytt passord her' : ''
// 			),
			'options' => array (
				'label' => _('Password'),
			),
		));
		$this->add(array (
			'name' => 'access_level',
			'type' => 'Select',
			'options' => array (
				'label' => _('Access level'),
				'value_options' => $user->getValueOptions()
// 				'value_options' => array(
// 					0 => _('None'),
// 					1 => _('View'),
// 					3 => _('Edit all'),
// 				),
// 				'value' => 1,
			)
		));
		
		$this->add(array (
				'name' => 'submit',
				'type' => 'Submit',
				'attributes' => array (
						'id' => 'submitbutton'
				),
				'options' => array(
						'label' => _('Save'),
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
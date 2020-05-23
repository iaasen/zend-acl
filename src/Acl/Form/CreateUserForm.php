<?php
namespace Acl\Form;

//use Laminas\Captcha\AdapterInterface as CaptchaAdapter;
//use Laminas\Form\Element;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Acl\Model\User;

class CreateUserForm extends Form implements InputFilterProviderInterface {
	public function __construct($name = null) {
		parent::__construct('user');
		
		$this->setAttribute('method', 'post');
		
		$this->add(array(
			'name' => 'group',
			'type' => 'Hidden',
		));
		
		$this->add(array(
			'name'		=> 'name',
			'options'	=> array(
				'label'	=> 'Navn:',
			),
			'attributes' => array(
				'autofocus' => 'autofocus',
			),
			'type'		=> 'Text',
		));
		
		$this->add(array(
			'name'		=> 'email',
			'options'	=> array(
				'label' => 'E-post:',
			),
			'type'		=> 'Text',
		));
		
		$this->add(array(
			'name'		=> 'email_confirm',
			'options'	=> array(
				'label' => 'Bekreft e-post:',
			),
			'type'		=> 'Text',
		));
		
		$this->add(array(
			'type'		=> 'Select',
			'name'		=> 'access_level',
			'options'	=> array(
				'label' => 'Tilgangsnivå:',
				'value_options' => User::$access_level,
			),
		));
		
		$this->add(array(
			'type'		=> 'checkbox',
			'name'		=> 'onnshop',
			'options'	=> array(
				'label' => 'Tilgang til å bestille på Onnshop:',
			),
		));
		
		$this->add(array(
			'type'		=> 'Submit',
			'name'		=> 'submit',
			'attributes'	=> array(
				'value' => 'Opprett',
			),
			'options' => array(
				'label' => 'Opprett',
			),
		));
	}
	
	public function getInputFilterSpecification() {
		return array(
			'group' => array(
				'required' => true,
				'filters' => array(
					array(
						'name' => 'Int',
					)
				),
			),
			'name' => array(
				'required' => true,
				'filters' => array(
					array(
						'name' => 'StripTags'
					),
					array(
						'name' => 'StringTrim'
					)
				),
				'validators' => array(
					array(
						'name' => 'StringLength',
						'options' => array(
							'encoding' => 'UTF-8',
							'min' => 1,
							'max' => 100
						)
					)
				)
			),
			'email' => array(
				'required' => true,
				'filters' => array(
					array(
						'name' => 'StripTags'
					),
					array(
						'name' => 'StringTrim'
					)
				),
				'validators' => array(
					array(
						'name' => 'EmailAddress',
					)
				)
			),
			'email_confirm' => array(
				'required' => true,
				'filters' => array(
					array(
						'name' => 'StripTags'
					),
					array(
						'name' => 'StringTrim'
					)
				),
				'validators' => array(
					array(
						'name' => 'Identical',
						'options' => array(
							'token' => 'email',
						),
					),
					array(
						'name' => 'EmailAddress',
					),
				)
			),
			'access_level' => array(
				'required' => false,
				'filters' => array(
					array(
						'name' => 'Int'
					)
				)
			),
			'onnshop' => array(
				'required' => false,
				'filters' => array(
					array(
						'name' => 'Boolean'
					)
				)
			),
		);
	}
}
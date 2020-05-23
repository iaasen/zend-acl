<?php

namespace Acl\Form;

use Priceestimator\Form\Element\Submit;
use Priceestimator\Form\Element\Text;
use Laminas\Filter\StringTrim;
use Laminas\Filter\StripTags;
use Laminas\Filter\ToInt;
use Laminas\Form\Element\Hidden;
use Laminas\Form\Element\Password;
use Laminas\Form\Element\Select;
use Laminas\Form\Form;
use Laminas\Hydrator\ArraySerializableHydrator;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\InputFilter\InputFilter;
use Acl\Model\User;
use Laminas\Validator\Identical;
use Laminas\Validator\StringLength;

class EditUserForm extends Form implements InputFilterProviderInterface {

	public function __construct(User $user) {
		// we want to ignore the name passed
		parent::__construct('useredit');
		$this->setAttribute('method', 'post');
		//$this->setAttribute('autocomplete', 'off');
		$this->setHydrator(new ArraySerializableHydrator());
		$this->setInputFilter(new InputFilter());
		$this->setObject(new User());
		
		$this->add([
			'name' => 'id',
			'type' => Hidden::class
		]);
		$this->add([
			'name' => 'username', // Slash to avoid Firefox autofilling password
			'type' => Text::class,
			'options' => [
				'label' => 'Brukernavn'
			]
		]);
		$this->add([
			'name' => 'name',
			'type' => Text::class,
			'attributes' => [
				'autofocus' => 'autofocus'
			],
			'options' => [
				'label' => 'Navn'
			]
		]);

		$this->add([
			'name' => 'old_password',
			'type' => Password::class,
			'options' => [
				'label' => 'Gammelt passord',
			],
		]);

		$this->add([
			'name' => 'new_password',
			'type' => Password::class,
			'options' => [
				'label' => 'Nytt passord',
			],
		]);

		$this->add([
			'name' => 'new_password_confirm',
			'type' => Password::class,
			'options' => [
				'label' => 'Bekreft nytt passord',
			],
		]);

		$this->add([
			'name' => 'email',
			'type' => Text::class,
			'options' => [
				'label' => 'E-post'
			]
		]);

		$this->add([
			'name' => 'access_level',
			'type' => Select::class,
			'options' => [
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
			]
		]);
		
		$this->add([
				'name' => 'submit',
				'type' => Submit::class,
				'attributes' => [
						'value' => 'Lagre',
						'id' => 'submitbutton'
				],
				'options' => [
						'label' => 'Lagre',
				],
		]);
	}

	public function getInputFilterSpecification() {
		return [
			'id' => [
				'required' => false,
				'filters' => [
					['name' => ToInt::class]
				]
			],
			'username' => [
				'required' => false,
				'filters' => [
					['name' => StripTags::class],
					['name' => StringTrim::class],
				],
				'validators' => [
					[
						'name' => StringLength::class,
						'options' => [
							'min' => 1,
							'max' => 100
						]
					]
				]
			],
			'new_password' => [
				'required' => false,
				'filters' => [
					['name' => StringTrim::class]
				],
				'validators' => [
					[
						'name' => StringLength::class,
						'options' => [
							'min' => 1,
							'max' => 100
						]
					]
				]
			],
			'new_password_confirm' => [
				'required' => false,
				'filters' => [
					['name' => StringTrim::class]
				],
				'validators' => [
					[
						'name' => Identical::class,
						'options' => [
							'token' => 'new_password'
						]
					],
					[
						'name' => StringLength::class,
						'options' => [
							'min' => 1,
							'max' => 100
						]
					]
				]
			],
			'name' => [
				'required' => false,
				'filters' => [
					['name' => StripTags::class],
					['name' => StringTrim::class]
				],
				'validators' => [
					[
						'name' => StringLength::class,
						'options' => [
							'min' => 1,
							'max' => 100
						]
					]
				]
			],
			'email' => [
				'required' => false,
				'filters' => [
					['name' => StripTags::class],
					['name' => StringTrim::class]
				],
				'validators' => [
					[
						'name' => StringLength::class,
						'options' => [
							'min' => 1,
							'max' => 100
						]
					]
				]
			],
			'access_level' => [
				'required' => false,
				'filters' => [
					['name' => ToInt::class]
				]
			],
		];
	}
}
<?php
namespace Acl\Form;

use Iaasen\Form\Element\Checkbox;
use Iaasen\Form\Element\Submit;
use Iaasen\Form\Element\Text;
use Laminas\Form\Element\Password;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\StringLength;

class LoginForm extends Form implements InputFilterProviderInterface {
	public function __construct($name = null) {
		parent::__construct('user');
		
		$this->setAttribute('method', 'post');

		$this->add([
			'name' => 'username',
			'type' => Text::class,
			'options' => [
				'label'	=> 'Brukernavn/E-post',
			],
			'attributes' => [
				'autofocus' => 'autofocus',
			],
		]);
		
		$this->add([
			'name' => 'password',
			'type' => Password::class,
			'options' => [
				'label' => 'Passord',
			],
		]);
		
		$this->add([
			'name' => 'rememberMe',
			'type' => Checkbox::class,
			'options' => [
				'label' => 'Husk meg',
			],
		]);

		$this->add([
			'name' => 'submit',
			'type' => Submit::class,
			'options' => [
				'label' => 'Logg inn',
			],
			'attributes' => [
				'value' => 'Logg inn',
			],
		]);
	}


	/**
	 * Should return an array specification compatible with
	 * {@link Laminas\InputFilter\Factory::createInputFilter()}.
	 *
	 * @return array
	 */
	public function getInputFilterSpecification()
	{
		return [
			'password' => [
				'validators' => [
					[
						'name' => StringLength::class,
						'options' => [
							'min' => 1,
						]
					],

				],
			],
		];
	}
}
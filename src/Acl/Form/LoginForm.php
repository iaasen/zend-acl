<?php
namespace Acl\Form;

use Oppned\Form\Element\Checkbox;
use Oppned\Form\Element\Submit;
use Oppned\Form\Element\Text;
use Zend\Form\Element\Hidden;
use Zend\Form\Element\Password;
use Zend\Form\Form;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\Validator\StringLength;

class LoginForm extends Form implements InputFilterProviderInterface {
	public function __construct($name = null) {
		parent::__construct('user');
		
		$this->setAttribute('method', 'post');
		
		$this->add([
			'name' => 'redirect',
			'type' => Hidden::class,
		]);
		
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
	 * {@link Zend\InputFilter\Factory::createInputFilter()}.
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
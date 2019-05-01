<?php

namespace Acl\Form;

use Zend\Form\Form;
use Zend\Hydrator\ArraySerializableHydrator;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\InputFilter\InputFilter;
use Acl\Model\User;

class EditTokenForm extends Form implements InputFilterProviderInterface {

	public function __construct($name = null, $selectValues = null) {
		// we want to ignore the name passed
		parent::__construct('useredit');
		$this->setAttribute('method', 'post');
		$this->setHydrator(new ArraySerializableHydrator());
		$this->setInputFilter(new InputFilter());
		$this->setObject(new User());
		
		$this->add(array (
			'name' => 'id',
			'type' => 'Hidden'
		));
		$this->add(array (
			'name' => 'template_id',
			'type' => 'Hidden'
		));
		$this->add(array (
			'name' => 'username',
			'type' => 'Text',
			'attributes' => array (
				'autofocus' => 'autofocus'
			),
			'options' => array (
				'label' => 'Tittel'
			) 
		));
		$this->add(array (
			'name' => 'date',
			'type' => 'Date',
			'options' => array (
				'label' => 'Dato',
				'format' => 'd.m.Y'
			),
			'attributes' => array (
				'step' => '1' 
			) // days; default step interval is 1 day )
 
		));
		
		// Pricelist
		$this->add(array (
			'name' => 'price_calculation',
			'type' => 'Select',
			'options' => array (
				'label' => 'Prisberegning',
				'value_options' => $selectValues['price_calculation'],
			)
		));
		$this->add(array (
			'name' => 'pricelists_id',
			'type' => 'Select',
			'options' => array (
				'label' => 'Prisliste for punkter',
				'value_options' => $selectValues['pricelists_id'],
			)
		));
		$this->add(array (
			'name' => 'productprice_source',
			'type' => 'Select',
			'options' => array (
				'label' => 'Prisliste for produkter',
				'value_options' => $selectValues['productprice_source'],
			)
		));
		$this->add(array (
			'name' => 'itemprice_percent',
			'type' => 'Number',
			'options' => array (
				'label' => 'Påslag på punktpris (%)'
			)
		));
		$this->add(array (
			'name' => 'productprice_percent',
			'type' => 'Number',
			'options' => array (
				'label' => 'Påslag på produktpris (%)'
			)
		));
		$this->add(array (
			'name' => 'quotation_discount',
			'type' => 'Number',
			'options' => array (
				'label' => 'Rabatt på totalsum (%)'
			)
		));
		$this->add(array (
			'name' => 'update_prices',
			'type' => 'Checkbox',
			'options' => array (
				'label' => 'Oppdater med priser fra prislistene'
			)
		));
		
		// Customer info
		$this->add(array (
			'name' => 'customer_name',
			'type' => 'Text',
			'options' => array (
				'label' => 'Navn' 
			) 
		));
		$this->add(array (
			'name' => 'customer_address1',
			'type' => 'Text',
			'options' => array (
				'label' => 'Adresse 1' 
			) 
		));
		$this->add(array (
			'name' => 'customer_address2',
			'type' => 'Text',
			'options' => array (
				'label' => 'Adresse 2' 
			) 
		));
		$this->add(array (
			'name' => 'customer_postalcode',
			'type' => 'Text',
			'options' => array (
				'label' => 'Postnr' 
			) 
		));
		$this->add(array (
			'name' => 'customer_postalarea',
			'type' => 'Text',
			'options' => array (
				'label' => 'Poststed' 
			) 
		));
		
		// Report settings
		$this->add(array (
			'name' => 'pdf_pre_heading',
			'type' => 'Text',
			'options' => array (
				'label' => 'Overskrift' 
			) 
		));
		$this->add(array (
			'name' => 'pdf_pre_text',
			'type' => 'Textarea',
			'options' => array (
				'label' => 'Innledende tekst' 
			) 
		));
		$this->add(array (
			'name' => 'pdf_post_heading',
			'type' => 'Text',
			'options' => array (
				'label' => 'Vilkår overskrift' 
			) 
		));
		$this->add(array (
			'name' => 'pdf_post_text',
			'type' => 'Textarea',
			'options' => array (
				'label' => 'Vilkår tekst' 
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
			'template_id' => array (
				'required' => false,
				'filters' => array (
					array (
						'name' => 'Int' 
					) 
				) 
			),
			'plant_id' => array (
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
			'custom_id' => array (
				'required' => false,
				'filters' => array (
					array (
						'name' => 'Int' 
					) 
				) 
			),
			'title' => array (
				'required' => true,
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
			'date' => array (
				'required' => true,
				'filters' => array (
					array (
						'name' => 'StringTrim' 
					),
					array (
						'name' => 'DateTimeFormatter',
						'options' => array (
							'format' => 'd.m.Y' 
						) 
					) 
				),
				'validators' => array (
					array (
						'name' => 'Date',
						'options' => array (
							'format' => 'd.m.Y' 
						) 
					) 
				) 
			),
			'price_calculation' => array (
				'required' => true,
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
			'pricelists_id' => array (
				'required' => false,
				'filters' => array (
					array (
						'name' => 'Int' 
					) 
				) 
			),
			'productprice_source' => array (
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
			'itemprice_percent' => array (
				'required' => false,
				'filters' => array (
					array (
						'name' => 'Int' 
					) 
				) 
			),
			'productprice_percent' => array (
				'required' => false,
				'filters' => array (
					array (
						'name' => 'Int' 
					) 
				) 
			),
			'quotation_discount' => array (
				'required' => false,
				'filters' => array (
					array (
						'name' => 'Int' 
					) 
				) 
			),
			'update_prices' => array (
				'required' => false,
				'filters' => array (
					array (
						'name' => 'Boolean' 
					) 
				) 
			),
			'customer_name' => array (
				'required' => true,
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
			'customer_address1' => array (
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
			'customer_address2' => array (
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
			'customer_postalcode' => array (
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
			'customer_postalarea' => array (
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
			'pdf_pre_heading' => array (
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
			'pdf_pre_text' => array (
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
							'encoding' => 'UTF-8' 
						) 
					) 
				) 
			),
			'pdf_post_heading' => array (
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
			'pdf_post_text' => array (
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
							'encoding' => 'UTF-8' 
						) 
					) 
				) 
			) 
		);
	}
}
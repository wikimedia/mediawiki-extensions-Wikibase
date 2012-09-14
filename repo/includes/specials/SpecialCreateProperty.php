<?php

/**
 * Page for creating new Wikibase properties.
 *
 * @since 0.1
 *
 * @file 
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 */
class SpecialCreateProperty extends SpecialCreateEntity {

	/**
	 * @var string
	 */
	protected $datatype = null;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 */
	public function __construct() {
		parent::__construct( 'CreateProperty' );
	}

	/**
	 * @see SpecialCreateEntity::prepareArguments()
	 */
	protected function prepareArguments() {
		parent::prepareArguments();
		$this->datatype = $this->getRequest()->getVal( 'datatype', isset( $this->parts[2] ) ? $this->parts[2] : '' );
		return true;
	}

	/**
	 * @see SpecialCreateEntity::hasSufficientArguments()
	 */
	protected function hasSufficientArguments() {
		// TODO: Needs refinement
		return parent::hasSufficientArguments() && ( $this->datatype !== '' );
	}

	/**
	 * @see SpecialCreateEntity::createEntity()
	 */
	protected function createEntity() {
		return \Wikibase\PropertyContent::newEmpty();
	}

	/**
	 * @see SpecialCreateEntity::modifyEntity()
	 */
	protected function modifyEntity( \Wikibase\EntityContent &$entity ) {
		$status = parent::modifyEntity( $entity );
		$lang = $this->getLanguage()->getCode();
		if ( $this->datatype !== '' ) {
			// how to set this and where to get it
			//$entity->getEntity()->setDatatype( $lang, $this->datatype );
		}
		return $status;
	}

	/**
	 * @see SpecialCreateEntity::additionalFormElements()
	 */
	protected function additionalFormElements() {
		return parent::additionalFormElements()
			. Html::element(
				'label',
				array(
					'for' => 'wb-createproperty-datatype',
					'class' => 'wb-label'
				),
				$this->msg( 'wikibase-createproperty-datatype' )->text()
			)
			. Html::openElement(
				'select',
				array(
					'id' => 'wb-createproperty-datatype',
					'class' => 'wb-select'
				)
			)
			. Html::element(
				'option',
				array(),
				'data type 1'
			)
			. Html::element(
				'option',
				array(),
				'data type 2'
			)
			. Html::element( 'br' );
	}

	/**
	 * @see SpecialCreateEntity::getLegend()
	 */
	protected function getLegend() {
		return $this->msg( 'wikibase-createproperty-fieldset' );
	}

}

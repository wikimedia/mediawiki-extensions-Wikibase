<?php

/*
 * NOTE: This class is disabled in the phase 1 beta branch!
 *       See $wgSpecialPages and $wgAutoloadClasses in Wikibase.php
 */

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
			. $this->getDataTypes()
			. Html::element( 'br' );
	}

	protected function getDataTypes() {
		$html = '';
		foreach ( \Wikibase\Settings::get( 'testDataTypes' ) as $option ) {
			$html .= Html::element(
				'option',
				array(),
				$option
			);
		}
		return
			\Html::rawElement(
				'select',
				array(
					'name' => 'datatype',
					'id' => 'wb-createproperty-datatype',
					'class' => 'wb-select'
				),
				$html
			);
	}

	/**
	 * @see SpecialCreateEntity::getLegend()
	 */
	protected function getLegend() {
		return $this->msg( 'wikibase-createproperty-fieldset' );
	}

}

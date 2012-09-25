<?php

use Wikibase\PropertyContent, Wikibase\EntityContent;

/**
 * Page for creating new Wikibase properties.
 *
 * @since 0.2
 *
 * @file 
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 */
class SpecialCreateProperty extends SpecialCreateEntity {

	/**
	 * @since 0.2
	 * 
	 * @var string|null
	 */
	protected $dataType = null;

	/**
	 * Constructor.
	 *
	 * @since 0.2
	 */
	public function __construct() {
		parent::__construct( 'CreateProperty' );
	}

	/**
	 * @see SpecialCreateEntity::prepareArguments()
	 */
	protected function prepareArguments() {
		parent::prepareArguments();
		$this->dataType = $this->getRequest()->getVal( 'datatype', isset( $this->parts[2] ) ? $this->parts[2] : '' );
		return true;
	}

	/**
	 * @see SpecialCreateEntity::hasSufficientArguments()
	 */
	protected function hasSufficientArguments() {
		// TODO: Needs refinement
		return parent::hasSufficientArguments() && ( $this->dataType !== '' );
	}

	/**
	 * @see SpecialCreateEntity::createEntity()
	 */
	protected function createEntity() {
		return \Wikibase\PropertyContent::newEmpty();
	}

	/**
	 * @see SpecialCreateEntity::modifyEntity()
	 *
	 * @param EntityContent $propertyContent
	 *
	 * @return Status
	 */
	protected function modifyEntity( EntityContent &$propertyContent ) {
		/**
		 * @var PropertyContent $propertyContent
		 */
		$status = parent::modifyEntity( $propertyContent );

		if ( $this->dataType !== '' ) {
			// TODO: lookup property by lang+label rather then by id
			$lang = $this->getLanguage()->getCode();

			try {
				$propertyContent->getProperty()->setDataTypeById( $this->dataType );
			}
			catch ( MWException $exception ) {
				// TODO: we want a nice internationalized error message
				$status->fatal( $exception->getText() );
			}
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

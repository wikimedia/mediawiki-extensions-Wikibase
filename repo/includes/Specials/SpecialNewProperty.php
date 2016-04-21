<?php

namespace Wikibase\Repo\Specials;

use InvalidArgumentException;
use Status;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataTypeSelector;
use Wikibase\Repo\WikibaseRepo;

/**
 * Page for creating new Wikibase properties.
 *
 * @since 0.2
 *
 * @license GPL-2.0+
 * @author John Erling Blad < jeblad@gmail.com >
 */
class SpecialNewProperty extends SpecialNewEntity {

	/**
	 * @var string|null
	 */
	private $dataType = null;

	/**
	 * @since 0.2
	 */
	public function __construct() {
		parent::__construct( 'NewProperty', 'property-create' );
	}

	public function doesWrites() {
		return true;
	}

	/**
	 * @see SpecialNewEntity::prepareArguments
	 */
	protected function prepareArguments() {
		parent::prepareArguments();

		$this->dataType = $this->getRequest()->getVal(
			'datatype',
			isset( $this->parts[2] ) ? $this->parts[2] : ''
		);
	}

	/**
	 * @see SpecialNewEntity::hasSufficientArguments()
	 */
	protected function hasSufficientArguments() {
		// TODO: Needs refinement
		return parent::hasSufficientArguments() && ( $this->dataType !== '' );
	}

	/**
	 * @see SpecialNewEntity::createEntity
	 */
	protected function createEntity() {
		return Property::newFromType( 'string' );
	}

	/**
	 * @see SpecialNewEntity::modifyEntity
	 *
	 * @param EntityDocument $property
	 *
	 * @throws InvalidArgumentException
	 * @return Status
	 */
	protected function modifyEntity( EntityDocument &$property ) {
		$status = parent::modifyEntity( $property );

		if ( $this->dataType !== '' ) {
			if ( !( $property instanceof Property ) ) {
				throw new InvalidArgumentException( 'Unexpected entity type' );
			}

			if ( $this->dataTypeExists() ) {
				$property->setDataTypeId( $this->dataType );
			} else {
				$status->fatal( 'wikibase-newproperty-invalid-datatype' );
			}
		}

		return $status;
	}

	protected function dataTypeExists() {
		$dataTypeFactory = WikibaseRepo::getDefaultInstance()->getDataTypeFactory();
		$ids = $dataTypeFactory->getTypeIds();
		return in_array( $this->dataType, $ids );
	}

	/**
	 * @see SpecialNewEntity::additionalFormElements()
	 */
	protected function additionalFormElements() {
		$dataTypeFactory = WikibaseRepo::getDefaultInstance()->getDataTypeFactory();

		$selector = new DataTypeSelector( $dataTypeFactory->getTypes(), $this->getLanguage()->getCode() );

		$formDescriptor = parent::additionalFormElements();
		$formDescriptor['datatype'] = array(
			'name' => 'datatype',
			'type' => 'select',
			'options' => array_flip( $selector->getOptionsArray() ),
			'id' => 'wb-newproperty-datatype',
			'label-message' => 'wikibase-newproperty-datatype'
		);

		return $formDescriptor;
	}

	/**
	 * @see SpecialNewEntity::getLegend()
	 */
	protected function getLegend() {
		return $this->msg( 'wikibase-newproperty-fieldset' );
	}

	/**
	 * @see SpecialCreateEntity::getWarnings()
	 */
	protected function getWarnings() {
		$warnings = [];

		if ( $this->getUser()->isAnon() ) {
			$warnings[] = $this->msg(
				'wikibase-anonymouseditwarning',
				$this->msg( 'wikibase-entity-property' )
			);
		}

		return $warnings;
	}

}

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
	protected function modifyEntity( EntityDocument $property ) {
		$status = parent::modifyEntity( $property );

		if ( $this->dataType !== '' ) {
			if ( !( $property instanceof Property ) ) {
				throw new InvalidArgumentException( 'Unexpected entity type' );
			}

			if ( $this->dataTypeExists( $this->dataType ) ) {
				$property->setDataTypeId( $this->dataType );
			} else {
				$status->fatal( 'wikibase-newproperty-invalid-datatype' );
			}
		}

		return $status;
	}

	/**
	 * @return bool
	 */
	private function dataTypeExists( $dataType ) {
		$dataTypeFactory = WikibaseRepo::getDefaultInstance()->getDataTypeFactory();

		return in_array( $dataType, $dataTypeFactory->getTypeIds() );
	}

	/**
	 * @see SpecialNewEntity::additionalFormElements()
	 *
	 * @return array[]
	 */
	protected function additionalFormElements() {
		$formDescriptor = parent::additionalFormElements();

		$dataTypeFactory = WikibaseRepo::getDefaultInstance()->getDataTypeFactory();
		$selector = new DataTypeSelector( $dataTypeFactory->getTypes(), $this->getLanguage()->getCode() );

		$formDescriptor['datatype'] = [
			'name' => 'datatype',
			'type' => 'select',
			'default' => $this->dataType,
			'options' => array_flip( $selector->getOptionsArray() ),
			'id' => 'wb-newproperty-datatype',
			'validation-callback' => function ( $dataType, $formData, $form ) {
				if ( !$this->dataTypeExists( $dataType ) ) {
					return [ $this->msg( 'wikibase-newproperty-invalid-datatype' )->text() ];
				}

				return true;
			},
			'label-message' => 'wikibase-newproperty-datatype'
		];

		return $formDescriptor;
	}

	/**
	 * @see SpecialNewEntity::getLegend()
	 */
	protected function getLegend() {
		return $this->msg( 'wikibase-newproperty-fieldset' );
	}

	/**
	 * @see SpecialCreateEntity::getWarnings
	 *
	 * @return string[]
	 */
	protected function getWarnings() {
		if ( $this->getUser()->isAnon() ) {
			return [
				$this->msg(
					'wikibase-anonymouseditwarning',
					$this->msg( 'wikibase-entity-property' )
				),
			];
		}

		return [];
	}

	/**
	 * @param array $formData
	 *
	 * @return Status
	 */
	protected function validateFormData( array $formData ) {
		if ( $formData['label'] === '' && $formData['description'] === '' && $formData['aliases'] === '' ) {
			return Status::newFatal( 'You need to fill either label, description, or aliases.' );
		}

		return Status::newGood();
	}
}

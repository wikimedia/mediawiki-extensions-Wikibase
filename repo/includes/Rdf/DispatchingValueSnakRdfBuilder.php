<?php

namespace Wikibase\Rdf;

use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikimedia\Assert\Assert;
use Wikimedia\Purtle\RdfWriter;

/**
 * Dispatching implementation of ValueSnakRdfBuilder. This allows extensions to register
 * ValueSnakRdfBuilders for custom data types.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class DispatchingValueSnakRdfBuilder implements ValueSnakRdfBuilder {

	/**
	 * @var ValueSnakRdfBuilder[]
	 */
	private $valueBuilders;

	/**
	 * @param ValueSnakRdfBuilder[] $valueBuilders ValueSnakRdfBuilder objects keyed by data type
	 * (with prefix "PT:") or value type (with prefix "VT:").
	 */
	public function __construct( array $valueBuilders ) {
		Assert::parameterElementType( ValueSnakRdfBuilder::class, $valueBuilders, '$valueBuilders' );

		$this->valueBuilders = $valueBuilders;
	}

	/**
	 * Adds specific value
	 *
	 * @param RdfWriter $writer
	 * @param string $propertyValueNamespace Property value relation namespace
	 * @param string $propertyValueLName Property value relation name
	 * @param string $dataType Property data type
	 * @param PropertyValueSnak $snak
	 */
	public function addValue(
		RdfWriter $writer,
		$propertyValueNamespace,
		$propertyValueLName,
		$dataType,
		PropertyValueSnak $snak
	) {
		$valueType = $snak->getDataValue()->getType();
		$builder = $this->getValueBuilder( $dataType, $valueType );

		if ( $builder ) {
			$builder->addValue( $writer, $propertyValueNamespace, $propertyValueLName, $dataType, $snak );
		}
	}

	/**
	 * @param string|null $dataTypeId
	 * @param string $dataValueType
	 *
	 * @return null|ValueSnakRdfBuilder
	 */
	private function getValueBuilder( $dataTypeId, $dataValueType ) {
		if ( $dataTypeId !== null ) {
			if ( isset( $this->valueBuilders["PT:$dataTypeId"] ) ) {
				return $this->valueBuilders["PT:$dataTypeId"];
			}
		}

		if ( isset( $this->valueBuilders["VT:$dataValueType"] ) ) {
			return $this->valueBuilders["VT:$dataValueType"];
		}

		if ( $dataTypeId !== null ) {
			wfLogWarning( __METHOD__ . ": No RDF builder defined for data type $dataTypeId nor for value type $dataValueType." );
		} else {
			wfLogWarning( __METHOD__ . ": No RDF builder defined for value type $dataValueType." );
		}

		return null;
	}

}

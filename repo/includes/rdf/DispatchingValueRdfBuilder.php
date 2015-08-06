<?php

namespace Wikibase\Rdf;

use DataValues\DataValue;
use Wikimedia\Assert\Assert;
use Wikimedia\Purtle\RdfWriter;

/**
 * Dispatching implementation of DataValueRdfBuilder. This allows extensions to register
 * DataValueRdfBuilders for custom data types.
 *
 * @todo FIXME: test case!
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class DispatchingValueRdfBuilder implements DataValueRdfBuilder {

	/**
	 * @var DataValueRdfBuilder[]
	 */
	private $valueBuilders;

	/**
	 * @param DataValueRdfBuilder[] $valueBuilders DataValueRdfBuilder objects keyed by data type
	 * (with prefix "PT:") or value type (with prefix "VT:").
	 */
	public function __construct( array $valueBuilders ) {
		Assert::parameterElementType( 'Wikibase\Rdf\DataValueRdfBuilder', $valueBuilders, '$valueBuilders' );

		$this->valueBuilders = $valueBuilders;
	}

	/**
	 * Adds specific value
	 *
	 * @param RdfWriter $writer
	 * @param string $propertyValueNamespace Property value relation namespace
	 * @param string $propertyValueLName Property value relation name
	 * @param string $dataType Property data type
	 * @param DataValue $value
	 */
	public function addValue(
		RdfWriter $writer,
		$propertyValueNamespace,
		$propertyValueLName,
		$dataType,
		DataValue $value
	) {
		$builder = $this->getValueBuilder( $dataType, $value->getType() );

		if ( $builder ) {
			$builder->addValue( $writer, $propertyValueNamespace, $propertyValueLName, $dataType, $value );
		}
	}

	/**
	 * @param string|null $dataTypeId
	 * @param string $dataValueType
	 *
	 * @return null|DataValueRdfBuilder
	 */
	private function getValueBuilder( $dataTypeId, $dataValueType ) {
		/* @var DataValueRdfBuilder */
		$builder = null;

		if ( $dataTypeId !== null ) {
			if ( isset( $this->valueBuilders["PT:$dataTypeId"] ) ) {
				$builder = $this->valueBuilders["PT:$dataTypeId"];
			}
		}

		if ( $builder === null ) {
			if ( isset( $this->valueBuilders["VT:$dataValueType"] ) ) {
				$builder = $this->valueBuilders["VT:$dataValueType"];
			}
		}

		if ( $builder === null ) {
			if ( $dataTypeId !== null ) {
				wfLogWarning( __METHOD__ . ": No RDF builder defined for data type $dataTypeId nor for value type $dataValueType." );
			} else {
				wfLogWarning( __METHOD__ . ": No RDF builder defined for value type $dataValueType." );
			}
		}

		return $builder;

	}


}

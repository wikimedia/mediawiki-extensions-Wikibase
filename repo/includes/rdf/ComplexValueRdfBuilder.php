<?php

namespace Wikibase;

use DataValues\DataValue;
use Wikibase\DataModel\Entity\PropertyDataTypeLookup;
use Wikibase\RDF\RdfWriter;

/**
 * RDF mapping for wikibase data values, using the fully expanded data representation.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class ComplexValueRdfBuilder extends SimpleValueRdfBuilder {

	const NS_ONTOLOGY = 'wikibase'; // wikibase ontology (shared)
	const NS_VALUE = 'v';

	/**
	 * @var callable
	 */
	private $dedupeCallback; // statement -> value

	/**
	 * @param RdfWriter $writer
	 * @param PropertyDataTypeLookup $propertyLookup
	 */
	public function __construct( RdfWriter $writer, PropertyDataTypeLookup $propertyLookup ) {
		parent::__construct( $writer, $propertyLookup );
	}

	/**
	 * @return callable
	 */
	public function getDedupeCallback() {
		return $this->dedupeCallback;
	}

	/**
	 * @param callable $dedupeCallback
	 */
	public function setDedupeCallback( $dedupeCallback ) {
		$this->dedupeCallback = $dedupeCallback;
	}


	/**
	 * Adds the value of the given property to the RDF graph.
	 *
	 * @param string $propertyValueNamespace Property value relation namespace
	 * @param string $propertyValueLName Property value relation name
	 * @param string $dataType Property data type
	 * @param DataValue $value
	 */
	protected function addExpandedValueForDataType( $propertyValueNamespace, $propertyValueLName, $dataType, $value ) {
		parent::addExpandedValueForDataType( $propertyValueNamespace, $propertyValueLName, $dataType, $value );

		switch ( $dataType ) {
			case 'time':
				$prefix = 'time';
				$fields = array(  'time' => null,
					// TODO: eventually use identifier here
					'precision' => 'integer',
					'timezone' => 'integer',
					'calendarModel' => 'url',
				);
				break;

			case 'quantity':
				$prefix = 'quantity';
				$fields = array(  'amount' => 'decimal',
					'upperBound' => 'decimal',
					'lowerBound' => 'decimal',
					'unit' => null, //FIXME: it's a URI (or "1"), should be of type url!
				);
				break;

			case 'globecoordinate':
				$prefix = 'geo';
				$fields = array(  'latitude' => 'decimal',
					'longitude' => 'decimal',
					'precision' => 'decimal',
					'globe' => 'url',
				);
				break;

			default:
				$prefix = null;
				$fields = null;
		}

		if ( !empty( $fields ) ) {
			$valueLName = $this->addExpandedValue( $value, $prefix, $fields );
			$this->writer->say( $propertyValueNamespace, $propertyValueLName."-value" )->is( self::NS_VALUE, $valueLName );
		}
	}

	/**
	 * Created full data value
	 *
	 * @param DataValue $value
	 * @param string $prefix Prefix to use for predicate values
	 * @param array $props List of properties
	 *
	 * @return string the id of the value node, for use with the self::NS_VALUE namespace.
	 */
	private function addExpandedValue( DataValue $value, $prefix, array $props ) {
		$valueLName = $value->getHash();

		if ( $this->dedupeCallback ) {
			if ( call_user_func( $this->dedupeCallback, $valueLName ) ) {
				return $valueLName;
			}
		}

		$this->writer->about( self::NS_VALUE, $valueLName )->a( self::NS_ONTOLOGY, 'Value' );

		foreach ( $props as $prop => $type ) {
			$propLName = $prefix . ucfirst( $prop );
			$getter = "get" . $prop;
			$data = $value->$getter();
			if ( !is_null( $data ) ) {
				$this->addValueToNode( $this->writer, self::NS_ONTOLOGY, $propLName, $type, $data );
			}
		}

		return $valueLName;
	}

}

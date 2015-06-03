<?php

namespace Wikibase\Rdf;

use DataValues\DataValue;
use Wikibase\DataModel\Entity\PropertyDataTypeLookup;
use Wikimedia\Purtle\RdfWriter;

/**
 * RDF mapping for wikibase data values, using the fully expanded data representation.
 * This outputs expanded values (when appropriate) in addition to simple values.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class ComplexValueRdfBuilder extends SimpleValueRdfBuilder {

	/**
	 * @var DedupeBag
	 */
	private $dedupeBag;

	/**
	 * @var RdfWriter
	 */
	private $valueWriter;

	/**
	 * @param RdfVocabulary $vocabulary
	 * @param RdfWriter $valueWriter
	 * @param PropertyDataTypeLookup $propertyLookup
	 */
	public function __construct( RdfVocabulary $vocabulary, RdfWriter $valueWriter, PropertyDataTypeLookup $propertyLookup ) {
		parent::__construct( $vocabulary, $propertyLookup );

		$this->dedupeBag = new NullDedupeBag();
		$this->valueWriter = $valueWriter;
	}

	/**
	 * @return DedupeBag
	 */
	public function getDedupeBag() {
		return $this->dedupeBag;
	}

	/**
	 * @param DedupeBag $dedupeBag
	 */
	public function setDedupeBag( DedupeBag $dedupeBag ) {
		$this->dedupeBag = $dedupeBag;
	}

	/**
	 * Adds the value of the given property to the RDF graph.
	 *
	 * @param RdfWriter $writer
	 * @param string $propertyValueNamespace Property value relation namespace
	 * @param string $propertyValueLName Property value relation name
	 * @param string $dataType Property data type
	 * @param DataValue $value
	 */
	protected function addValueForDataType( RdfWriter $writer, $propertyValueNamespace, $propertyValueLName, $dataType, $value ) {
		parent::addValueForDataType( $writer, $propertyValueNamespace, $propertyValueLName, $dataType, $value );

		switch ( $value->getType() ) {
			case 'time':
				$prefix = 'time';
				$fields = array(
					'value' => 'dateTime',
					'precision' => 'integer', // TODO: eventually use identifier here
					'timezone' => 'integer',
					'calendarModel' => 'url',
				);
				break;

			case 'quantity':
				$prefix = 'quantity';
				$fields = array(
					'amount' => 'decimal',
					'upperBound' => 'decimal',
					'lowerBound' => 'decimal',
					'unit' => null, //FIXME: it's a URI (or "1"), should be of type url!
				);
				break;

			case 'globecoordinate':
				$prefix = 'geo';
				$fields = array(
					'latitude' => 'decimal',
					'longitude' => 'decimal',
					'precision' => 'decimal',
					'globe' => 'url',
				);
				break;

			default:
				return;
		}

		$valueLName = $this->addExpandedValue( $value, $prefix, $fields );
		$writer->say( RdfVocabulary::$claimToValue[$propertyValueNamespace], $propertyValueLName )->is( RdfVocabulary::NS_VALUE, $valueLName );
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

		if ( $this->dedupeBag->alreadySeen( $valueLName, 'V' ) !== false ) {
			return $valueLName;
		}

		$this->valueWriter->about( RdfVocabulary::NS_VALUE, $valueLName )->a( RdfVocabulary::NS_ONTOLOGY, 'Value' );

		$this->valueWriter->a( RdfVocabulary::NS_ONTOLOGY, $this->vocabulary->getValueTypeName( $value ) );

		foreach ( $props as $prop => $type ) {
			$propLName = $prefix . ucfirst( $prop );
			$getter = "get" . $prop;
			$data = $value->$getter();
			if ( !is_null( $data ) ) {
				$this->addValueToNode( $this->valueWriter, RdfVocabulary::NS_ONTOLOGY, $propLName, $type, $data );
			}
		}

		return $valueLName;
	}

}

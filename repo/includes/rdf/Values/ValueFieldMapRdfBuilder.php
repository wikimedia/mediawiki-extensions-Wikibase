<?php

namespace Wikibase\Rdf\Values;

use DataValues\DataValue;
use Wikibase\Rdf\DataValueRdfBuilder;
use Wikibase\Rdf\DedupeBag;
use Wikibase\Rdf\RdfVocabulary;
use Wikimedia\Purtle\RdfWriter;

/**
 * RDF mapping based on the fields in the array representation of a complex
 * DataValue.
 *
 * @todo FIXME test me!
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class ObjectValueRdfBuilder implements DataValueRdfBuilder {

	/**
	 * @var
	 */
	private $propNamePrefix;

	/**
	 * @var string[]
	 */
	private $fieldmap;

	/**
	 * @var RdfWriter
	 */
	private $valueWriter;

	/**
	 * @var RdfVocabulary
	 */
	private $vocabulary;

	/**
	 * @var DedupeBag
	 */
	private $dedupe;

	/**
	 * @param string $propNamePrefix
	 * @param string[] $fieldmap
	 * @param RdfVocabulary $vocabulary
	 * @param RdfWriter $valueWriter
	 * @param DedupeBag $dedupe
	 */
	public function __construct( $propNamePrefix, array $fieldmap, RdfVocabulary $vocabulary, RdfWriter $valueWriter, DedupeBag $dedupe ) {
		$this->propNamePrefix = $propNamePrefix;
		$this->fieldmap = $fieldmap;
		$this->valueWriter = $valueWriter;
		$this->vocabulary = $vocabulary;
		$this->dedupe = $dedupe;
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
		$valueLName = $value->getHash();

		if ( $this->dedupe->alreadySeen( $valueLName, 'V' ) !== false ) {
			return $valueLName;
		}

		$this->valueWriter->about( RdfVocabulary::NS_VALUE, $valueLName )
			->a( RdfVocabulary::NS_ONTOLOGY, $this->vocabulary->getValueTypeName( $value ) );

		foreach ( $this->fieldmap as $prop => $type ) {
			$propLName = $this->propNamePrefix . ucfirst( $prop );
			$getter = "get" . $prop;
			$data = $value->$getter();
			if ( !is_null( $data ) ) {
				$this->addValueToNode( $this->valueWriter, RdfVocabulary::NS_ONTOLOGY, $propLName, $type, $data );
			}
		}

		return $valueLName;
	}

	/**
	 * Add value to a node
	 * This function does massaging needed for RDF data types.
	 *
	 * @param RdfWriter $writer
	 * @param string $propertyValueNamespace
	 * @param string $propertyValueLName
	 * @param string $type
	 * @param mixed $value
	 */
	protected function addValueToNode( RdfWriter $writer, $propertyValueNamespace, $propertyValueLName, $type, $value ) {
		if ( $type === 'url' ) {
			if ( $value === "1" ) {
				// hack for units support, see https://phabricator.wikimedia.org/T105432
				$value = RdfVocabulary::ONE_ENTITY;
			}
			// Trims extra whitespace since we had a bug in wikidata where some URLs end up having it
			$writer->say( $propertyValueNamespace, $propertyValueLName )->is( trim( $value ) );
		} elseif ( $type === 'dateTime' && $value instanceof TimeValue ) {
			$writer->say( $propertyValueNamespace, $propertyValueLName );
			$this->sayDateLiteral( $writer, $value );
		} elseif ( $type === 'decimal' ) {
			// TODO: handle precision here?
			if ( $value instanceof DecimalValue ) {
				$value = $value->getValue();
			}
			$writer->say( $propertyValueNamespace, $propertyValueLName )->value( $value, 'xsd', 'decimal' );
		} else {
			if ( !is_scalar( $value ) ) {
				// somehow we got a weird value, better not risk it and bail
				$vtype = gettype( $value );
				wfLogWarning( "Bad value passed to addValueToNode for $propertyValueNamespace:$propertyValueLName: $vtype" );
				return;
			}
			$nsType = $type === null ? null : 'xsd';
			$writer->say( $propertyValueNamespace, $propertyValueLName )->value( $value, $nsType, $type );
		}
	}
}

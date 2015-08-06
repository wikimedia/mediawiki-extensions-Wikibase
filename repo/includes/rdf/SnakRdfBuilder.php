<?php

namespace Wikibase\Rdf;

use DataValues\DataValue;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikimedia\Purtle\RdfWriter;

/**
 * Implementation for RDF mapping for Snaks.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class SnakRdfBuilder {

	/**
	 * @var EntityMentionListener
	 */
	private $mentionedEntityTracker;

	/**
	 * @var RdfVocabulary
	 */
	private $vocabulary;

	/**
	 * @var DataValueRdfBuilder
	 */
	private $valueBuilder;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $propertyLookup;

	/**
	 * @param RdfVocabulary $vocabulary
	 * @param DataValueRdfBuilder $valueBuilder
	 * @param PropertyDataTypeLookup $propertyLookup
	 */
	public function __construct( RdfVocabulary $vocabulary, DataValueRdfBuilder $valueBuilder, PropertyDataTypeLookup $propertyLookup ) {
		$this->vocabulary = $vocabulary;
		$this->valueBuilder = $valueBuilder;
		$this->propertyLookup = $propertyLookup;

		$this->mentionedEntityTracker = new NullEntityMentionListener();
	}

	/**
	 * @return EntityMentionListener
	 */
	public function getEntityMentionListener() {
		return $this->mentionedEntityTracker;
	}

	/**
	 * @param EntityMentionListener $mentionedEntityTracker
	 */
	public function setEntityMentionListener( EntityMentionListener $mentionedEntityTracker ) {
		$this->mentionedEntityTracker = $mentionedEntityTracker;
	}

	/**
	 * Adds the given Statement's main Snak to the RDF graph.
	 *
	 * @param RdfWriter $writer
	 * @param Snak $snak
	 * @param string $propertyNamespace
	 *
	 * @throws InvalidArgumentException
	 */
	public function addSnak( RdfWriter $writer, Snak $snak, $propertyNamespace ) {
		$propertyId = $snak->getPropertyId();
		switch ( $snak->getType() ) {
			case 'value':
				/** @var PropertyValueSnak $snak */
				$this->addSnakValue( $writer, $propertyId, $snak->getDataValue(), $propertyNamespace );
				break;
			case 'somevalue':
				$propertyValueLName = $this->vocabulary->getEntityLName( $propertyId );

				$writer->say( $propertyNamespace, $propertyValueLName )->is( '_', $writer->blank() );
				break;
			case 'novalue':
				$propertyValueLName = $this->vocabulary->getEntityLName( $propertyId );

				$writer->say( 'a' )->is( RdfVocabulary::NSP_NOVALUE, $propertyValueLName );
				break;
			default:
				throw new InvalidArgumentException( 'Unknown snak type: ' . $snak->getType() );
		}

		$this->mentionedEntityTracker->propertyMentioned( $snak->getPropertyId() );
	}

	private $propertyTypes = array();

	private function getDataTypeIdForProperty( $propertyId ) {

		return $this->propertyTypes[$key];
	}

	/**
	 * Adds the value of the given property to the RDF graph.
	 *
	 * @param RdfWriter $writer
	 * @param PropertyId $propertyId
	 * @param DataValue $value
	 * @param string $propertyNamespace The property namespace for this snak
	 */
	private function addSnakValue(
		RdfWriter $writer,
		PropertyId $propertyId,
		DataValue $value,
		$propertyNamespace
	) {
		$propertyValueLName = $this->vocabulary->getEntityLName( $propertyId );
		$propertyKey = $propertyId->getSerialization();

		// cache data type for all properties we encounter
		if ( !isset( $this->propertyTypes[$propertyKey] ) ) {
			try {
				$this->propertyTypes[$propertyKey] = $this->propertyLookup->getDataTypeIdForProperty( $propertyId );
			} catch ( PropertyDataTypeLookupException $e ) {
				$this->propertyTypes[$propertyKey] = "unknown";
			}
		}

		$dataType = $this->propertyTypes[$propertyKey];
		$this->valueBuilder->addValue( $writer, $propertyNamespace, $propertyValueLName, $dataType, $value );
	}

}

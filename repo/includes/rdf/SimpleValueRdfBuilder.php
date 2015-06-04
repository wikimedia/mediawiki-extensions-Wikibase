<?php

namespace Wikibase\Rdf;

use DataValues\DataValue;
use DataValues\DecimalValue;
use DataValues\GlobeCoordinateValue;
use DataValues\MonolingualTextValue;
use DataValues\QuantityValue;
use DataValues\StringValue;
use DataValues\TimeValue;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\PropertyDataTypeLookup;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Entity\PropertyNotFoundException;
use Wikimedia\Purtle\RdfWriter;

/**
 * RDF mapping for wikibase data values, using a simplified projection.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class SimpleValueRdfBuilder implements SnakValueRdfBuilder {

	/**
	 * @var EntityMentionListener
	 */
	private $mentionedEntityTracker;

	/**
	 * @var RdfVocabulary
	 */
	protected $vocabulary;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $propertyLookup;

	/**
	 * @var DateTimeValueCleaner
	 */
	private $dateCleaner;

	/**
	 * @param RdfVocabulary $vocabulary
	 * @param PropertyDataTypeLookup $propertyLookup
	 */
	public function __construct( RdfVocabulary $vocabulary, PropertyDataTypeLookup $propertyLookup ) {
		$this->vocabulary = $vocabulary;
		$this->propertyLookup = $propertyLookup;

		// TODO: if data is fixed to be always Gregorian, replace with DateTimeValueCleaner
		$this->dateCleaner = new JulianDateTimeValueCleaner();
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
	public function setEntityMentionListener( $mentionedEntityTracker ) {
		$this->mentionedEntityTracker = $mentionedEntityTracker;
	}

	/**
	 * Adds the value of the given property to the RDF graph.
	 *
	 * @param RdfWriter $writer
	 * @param PropertyId $propertyId
	 * @param DataValue $value
	 * @param string $propertyNamespace The property namespace for this snak
	 */
	public function addSnakValue(
		RdfWriter $writer,
		PropertyId $propertyId,
		DataValue $value,
		$propertyNamespace
	) {
		$propertyValueLName = $this->vocabulary->getEntityLName( $propertyId );

		$typeId = $value->getType();
		$dataType = null;

		if ( $typeId === 'string' ) {
			// We only care about the actual data type of strings, so we can save time but not asking
			// for any other types
			try {
				$dataType = $this->propertyLookup->getDataTypeIdForProperty( $propertyId );
			} catch( PropertyNotFoundException $e ) {
				// keep "unknown"
			}
		}

		$this->addValueForDataType( $writer, $propertyNamespace, $propertyValueLName, $dataType, $value );
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
	protected function addValueForDataType(
		RdfWriter $writer,
		$propertyValueNamespace,
		$propertyValueLName,
		$dataType,
		$value
	) {
		//FIXME: use a proper registry / dispatching builder
		switch ( $value->getType() ) {
			//TODO: RdfWriter could support aliases -> instead of passing around $propertyNamespace
			//      and $propertyValueLName, we could define an alias for that and use e.g. '%property' to refer to them.
			case 'wikibase-entityid':
				$this->addStatementForWikibaseEntityid( $writer, $propertyValueNamespace, $propertyValueLName, $dataType, $value );
				break;
			case 'string':
				$this->addStatementForString( $writer, $propertyValueNamespace, $propertyValueLName, $dataType, $value );
				break;
			case 'monolingualtext':
				$this->addStatementForMonolingualtext( $writer, $propertyValueNamespace, $propertyValueLName, $dataType, $value );
				break;
			case 'time':
				$this->addStatementForTime( $writer, $propertyValueNamespace, $propertyValueLName, $dataType, $value );
				break;
			case 'quantity':
				$this->addStatementForQuantity( $writer, $propertyValueNamespace, $propertyValueLName, $dataType, $value );
				break;
			case 'globecoordinate':
				$this->addStatementForGlobecoordinate( $writer, $propertyValueNamespace, $propertyValueLName, $dataType, $value );
				break;
			default:
				wfLogWarning( __METHOD__ . ': Unsupported data value type: ' . $value->getType() );
		}

		// TODO: add special handling like in WDTK?
		// https://github.com/Wikidata/Wikidata-Toolkit/blob/master/wdtk-rdf/src/main/java/org/wikidata/wdtk/rdf/extensions/SimpleIdExportExtension.java
	}

	/**
	 * Adds specific value
	 *
	 * @param RdfWriter $writer
	 * @param string $propertyValueNamespace Property value relation namespace
	 * @param string $propertyValueLName Property value relation name
	 * @param string $dataType Property data type
	 * @param EntityIdValue $value
	 */
	private function addStatementForWikibaseEntityid(
		RdfWriter $writer,
		$propertyValueNamespace,
		$propertyValueLName,
		$dataType,
		EntityIdValue $value
	) {
		$entityId = $value->getValue()->getEntityId();
		$entityLName = $this->vocabulary->getEntityLName( $entityId );
		$writer->say( $propertyValueNamespace, $propertyValueLName )->is( RdfVocabulary::NS_ENTITY, $entityLName );

		$this->mentionedEntityTracker->entityReferenceMentioned( $entityId );
	}

	/**
	 * Adds specific value
	 *
	 * @param RdfWriter $writer
	 * @param string $propertyValueNamespace Property value relation namespace
	 * @param string $propertyValueLName Property value relation name
	 * @param string $dataType Property data type
	 * @param StringValue $value
	 */
	private function addStatementForString(
		RdfWriter $writer,
		$propertyValueNamespace,
		$propertyValueLName,
		$dataType,
		StringValue $value
	) {
		if ( $dataType === 'commonsMedia' ) {
			$this->addValueToNode( $writer, $propertyValueNamespace, $propertyValueLName, 'url', $this->vocabulary->getCommonsURI( $value->getValue() ) );
		} elseif ( $dataType === 'url' ) {
			$this->addValueToNode( $writer, $propertyValueNamespace, $propertyValueLName, 'url', $value->getValue() );
		} else {
			$writer->say( $propertyValueNamespace, $propertyValueLName )->text( $value->getValue() );
		}
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

	/**
	 * Adds specific value
	 *
	 * @param RdfWriter $writer
	 * @param string $propertyValueNamespace Property value relation namespace
	 * @param string $propertyValueLName Property value relation name
	 * @param string $dataType Property data type
	 * @param MonolingualTextValue $value
	 */
	private function addStatementForMonolingualtext(
		RdfWriter $writer,
		$propertyValueNamespace,
		$propertyValueLName,
		$dataType,
		MonolingualTextValue $value
	) {
		$writer->say( $propertyValueNamespace, $propertyValueLName )->text( $value->getText(), $value->getLanguageCode() );
	}

	/**
	 * Produce literal that reperesent the date in RDF
	 * If we can convert it to xsd:dateTime, we'll do that.
	 * Otherwise, we leave it as string
	 *
	 * @param RdfWriter $writer The writer to receive the property value (must be primed to expect a predicate).
	 * @param TimeValue $value
	 */
	private function sayDateLiteral( RdfWriter $writer, TimeValue $value ) {
		$dateValue = $this->dateCleaner->getStandardValue( $value );
		if ( !is_null( $dateValue ) ) {
			$writer->value( $dateValue, 'xsd', 'dateTime' );
		} else {
			$writer->value( $value->getTime() );
		}
	}

	/**
	 * Adds specific value
	 *
	 * @param RdfWriter $writer
	 * @param string $propertyValueNamespace Property value relation namespace
	 * @param string $propertyValueLName Property value relation name
	 * @param string $dataType Property data type
	 * @param TimeValue $value
	 */
	private function addStatementForTime(
		RdfWriter $writer,
		$propertyValueNamespace,
		$propertyValueLName,
		$dataType,
		TimeValue $value
	) {
		$this->addValueToNode( $writer, $propertyValueNamespace, $propertyValueLName, 'dateTime', $value );
	}

	/**
	 * Adds specific value
	 *
	 * @param RdfWriter $writer
	 * @param string $propertyValueNamespace Property value relation namespace
	 * @param string $propertyValueLName Property value relation name
	 * @param string $dataType Property data type
	 * @param GlobeCoordinateValue $value
	 */
	private function addStatementForGlobecoordinate(
		RdfWriter $writer,
		$propertyValueNamespace,
		$propertyValueLName,
		$dataType,
		GlobeCoordinateValue $value
	) {
		$point = "Point({$value->getLatitude()} {$value->getLongitude()})";
		$writer->say( $propertyValueNamespace, $propertyValueLName )->value( $point, RdfVocabulary::NS_GEO, "wktLiteral" );
	}

	/**
	 * Adds specific value
	 *
	 * @param RdfWriter $writer
	 * @param string $propertyValueNamespace Property value relation namespace
	 * @param string $propertyValueLName Property value relation name
	 * @param string $dataType Property data type
	 * @param QuantityValue $value
	 */
	private function addStatementForQuantity(
		RdfWriter $writer,
		$propertyValueNamespace,
		$propertyValueLName,
		$dataType,
		QuantityValue $value
	) {
		$writer->say( $propertyValueNamespace, $propertyValueLName )->value( $value->getAmount(), 'xsd', 'decimal' );
	}

}

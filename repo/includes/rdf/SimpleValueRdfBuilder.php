<?php

namespace Wikibase;

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
use Wikibase\RDF\RdfWriter;

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
	 * @var callable
	 */
	private $entityMentionCallback = null;

	/**
	 * @var RdfVocabulary
	 */
	private $vocabulary;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $propertyLookup;

	/**
	 * @param RdfVocabulary $vocabulary
	 * @param PropertyDataTypeLookup $propertyLookup
	 */
	public function __construct( RdfVocabulary $vocabulary, PropertyDataTypeLookup $propertyLookup ) {
		$this->vocabulary = $vocabulary;
		$this->propertyLookup = $propertyLookup;
	}

	/**
	 * @return callable
	 */
	public function getEntityMentionCallback() {
		return $this->entityMentionCallback;
	}

	/**
	 * @param callable $entityMentionCallback
	 */
	public function setEntityMentionCallback( $entityMentionCallback ) {
		$this->entityMentionCallback = $entityMentionCallback;
	}

	/**
	 * Adds the value of the given property to the RDF graph.
	 *
	 * @param RdfWriter $writer
	 * @param PropertyId $propertyId
	 * @param DataValue $value
	 * @param string $propertyNamespace The property namespace for this snak
	 */
	public function addSnakValue( RdfWriter $writer, PropertyId $propertyId,
			DataValue $value, $propertyNamespace
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

		$this->addExpandedValueForDataType( $writer, $propertyNamespace, $propertyValueLName, $dataType, $value );
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
	protected function addExpandedValueForDataType( RdfWriter $writer, $propertyValueNamespace, $propertyValueLName, $dataType, $value ) {

		//FIXME: use a proper registry / dispatching builder
		$typeFunc = 'addStatementFor' . preg_replace( '/[^\w]/', '', ucwords( $value->getType() ) );

		if ( !is_callable( array( $this, $typeFunc ) ) ) {
			wfLogWarning( __METHOD__ . ": Unsupported data type: $dataType" );
		} else {
			//TODO: RdfWriter could support aliases -> instead of passing around $propertyNamespace
			//      and $propertyValueLName, we could define an alias for that and use e.g. '%property' to refer to them.
			$this->$typeFunc( $writer, $propertyValueNamespace, $propertyValueLName, $dataType, $value );
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
	private function addStatementForWikibaseEntityid( RdfWriter $writer, $propertyValueNamespace, $propertyValueLName, $dataType,
			EntityIdValue $value ) {

		$entityId = $value->getValue()->getEntityId();
		$entityLName = $this->vocabulary->getEntityLName( $entityId );
		$writer->say( $propertyValueNamespace, $propertyValueLName )->is( RdfVocabulary::NS_ENTITY, $entityLName );

		if ( $this->entityMentionCallback ) {
			call_user_func( $this->entityMentionCallback, $entityId );
		}
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
	private function addStatementForString( RdfWriter $writer, $propertyValueNamespace, $propertyValueLName, $dataType,
			StringValue $value ) {
		if ( $dataType == 'commonsMedia' ) {
			$this->addValueToNode( $writer, $propertyValueNamespace, $propertyValueLName, 'url', $this->vocabulary->getCommonsURI( $value->getValue() ) );
		} elseif ( $dataType == 'url' ) {
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
	 * @param string $value
	 */
	protected function addValueToNode( RdfWriter $writer, $propertyValueNamespace, $propertyValueLName, $type, $value ) {
		if( $type == 'url' ) {
			$writer->say( $propertyValueNamespace, $propertyValueLName )->is( $value );
		} elseif( $type == 'dateTime' ) {
			$writer->say( $propertyValueNamespace, $propertyValueLName )->value( $this->cleanupDateValue( $value ), 'xsd', 'dateTime' );
		} elseif( $type == 'decimal' ) {
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
	private function addStatementForMonolingualtext( RdfWriter $writer, $propertyValueNamespace, $propertyValueLName, $dataType,
			MonolingualTextValue $value ) {
		$writer->say( $propertyValueNamespace, $propertyValueLName )->text( $value->getText(), $value->getLanguageCode() );
	}

	/**
	 * Clean up Wikidata date value in Gregorian calendar
	 * - remove + from the start - not all data stores like that
	 * - validate month and date value
	 *
	 * @param string $dateValue
	 *
	 * @return string Value compatible with xsd:dateTime type
	 */
	private function cleanupDateValue( $dateValue ) {
		list( $date, $time ) = explode( 'T', $dateValue, 2 );
		if ( $date[0] === '-' ) {
			list( $y, $m, $d ) = explode( '-', substr( $date, 1 ), 3 );
			$y = -(int)$y;
		} else {
			list( $y, $m, $d ) = explode( '-', $date, 3 );
			$y = (int)$y;
		}

		$m = (int)$m;
		$d = (int)$d;

		// PHP source docs say PHP gregorian calendar can work down to 4714 BC
		// for smaller dates, we ignore month/day
		if ( $y <= -4714 ) {
			$d = $m = 1;
		}

		if ( $m <= 0 ) {
			$m = 1;
		}
		if ( $m >= 12 ) {
			// Why anybody would do something like that? Anyway, better to check.
			$m = 12;
		}
		if ( $d <= 0 ) {
			$d = 1;
		}
		// check if the date "looks safe". If not, we do deeper check
		if ( !( $d <= 28 || ( $m != 2 && $d <= 30 ) ) ) {
			$max = cal_days_in_month( CAL_GREGORIAN, $m, $y );
			// We just put it as the last day in month, won't bother further
			if ( $d > $max ) {
				$d = $max;
			}
		}
		// This is a bit weird since xsd:dateTime requires >=4 digit always,
		// and leading 0 is not allowed for 5 digits
		// But sprintf counts - as digit
		// See: http://www.w3.org/TR/xmlschema-2/#dateTime
		return sprintf( "%s%04d-%02d-%02dT%s", $y < 0 ? '-' : '', abs( $y ), $m, $d, $time );
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
		$calendar = $value->getCalendarModel();
		if ( $calendar == RdfVocabulary::GREGORIAN_CALENDAR ) {
			$writer->value( $this->cleanupDateValue( $value->getTime() ), 'xsd', 'dateTime' );
			return;
		}
		// TODO: add handling for Julian values
		$writer->value( $value->getTime() );
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
	private function addStatementForTime( RdfWriter $writer, $propertyValueNamespace, $propertyValueLName, $dataType,
			TimeValue $value ) {

		$writer->say( $propertyValueNamespace, $propertyValueLName );
		$this->sayDateLiteral( $writer, $value );
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
	private function addStatementForGlobecoordinate( RdfWriter $writer, $propertyValueNamespace, $propertyValueLName, $dataType,
			GlobeCoordinateValue $value ) {

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
	private function addStatementForQuantity( RdfWriter $writer, $propertyValueNamespace, $propertyValueLName, $dataType,
		QuantityValue $value ) {
		$writer->say( $propertyValueNamespace, $propertyValueLName )->value( $value->getAmount(), 'xsd', 'decimal' );
	}

}

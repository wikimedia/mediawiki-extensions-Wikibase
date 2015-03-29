<?php

namespace Wikibase;

use DataValues\DataValue;
use DataValues\DecimalValue;
use DataValues\GlobeCoordinateValue;
use DataValues\MonolingualTextValue;
use DataValues\QuantityValue;
use DataValues\StringValue;
use DataValues\TimeValue;
use Wikibase\DataModel\Entity\EntityId;
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
class SimpleValueRdfBuilder {

	const NS_ENTITY = 'entity'; // concept uris
	const NS_GEO = 'geo'; // prefix for geolocations

	// Gregorian calendar link.
	// I'm not very happy about hardcoding it here but see no better way so far
	const GREGORIAN_CALENDAR = 'http://www.wikidata.org/entity/Q1985727';

	const COMMONS_URI = 'http://commons.wikimedia.org/wiki/Special:FilePath/'; //FIXME: get from config

	/**
	 * @var RdfWriter
	 */
	protected $writer;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $propertyLookup;

	/**
	 * @var callable
	 */
	private $entityMentionCallback = null;

	/**
	 * @param RdfWriter $writer
	 * @param PropertyDataTypeLookup $propertyLookup
	 */
	public function __construct( RdfWriter $writer, PropertyDataTypeLookup $propertyLookup ) {
		$this->writer = $writer;
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
	 * Returns a local name for the given entity using the given prefix.
	 *
	 * @param EntityId $entityId
	 *
	 * @return string
	 */
	private function getEntityLName( EntityId $entityId ) {
		return ucfirst( $entityId->getSerialization() );
	}

	/**
	 * Adds the value of the given property to the RDF graph.
	 *
	 * @param PropertyId $propertyId
	 * @param DataValue $value
	 * @param string $propertyNamespace The property namespace for this snak
	 */
	public function addSnakValue( PropertyId $propertyId,
			DataValue $value, $propertyNamespace ) {
		$propertyValueLName = $this->getEntityLName( $propertyId );

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

		$this->addExpandedValueForDataType( $propertyNamespace, $propertyValueLName, $dataType, $value );
	}

	/**
	 * Adds specific value
	 *
	 * @param string $propertyValueNamespace Property value relation namespace
	 * @param string $propertyValueLName Property value relation name
	 * @param string $dataType Property data type
	 * @param DataValue $value
	 */
	protected function addExpandedValueForDataType( $propertyValueNamespace, $propertyValueLName, $dataType, $value ) {

		//FIXME: use a proper registry / dispatching builder
		$typeFunc = 'addStatementFor' . preg_replace( '/[^\w]/', '', ucwords( $dataType ) );

		if ( !is_callable( array( $this, $typeFunc ) ) ) {
			wfLogWarning( __METHOD__ . ": Unsupported data type: $dataType" );
		} else {
			//TODO: RdfWriter could support aliases -> instead of passing around $propertyNamespace
			//      and $propertyValueLName, we could define an alias for that and use e.g. '%property' to refer to them.
			$this->$typeFunc( $propertyValueNamespace, $propertyValueLName, $dataType, $value );
		}
		// TODO: add special handling like in WDTK?
		// https://github.com/Wikidata/Wikidata-Toolkit/blob/master/wdtk-rdf/src/main/java/org/wikidata/wdtk/rdf/extensions/SimpleIdExportExtension.java

	}

	/**
	 * Create Commons URL from filename value
	 *
	 * @param string $file
	 *
	 * @return string
	 */
	private function getCommonsURI( $file ) {
		return self::COMMONS_URI . rawurlencode( $file );
	}

	/**
	 * Adds specific value
	 *
	 * @param string $propertyValueNamespace Property value relation namespace
	 * @param string $propertyValueLName Property value relation name
	 * @param string $dataType Property data type
	 * @param EntityIdValue $value
	 */
	private function addStatementForWikibaseEntityid( $propertyValueNamespace, $propertyValueLName, $dataType,
			EntityIdValue $value ) {

		$entityId = $value->getValue()->getEntityId();
		$entityLName = $this->getEntityLName( $entityId );
		$this->writer->say( $propertyValueNamespace, $propertyValueLName )->is( self::NS_ENTITY, $entityLName );

		if ( $this->entityMentionCallback ) {
			call_user_func( $this->entityMentionCallback, $entityId );
		}
	}

	/**
	 * Adds specific value
	 *
	 * @param string $propertyValueNamespace Property value relation namespace
	 * @param string $propertyValueLName Property value relation name
	 * @param string $dataType Property data type
	 * @param StringValue $value
	 */
	private function addStatementForString( $propertyValueNamespace, $propertyValueLName, $dataType,
			StringValue $value ) {
		if ( $dataType == 'commonsMedia' ) {
			$this->addValueToNode( $propertyValueNamespace, $propertyValueLName, 'url', $this->getCommonsURI( $value->getValue() ) );
		} elseif ( $dataType == 'url' ) {
			$this->addValueToNode( $propertyValueNamespace, $propertyValueLName, 'url', $value->getValue() );
		} else {
			$this->writer->say( $propertyValueNamespace, $propertyValueLName )->text( $value->getValue() );
		}
	}

	/**
	 * Add value to a node
	 * This function does massaging needed for RDF data types.
	 *
	 * @param string $propertyValueNamespace
	 * @param string $propertyValueLName
	 * @param string $type
	 * @param string $value
	 */
	protected function addValueToNode( $propertyValueNamespace, $propertyValueLName, $type, $value ) {
		if( $type == 'url' ) {
			$this->writer->say( $propertyValueNamespace, $propertyValueLName )->is( $value );
		} elseif( $type == 'dateTime' ) {
			$this->writer->say( $propertyValueNamespace, $propertyValueLName )->value( $this->cleanupDateValue( $value ), 'xsd', 'dateTime' );
		} elseif( $type == 'decimal' ) {
			// TODO: handle precision here?
			if ( $value instanceof DecimalValue ) {
				$value = $value->getValue();
			}
			$this->writer->say( $propertyValueNamespace, $propertyValueLName )->value( $value, 'xsd', 'decimal' );
		} else {
			if ( !is_scalar( $value ) ) {
				// somehow we got a weird value, better not risk it and bail
				$vtype = gettype( $value );
				wfLogWarning( "Bad value passed to addValueToNode for $propertyValueNamespace:$propertyValueLName: $vtype" );
				return;
			}
			$nsType = $type === null ? null : 'xsd';
			$this->writer->say( $propertyValueNamespace, $propertyValueLName )->value( $value, $nsType, $type );
		}
	}

	/**
	 * Adds specific value
	 *
	 * @param string $propertyValueNamespace Property value relation namespace
	 * @param string $propertyValueLName Property value relation name
	 * @param string $dataType Property data type
	 * @param MonolingualTextValue $value
	 */
	private function addStatementForMonolingualtext( $propertyValueNamespace, $propertyValueLName, $dataType,
			MonolingualTextValue $value ) {
		$this->writer->say( $propertyValueNamespace, $propertyValueLName )->text( $value->getText(), $value->getLanguageCode() );
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
	 * @param RdfWriter $writer The writer to receive the property value (must be primed to expect a predicate).
	 * @param TimeValue $value
	 */
	private function sayDateLiteral( RdfWriter $writer, TimeValue $value ) {
		$calendar = $value->getCalendarModel();
		if ( $calendar == self::GREGORIAN_CALENDAR ) {
			$this->writer->value( $this->cleanupDateValue( $value->getTime() ), 'xsd', 'dateTime' );
			return;
		}
		// TODO: add handling for Julian values
		$writer->value( $value->getTime() );
	}

	/**
	 * Adds specific value
	 *
	 * @param string $propertyValueNamespace Property value relation namespace
	 * @param string $propertyValueLName Property value relation name
	 * @param string $dataType Property data type
	 * @param TimeValue $value
	 */
	private function addStatementForTime( $propertyValueNamespace, $propertyValueLName, $dataType,
			TimeValue $value ) {

		$this->writer->say( $propertyValueNamespace, $propertyValueLName );
		$this->sayDateLiteral( $this->writer, $value );
	}

	/**
	 * Adds specific value
	 *
	 * @param string $propertyValueNamespace Property value relation namespace
	 * @param string $propertyValueLName Property value relation name
	 * @param string $dataType Property data type
	 * @param GlobeCoordinateValue $value
	 */
	private function addStatementForGlobecoordinate( $propertyValueNamespace, $propertyValueLName, $dataType,
			GlobeCoordinateValue $value ) {

		$point = "Point({$value->getLatitude()} {$value->getLongitude()})";
		$this->writer->say( $propertyValueNamespace, $propertyValueLName )->value( $point, self::NS_GEO, "wktLiteral" );
	}

	/**
	 * Adds specific value
	 *
	 * @param string $propertyValueNamespace Property value relation namespace
	 * @param string $propertyValueLName Property value relation name
	 * @param string $dataType Property data type
	 * @param QuantityValue $value
	 */
	private function addStatementForQuantity( $propertyValueNamespace, $propertyValueLName, $dataType,
		QuantityValue $value ) {
		$this->writer->say( $propertyValueNamespace, $propertyValueLName )->value( $value->getAmount(), 'xsd', 'decimal' );
	}

}

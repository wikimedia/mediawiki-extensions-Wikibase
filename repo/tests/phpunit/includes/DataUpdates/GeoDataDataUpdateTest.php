<?php

namespace Wikibase\Test;

use DataValues\DataValue;
use DataValues\Geo\Values\LatLongValue;
use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\StringValue;
use ParserOutput;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Lib\Store\PropertyDataTypeMatcher;
use Wikibase\Repo\DataUpdates\GeoDataDataUpdate;

/**
 * @covers Wikibase\Repo\DataUpdates\GeoDataDataUpdate;
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group Database
 *
 * @license GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class GeoDataDataUpdateTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider processStatementProvider
	 */
	public function testProcessStatement( array $expected, array $statements, $message ) {
		$dataUpdate = new GeoDataDataUpdate(
			new PropertyDataTypeMatcher( $this->getPropertyDataTypeLookup() ),
			array( 'P625', 'P9000' )
		);

		foreach ( $statements as $statement ) {
			$dataUpdate->processStatement( $statement );
		}

		$this->assertAttributeEquals(
			$expected,
			'statementsByGeoProperty',
			$dataUpdate,
			$message
		);
	}

	public function processStatementProvider() {
		$stringValueStatement = $this->newStatement(
			new PropertyId( 'P42' ),
			new StringValue( 'kittens!' )
		);

		$geoValueStatement = $this->newStatement(
			new PropertyId( 'P625' ),
			$this->newGlobeCoordinateValue( 35.690278, 139.700556 )
		);

		$geoValueStatement2 = $this->newStatement(
			new PropertyId( 'P10' ),
			$this->newGlobeCoordinateValue( 40.748433, -73.985655 )
		);

		$geoValueStatement3 = $this->newStatement(
			new PropertyId( 'P10' ),
			$this->newGlobeCoordinateValue( 39.849922, -73.742641 )
		);

		$mismatchingSnakStatement = $this->newStatement(
			new PropertyId( 'P10' ),
			new StringValue( 'omg! wrong value type' )
		);

		$someValueSnakStatement = new Statement(
			new PropertySomeValueSnak( new PropertyId( 'P42' ) )
		);

		$statementWithDeletedProperty = $this->newStatement(
			new PropertyId( 'P404' ),
			$this->newGlobeCoordinateValue( 40.733643, -72.352153 )
		);

		return array(
			array(
				array(),
				array( $stringValueStatement ),
				'non-geo property'
			),
			array(
				array(
					'P625' => new StatementList(
						array( $geoValueStatement )
					)
				),
				array( $geoValueStatement ),
				'geo property'
			),
			array(
				array(
					'P10' => new StatementList(
						array( $geoValueStatement2 )
					)
				),
				array( $geoValueStatement2 ),
				'non-preferred geo property'
			),
			array(
				array(
					'P10' => new StatementList(
						array( $geoValueStatement2, $geoValueStatement3 )
					)
				),
				array( $geoValueStatement2, $geoValueStatement3 ),
				'multiple geo statements'
			),
			array(
				array(),
				array( $mismatchingSnakStatement ),
				'mismatching snak but still added at this stage'
			),
			array(
				array(),
				array( $someValueSnakStatement ),
				'some value snak'
			),
			array(
				array(),
				array( $statementWithDeletedProperty ),
				'statement with deleted property, not in PropertyDataTypeLookup'
			)
		);
	}

	private function newStatement( PropertyId $propertyId, DataValue $dataValue ) {
		$guidGenerator = new GuidGenerator();

		$snak = new PropertyValueSnak( $propertyId, $dataValue );
		$guid = $guidGenerator->newGuid( new ItemId( 'Q64' ) );

		return new Statement( $snak, null, null, $guid );
	}

	private function newGlobeCoordinateValue( $lat, $lon ) {
		$latLongValue = new LatLongValue( $lat, $lon );

		return new GlobeCoordinateValue( $latLongValue, 0.001 );
	}

	public function testUpdateParserOutput() {
		$parserOutput = new ParserOutput();

		// @todo
	}

	private function getPropertyDataTypeLookup() {
		$dataTypeLookup = new InMemoryDataTypeLookup();

		$dataTypeLookup->setDataTypeForProperty( new PropertyId( 'P42' ), 'string' );
		$dataTypeLookup->setDataTypeForProperty( new PropertyId( 'P10' ), 'globe-coordinate' );
		$dataTypeLookup->setDataTypeForProperty( new PropertyId( 'P625' ), 'globe-coordinate' );
		$dataTypeLookup->setDataTypeForProperty( new PropertyId( 'P9000' ), 'globe-coordinate' );

		return $dataTypeLookup;
	}

}

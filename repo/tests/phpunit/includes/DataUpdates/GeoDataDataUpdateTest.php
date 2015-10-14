<?php

namespace Wikibase\Test;

use Coord;
use CoordinatesOutput;
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

	protected function setUp() {
		if ( !class_exists( 'GeoData' ) ) {
			$this->markTestSkipped( 'GeoData extension is required.' );
		}

		parent::setUp();
	}

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

	public function testUpdateParserOutput() {
		$statements = $this->getStatements();

		$statementsByGeoProperty = array(
			'P625' => new StatementList( array(
				$statements['geo-property-P625']
			) ),
			'P10' => new StatementList( array(
				$statements['geo-property-P10-A'],
				$statements['geo-property-P10-B'],
				$statements['mismatch-P10']
			) ),
			'P9000' => new StatementList( array(
				$statements['geo-property-P9000']
			) ),
			'P20' => new StatementList( array(
				$statements['some-value-P20']
			) ),
			'P17' => new StatementList( array(
				$statements['deprecated-geo-P17']
			) )
		);

		$dataUpdate = new GeoDataDataUpdate(
			new PropertyDataTypeMatcher( $this->getPropertyDataTypeLookup() ),
			array( 'P17', 'P404', 'P10', 'P20', 'P9000', 'P625' ),
			$statementsByGeoProperty
		);

		$parserOutput = new ParserOutput();

		$dataUpdate->updateParserOutput( $parserOutput );

		$expected = new CoordinatesOutput();

		// P9000 statement
		$coord = new Coord( 33.643664, 20.464222 );
		$coord->primary = true;

		$expected->addPrimary( $coord );
		$expected->addSecondary( new Coord( 35.690278, 139.700556 ) );
		$expected->addSecondary( new Coord( 40.748433, -73.985655 ) );
		$expected->addSecondary( new Coord( 44.264464, 52.643666 ) );
		$expected->addSecondary( new Coord( 10.0234, 11.52352 ) );

		$this->assertEquals( $expected, $parserOutput->geoData );
	}

	public function processStatementProvider() {
		$statements = $this->getStatements();

		return array(
			array(
				array(),
				array( $statements['string-property'] ),
				'non-geo property'
			),
			array(
				array(
					'P625' => new StatementList(
						array( $statements['geo-property-P625'] )
					)
				),
				array( $statements['geo-property-P625'] ),
				'geo property'
			),
			array(
				array(
					'P17' => new StatementList(
						array( $statements['deprecated-geo-P17'] )
					)
				),
				array( $statements['deprecated-geo-P17'] ),
				'deprecated geo statement'
			),
			array(
				array(
					'P10' => new StatementList(
						array(
							$statements['geo-property-P10-A'],
							$statements['geo-property-P10-B']
						)
					)
				),
				array( $statements['geo-property-P10-A'], $statements['geo-property-P10-B'] ),
				'multiple geo statements'
			),
			array(
				array(
					'P20' => new StatementList(
						array( $statements['some-value-P20'] )
					)
				),
				array( $statements['some-value-P20'] ),
				'some value snak, still added during initial processing'
			),
			array(
				array(),
				array( $statements['unknown-property'] ),
				'statement with unknown property, not in PropertyDataTypeLookup'
			)
		);
	}

	private function getStatements() {
		$statements = array();

		$statements['string-property'] = $this->newStatement(
			new PropertyId( 'P42' ),
			new StringValue( 'kittens!' )
		);

		$statements['geo-property-P625'] = $this->newStatement(
			new PropertyId( 'P625' ),
			$this->newGlobeCoordinateValue( 35.690278, 139.700556 )
		);

		$statements['geo-property-P10-A'] = $this->newStatement(
			new PropertyId( 'P10' ),
			$this->newGlobeCoordinateValue( 40.748433, -73.985655 )
		);

		$statements['geo-property-P10-B'] = $this->newStatement(
			new PropertyId( 'P10' ),
			$this->newGlobeCoordinateValue( 44.264464, 52.643666 )
		);

		$statements['geo-property-P9000'] = $this->newStatement(
			new PropertyId( 'P9000' ),
			$this->newGlobeCoordinateValue( 33.643664, 20.464222 )
		);

		$deprecatedGeoValueStatement = $this->newStatement(
			new PropertyId( 'P17' ),
			$this->newGlobeCoordinateValue( 10.0234, 11.52352 )
		);

		$deprecatedGeoValueStatement->setRank( Statement::RANK_DEPRECATED );

		$statements['deprecated-geo-P17'] = $deprecatedGeoValueStatement;

		$statements['some-value-P20'] = $this->newStatement( new PropertyId( 'P20' ) );

		$statements['mismatch-P10'] = $this->newStatement(
			new PropertyId( 'P10' ),
			new StringValue( 'omg! wrong value type' )
		);

		$statements['unknown-property'] = $this->newStatement(
			new PropertyId( 'P404' ),
			$this->newGlobeCoordinateValue( 40.733643, -72.352153 )
		);

		return $statements;
	}

	private function newStatement( PropertyId $propertyId, DataValue $dataValue = null ) {
		$guidGenerator = new GuidGenerator();

		if ( $dataValue === null ) {
			$snak = new PropertySomeValueSnak( $propertyId );
		} else {
			$snak = new PropertyValueSnak( $propertyId, $dataValue );
		}

		$guid = $guidGenerator->newGuid( new ItemId( 'Q64' ) );

		return new Statement( $snak, null, null, $guid );
	}

	private function newGlobeCoordinateValue( $lat, $lon ) {
		$latLongValue = new LatLongValue( $lat, $lon );

		return new GlobeCoordinateValue( $latLongValue, 0.001 );
	}

	private function getPropertyDataTypeLookup() {
		$dataTypeLookup = new InMemoryDataTypeLookup();

		$dataTypeLookup->setDataTypeForProperty( new PropertyId( 'P42' ), 'string' );
		$dataTypeLookup->setDataTypeForProperty( new PropertyId( 'P10' ), 'globe-coordinate' );
		$dataTypeLookup->setDataTypeForProperty( new PropertyId( 'P17' ), 'globe-coordinate' );
		$dataTypeLookup->setDataTypeForProperty( new PropertyId( 'P20' ), 'globe-coordinate' );
		$dataTypeLookup->setDataTypeForProperty( new PropertyId( 'P625' ), 'globe-coordinate' );
		$dataTypeLookup->setDataTypeForProperty( new PropertyId( 'P9000' ), 'globe-coordinate' );

		return $dataTypeLookup;
	}

}

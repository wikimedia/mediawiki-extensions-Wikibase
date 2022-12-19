<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use DataValues\DataValue;
use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\Geo\Values\LatLongValue;
use DataValues\StringValue;
use ExtensionRegistry;
use GeoData\Coord;
use GeoData\CoordinatesOutput;
use MediaWikiIntegrationTestCase;
use ParserOutput;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Entity\PropertyDataTypeMatcher;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\ParserOutput\GeoDataDataUpdater;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikibase\Repo\ParserOutput\GeoDataDataUpdater
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class GeoDataDataUpdaterTest extends MediaWikiIntegrationTestCase {

	private function willSkipTests() {
		return !ExtensionRegistry::getInstance()->isLoaded( 'GeoData' );
	}

	protected function setUp(): void {
		if ( $this->willSkipTests() ) {
			$this->markTestSkipped( 'GeoData extension is required.' );
		}

		parent::setUp();
	}

	/**
	 * @dataProvider processStatementProvider
	 */
	public function testProcessStatement( array $expected, array $statements, $message ) {
		$updater = $this->newGeoDataDataUpdater(
			[ 'P625', 'P9000' ]
		);

		foreach ( $statements as $statement ) {
			$updater->processStatement( $statement );
		}

		$updater = TestingAccessWrapper::newFromObject( $updater );

		$this->assertEquals( $expected, $updater->coordinates, $message );
	}

	public function processStatementProvider() {
		if ( $this->willSkipTests() ) {
			return [ [ [], [], 'dummy test will be skipped' ] ];
		}

		$statements = $this->getStatements();
		$coords = $this->getCoords();

		return [
			[
				[],
				[ $statements['P42-string'] ],
				'non-geo statement',
			],
			[
				[
					'P625|1' => [ $coords['P625-geo'] ],
				],
				[ $statements['P625-geo'] ],
				'one normal geo statement',
			],
			[
				[],
				[ $statements['P17-geo-deprecated'] ],
				'deprecated geo statement',
			],
			[
				[
					'P10|1' => [
						$coords['P10-geo-A'],
						$coords['P10-geo-B'],
					],
				],
				[
					$statements['P10-geo-A'],
					$statements['P10-geo-B'],
				],
				'multiple normal statements',
			],
			[
				[
					'P10|1' => [
						$coords['P10-geo-A'],
					],
					'P10|2' => [
						$coords['P10-geo-preferred-A'],
						$coords['P10-geo-preferred-B'],
					],
				],
				[
					$statements['P10-geo-A'],
					$statements['P10-geo-preferred-A'],
					$statements['P10-geo-preferred-B'],
				],
				'multiple preferred, one normal',
			],
			[
				[
					'P10|1' => [
						$coords['P10-geo-A'],
						$coords['P10-geo-B'],
					],
					'P10|2' => [
						$coords['P10-geo-preferred-A'],
					],
				],
				[
					$statements['P10-geo-A'],
					$statements['P10-geo-B'],
					$statements['P10-geo-preferred-A'],
				],
				'multiple normal, one preferred',
			],
			[
				[],
				[ $statements['P20-some-value'] ],
				'geo property with some value snak',
			],
			[
				[],
				[ $statements['P404-unknown-property'] ],
				'statement with unknown property, not in PropertyDataTypeLookup',
			],
			[
				[],
				[ $statements['P9002-unknown-globe'] ],
				'statement with unknown globe',
			],
		];
	}

	public function testUpdateParserOutput_withPrimaryCoordPreferredStatement() {
		$updater = $this->getUpdaterWithStatements(
			[ 'P9000', 'P625' ]
		);

		$coords = $this->getCoords();

		$expected = new CoordinatesOutput();

		$primaryCoordinate = $coords['P9000-geo-preferred'];
		$primaryCoordinate->primary = true;

		$expected->addPrimary( $primaryCoordinate );
		unset( $coords['P9000-geo-preferred'] );

		foreach ( $coords as $coord ) {
			$expected->addSecondary( $coord );
		}

		$parserOutput = new ParserOutput();
		$updater->updateParserOutput( $parserOutput );

		$this->assertEquals( $expected,
			CoordinatesOutput::getFromParserOutput( $parserOutput ) );
	}

	public function testUpdateParserOutput_withPrimaryCoordNormalStatement() {
		$updater = $this->getUpdaterWithStatements(
			[ 'P625', 'P10' ]
		);

		$expected = new CoordinatesOutput();
		$coords = $this->getCoords();

		$primaryCoordinate = $coords['P625-geo'];
		$primaryCoordinate->primary = true;

		$expected->addPrimary( $primaryCoordinate );
		unset( $coords['P625-geo'] );

		foreach ( $coords as $coord ) {
			$expected->addSecondary( $coord );
		}

		$parserOutput = new ParserOutput();
		$updater->updateParserOutput( $parserOutput );

		$this->assertEquals( $expected,
			CoordinatesOutput::getFromParserOutput( $parserOutput ) );
	}

	public function testUpdateParserOutput_noPrimaryCoord() {
		$expected = new CoordinatesOutput();

		foreach ( $this->getCoords() as $coord ) {
			$expected->addSecondary( $coord );
		}

		$parserOutput = new ParserOutput();

		$updater = $this->getUpdaterWithStatements(
			[ 'P17', 'P404', 'P10', 'P20', 'P9000', 'P9001', 'P625' ]
		);

		$updater->updateParserOutput( $parserOutput );

		$this->assertEquals( $expected,
			CoordinatesOutput::getFromParserOutput( $parserOutput ) );
	}

	public function testUpdateParserOutput_withExistingCoordinates() {
		$parserOutput = new ParserOutput();
		$coordinatesOutput = CoordinatesOutput::getOrBuildFromParserOutput( $parserOutput );

		$coord = new Coord( 39.0987, -70.0051 );
		$coord->primary = true;

		$coordinatesOutput->addPrimary( $coord );
		$coordinatesOutput->setToParserOutput( $parserOutput );

		$updater = $this->getUpdaterWithStatements( [ 'P625', 'P10' ] );
		$updater->updateParserOutput( $parserOutput );

		$this->assertEquals( $coord,
			CoordinatesOutput::getFromParserOutput( $parserOutput )->getPrimary() );
	}

	private function getUpdaterWithStatements( array $preferredProperties ) {
		$updater = $this->newGeoDataDataUpdater( $preferredProperties );

		foreach ( $this->getStatements() as $statement ) {
			$updater->processStatement( $statement );
		}

		return $updater;
	}

	/**
	 * @param string[] $preferredProperties
	 *
	 * @return GeoDataDataUpdater
	 */
	private function newGeoDataDataUpdater( array $preferredProperties ) {
		return new GeoDataDataUpdater(
			new PropertyDataTypeMatcher( $this->getPropertyDataTypeLookup() ),
			$preferredProperties,
			[
				'http://www.wikidata.org/entity/Q2' => 'earth',
				'http://www.wikidata.org/entity/Q111' => 'mars',
			]
		);
	}

	private function getStatements() {
		$statements = [];

		$statements['P42-string'] = $this->newStatement(
			new NumericPropertyId( 'P42' ),
			new StringValue( 'kittens!' )
		);

		$statements['P625-geo'] = $this->newStatement(
			new NumericPropertyId( 'P625' ),
			$this->newGlobeCoordinateValue( 19.7, 306.8, 'Q111' )
		);

		$statements['P10-geo-A'] = $this->newStatement(
			new NumericPropertyId( 'P10' ),
			$this->newGlobeCoordinateValue( 40.748433, -73.985655 )
		);

		$statements['P10-geo-B'] = $this->newStatement(
			new NumericPropertyId( 'P10' ),
			$this->newGlobeCoordinateValue( 44.264464, 52.643666 )
		);

		$statements['P10-geo-preferred-A'] = $this->newStatementWithRank(
			new NumericPropertyId( 'P10' ),
			$this->newGlobeCoordinateValue( 50.02440, 41.50202 ),
			Statement::RANK_PREFERRED
		);

		$statements['P10-geo-preferred-B'] = $this->newStatementWithRank(
			new NumericPropertyId( 'P10' ),
			$this->newGlobeCoordinateValue( 70.0144, 30.0015 ),
			Statement::RANK_PREFERRED
		);

		$statements['P9000-geo-A'] = $this->newStatement(
			new NumericPropertyId( 'P9000' ),
			$this->newGlobeCoordinateValue( 33.643664, 20.464222 )
		);

		$statements['P9000-geo-B'] = $this->newStatementWithQualifier(
			new NumericPropertyId( 'P9000' ),
			$this->newGlobeCoordinateValue( 11.1234, 12.5678 ),
			new SnakList( [
				new PropertyValueSnak(
					new NumericPropertyId( 'P625' ),
					$this->newGlobeCoordinateValue( 30.0987, 20.1234 )
				),
			] )
		);

		$statements['P9000-geo-preferred'] = $this->newStatementWithRank(
			new NumericPropertyId( 'P9000' ),
			$this->newGlobeCoordinateValue( 77.7777, 33.3333 ),
			Statement::RANK_PREFERRED
		);

		$statements['P17-geo-deprecated'] = $this->newStatementWithRank(
			new NumericPropertyId( 'P17' ),
			$this->newGlobeCoordinateValue( 10.0234, 11.52352 ),
			Statement::RANK_DEPRECATED
		);

		$statements['P20-some-value'] = $this->newStatement( new NumericPropertyId( 'P20' ) );

		$statements['P10-mismatch'] = $this->newStatement(
			new NumericPropertyId( 'P10' ),
			new StringValue( 'omg! wrong value type' )
		);

		$statements['P404-unknown-property'] = $this->newStatement(
			new NumericPropertyId( 'P404' ),
			$this->newGlobeCoordinateValue( 40.733643, -72.352153 )
		);

		$statements['P9002-unknown-globe'] = $this->newStatement(
			new NumericPropertyId( 'P9002' ),
			$this->newGlobeCoordinateValue( 9.017, 14.0987, 'Q147' )
		);

		return $statements;
	}

	private function getCoords() {
		return [
			'P625-geo' => new Coord( 19.7, 306.8, 'mars' ),
			'P10-geo-A' => new Coord( 40.748433, -73.985655 ),
			'P10-geo-B' => new Coord( 44.264464, 52.643666 ),
			'P10-geo-preferred-A' => new Coord( 50.02440, 41.50202 ),
			'P10-geo-preferred-B' => new Coord( 70.0144, 30.0015 ),
			'P9000-geo-A' => new Coord( 33.643664, 20.464222 ),
			'P9000-geo-B' => new Coord( 11.1234, 12.5678 ),
			'P9000-geo-preferred' => new Coord( 77.7777, 33.3333 ),
		];
	}

	private function newStatement( NumericPropertyId $propertyId, DataValue $dataValue = null ) {
		$guidGenerator = new GuidGenerator();

		if ( $dataValue === null ) {
			$snak = new PropertySomeValueSnak( $propertyId );
		} else {
			$snak = new PropertyValueSnak( $propertyId, $dataValue );
		}

		$guid = $guidGenerator->newGuid( new ItemId( 'Q64' ) );

		return new Statement( $snak, null, null, $guid );
	}

	private function newStatementWithRank(
		NumericPropertyId $propertyId,
		DataValue $dataValue,
		$rank
	 ) {
		$rankedStatement = $this->newStatement( $propertyId, $dataValue );
		$rankedStatement->setRank( $rank );

		return $rankedStatement;
	}

	private function newStatementWithQualifier(
		NumericPropertyId $propertyId,
		DataValue $dataValue,
		SnakList $qualifiers
	) {
		$statement = $this->newStatement( $propertyId, $dataValue );
		$statement->setQualifiers( $qualifiers );

		return $statement;
	}

	private function newGlobeCoordinateValue( $lat, $lon, $globeId = 'Q2' ) {
		$latLongValue = new LatLongValue( $lat, $lon );

		// default globe is 'Q2' (earth)
		$globe = "http://www.wikidata.org/entity/$globeId";

		return new GlobeCoordinateValue( $latLongValue, 0.001, $globe );
	}

	private function getPropertyDataTypeLookup() {
		$dataTypeLookup = new InMemoryDataTypeLookup();

		$dataTypeLookup->setDataTypeForProperty( new NumericPropertyId( 'P42' ), 'string' );
		$dataTypeLookup->setDataTypeForProperty( new NumericPropertyId( 'P10' ), 'globe-coordinate' );
		$dataTypeLookup->setDataTypeForProperty( new NumericPropertyId( 'P17' ), 'globe-coordinate' );
		$dataTypeLookup->setDataTypeForProperty( new NumericPropertyId( 'P20' ), 'globe-coordinate' );
		$dataTypeLookup->setDataTypeForProperty( new NumericPropertyId( 'P625' ), 'globe-coordinate' );
		$dataTypeLookup->setDataTypeForProperty( new NumericPropertyId( 'P9000' ), 'globe-coordinate' );
		$dataTypeLookup->setDataTypeForProperty( new NumericPropertyId( 'P9001' ), 'globe-coordinate' );
		$dataTypeLookup->setDataTypeForProperty( new NumericPropertyId( 'P9002' ), 'globe-coordinate' );

		return $dataTypeLookup;
	}

}

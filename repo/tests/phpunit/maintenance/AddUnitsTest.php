<?php
namespace Wikibase\Test;

use DataValues\DecimalValue;
use DataValues\QuantityValue;
use MediaWikiLangTestCase;
use Wikibase\Lib\UnitConverter;
use Wikibase\Repo\Maintenance\SPARQLClient;
use Wikibase\UpdateUnits;

require_once __DIR__ . '/MockAddUnits.php';

/**
 * @covers updateUnits.php
 * @group Wikibase
 */
class AddUnitsTest extends MediaWikiLangTestCase {

	/**
	 * @var MockAddUnits
	 */
	private $script;
	/**
	 * @var SPARQLClient
	 */
	private $client;
	/**
	 * @var UnitConverter
	 */
	private $uc;

	public function setUp() {
		parent::setUp();
		$this->script = new MockAddUnits();
		$this->client =
			$this->getMockBuilder( SPARQLClient::class )->disableOriginalConstructor()->getMock();
		$this->script->setClient( $this->client );
		$this->script->initializeWriter( "http://acme.test/" );
		$this->uc =
			$this->getMockBuilder( UnitConverter::class )->disableOriginalConstructor()->getMock();
		$this->script->setUnitConverter( $this->uc );
	}

	public function getUnitsData() {
		$qConverted =
			new QuantityValue( new DecimalValue( '+1234.5' ), 'Q2', new DecimalValue( '+1235.0' ),
				new DecimalValue( '+1233.9' ) );

		return [
			'base unit' => [
				// units
				[
					[
						'v' => 'http://acme.test/value/testunit',
						'amount' => '123.45',
						'upper' => '123.50',
						'lower' => '123.39'
					]
				],
				// statements
				[
					[
						's' => 'Qstatement',
						'p' => 'http://acme.test/prop/statement/value/P123',
						'v' => 'http://acme.test/value/testunit'
					],
					[
						's' => 'Qstatement',
						'p' => 'http://acme.test/prop/qualifier/value/P123',
						'v' => 'http://acme.test/value/testunit'
					],
					[
						's' => 'Qstatement-another',
						'p' => 'http://acme.test/prop/reference/value/P123',
						'v' => 'http://acme.test/value/testunit'
					],
				],
				// convert
				null,
				// ttl
				'base'
			],
			'converted unit' => [
				// units
				[
					[
						'v' => 'http://acme.test/value/testunit',
						'amount' => '123.45',
						'upper' => '123.50',
						'lower' => '123.39'
					]
				],
				// statements
				[
					[
						's' => 'Qstatement',
						'p' => 'http://acme.test/prop/statement/value/P123',
						'v' => 'http://acme.test/value/testunit'
					],
					[
						's' => 'Qstatement',
						'p' => 'http://acme.test/prop/qualifier/value/P123',
						'v' => 'http://acme.test/value/testunit'
					],
					[
						's' => 'Qstatement-another',
						'p' => 'http://acme.test/prop/reference/value/P123',
						'v' => 'http://acme.test/value/testunit'
					],
				],
				// convert
				$qConverted,
				// ttl
				'converted',
			],
			'no statements' => [
				// units
				[
					[
						'v' => 'http://acme.test/value/testunit',
						'amount' => '123.45',
						'upper' => '123.50',
						'lower' => '123.39'
					]
				],
				// statements
				[],
				// convert
				null,
				// ttl
				'onlyvalue'
			],
		];
	}

	/**
	 * @param $unit
	 * @param $statements
	 * @param $converted
	 * @param $result
	 * @dataProvider getUnitsData
	 */
	public function testBaseUnit( $unit, $statements, $converted, $result ) {
		$this->client->expects( $this->any() )
			->method( 'query' )
			->will( $this->onConsecutiveCalls( $unit, $statements ) );

		$this->uc->expects( $this->any() )->method( 'toStandardUnits' )->will( $converted
			? $this->returnValue( $converted ) : $this->returnArgument( 0 ) );

		$unit = 'Q1';
		$this->script->processUnit( $unit );
		$this->assertSameTTL( $this->script->output, $result );
	}

	private function assertSameTTL( $data, $filename ) {
		$expected = file_get_contents( __DIR__ . "/../data/maintenance/$filename.ttl" );
		$expected = trim( $expected );
		$ttlLines = join( "\n", array_filter( explode( "\n", $data ), function ( $str ) {
			return ( $str && $str[0] != '@' );
		} ) );
		$this->assertEquals( $expected, $ttlLines );
	}

}


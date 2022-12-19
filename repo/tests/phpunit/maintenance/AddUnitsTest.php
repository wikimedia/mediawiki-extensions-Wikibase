<?php

namespace Wikibase\Repo\Tests\Maintenance;

use DataValues\DecimalValue;
use DataValues\QuantityValue;
use MediaWiki\Sparql\SparqlClient;
use MediaWikiLangTestCase;
use Wikibase\Lib\Units\UnitConverter;
use Wikibase\Repo\Tests\Rdf\NTriplesRdfTestHelper;
use Wikibase\Repo\Tests\Rdf\RdfBuilderTestData;

/**
 * @covers \Wikibase\Repo\Maintenance\AddUnitConversions
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Stas Malyshev
 */
class AddUnitsTest extends MediaWikiLangTestCase {

	/**
	 * @var MockAddUnits
	 */
	private $script;
	/**
	 * @var SparqlClient
	 */
	private $client;
	/**
	 * @var UnitConverter
	 */
	private $uc;
	/**
	 * @var NTriplesRdfTestHelper
	 */
	private $helper;

	protected function setUp(): void {
		parent::setUp();
		$this->script = new MockAddUnits();
		$this->client =
			$this->createMock( SparqlClient::class );
		$this->script->setClient( $this->client );
		$this->script->initializeWriter( 'http://acme.test/', 'nt' );
		$this->uc =
			$this->createMock( UnitConverter::class );
		$this->script->setUnitConverter( $this->uc );
		$this->script->initializeBuilder();
		$this->helper = new NTriplesRdfTestHelper(
			new RdfBuilderTestData(
				__DIR__ . '/../data/maintenance',
				__DIR__ . '/../data/maintenance'
			)
		);
	}

	public function getUnitsData() {
		$qConverted =
			new QuantityValue( new DecimalValue( '+1234.5' ), 'Q2', new DecimalValue( '+1235.0' ),
				new DecimalValue( '+1233.9' ) );
		/*
		 * The results files are in tests/phpunit/data/maintenance/*.nt
		 */
		return [
			'base unit' => [
				// values
				[
					[
						'v' => 'http://acme.test/value/testunit',
						'amount' => '123.45',
						'upper' => '123.50',
						'lower' => '123.39',
					],
				],
				// statements
				[
					[
						's' => 'Qstatement',
						'p' => 'http://acme.test/prop/statement/value/P123',
						'v' => 'http://acme.test/value/testunit',
					],
					[
						's' => 'Qstatement',
						'p' => 'http://acme.test/prop/qualifier/value/P123',
						'v' => 'http://acme.test/value/testunit',
					],
					[
						's' => 'Qstatement-another',
						'p' => 'http://acme.test/prop/reference/value/P123',
						'v' => 'http://acme.test/value/testunit',
					],
				],
				// convert
				null,
				// ttl
				'base',
			],
			'converted unit' => [
				// values
				[
					[
						'v' => 'http://acme.test/value/testunit',
						'amount' => '123.45',
						'upper' => '123.50',
						'lower' => '123.39',
					],
				],
				// statements
				[
					[
						's' => 'Qstatement',
						'p' => 'http://acme.test/prop/statement/value/P123',
						'v' => 'http://acme.test/value/testunit',
					],
					[
						's' => 'Qstatement',
						'p' => 'http://acme.test/prop/qualifier/value/P123',
						'v' => 'http://acme.test/value/testunit',
					],
					[
						's' => 'Qstatement-another',
						'p' => 'http://acme.test/prop/reference/value/P123',
						'v' => 'http://acme.test/value/testunit',
					],
				],
				// convert
				$qConverted,
				// ttl
				'converted',
			],
			'no statements' => [
				// values
				[
					[
						'v' => 'http://acme.test/value/testunit',
						'amount' => '123.45',
						'upper' => '123.50',
						'lower' => '123.39',
					],
				],
				// statements
				[],
				// convert
				null,
				// ttl
				'onlyvalue',
			],
		];
	}

	/**
	 * @param array $values  List of values linked to unit
	 * @param array $statements List of statements using values from $values
	 * @param array|null $converted Converted value
	 * @param string $result Expected result filename, in tests/phpunit/data/maintenance/
	 * @dataProvider getUnitsData
	 */
	public function testBaseUnit( $values, $statements, $converted, $result ) {
		$this->client->method( 'query' )
			->will( $this->onConsecutiveCalls( $values, $statements ) );

		$this->uc->method( 'toStandardUnits' )->will( $converted
			? $this->returnValue( $converted ) : $this->returnArgument( 0 ) );

		$values = 'Q1';
		$this->script->processUnit( $values );
		$this->helper->assertNTriplesEqualsDataset( $result, $this->script->output );
	}

}

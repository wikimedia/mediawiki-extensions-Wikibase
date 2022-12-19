<?php

namespace Wikibase\Repo\Tests\Rdf\Values;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Repo\Rdf\Values\LiteralValueRdfBuilder;
use Wikibase\Repo\Tests\Rdf\NTriplesRdfTestHelper;
use Wikimedia\Purtle\NTriplesRdfWriter;

/**
 * @covers \Wikibase\Repo\Rdf\Values\LiteralValueRdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRdf
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class LiteralValueRdfBuilderTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var NTriplesRdfTestHelper
	 */
	private $helper;

	protected function setUp(): void {
		parent::setUp();

		$this->helper = new NTriplesRdfTestHelper();
	}

	public function provideAddValue() {
		$p11 = new NumericPropertyId( 'P11' );
		$stringSnak = new PropertyValueSnak( $p11, new StringValue( 'Hello World' ) );
		$numberSnak = new PropertyValueSnak( $p11, new StringValue( '15' ) );

		return [
			'plain string' => [
				null, null,
				$stringSnak,
				'<http://www/Q1> <http://acme/testing> "Hello World" .',
			],
			'xsd decimal' => [
				null, 'decimal',
				$numberSnak,
				'<http://www/Q1> <http://acme/testing> "15"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
			],
			'wd id' => [
				'xx', 'id',
				$stringSnak,
				'<http://www/Q1> <http://acme/testing> "Hello World"^^<http://xx/id> .',
			],
		];
	}

	/**
	 * @dataProvider provideAddValue
	 */
	public function testAddValue(
		$typeBase, $typeLocal,
		PropertyValueSnak $snak,
		$expected
	) {
		$builder = new LiteralValueRdfBuilder( $typeBase, $typeLocal );

		$writer = new NTriplesRdfWriter();
		$writer->prefix( 'www', "http://www/" );
		$writer->prefix( 'acme', "http://acme/" );
		$writer->prefix( $typeBase, "http://$typeBase/" );

		$writer->start();
		$writer->about( 'www', 'Q1' );

		$builder->addValue( $writer, 'acme', 'testing', 'DUMMY', '', $snak );

		$this->helper->assertNTriplesEquals( $expected, $writer->drain() );
	}

}

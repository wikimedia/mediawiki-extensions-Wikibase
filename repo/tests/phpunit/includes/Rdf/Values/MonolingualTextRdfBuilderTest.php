<?php

namespace Wikibase\Repo\Tests\Rdf\Values;

use DataValues\MonolingualTextValue;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Repo\Rdf\Values\MonolingualTextRdfBuilder;
use Wikibase\Repo\Tests\Rdf\NTriplesRdfTestHelper;
use Wikimedia\Purtle\NTriplesRdfWriter;

/**
 * @covers \Wikibase\Repo\Rdf\Values\MonolingualTextRdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRdf
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class MonolingualTextRdfBuilderTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var NTriplesRdfTestHelper
	 */
	private $helper;

	protected function setUp(): void {
		parent::setUp();

		$this->helper = new NTriplesRdfTestHelper();
	}

	public function testAddValue() {
		$builder = new MonolingualTextRdfBuilder();
		$writer = new NTriplesRdfWriter();
		$writer->prefix( 'www', "http://www/" );
		$writer->prefix( 'acme', "http://acme/" );

		$writer->start();
		$writer->about( 'www', 'Q1' );

		$snak = new PropertyValueSnak( new NumericPropertyId( 'P1' ), new MonolingualTextValue( 'de', 'Hallo Welt' ) );
		$builder->addValue( $writer, 'acme', 'testing', 'DUMMY', '', $snak );

		$expected = '<http://www/Q1> <http://acme/testing> "Hallo Welt"@de .';
		$this->helper->assertNTriplesEquals( $expected, $writer->drain() );
	}

}

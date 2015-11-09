<?php

namespace Wikibase\Test\Rdf;

use DataValues\MonolingualTextValue;
use DataValues\StringValue;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Rdf\Values\MonolingualTextRdfBuilder;
use Wikimedia\Purtle\NTriplesRdfWriter;
use Wikibase\DataModel\Snak\PropertyValueSnak;

/**
 * @covers Wikibase\Rdf\Values\MonolingualTextRdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseRdf
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class MonolingualTextRdfBuilderTest extends \PHPUnit_Framework_TestCase {

	public function testAddValue() {
		$builder = new MonolingualTextRdfBuilder();
		$writer = new NTriplesRdfWriter();
		$writer->prefix( 'www', "http://www/" );
		$writer->prefix( 'acme', "http://acme/" );

		$writer->start();
		$writer->about( 'www', 'Q1' );

		$snak = new PropertyValueSnak( new PropertyId( 'P1' ), new MonolingualTextValue( 'de', 'Hallo Welt' ) );
		$builder->addValue( $writer, 'acme', 'testing', 'DUMMY', $snak );


		$expected = array( '<http://www/Q1> <http://acme/testing> "Hallo Welt"@de .' );
		$triples = explode( "\n", trim( $writer->drain() ) );
		$this->assertEquals( $expected, $triples );
	}

}

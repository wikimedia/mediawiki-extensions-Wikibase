<?php

namespace Wikibase\Test\Rdf;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Rdf\RdfVocabulary;
use Wikibase\Rdf\Values\CommonsMediaRdfBuilder;
use Wikimedia\Purtle\NTriplesRdfWriter;
use Wikibase\DataModel\Snak\PropertyValueSnak;

/**
 * @covers Wikibase\Rdf\Values\CommonsMediaRdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseRdf
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class CommonsMediaRdfBuilderTest extends \PHPUnit_Framework_TestCase {

	public function testAddValue() {
		$vocab = new RdfVocabulary( 'http://test/item/', 'http://test/data/' );
		$builder = new CommonsMediaRdfBuilder( $vocab );

		$writer = new NTriplesRdfWriter();
		$writer->prefix( 'www', "http://www/" );
		$writer->prefix( 'acme', "http://acme/" );

		$writer->start();
		$writer->about( 'www', 'Q1' );

		$snak = new PropertyValueSnak(
			new PropertyId( 'P1' ),
			new StringValue( 'Bunny.jpg' )
		);

		$builder->addValue( $writer, 'acme', 'testing', 'DUMMY', $snak );

		$expected = array( '<http://www/Q1> <http://acme/testing> <http://commons.wikimedia.org/wiki/Special:FilePath/Bunny.jpg> .' );
		$triples = explode( "\n", trim( $writer->drain() ) );
		$this->assertEquals( $expected, $triples );
	}

}

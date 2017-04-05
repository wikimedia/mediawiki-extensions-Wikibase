<?php

namespace Wikibase\Repo\Tests\Rdf\Values;

use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Rdf\NullEntityMentionListener;
use Wikibase\Rdf\RdfVocabulary;
use Wikibase\Rdf\Values\EntityIdRdfBuilder;
use Wikibase\Repo\Tests\Rdf\NTriplesRdfTestHelper;
use Wikimedia\Purtle\NTriplesRdfWriter;
use Wikibase\DataModel\Snak\PropertyValueSnak;

/**
 * @covers Wikibase\Rdf\Values\EntityIdRdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRdf
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class EntityIdRdfBuilderTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var NTriplesRdfTestHelper
	 */
	private $helper;

	protected function setUp() {
		parent::setUp();

		$this->helper = new NTriplesRdfTestHelper();
	}

	public function testAddValue() {
		$vocab = new RdfVocabulary( [ '' => 'http://test/item/' ], 'http://test/data/' );
		$builder = new EntityIdRdfBuilder( $vocab, new NullEntityMentionListener() );

		$writer = new NTriplesRdfWriter();
		$writer->prefix( 'www', "http://www/" );
		$writer->prefix( 'acme', "http://acme/" );
		$writer->prefix( RdfVocabulary::NS_ENTITY, 'http://test/item/' );

		$writer->start();
		$writer->about( 'www', 'Q1' );

		$snak = new PropertyValueSnak(
			new PropertyId( 'P1' ),
			new EntityIdValue( new ItemId( 'Q23' ) )
		);

		$builder->addValue( $writer, 'acme', 'testing', 'DUMMY', $snak );

		$expected = '<http://www/Q1> <http://acme/testing> <http://test/item/Q23> .';
		$this->helper->assertNTriplesEquals( $expected, $writer->drain() );
	}

}

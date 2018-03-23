<?php

namespace Wikibase\Repo\Tests\Rdf\Values;

use DataValues\StringValue;
use Wikibase\Rdf\HashDedupeBag;
use Wikibase\Rdf\RdfVocabulary;
use Wikibase\Rdf\Values\ComplexValueRdfHelper;
use Wikibase\Repo\Tests\Rdf\NTriplesRdfTestHelper;
use Wikimedia\Purtle\NTriplesRdfWriter;

/**
 * @covers Wikibase\Rdf\Values\ComplexValueRdfHelper
 *
 * @group Wikibase
 * @group WikibaseRdf
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class ComplexValueRdfHelperTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var NTriplesRdfTestHelper
	 */
	private $helper;

	protected function setUp() {
		parent::setUp();

		$this->helper = new NTriplesRdfTestHelper();
	}

	public function testAttachValueNode() {
		$vocab = new RdfVocabulary( [ '' => 'http://acme.com/item/' ], 'http://acme.com/data/' );

		$snakWriter = new NTriplesRdfWriter();
		$snakWriter->prefix( 'www', "http://www/" );
		$snakWriter->prefix( RdfVocabulary::NSP_CLAIM_VALUE, "http://acme/statement/value/" );
		$snakWriter->prefix( RdfVocabulary::NS_VALUE, "http://acme/value/" );

		$valueWriter = new NTriplesRdfWriter();
		$valueWriter->prefix( RdfVocabulary::NS_VALUE, "http://acme/value/" );
		$valueWriter->prefix( RdfVocabulary::NS_ONTOLOGY, "http://acme/onto/" );

		$helper = new ComplexValueRdfHelper( $vocab, $valueWriter, new HashDedupeBag() );

		// check we get the correct value writer
		$this->assertSame( $valueWriter, $helper->getValueNodeWriter() );

		$snakWriter->start();
		$snakWriter->about( 'www', 'Q1' );

		$valueWriter->start();

		// attach a value node
		$value = new StringValue( 'http://en.wikipedia.org/wiki/Wikidata' );
		$lvalue = $helper->attachValueNode( $snakWriter, RdfVocabulary::NSP_CLAIM_STATEMENT, 'testing', 'DUMMY', $value );
		$this->assertEquals( 'e93b68fef814eb52e813bb72e6867432', $lvalue );

		// do it again, tests dedupe
		$snakWriter->about( 'www', 'Q2' );
		$lvalue = $helper->attachValueNode( $snakWriter, RdfVocabulary::NSP_CLAIM_STATEMENT, 'testing', 'DUMMY', $value );
		$this->assertNull( $lvalue, 'lvalue produced by adding a value a second time should be null' );

		// check the triples written to the snak writer
		$expected = [
			'<http://www/Q1> <http://acme/statement/value/testing> <http://acme/value/e93b68fef814eb52e813bb72e6867432> .',
			'<http://www/Q2> <http://acme/statement/value/testing> <http://acme/value/e93b68fef814eb52e813bb72e6867432> .'
		];

		$this->helper->assertNTriplesEquals( $expected, $snakWriter->drain() );

		// check the triples written to the value writer
		$expected = '<http://acme/value/e93b68fef814eb52e813bb72e6867432> '
			. '<http://www.w3.org/1999/02/22-rdf-syntax-ns#type> '
			. '<http://acme/onto/StringValue> .';
		$this->helper->assertNTriplesEquals( $expected, $valueWriter->drain() );
	}

}

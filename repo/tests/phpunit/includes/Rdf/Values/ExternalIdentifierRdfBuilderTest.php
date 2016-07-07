<?php

namespace Wikibase\Test\Rdf;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Rdf\Values\ExternalIdentifierRdfBuilder;
use Wikibase\Repo\Tests\Rdf\NTriplesRdfTestHelper;
use Wikimedia\Purtle\NTriplesRdfWriter;
use Wikibase\DataModel\Snak\PropertyValueSnak;

/**
 * @covers Wikibase\Rdf\Values\ExternalIdentifierRdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseRdf
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class ExternalIdentifierRdfBuilderTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var NTriplesRdfTestHelper
	 */
	private $helper;

	protected function setUp() {
		parent::setUp();

		$this->helper = new NTriplesRdfTestHelper();
	}

	public function testAddValue() {
		$uriPatternProvider = $this->getMock( 'Wikibase\Lib\PropertyInfoProvider' );
		$uriPatternProvider->expects( $this->any() )
			->method( 'getPropertyInfo' )
			->will( $this->returnCallback( function( PropertyId $id ) {
				return $id->getSerialization() === 'P1' ? 'http://xyzzy.com/vocab/$1' : null;
			} ) );

		$builder = new ExternalIdentifierRdfBuilder( $uriPatternProvider );

		$writer = new NTriplesRdfWriter();
		$writer->prefix( 'www', "http://www/" );
		$writer->prefix( 'wbp', "http://acme/prop/" );

		$writer->start();
		$writer->about( 'www', 'Q1' );

		$snakP1 = new PropertyValueSnak(
			new PropertyId( 'P1' ),
			new StringValue( 'AB&123' )
		);

		$snakP345 = new PropertyValueSnak(
			new PropertyId( 'P345' ),
			new StringValue( 'XY-23' )
		);

		$builder->addValue( $writer, 'wbp', 'P1', 'DUMMY', $snakP1 );
		$builder->addValue( $writer, 'wbp', 'P345', 'DUMMY', $snakP345 );

		$expected = array(
			'<http://www/Q1> <http://acme/prop/P1> <http://xyzzy.com/vocab/AB%26123> .',
			'<http://www/Q1> <http://acme/prop/P345> "XY-23" .',
		);
		$this->helper->assertNTriplesEquals( $expected, $writer->drain() );
	}

}

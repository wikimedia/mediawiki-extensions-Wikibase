<?php

namespace Wikibase\Test\Rdf;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\PropertyInfoProvider;
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
 * @license GNU GPL v2+
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
		$uriPatternProvider = $this->getMock( PropertyInfoProvider::class );
		$uriPatternProvider->expects( $this->any() )
			->method( 'getPropertyInfo' )
			->will( $this->returnCallback( function( PropertyId $id ) {
				return $id->getSerialization() === 'P1' ? 'http://xyzzy.test/vocab/$1' : null;
			} ) );

		$builder = new ExternalIdentifierRdfBuilder( $uriPatternProvider );

		$writer = new NTriplesRdfWriter();
		$writer->prefix( 'www', "http://www.test/" );
		$writer->prefix( 'wdt', "http://acme.test/prop/" );
		$writer->prefix( 'wdtn', "http://acme.test/prop-normalized/" );

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

		$builder->addValue( $writer, 'wdt', 'P1', 'DUMMY', $snakP1 );
		$builder->addValue( $writer, 'wdt', 'P345', 'DUMMY', $snakP345 );

		$expected = [
			'<http://www.test/Q1> <http://acme.test/prop-normalized/P1> <http://xyzzy.test/vocab/AB%26123> .',
			'<http://www.test/Q1> <http://acme.test/prop/P1> "AB&123" .',
			'<http://www.test/Q1> <http://acme.test/prop/P345> "XY-23" .',
		];
		$this->helper->assertNTriplesEquals( $expected, $writer->drain() );
	}

}

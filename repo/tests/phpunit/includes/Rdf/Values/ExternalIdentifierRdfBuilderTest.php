<?php

namespace Wikibase\Repo\Tests\Rdf\Values;

use DataValues\StringValue;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\Store\PropertyInfoProvider;
use Wikibase\Lib\SubEntityTypesMapper;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikibase\Repo\Rdf\Values\ExternalIdentifierRdfBuilder;
use Wikibase\Repo\Tests\Rdf\NTriplesRdfTestHelper;
use Wikimedia\Purtle\NTriplesRdfWriter;

/**
 * @covers \Wikibase\Repo\Rdf\Values\ExternalIdentifierRdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseRdf
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class ExternalIdentifierRdfBuilderTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var NTriplesRdfTestHelper
	 */
	private $helper;

	protected function setUp(): void {
		parent::setUp();

		$this->helper = new NTriplesRdfTestHelper();
	}

	public function testAddValue() {
		$uriPatternProvider = $this->createMock( PropertyInfoProvider::class );
		$uriPatternProvider->method( 'getPropertyInfo' )
			->willReturnCallback( function( NumericPropertyId $id ) {
				return $id->getSerialization() === 'P1' ? 'http://xyzzy.test/vocab/$1' : null;
			} );

		$vocabulary = new RdfVocabulary(
			[ '' => '<BASE>' ],
			[ '' => '<DATA>' ],
			new EntitySourceDefinitions( [], new SubEntityTypesMapper( [] ) ),
			[ '' => '' ],
			[ '' => '' ]
		);
		$builder = new ExternalIdentifierRdfBuilder(
			$vocabulary,
			$uriPatternProvider
		);

		$directClaimNamespace = $vocabulary->propertyNamespaceNames[''][RdfVocabulary::NSP_DIRECT_CLAIM];

		$writer = new NTriplesRdfWriter();
		$writer->prefix( 'www', "http://www.test/" );
		$writer->prefix( $directClaimNamespace, "http://acme.test/prop/" );
		$writer->prefix(
			$vocabulary->propertyNamespaceNames[''][RdfVocabulary::NSP_DIRECT_CLAIM_NORM],
			"http://acme.test/prop-normalized/"
		);

		$writer->start();
		$writer->about( 'www', 'Q1' );

		$snakP1 = new PropertyValueSnak(
			new NumericPropertyId( 'P1' ),
			new StringValue( 'AB&123' )
		);

		$snakP345 = new PropertyValueSnak(
			new NumericPropertyId( 'P345' ),
			new StringValue( 'XY-23' )
		);

		$builder->addValue(
			$writer,
			$directClaimNamespace,
			'P1',
			'DUMMY',
			RdfVocabulary::NS_VALUE,
			$snakP1
		);
		$builder->addValue(
			$writer,
			$directClaimNamespace,
			'P345',
			'DUMMY',
			RdfVocabulary::NS_VALUE,
			$snakP345
		);

		$expected = [
			'<http://www.test/Q1> <http://acme.test/prop-normalized/P1> <http://xyzzy.test/vocab/AB%26123> .',
			'<http://www.test/Q1> <http://acme.test/prop/P1> "AB&123" .',
			'<http://www.test/Q1> <http://acme.test/prop/P345> "XY-23" .',
		];
		$this->helper->assertNTriplesEquals( $expected, $writer->drain() );
	}

}

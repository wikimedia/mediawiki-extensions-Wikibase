<?php

namespace Wikibase\Repo\Tests\Rdf\Values;

use DataValues\StringValue;
use PHPUnit4And6Compat;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\Tests\DataAccessSettingsFactory;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\PropertyInfoProvider;
use Wikibase\Rdf\RdfVocabulary;
use Wikibase\Rdf\Values\ExternalIdentifierRdfBuilder;
use Wikibase\Repo\Tests\Rdf\NTriplesRdfTestHelper;
use Wikimedia\Purtle\NTriplesRdfWriter;
use Wikibase\DataModel\Snak\PropertyValueSnak;

/**
 * @covers \Wikibase\Rdf\Values\ExternalIdentifierRdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseRdf
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class ExternalIdentifierRdfBuilderTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

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

		$vocabulary = new RdfVocabulary(
			[ '' => '<BASE>' ],
			[ '' => '<DATA>' ],
			DataAccessSettingsFactory::repositoryPrefixBasedFederation(),
			new EntitySourceDefinitions( [] ),
			'',
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
		$writer->prefix( $vocabulary->propertyNamespaceNames[''][RdfVocabulary::NSP_DIRECT_CLAIM_NORM], "http://acme.test/prop-normalized/" );

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

		$builder->addValue( $writer, $directClaimNamespace, 'P1', 'DUMMY', $snakP1 );
		$builder->addValue( $writer, $directClaimNamespace, 'P345', 'DUMMY', $snakP345 );

		$expected = [
			'<http://www.test/Q1> <http://acme.test/prop-normalized/P1> <http://xyzzy.test/vocab/AB%26123> .',
			'<http://www.test/Q1> <http://acme.test/prop/P1> "AB&123" .',
			'<http://www.test/Q1> <http://acme.test/prop/P345> "XY-23" .',
		];
		$this->helper->assertNTriplesEquals( $expected, $writer->drain() );
	}

	public function testAddValue_entitySourceBasedFederation() {
		$uriPatternProvider = $this->getMock( PropertyInfoProvider::class );
		$uriPatternProvider->expects( $this->any() )
			->method( 'getPropertyInfo' )
			->will( $this->returnCallback( function( PropertyId $id ) {
				return $id->getSerialization() === 'P1' ? 'http://xyzzy.test/vocab/$1' : null;
			} ) );

		$vocabulary = new RdfVocabulary(
			[ '' => '<BASE>' ],
			[ '' => '<DATA>' ],
			DataAccessSettingsFactory::entitySourceBasedFederation(),
			new EntitySourceDefinitions( [] ),
			'',
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
		$writer->prefix( $vocabulary->propertyNamespaceNames[''][RdfVocabulary::NSP_DIRECT_CLAIM_NORM], "http://acme.test/prop-normalized/" );

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

		$builder->addValue( $writer, $directClaimNamespace, 'P1', 'DUMMY', $snakP1 );
		$builder->addValue( $writer, $directClaimNamespace, 'P345', 'DUMMY', $snakP345 );

		$expected = [
			'<http://www.test/Q1> <http://acme.test/prop-normalized/P1> <http://xyzzy.test/vocab/AB%26123> .',
			'<http://www.test/Q1> <http://acme.test/prop/P1> "AB&123" .',
			'<http://www.test/Q1> <http://acme.test/prop/P345> "XY-23" .',
		];
		$this->helper->assertNTriplesEquals( $expected, $writer->drain() );
	}

}

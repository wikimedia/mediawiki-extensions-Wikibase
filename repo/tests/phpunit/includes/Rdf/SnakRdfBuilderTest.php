<?php

namespace Wikibase\Repo\Tests\Rdf;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Rdf\EntityMentionListener;
use Wikibase\Rdf\RdfVocabulary;
use Wikibase\Rdf\SnakRdfBuilder;
use Wikibase\Rdf\ValueSnakRdfBuilder;

/**
 * @covers Wikibase\Rdf\SnakRdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRdf
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class SnakRdfBuilderTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var NTriplesRdfTestHelper
	 */
	private $helper;

	protected function setUp() {
		parent::setUp();

		$this->helper = new NTriplesRdfTestHelper(
			new RdfBuilderTestData(
				__DIR__ . '/../../data/rdf/entities',
				__DIR__ . '/../../data/rdf/SnakRdfBuilder'
			)
		);
	}

	/**
	 * Initialize repository data
	 *
	 * @return RdfBuilderTestData
	 */
	private function getTestData() {
		return $this->helper->getTestData();
	}

	/**
	 * @param string $propertyNamespace
	 * @param string $propertyValueLName
	 * @param string $dataType
	 * @param Snak|null $snak
	 * @param EntityId[] &$mentioned receives the IDs of any mentioned entities.
	 *
	 * @return SnakRdfBuilder
	 */
	private function newBuilder(
		$propertyNamespace,
		$propertyValueLName,
		$dataType,
		Snak $snak = null,
		array &$mentioned = []
	) {
		$mentionTracker = $this->getMock( EntityMentionListener::class );
		$mentionTracker->expects( $this->any() )
			->method( 'propertyMentioned' )
			->will( $this->returnCallback( function( EntityId $id ) use ( &$mentioned ) {
				$key = $id->getSerialization();
				$mentioned[$key] = $id;
			} ) );

		$valueBuilder = $this->getMock( ValueSnakRdfBuilder::class );

		if ( $snak instanceof PropertyValueSnak ) {
			$valueBuilder->expects( $this->once() )
				->method( 'addValue' )
				->with( $this->anything(), $propertyNamespace, $propertyValueLName, $dataType, $snak );
		} else {
			$valueBuilder->expects( $this->never() )
				->method( 'addValue' );
		}

		$vocabulary = $this->getTestData()->getVocabulary();

		$builder = new SnakRdfBuilder( $vocabulary, $valueBuilder, $this->getTestData()->getMockRepository() );
		$builder->setEntityMentionListener( $mentionTracker );

		return $builder;
	}

	public function provideAddSnakValue() {
		// NOTE: data types must match $this->getTestData()->getMockRepository();

		return [
			'value snak' => [
				new PropertyValueSnak(
					new PropertyId( 'P2' ),
					new EntityIdValue( new ItemId( 'Q42' ) )
				),
				'wikibase-item',
			],
			'value snak with data type' => [
				new PropertyValueSnak(
					new PropertyId( 'P9' ),
					new StringValue( 'http://acme.com' )
				),
				'url'
			],
		];
	}

	/**
	 * @dataProvider provideAddSnakValue
	 */
	public function testAddSnakValue( Snak $snak, $dataType ) {
		$writer = $this->getTestData()->getNTriplesWriter();

		$writer->about( RdfVocabulary::NS_ENTITY, 'Q11' );

		$propertyId = $snak->getPropertyId();

		$builder = $this->newBuilder(
			RdfVocabulary::NSP_DIRECT_CLAIM,
			$propertyId->getSerialization(),
			$dataType,
			$snak
		);

		// assertions are done by the mocks
		$builder->addSnak( $writer, $snak, RdfVocabulary::NSP_DIRECT_CLAIM );
	}

	public function testAddSnakValue_novalue() {
		$propertyId = new PropertyId( 'P2' );
		$snak = new PropertyNoValueSnak( $propertyId );

		$writer = $this->getTestData()->getNTriplesWriter();
		$writer->about( RdfVocabulary::NS_ENTITY, 'Q11' );

		$builder = $this->newBuilder(
			RdfVocabulary::NSP_DIRECT_CLAIM,
			$propertyId->getSerialization(),
			'wikibase-item'
		);

		$expectedTriples = [
			'<http://acme.test/Q11> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://acme.test/prop/novalue/P2> .',
		];

		$builder->addSnak( $writer, $snak, RdfVocabulary::NSP_DIRECT_CLAIM );

		$this->helper->assertNTriplesEquals( $expectedTriples, $writer->drain() );
	}

	public function testAddSnakValue_mention() {
		$propertyId = new PropertyId( 'P2' );
		$value = new EntityIdValue( new ItemId( 'Q42' ) );
		$snak = new PropertyValueSnak( $propertyId, $value );

		$writer = $this->getTestData()->getNTriplesWriter();
		$writer->about( RdfVocabulary::NS_ENTITY, 'Q11' );

		$mentioned = [];
		$builder = $this->newBuilder(
			RdfVocabulary::NSP_DIRECT_CLAIM,
			$propertyId->getSerialization(),
			'wikibase-item',
			$snak,
			$mentioned
		);

		$builder->addSnak( $writer, $snak, RdfVocabulary::NSP_DIRECT_CLAIM );
		$this->assertEquals( [ 'P2' ], array_keys( $mentioned ) );
	}

}

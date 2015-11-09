<?php

namespace Wikibase\Test\Rdf;

use DataValues\DataValue;
use DataValues\StringValue;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Rdf\RdfVocabulary;
use Wikibase\Rdf\SnakRdfBuilder;
use Wikimedia\Purtle\RdfWriter;

/**
 * @covers Wikibase\Rdf\SnakRdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseRdf
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class SnakRdfBuilderTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var RdfBuilderTestData|null
	 */
	private $testData = null;

	/**
	 * Initialize repository data
	 *
	 * @return RdfBuilderTestData
	 */
	private function getTestData() {
		if ( $this->testData === null ) {
			$this->testData = new RdfBuilderTestData(
				__DIR__ . "/../../data/rdf",
				__DIR__ . "/../../data/rdf/SnakRdfBuilder"
			);
		}

		return $this->testData;
	}

	/**
	 * @param string $propertyNamespace
	 * @param string $propertyValueLName
	 * @param string $dataType
	 * @param DataValue $value
	 * @param EntityId[] &$mentioned receives the IDs of any mentioned entities.
	 *
	 * @return SnakRdfBuilder
	 */
	private function newBuilder(
		$propertyNamespace,
		$propertyValueLName,
		$dataType = null,
		DataValue $value = null,
		array &$mentioned = array()
	) {
		$mentionTracker = $this->getMock( 'Wikibase\Rdf\EntityMentionListener' );
		$mentionTracker->expects( $this->any() )
			->method( 'propertyMentioned' )
			->will( $this->returnCallback( function( EntityId $id ) use ( &$mentioned ) {
				$key = $id->getSerialization();
				$mentioned[$key] = $id;
			} ) );

		$valueBuilder = $this->getMock( 'Wikibase\Rdf\ValueSnakRdfBuilder' );

		if ( $value ) {
			$valueBuilder->expects( $this->once() )
				->method( 'addValue' )
				->with( $this->anything(), $propertyNamespace, $propertyValueLName, $dataType, $value );
		} else {
			$valueBuilder->expects( $this->never() )
				->method( 'addValue' );
		}

		$vocabulary = $this->getTestData()->getVocabulary();

		$builder = new SnakRdfBuilder( $vocabulary, $valueBuilder, $this->getTestData()->getMockRepository() );
		$builder->setEntityMentionListener( $mentionTracker );

		return $builder;
	}

	/**
	 * Extract text test data from RDF builder
	 *
	 * @param RdfWriter $writer
	 *
	 * @return string[] ntriples lines, sorted
	 */
	private function getDataFromWriter( RdfWriter $writer ) {
		$ntriples = $writer->drain();

		$lines = explode( "\n", trim( $ntriples ) );
		sort( $lines );
		return $lines;
	}

	private function assertTriplesEqual( array $expectedTriples, RdfWriter $writer ) {
		$actualTripels = $this->getDataFromWriter( $writer );
		sort( $expectedTriples );

		$this->assertEquals( $expectedTriples, $actualTripels );
	}

	public function provideAddSnakValue() {
		// NOTE: data types must match $this->getTestData()->getMockRepository();

		return array(
			'value snak' => array(
				new PropertyValueSnak(
					new PropertyId( 'P2' ),
					new EntityIdValue( new ItemId( 'Q42' ) )
				),
				null, // Currently, the data type is only looked up for string values
			),
			'value snak with data type' => array(
				new PropertyValueSnak(
					new PropertyId( 'P9' ),
					new StringValue( 'http://acme.com' )
				),
				'url', // Data type should be supplied at least for string values
				array()
			),
		);
	}

	/**
	 * @dataProvider provideAddSnakValue
	 */
	public function testAddSnakValue( Snak $snak, $dataType ) {
		$writer = $this->getTestData()->getNTriplesWriter();

		$writer->about( RdfVocabulary::NS_ENTITY, 'Q11' );

		$propertyId = $snak->getPropertyId();
		$value = $snak instanceof PropertyValueSnak ? $snak->getDataValue() : null;

		$builder = $this->newBuilder(
			RdfVocabulary::NSP_DIRECT_CLAIM,
			$propertyId->getSerialization(),
			$dataType,
			$value
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
			$propertyId->getSerialization()
		);

		$expectedTriples = array(
			'<http://acme.test/Q11> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://acme.test/prop/novalue/P2> .',
		);

		$builder->addSnak( $writer, $snak, RdfVocabulary::NSP_DIRECT_CLAIM );
		$this->assertTriplesEqual( $expectedTriples, $writer );
	}

	public function testAddSnakValue_mention() {
		$propertyId = new PropertyId( 'P2' );
		$value = new EntityIdValue( new ItemId( 'Q42' ) );
		$snak = new PropertyValueSnak( $propertyId, $value );

		$writer = $this->getTestData()->getNTriplesWriter();
		$writer->about( RdfVocabulary::NS_ENTITY, 'Q11' );

		$mentioned = array();
		$builder = $this->newBuilder(
			RdfVocabulary::NSP_DIRECT_CLAIM,
			$propertyId->getSerialization(),
			null,
			$value,
			$mentioned
		);

		$builder->addSnak( $writer, $snak, RdfVocabulary::NSP_DIRECT_CLAIM );
		$this->assertEquals( array( 'P2' ), array_keys( $mentioned ) );
	}

}

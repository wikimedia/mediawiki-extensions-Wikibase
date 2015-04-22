<?php

namespace Wikibase\Test\Rdf;

use DataValues\DataValue;
use DataValues\DecimalValue;
use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\LatLongValue;
use DataValues\MonolingualTextValue;
use DataValues\QuantityValue;
use DataValues\StringValue;
use DataValues\TimeValue;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikimedia\Purtle\RdfWriter;
use Wikibase\Rdf\RdfVocabulary;
use Wikibase\Rdf\SimpleValueRdfBuilder;

/**
 * @covers Wikibase\Rdf\SimpleValueRdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseRdf
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class SimpleValueRdfBuilderTest extends \PHPUnit_Framework_TestCase {

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
				__DIR__ . "/../../data/rdf/SimpleValueRdfBuilder"
			);
		}

		return $this->testData;
	}

	/**
	 * @param EntityId[] &$mentioned receives the IDs of any mentioned entities.
	 *
	 * @return SimpleValueRdfBuilder
	 */
	private function newBuilder( array &$mentioned = array() ) {
		$mentionTracker = $this->getMock( 'Wikibase\Rdf\EntityMentionListener' );
		$mentionTracker->expects( $this->any() )
			->method( 'entityReferenceMentioned' )
			->will( $this->returnCallback( function( EntityId $id ) use ( &$mentioned ) {
			$key = $id->getSerialization();
			$mentioned[$key] = $id;
		} ) );

		$vocabulary = $this->getTestData()->getVocabulary();

		$builder = new SimpleValueRdfBuilder( $vocabulary, $this->getTestData()->getMockRepository() );
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
			'wikibase-entityid' => array(
				new PropertyId( 'P2' ),
				new EntityIdValue( new ItemId( 'Q42' ) ),
				array(
					'<http://acme.test/Q11> <http://acme.test/prop/direct/P2> <http://acme.test/Q42> .',
				)
			),
			'commonsMedia' => array(
				new PropertyId( 'P3' ),
				new StringValue( 'Test.jpg' ),
				array(
					'<http://acme.test/Q11> <http://acme.test/prop/direct/P3> <http://commons.wikimedia.org/wiki/Special:FilePath/Test.jpg> .',
				)
			),
			'globecoordinate' => array(
				new PropertyId( 'P4' ),
				new GlobeCoordinateValue(
					new LatLongValue( 12.25, -45.5 ),
					0.025,
					'https://www.wikidata.org/entity/Q2'
				),
				array(
					'<http://acme.test/Q11> <http://acme.test/prop/direct/P4> "Point(12.25 -45.5)"^^<http://www.opengis.net/ont/geosparql#wktLiteral> .',
				)
			),
			'monolingualtext' => array(
				new PropertyId( 'P5' ),
				new MonolingualTextValue( 'ru', 'Берлин' ),
				array(
					'<http://acme.test/Q11> <http://acme.test/prop/direct/P5> "\u0411\u0435\u0440\u043B\u0438\u043D"@ru .',
				)
			),
			'quantity' => array(
				new PropertyId( 'P6' ),
				new QuantityValue(
					new DecimalValue( '+0.00011' ),
					'1',
					new DecimalValue( '+0.00013' ),
					new DecimalValue( '+0.00010' ) ),
				array(
					'<http://acme.test/Q11> <http://acme.test/prop/direct/P6> "+0.00011"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
				)
			),
			'quantity-unit' => array(
				new PropertyId( 'P6' ),
				new QuantityValue(
					new DecimalValue( '-2.3' ),
					'https://www.wikidata.org/entity/Q11573',
					new DecimalValue( '-2.3' ),
					new DecimalValue( '-2.3' ) ),
				array(
					'<http://acme.test/Q11> <http://acme.test/prop/direct/P6> "-2.3"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
				)
			),
			'string' => array(
				new PropertyId( 'P7' ),
				new StringValue( 'Kittens' ),
				array(
					'<http://acme.test/Q11> <http://acme.test/prop/direct/P7> "Kittens" .',
				)
			),
			'time' => array(
				new PropertyId( 'P8' ),
				new TimeValue( '+2015-03-03T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_DAY, RdfVocabulary::GREGORIAN_CALENDAR ),
				array(
					'<http://acme.test/Q11> <http://acme.test/prop/direct/P8> "2015-03-03T00:00:00Z"^^<http://www.w3.org/2001/XMLSchema#dateTime> .',
				)
			),
			'time-year' => array( // NOTE: may changed to use xsd:gYear
				new PropertyId( 'P8' ),
				new TimeValue( '+2015-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_YEAR, RdfVocabulary::GREGORIAN_CALENDAR ),
				array(
					'<http://acme.test/Q11> <http://acme.test/prop/direct/P8> "2015-01-01T00:00:00Z"^^<http://www.w3.org/2001/XMLSchema#dateTime> .',
				)
			),
			'time-margin' => array(
				new PropertyId( 'P8' ),
				new TimeValue( '+2015-03-03T00:00:00Z', 0, 3, 3, TimeValue::PRECISION_DAY, RdfVocabulary::GREGORIAN_CALENDAR ),
				array(
					'<http://acme.test/Q11> <http://acme.test/prop/direct/P8> "2015-03-03T00:00:00Z"^^<http://www.w3.org/2001/XMLSchema#dateTime> .',
				)
			),
			'time-bce' => array( // NOTE: This assumes that internal data and the RDF data use the
				                 //       same notion of negative years. If one uses traditional
				                 //       numbering (-44 means 44 BCE, XSD 1.0) and the other
				                 //       astronomical numbering (-44 means 43 BCE, XSD 1.1),
				                 //       conversion would apply.
				new PropertyId( 'P8' ),
				new TimeValue( '-0044-03-15T00:00:00Z', 0, 3, 3, TimeValue::PRECISION_DAY, RdfVocabulary::GREGORIAN_CALENDAR ),
				array(
					'<http://acme.test/Q11> <http://acme.test/prop/direct/P8> "-0044-03-15T00:00:00Z"^^<http://www.w3.org/2001/XMLSchema#dateTime> .',
				)
			),
			'time-julian' => array( // NOTE: Currently, giving a calendar other than gregorian
				                    //       causes the timestamp to be output as an untyped literal.
				                    //       If the calendar and interpretation of the timestamp
				                    //       are known, they could be converted to (proleptic)
				                    //       gregorian an output as an XSD date.
				new PropertyId( 'P8' ),
				new TimeValue( '+1492-10-12T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_DAY, RdfVocabulary::JULIAN_CALENDAR ),
				array(
					'<http://acme.test/Q11> <http://acme.test/prop/direct/P8> "1492-10-21T00:00:00Z"^^<http://www.w3.org/2001/XMLSchema#dateTime> .',
				)
			),
			'url' => array(
				new PropertyId( 'P9' ),
				new StringValue( 'http://quux.test/xyzzy' ),
				array(
					'<http://acme.test/Q11> <http://acme.test/prop/direct/P9> <http://quux.test/xyzzy> .',
				)
			),
			'url-mailto' => array(
				new PropertyId( 'P9' ),
				new StringValue( 'mailto:xyzzy@quux.test' ),
				array(
					'<http://acme.test/Q11> <http://acme.test/prop/direct/P9> <mailto:xyzzy@quux.test> .',
				)
			),
		);
	}

	/**
	 * @dataProvider provideAddSnakValue
	 */
	public function testAddSnakValue( PropertyId $propertyId, DataValue $value, $expectedTriples ) {
		$writer = $this->getTestData()->getNTriplesWriter();

		$writer->about( RdfVocabulary::NS_ENTITY, 'Q11' );

		$builder = $this->newBuilder();
		$builder->addSnakValue( $writer, $propertyId, $value, RdfVocabulary::NSP_DIRECT_CLAIM );

		$this->assertTriplesEqual( $expectedTriples, $writer );
	}

	public function testAddSnakValue_mention() {
		$propertyId = new PropertyId( 'P2' );
		$value = new EntityIdValue( new ItemId( 'Q42' ) );

		$writer = $this->getTestData()->getNTriplesWriter();

		$writer->about( RdfVocabulary::NS_ENTITY, 'Q11' );

		$mentioned = array();
		$builder = $this->newBuilder( $mentioned );
		$builder->addSnakValue( $writer, $propertyId, $value, RdfVocabulary::NSP_DIRECT_CLAIM );

		$this->assertEquals( array( 'Q42' ), array_keys( $mentioned ) );
	}

}

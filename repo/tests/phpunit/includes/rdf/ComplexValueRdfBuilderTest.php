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
use Wikibase\Rdf\DedupeBag;
use Wikibase\Rdf\HashDedupeBag;
use Wikibase\Rdf\NullDedupeBag;
use Wikimedia\Purtle\RdfWriter;
use Wikibase\Rdf\Test\RdfBuilderTestData;
use Wikibase\Rdf\RdfVocabulary;
use Wikibase\Rdf\ComplexValueRdfBuilder;

/**
 * @covers Wikibase\ComplexValueRdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseRdf
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class ComplexValueRdfBuilderTest extends \PHPUnit_Framework_TestCase {

	private $testData;

	/**
	 * Initialize repository data
	 *
	 * @return RdfBuilderTestData
	 */
	private function getTestData()
	{
		if( empty( $this->testData ) ) {
			$this->testData = new RdfBuilderTestData(
				__DIR__ . "/../../data/rdf",
				__DIR__ . "/../../data/rdf/ComplexValueRdfBuilder"
			);
		}
		return $this->testData;
	}

	/**
	 * @param EntityId[] &$mentioned receives the IDs of any mentioned entities.
	 * @param string[] $valuesSeen A list of value hashes that should be considered "already seen".
	 *
	 * @return ComplexValueRdfBuilder
	 */
	private function newBuilder( array &$mentioned = array(), DedupeBag $bag = null ) {
		$mentionTracker = $this->getMock( 'Wikibase\Rdf\EntityMentionListener' );
		$mentionTracker->expects( $this->any() )
			->method( 'entityReferenceMentioned' )
			->will( $this->returnCallback( function( EntityId $id ) use ( &$mentioned ) {
				$key = $id->getSerialization();
				$mentioned[$key] = $id;
			} ) );

		$vocabulary = $this->getTestData()->getVocabulary();
		$valueWriter = $this->getTestData()->getNTriplesWriter();

		$builder = new ComplexValueRdfBuilder( $vocabulary, $valueWriter, $this->getTestData()->getMockRepository() );
		$builder->setEntityMentionListener( $mentionTracker );
		$builder->setDedupeBag( $bag ?: new NullDedupeBag() );

		// HACK: glue on the value writer as a public field, so we can evaluate it later.
		$builder->test_value_writer = $valueWriter;

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

		if ( $ntriples === '' ) {
			return array();
		}

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
					'<http://acme.test/Q11> <http://acme.test/prop/statement/P2> <http://acme.test/Q42> .',
				),
				array(),
			),
			'commonsMedia' => array(
				new PropertyId( 'P3' ),
				new StringValue( 'Test.jpg' ),
				array(
					'<http://acme.test/Q11> <http://acme.test/prop/statement/P3> <http://commons.wikimedia.org/wiki/Special:FilePath/Test.jpg> .',
				),
				array(),
			),
			'globecoordinate' => array(
				new PropertyId( 'P4' ),
				new GlobeCoordinateValue(
					new LatLongValue( 12.25, -45.5 ),
					0.025,
					'https://www.wikidata.org/entity/Q2'
				),
				array(
					'<http://acme.test/Q11> <http://acme.test/prop/statement/P4> "Point(12.25 -45.5)"^^<http://www.opengis.net/ont/geosparql#wktLiteral> .',
					'<http://acme.test/Q11> <http://acme.test/prop/statement/value/P4> <http://acme.test/value/7901049a90a3b6a6cbbae50dc76c2da9> .',
				),
				array(
					0 => '<http://acme.test/value/7901049a90a3b6a6cbbae50dc76c2da9> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.wikidata.org/ontology-beta#Value> .',
					1 => '<http://acme.test/value/7901049a90a3b6a6cbbae50dc76c2da9> <http://www.wikidata.org/ontology-beta#geoGlobe> <https://www.wikidata.org/entity/Q2> .',
					2 => '<http://acme.test/value/7901049a90a3b6a6cbbae50dc76c2da9> <http://www.wikidata.org/ontology-beta#geoLatitude> "12.25"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					3 => '<http://acme.test/value/7901049a90a3b6a6cbbae50dc76c2da9> <http://www.wikidata.org/ontology-beta#geoLongitude> "-45.5"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					4 => '<http://acme.test/value/7901049a90a3b6a6cbbae50dc76c2da9> <http://www.wikidata.org/ontology-beta#geoPrecision> "0.025"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
				),
			),
			'monolingualtext' => array(
				new PropertyId( 'P5' ),
				new MonolingualTextValue( 'ru', 'Берлин' ),
				array(
					'<http://acme.test/Q11> <http://acme.test/prop/statement/P5> "\u0411\u0435\u0440\u043B\u0438\u043D"@ru .',
				),
				array(),
			),
			'quantity' => array(
				new PropertyId( 'P6' ),
				new QuantityValue(
					new DecimalValue( '+0.00011' ),
					'1',
					new DecimalValue( '+0.00013' ),
					new DecimalValue( '+0.00010' ) ),
				array(
					'<http://acme.test/Q11> <http://acme.test/prop/statement/P6> "+0.00011"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					'<http://acme.test/Q11> <http://acme.test/prop/statement/value/P6> <http://acme.test/value/ea39bdf723a70acd2e22d07dd0db7721> .',
				),
				array(
					0 => '<http://acme.test/value/ea39bdf723a70acd2e22d07dd0db7721> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.wikidata.org/ontology-beta#Value> .',
					1 => '<http://acme.test/value/ea39bdf723a70acd2e22d07dd0db7721> <http://www.wikidata.org/ontology-beta#quantityAmount> "+0.00011"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					2 => '<http://acme.test/value/ea39bdf723a70acd2e22d07dd0db7721> <http://www.wikidata.org/ontology-beta#quantityLowerBound> "+0.00010"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					3 => '<http://acme.test/value/ea39bdf723a70acd2e22d07dd0db7721> <http://www.wikidata.org/ontology-beta#quantityUnit> "1" .',
					4 => '<http://acme.test/value/ea39bdf723a70acd2e22d07dd0db7721> <http://www.wikidata.org/ontology-beta#quantityUpperBound> "+0.00013"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
				),
			),
			'quantity-unit' => array(
				new PropertyId( 'P6' ),
				new QuantityValue(
					new DecimalValue( '-2.3' ),
					'https://www.wikidata.org/entity/Q11573',
					new DecimalValue( '-2.3' ),
					new DecimalValue( '-2.3' ) ),
				array(
					'<http://acme.test/Q11> <http://acme.test/prop/statement/P6> "-2.3"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					'<http://acme.test/Q11> <http://acme.test/prop/statement/value/P6> <http://acme.test/value/9744b3301e3a9b3b5a31f6c6ba46dae0> .',
				),
				array(
					0 => '<http://acme.test/value/9744b3301e3a9b3b5a31f6c6ba46dae0> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.wikidata.org/ontology-beta#Value> .',
					1 => '<http://acme.test/value/9744b3301e3a9b3b5a31f6c6ba46dae0> <http://www.wikidata.org/ontology-beta#quantityAmount> "-2.3"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					2 => '<http://acme.test/value/9744b3301e3a9b3b5a31f6c6ba46dae0> <http://www.wikidata.org/ontology-beta#quantityLowerBound> "-2.3"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					3 => '<http://acme.test/value/9744b3301e3a9b3b5a31f6c6ba46dae0> <http://www.wikidata.org/ontology-beta#quantityUnit> "https://www.wikidata.org/entity/Q11573" .',
					4 => '<http://acme.test/value/9744b3301e3a9b3b5a31f6c6ba46dae0> <http://www.wikidata.org/ontology-beta#quantityUpperBound> "-2.3"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
				),
			),
			'string' => array(
				new PropertyId( 'P7' ),
				new StringValue( 'Kittens' ),
				array(
					'<http://acme.test/Q11> <http://acme.test/prop/statement/P7> "Kittens" .',
				),
				array(),
			),
			'time' => array(
				new PropertyId( 'P8' ),
				new TimeValue( '+2015-03-03T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_DAY, RdfVocabulary::GREGORIAN_CALENDAR ),
				array(
					'<http://acme.test/Q11> <http://acme.test/prop/statement/P8> "2015-03-03T00:00:00Z"^^<http://www.w3.org/2001/XMLSchema#dateTime> .',
					'<http://acme.test/Q11> <http://acme.test/prop/statement/value/P8> <http://acme.test/value/7a453935e4288ff180c20a7304bab948> .'
				),
				array(
					0 => '<http://acme.test/value/7a453935e4288ff180c20a7304bab948> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.wikidata.org/ontology-beta#Value> .',
					1 => '<http://acme.test/value/7a453935e4288ff180c20a7304bab948> <http://www.wikidata.org/ontology-beta#timeCalendarModel> <http://www.wikidata.org/entity/Q1985727> .',
					2 => '<http://acme.test/value/7a453935e4288ff180c20a7304bab948> <http://www.wikidata.org/ontology-beta#timePrecision> "11"^^<http://www.w3.org/2001/XMLSchema#integer> .',
					3 => '<http://acme.test/value/7a453935e4288ff180c20a7304bab948> <http://www.wikidata.org/ontology-beta#timeValue> "2015-03-03T00:00:00Z"^^<http://www.w3.org/2001/XMLSchema#dateTime> .',
					4 => '<http://acme.test/value/7a453935e4288ff180c20a7304bab948> <http://www.wikidata.org/ontology-beta#timeTimezone> "0"^^<http://www.w3.org/2001/XMLSchema#integer> .',
				),
			),
			'time-year' => array( // NOTE: may changed to use xsd:gYear
				new PropertyId( 'P8' ),
				new TimeValue( '+2015-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_YEAR, RdfVocabulary::GREGORIAN_CALENDAR ),
				array(
					'<http://acme.test/Q11> <http://acme.test/prop/statement/P8> "2015-01-01T00:00:00Z"^^<http://www.w3.org/2001/XMLSchema#dateTime> .',
					'<http://acme.test/Q11> <http://acme.test/prop/statement/value/P8> <http://acme.test/value/418aedfba643e02a5ba758952f8f7765> .',
				),
				array(
					0 => '<http://acme.test/value/418aedfba643e02a5ba758952f8f7765> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.wikidata.org/ontology-beta#Value> .',
					1 => '<http://acme.test/value/418aedfba643e02a5ba758952f8f7765> <http://www.wikidata.org/ontology-beta#timeCalendarModel> <http://www.wikidata.org/entity/Q1985727> .',
					2 => '<http://acme.test/value/418aedfba643e02a5ba758952f8f7765> <http://www.wikidata.org/ontology-beta#timePrecision> "9"^^<http://www.w3.org/2001/XMLSchema#integer> .',
					3 => '<http://acme.test/value/418aedfba643e02a5ba758952f8f7765> <http://www.wikidata.org/ontology-beta#timeValue> "2015-01-01T00:00:00Z"^^<http://www.w3.org/2001/XMLSchema#dateTime> .',
					4 => '<http://acme.test/value/418aedfba643e02a5ba758952f8f7765> <http://www.wikidata.org/ontology-beta#timeTimezone> "0"^^<http://www.w3.org/2001/XMLSchema#integer> .',
				),
			),
			'time-margin' => array(
				new PropertyId( 'P8' ),
				new TimeValue( '+2015-03-03T00:00:00Z', 0, 3, 3, TimeValue::PRECISION_DAY, RdfVocabulary::GREGORIAN_CALENDAR ),
				array(
					'<http://acme.test/Q11> <http://acme.test/prop/statement/P8> "2015-03-03T00:00:00Z"^^<http://www.w3.org/2001/XMLSchema#dateTime> .',
					'<http://acme.test/Q11> <http://acme.test/prop/statement/value/P8> <http://acme.test/value/8977346cbe7d0a6624ebd06fe27d749f> .',
				),
				array(
					0 => '<http://acme.test/value/8977346cbe7d0a6624ebd06fe27d749f> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.wikidata.org/ontology-beta#Value> .',
					1 => '<http://acme.test/value/8977346cbe7d0a6624ebd06fe27d749f> <http://www.wikidata.org/ontology-beta#timeCalendarModel> <http://www.wikidata.org/entity/Q1985727> .',
					2 => '<http://acme.test/value/8977346cbe7d0a6624ebd06fe27d749f> <http://www.wikidata.org/ontology-beta#timePrecision> "11"^^<http://www.w3.org/2001/XMLSchema#integer> .',
					3 => '<http://acme.test/value/8977346cbe7d0a6624ebd06fe27d749f> <http://www.wikidata.org/ontology-beta#timeValue> "2015-03-03T00:00:00Z"^^<http://www.w3.org/2001/XMLSchema#dateTime> .',
					4 => '<http://acme.test/value/8977346cbe7d0a6624ebd06fe27d749f> <http://www.wikidata.org/ontology-beta#timeTimezone> "0"^^<http://www.w3.org/2001/XMLSchema#integer> .',
				),
			),
			'time-bce' => array( // NOTE: This assumes that internal data and the RDF data use the
				                 //       same notion of negative years. If one uses traditional
				                 //       numbering (-44 means 44 BCE, XSD 1.0) and the other
				                 //       astronomical numbering (-44 means 43 BCE, XSD 1.1),
				                 //       conversion would apply.
				new PropertyId( 'P8' ),
				new TimeValue( '-0044-03-15T00:00:00Z', 0, 3, 3, TimeValue::PRECISION_DAY, RdfVocabulary::GREGORIAN_CALENDAR ),
				array(
					'<http://acme.test/Q11> <http://acme.test/prop/statement/P8> "-0044-03-15T00:00:00Z"^^<http://www.w3.org/2001/XMLSchema#dateTime> .',
					'<http://acme.test/Q11> <http://acme.test/prop/statement/value/P8> <http://acme.test/value/ef167a47c30f27b0c70e210b27257d50> .',
				),
				array(
					0 => '<http://acme.test/value/ef167a47c30f27b0c70e210b27257d50> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.wikidata.org/ontology-beta#Value> .',
					1 => '<http://acme.test/value/ef167a47c30f27b0c70e210b27257d50> <http://www.wikidata.org/ontology-beta#timeCalendarModel> <http://www.wikidata.org/entity/Q1985727> .',
					2 => '<http://acme.test/value/ef167a47c30f27b0c70e210b27257d50> <http://www.wikidata.org/ontology-beta#timePrecision> "11"^^<http://www.w3.org/2001/XMLSchema#integer> .',
					3 => '<http://acme.test/value/ef167a47c30f27b0c70e210b27257d50> <http://www.wikidata.org/ontology-beta#timeValue> "-0044-03-15T00:00:00Z"^^<http://www.w3.org/2001/XMLSchema#dateTime> .',
					4 => '<http://acme.test/value/ef167a47c30f27b0c70e210b27257d50> <http://www.wikidata.org/ontology-beta#timeTimezone> "0"^^<http://www.w3.org/2001/XMLSchema#integer> .',
				),
			),
			'time-julian' => array( // NOTE: Currently, giving a calendar other than gregorian
				                    //       causes the timestamp to be output as an untyped literal.
				                    //       If the calendar and interpretation of the timestamp
				                    //       are known, they could be converted to (proleptic)
				                    //       gregorian an output as an XSD date.
				new PropertyId( 'P8' ),
				new TimeValue( '+1492-10-12T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_DAY, RdfVocabulary::JULIAN_CALENDAR ),
				array (
					// Julian-to-Gregorian conversion applies.
					'<http://acme.test/Q11> <http://acme.test/prop/statement/P8> "1492-10-21T00:00:00Z"^^<http://www.w3.org/2001/XMLSchema#dateTime> .',
					'<http://acme.test/Q11> <http://acme.test/prop/statement/value/P8> <http://acme.test/value/23a636870974bab8f1771b34aa994936> .',
				),
				array(
					0 => '<http://acme.test/value/23a636870974bab8f1771b34aa994936> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.wikidata.org/ontology-beta#Value> .',
					1 => '<http://acme.test/value/23a636870974bab8f1771b34aa994936> <http://www.wikidata.org/ontology-beta#timeCalendarModel> <http://www.wikidata.org/entity/Q1985786> .',
					2 => '<http://acme.test/value/23a636870974bab8f1771b34aa994936> <http://www.wikidata.org/ontology-beta#timePrecision> "11"^^<http://www.w3.org/2001/XMLSchema#integer> .',
					3 => '<http://acme.test/value/23a636870974bab8f1771b34aa994936> <http://www.wikidata.org/ontology-beta#timeValue> "1492-10-21T00:00:00Z"^^<http://www.w3.org/2001/XMLSchema#dateTime> .',
					4 => '<http://acme.test/value/23a636870974bab8f1771b34aa994936> <http://www.wikidata.org/ontology-beta#timeTimezone> "0"^^<http://www.w3.org/2001/XMLSchema#integer> .',
				),
			),
			'url' => array(
				new PropertyId( 'P9' ),
				new StringValue( 'http://quux.test/xyzzy' ),
				array(
					'<http://acme.test/Q11> <http://acme.test/prop/statement/P9> <http://quux.test/xyzzy> .',
				),
				array(),
			),
			'url-mailto' => array(
				new PropertyId( 'P9' ),
				new StringValue( 'mailto:xyzzy@quux.test' ),
				array(
					'<http://acme.test/Q11> <http://acme.test/prop/statement/P9> <mailto:xyzzy@quux.test> .',
				),
				array(),
			),
		);
	}

	/**
	 * @dataProvider provideAddSnakValue
	 */
	public function testAddSnakValue( PropertyId $propertyId, DataValue $value, $expectedTriples, $expectedValueTriples ) {
		$writer = $this->getTestData()->getNTriplesWriter();
		$writer->about( RdfVocabulary::NS_ENTITY, 'Q11' );

		$builder = $this->newBuilder();
		$builder->addSnakValue( $writer, $propertyId, $value, RdfVocabulary::NSP_CLAIM_STATEMENT );

		$this->assertTriplesEqual( $expectedTriples, $writer );

		// HACK: $builder->test_value_writer is glued on by newBuilder()
		$this->assertTriplesEqual( $expectedValueTriples, $builder->test_value_writer );
	}

	public function testAddSnakValue_mention() {
		$propertyId = new PropertyId( 'P2' );
		$value = new EntityIdValue( new ItemId( 'Q42' ) );

		$writer = $this->getTestData()->getNTriplesWriter();
		$writer->about( RdfVocabulary::NS_ENTITY, 'Q11' );

		$mentioned = array();
		$builder = $this->newBuilder( $mentioned );
		$builder->addSnakValue( $writer, $propertyId, $value, RdfVocabulary::NSP_CLAIM_STATEMENT );

		$this->assertEquals( array( 'Q42' ), array_keys( $mentioned ) );
	}

	public function testAddSnakValue_seen() {
		$propertyId = new PropertyId( 'P8' );
		$value = new TimeValue( '+2015-03-03T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_DAY, RdfVocabulary::GREGORIAN_CALENDAR );

		$writer = $this->getTestData()->getNTriplesWriter();
		$writer->about( RdfVocabulary::NS_ENTITY, 'Q11' );

		$mentioned = array();

		$seen = new HashDedupeBag();
		$seen->alreadySeen( $value->getHash(), 'V' );

		$builder = $this->newBuilder( $mentioned, $seen );
		$builder->addSnakValue( $writer, $propertyId, $value, RdfVocabulary::NSP_CLAIM_STATEMENT );

		// since the value was already "seen", the value writer should be empty.
		$this->assertTriplesEqual( array(), $builder->test_value_writer );
	}

}

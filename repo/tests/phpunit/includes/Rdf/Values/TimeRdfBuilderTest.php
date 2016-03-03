<?php

namespace Wikibase\Test\Rdf;

use DataValues\TimeValue;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Rdf\HashDedupeBag;
use Wikibase\Rdf\JulianDateTimeValueCleaner;
use Wikibase\Rdf\RdfVocabulary;
use Wikibase\Rdf\Values\ComplexValueRdfHelper;
use Wikibase\Rdf\Values\TimeRdfBuilder;
use Wikibase\Repo\Tests\Rdf\NTriplesRdfTestHelper;
use Wikimedia\Purtle\NTriplesRdfWriter;
use Wikibase\DataModel\Snak\PropertyValueSnak;

/**
 * @covers Wikibase\Rdf\Values\TimeRdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseRdf
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class TimeRdfBuilderTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var NTriplesRdfTestHelper
	 */
	private $helper;

	protected function setUp() {
		parent::setUp();

		$this->helper = new NTriplesRdfTestHelper();
	}

	public function provideAddValue() {
		$value = new TimeValue( '+2015-11-11T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_DAY, TimeValue::CALENDAR_GREGORIAN );
		$snak = new PropertyValueSnak( new PropertyId( 'P7' ), $value );

		$valueJulian = new TimeValue( '+1345-11-11T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_DAY, TimeValue::CALENDAR_JULIAN );
		$snakJulian = new PropertyValueSnak( new PropertyId( 'P7' ), $valueJulian );

		return array(
			'simple' => array(
				$snak,
				false,
				array(
					'<http://www/Q1> <http://acme/statement/P7> "2015-11-11T00:00:00Z"^^<http://www.w3.org/2001/XMLSchema#dateTime> .',
				)
			),
			'complex' => array(
				$snak,
				true,
				array(
					'<http://www/Q1> '
						. '<http://acme/statement/P7> '
						. '"2015-11-11T00:00:00Z"^^<http://www.w3.org/2001/XMLSchema#dateTime> .',
					'<http://www/Q1> '
						. '<http://acme/statement/value/P7> '
						. '<http://acme/value/6a84c07a464062e0f3df0cd1884dbdfd> .',
					'<http://acme/value/6a84c07a464062e0f3df0cd1884dbdfd> '
						. '<http://www.w3.org/1999/02/22-rdf-syntax-ns#type> '
						. '<http://acme/onto/TimeValue> .',
					'<http://acme/value/6a84c07a464062e0f3df0cd1884dbdfd> '
						. '<http://acme/onto/timeValue> '
						. '"2015-11-11T00:00:00Z"^^<http://www.w3.org/2001/XMLSchema#dateTime> .',
					'<http://acme/value/6a84c07a464062e0f3df0cd1884dbdfd> '
						. '<http://acme/onto/timePrecision> '
						. '"11"^^<http://www.w3.org/2001/XMLSchema#integer> .',
					'<http://acme/value/6a84c07a464062e0f3df0cd1884dbdfd> '
						. '<http://acme/onto/timeTimezone> '
						. '"0"^^<http://www.w3.org/2001/XMLSchema#integer> .',
					'<http://acme/value/6a84c07a464062e0f3df0cd1884dbdfd> '
						. '<http://acme/onto/timeCalendarModel> '
						. '<http://www.wikidata.org/entity/Q1985727> .',
				)
			),
			'simple Julian' => array(
				$snakJulian,
				false,
				array(
					'<http://www/Q1> <http://acme/statement/P7> "1345-11-19T00:00:00Z"^^<http://www.w3.org/2001/XMLSchema#dateTime> .',
				)
			),
			'complex Julian' => array(
				$snakJulian,
				true,
				array(
					'<http://www/Q1> '
						. '<http://acme/statement/P7> '
						. '"1345-11-19T00:00:00Z"^^<http://www.w3.org/2001/XMLSchema#dateTime> .',
					'<http://www/Q1> '
						. '<http://acme/statement/value/P7> '
						. '<http://acme/value/b05c66f54b0960e1cb712466b7c192b4> .',
					'<http://acme/value/b05c66f54b0960e1cb712466b7c192b4> '
						. '<http://www.w3.org/1999/02/22-rdf-syntax-ns#type> '
						. '<http://acme/onto/TimeValue> .',
					'<http://acme/value/b05c66f54b0960e1cb712466b7c192b4> '
						. '<http://acme/onto/timeValue> '
						. '"1345-11-19T00:00:00Z"^^<http://www.w3.org/2001/XMLSchema#dateTime> .',
					'<http://acme/value/b05c66f54b0960e1cb712466b7c192b4> '
						. '<http://acme/onto/timePrecision> '
						. '"11"^^<http://www.w3.org/2001/XMLSchema#integer> .',
					'<http://acme/value/b05c66f54b0960e1cb712466b7c192b4> '
						. '<http://acme/onto/timeTimezone> '
						. '"0"^^<http://www.w3.org/2001/XMLSchema#integer> .',
					'<http://acme/value/b05c66f54b0960e1cb712466b7c192b4> '
						. '<http://acme/onto/timeCalendarModel> '
						. '<http://www.wikidata.org/entity/Q1985786> .',
				)
			),
		);
	}

	/**
	 * @dataProvider provideAddValue
	 */
	public function testAddValue( PropertyValueSnak $snak, $complex, array $expected ) {
		$vocab = new RdfVocabulary( 'http://acme.com/item/', 'http://acme.com/data/' );

		$snakWriter = new NTriplesRdfWriter();
		$snakWriter->prefix( 'www', "http://www/" );
		$snakWriter->prefix( 'acme', "http://acme/" );
		$snakWriter->prefix( RdfVocabulary::NSP_CLAIM_VALUE, "http://acme/statement/value/" );
		$snakWriter->prefix( RdfVocabulary::NSP_CLAIM_STATEMENT, "http://acme/statement/" );
		$snakWriter->prefix( RdfVocabulary::NS_VALUE, "http://acme/value/" );
		$snakWriter->prefix( RdfVocabulary::NS_ONTOLOGY, "http://acme/onto/" );

		if ( $complex ) {
			$valueWriter = $snakWriter->sub();
			$helper = new ComplexValueRdfHelper( $vocab, $valueWriter, new HashDedupeBag() );
		} else {
			$helper = null;
		}

		$dateCleaner = new JulianDateTimeValueCleaner();
		$builder = new TimeRdfBuilder( $dateCleaner, $helper );

		$snakWriter->start();
		$snakWriter->about( 'www', 'Q1' );

		$builder->addValue(
			$snakWriter,
			RdfVocabulary::NSP_CLAIM_STATEMENT,
			$vocab->getEntityLName( $snak->getPropertyid() ),
			'DUMMY',
			$snak
		);

		$this->helper->assertNTriplesEquals( $expected, $snakWriter->drain() );
	}

}

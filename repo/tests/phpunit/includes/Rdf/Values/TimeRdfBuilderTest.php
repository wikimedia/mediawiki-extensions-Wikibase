<?php

namespace Wikibase\Repo\Tests\Rdf\Values;

use DataValues\TimeValue;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\SubEntityTypesMapper;
use Wikibase\Repo\Rdf\HashDedupeBag;
use Wikibase\Repo\Rdf\JulianDateTimeValueCleaner;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikibase\Repo\Rdf\Values\ComplexValueRdfHelper;
use Wikibase\Repo\Rdf\Values\TimeRdfBuilder;
use Wikibase\Repo\Tests\Rdf\NTriplesRdfTestHelper;
use Wikimedia\Purtle\NTriplesRdfWriter;

/**
 * @covers \Wikibase\Repo\Rdf\Values\TimeRdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRdf
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class TimeRdfBuilderTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var NTriplesRdfTestHelper
	 */
	private $helper;

	protected function setUp(): void {
		parent::setUp();

		$this->helper = new NTriplesRdfTestHelper();
	}

	public function provideAddValue() {
		$greg = TimeValue::CALENDAR_GREGORIAN;
		$jul = TimeValue::CALENDAR_JULIAN;
		$day = TimeValue::PRECISION_DAY;

		$propertyId = new NumericPropertyId( 'P7' );
		$value = new TimeValue( '+2015-11-11T00:00:00Z', 0, 0, 0, $day, $greg );
		$snak = new PropertyValueSnak( $propertyId, $value );

		$valueJulian = new TimeValue( '+1345-11-11T00:00:00Z', 0, 0, 0, $day, $jul );
		$snakJulian = new PropertyValueSnak( $propertyId, $valueJulian );

		$valueJulianExtreme = new TimeValue( '-4714-01-02T00:00:00Z', 0, 0, 0, $day, $jul );
		$snakJulianExtreme = new PropertyValueSnak( $propertyId, $valueJulianExtreme );

		return [
			'simple' => [
				$snak,
				false,
				[
					'<http://www/Q1> '
						. '<http://acme/statement/P7> '
						. '"2015-11-11T00:00:00Z"^^<http://www.w3.org/2001/XMLSchema#dateTime> .',
				],
			],
			'complex' => [
				$snak,
				true,
				[
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
				],
			],
			'simple Julian' => [
				$snakJulian,
				false,
				[
					'<http://www/Q1> '
						. '<http://acme/statement/P7> '
						. '"1345-11-19T00:00:00Z"^^<http://www.w3.org/2001/XMLSchema#dateTime> .',
				],
			],
			'complex Julian' => [
				$snakJulian,
				true,
				[
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
				],
			],
			'simple Julian out of supported conversion range' => [
				$snakJulianExtreme,
				false,
				[
					'<http://www/Q1> '
						. '<http://acme/statement/P7> '
						. '"-4713-01-02T00:00:00Z"^^<http://www.w3.org/2001/XMLSchema#dateTime> .',
				],
			],
			'complex Julian out of supported conversion range' => [
				$snakJulianExtreme,
				true,
				[
					'<http://www/Q1> '
						. '<http://acme/statement/P7> '
						. '"-4713-01-02T00:00:00Z"^^<http://www.w3.org/2001/XMLSchema#dateTime> .',
					'<http://www/Q1> '
						. '<http://acme/statement/value/P7> '
						. '<http://acme/value/c375568301bcaae670fefc22d4adce4b> .',
					'<http://acme/value/c375568301bcaae670fefc22d4adce4b> '
						. '<http://www.w3.org/1999/02/22-rdf-syntax-ns#type> '
						. '<http://acme/onto/TimeValue> .',
					'<http://acme/value/c375568301bcaae670fefc22d4adce4b> '
						. '<http://acme/onto/timeValue> '
						. '"-4713-01-02T00:00:00Z"^^<http://www.w3.org/2001/XMLSchema#dateTime> .',
					'<http://acme/value/c375568301bcaae670fefc22d4adce4b> '
						. '<http://acme/onto/timePrecision> '
						. '"11"^^<http://www.w3.org/2001/XMLSchema#integer> .',
					'<http://acme/value/c375568301bcaae670fefc22d4adce4b> '
						. '<http://acme/onto/timeTimezone> '
						. '"0"^^<http://www.w3.org/2001/XMLSchema#integer> .',
					'<http://acme/value/c375568301bcaae670fefc22d4adce4b> '
						. '<http://acme/onto/timeCalendarModel> '
						. '<http://www.wikidata.org/entity/Q1985786> .',
				],
			],
		];
	}

	/**
	 * @dataProvider provideAddValue
	 */
	public function testAddValue( PropertyValueSnak $snak, $complex, array $expected ) {
		$vocab = new RdfVocabulary(
			[ '' => 'http://acme.com/item/' ],
			[ '' => 'http://acme.com/data/' ],
			new EntitySourceDefinitions( [], new SubEntityTypesMapper( [] ) ),
			[ '' => '' ],
			[ '' => '' ]
		);

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
			$vocab->getEntityLName( $snak->getPropertyId() ),
			'DUMMY',
			RdfVocabulary::NS_VALUE,
			$snak
		);

		$this->helper->assertNTriplesEquals( $expected, $snakWriter->drain() );
	}

}

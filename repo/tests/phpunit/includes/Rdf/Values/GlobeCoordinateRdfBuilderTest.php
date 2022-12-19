<?php

namespace Wikibase\Repo\Tests\Rdf\Values;

use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\Geo\Values\LatLongValue;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\SubEntityTypesMapper;
use Wikibase\Repo\Rdf\HashDedupeBag;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikibase\Repo\Rdf\Values\ComplexValueRdfHelper;
use Wikibase\Repo\Rdf\Values\GlobeCoordinateRdfBuilder;
use Wikibase\Repo\Tests\Rdf\NTriplesRdfTestHelper;
use Wikimedia\Purtle\NTriplesRdfWriter;

/**
 * @covers \Wikibase\Repo\Rdf\Values\GlobeCoordinateRdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRdf
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class GlobeCoordinateRdfBuilderTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var NTriplesRdfTestHelper
	 */
	private $helper;

	protected function setUp(): void {
		parent::setUp();

		$this->helper = new NTriplesRdfTestHelper();
	}

	public function provideAddValue() {
		$value = new GlobeCoordinateValue(
			new LatLongValue( 12.25, -45.5 ),
			0.025,
			'http://www.wikidata.org/entity/Q2'
		);

		$snak = new PropertyValueSnak( new NumericPropertyId( 'P7' ), $value );

		// Mare Tranquillitatis
		$value_moon = new GlobeCoordinateValue(
				new LatLongValue( 8.5, 31.4 ),
				0.1,
				'http://www.wikidata.org/entity/Q405'
		);

		$snak_moon = new PropertyValueSnak( new NumericPropertyId( 'P7' ), $value_moon );

		$data = [
			'simple' => [
				$snak,
				false,
				[
					'<http://www/Q1> '
						. '<http://acme/statement/P7> '
						. '"Point(-45.5 12.25)"^^<http://acme/geo/wktLiteral> .',
				],
			],
			'complex' => [
				$snak,
				true,
				[
					'<http://www/Q1> '
						. '<http://acme/statement/P7> '
						. '"Point(-45.5 12.25)"^^<http://acme/geo/wktLiteral> .',
					'<http://www/Q1> '
						. '<http://acme/statement/value/P7> '
						. '<http://acme/value/2a2da1b4852295168b0bad7e5881cfe6> .',
					'<http://acme/value/2a2da1b4852295168b0bad7e5881cfe6> '
						. '<http://www.w3.org/1999/02/22-rdf-syntax-ns#type> '
						. '<http://acme/onto/GlobecoordinateValue> .',
					'<http://acme/value/2a2da1b4852295168b0bad7e5881cfe6> '
						. '<http://acme/onto/geoLatitude> '
						. '"12.25"^^<http://www.w3.org/2001/XMLSchema#double> .',
					'<http://acme/value/2a2da1b4852295168b0bad7e5881cfe6> '
						. '<http://acme/onto/geoLongitude> '
						. '"-45.5"^^<http://www.w3.org/2001/XMLSchema#double> .',
					'<http://acme/value/2a2da1b4852295168b0bad7e5881cfe6> '
						. '<http://acme/onto/geoPrecision> '
						. '"0.025"^^<http://www.w3.org/2001/XMLSchema#double> .',
					'<http://acme/value/2a2da1b4852295168b0bad7e5881cfe6> '
						. '<http://acme/onto/geoGlobe> '
						. '<http://www.wikidata.org/entity/Q2> .',
				],
			],
			'moon' => [
				$snak_moon,
				false,
				[
					'<http://www/Q1> '
						. '<http://acme/statement/P7> '
						. '"<http://www.wikidata.org/entity/Q405> Point(31.4 8.5)"'
						. '^^<http://acme/geo/wktLiteral> .',
				],
			],
		];

		$value2 = new GlobeCoordinateValue(
			new LatLongValue( 12.25, -45.5 ),
			null,
			'http://www.wikidata.org/entity/Q2'
		);

		$snak2 = new PropertyValueSnak( new NumericPropertyId( 'P7' ), $value2 );

		$data["complex2"] = [
				$snak2,
				true,
				[
					'<http://www/Q1> '
						. '<http://acme/statement/P7> '
						. '"Point(-45.5 12.25)"^^<http://acme/geo/wktLiteral> .',
					'<http://www/Q1> '
						. '<http://acme/statement/value/P7> '
						. '<http://acme/value/da01b99e43c90736626d3d5dd9d71bcf> .',
					'<http://acme/value/da01b99e43c90736626d3d5dd9d71bcf> '
						. '<http://www.w3.org/1999/02/22-rdf-syntax-ns#type> '
						. '<http://acme/onto/GlobecoordinateValue> .',
					'<http://acme/value/da01b99e43c90736626d3d5dd9d71bcf> '
						. '<http://acme/onto/geoLatitude> '
						. '"12.25"^^<http://www.w3.org/2001/XMLSchema#double> .',
					'<http://acme/value/da01b99e43c90736626d3d5dd9d71bcf> '
						. '<http://acme/onto/geoLongitude> '
						. '"-45.5"^^<http://www.w3.org/2001/XMLSchema#double> .',
					'<http://acme/value/da01b99e43c90736626d3d5dd9d71bcf> '
						. '<http://www.w3.org/1999/02/22-rdf-syntax-ns#type> '
						. '<http://acme/onto/GeoAutoPrecision> .',
					'<http://acme/value/da01b99e43c90736626d3d5dd9d71bcf> '
						. '<http://acme/onto/geoPrecision> '
						. '"0.00027777777777778"^^<http://www.w3.org/2001/XMLSchema#double> .',
					'<http://acme/value/da01b99e43c90736626d3d5dd9d71bcf> '
						. '<http://acme/onto/geoGlobe> '
						. '<http://www.wikidata.org/entity/Q2> .',
				],
		];

		return $data;
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
		$snakWriter->prefix( RdfVocabulary::NS_GEO, "http://acme/geo/" );

		if ( $complex ) {
			$valueWriter = $snakWriter->sub();
			$helper = new ComplexValueRdfHelper( $vocab, $valueWriter, new HashDedupeBag() );
		} else {
			$helper = null;
		}

		$builder = new GlobeCoordinateRdfBuilder( $helper );

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

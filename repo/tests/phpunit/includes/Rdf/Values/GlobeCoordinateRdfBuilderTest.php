<?php

namespace Wikibase\Test\Rdf;

use DataValues\Geo\Values\LatLongValue;
use DataValues\Geo\Values\GlobeCoordinateValue;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Rdf\HashDedupeBag;
use Wikibase\Rdf\RdfVocabulary;
use Wikibase\Rdf\Values\ComplexValueRdfHelper;
use Wikibase\Rdf\Values\GlobeCoordinateRdfBuilder;
use Wikibase\Repo\Tests\Rdf\NTriplesRdfTestHelper;
use Wikimedia\Purtle\NTriplesRdfWriter;
use Wikibase\DataModel\Snak\PropertyValueSnak;

/**
 * @covers Wikibase\Rdf\Values\GlobeCoordinateRdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseRdf
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class GlobeCoordinateRdfBuilderTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var NTriplesRdfTestHelper
	 */
	private $helper;

	protected function setUp() {
		parent::setUp();

		$this->helper = new NTriplesRdfTestHelper();
	}

	public function provideAddValue() {
		$value = new GlobeCoordinateValue(
			new LatLongValue( 12.25, -45.5 ),
			0.025,
			'http://www.wikidata.org/entity/Q2'
		);

		$snak = new PropertyValueSnak( new PropertyId( 'P7' ), $value );

		// Mare Tranquillitatis
		$value_moon = new GlobeCoordinateValue(
				new LatLongValue( 8.5, 31.4 ),
				0.1,
				'http://www.wikidata.org/entity/Q405'
		);

		$snak_moon = new PropertyValueSnak( new PropertyId( 'P7' ), $value_moon );

		$data = array(
			'simple' => array(
				$snak,
				false,
				array(
					'<http://www/Q1> <http://acme/statement/P7> "Point(-45.5 12.25)"^^<http://acme/geo/wktLiteral> .',
				)
			),
			'complex' => array(
				$snak,
				true,
				array(
					'<http://www/Q1> '
						. '<http://acme/statement/P7> '
						. '"Point(-45.5 12.25)"^^<http://acme/geo/wktLiteral> .',
					'<http://www/Q1> '
						. '<http://acme/statement/value/P7> '
						. '<http://acme/value/d396dfb27235918ab6969509c5e87a48> .',
					'<http://acme/value/d396dfb27235918ab6969509c5e87a48> '
						. '<http://www.w3.org/1999/02/22-rdf-syntax-ns#type> '
						. '<http://acme/onto/GlobecoordinateValue> .',
					'<http://acme/value/d396dfb27235918ab6969509c5e87a48> '
						. '<http://acme/onto/geoLatitude> '
						. '"12.25"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					'<http://acme/value/d396dfb27235918ab6969509c5e87a48> '
						. '<http://acme/onto/geoLongitude> '
						. '"-45.5"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					'<http://acme/value/d396dfb27235918ab6969509c5e87a48> '
						. '<http://acme/onto/geoPrecision> '
						. '"0.025"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					'<http://acme/value/d396dfb27235918ab6969509c5e87a48> '
						. '<http://acme/onto/geoGlobe> '
						. '<http://www.wikidata.org/entity/Q2> .',
				)
			),
			'moon' => array(
				$snak_moon,
				false,
				array(
					'<http://www/Q1> <http://acme/statement/P7> "<http://www.wikidata.org/entity/Q405> Point(31.4 8.5)"^^<http://acme/geo/wktLiteral> .',
				)
			),
		);

		$value2 = new GlobeCoordinateValue(
			new LatLongValue( 12.25, -45.5 ),
			null,
			'http://www.wikidata.org/entity/Q2'
		);

		$snak2 = new PropertyValueSnak( new PropertyId( 'P7' ), $value2 );

		$data["complex2"] = array(
				$snak2,
				true,
				array(
					'<http://www/Q1> '
						. '<http://acme/statement/P7> '
						. '"Point(-45.5 12.25)"^^<http://acme/geo/wktLiteral> .',
					'<http://www/Q1> '
						. '<http://acme/statement/value/P7> '
						. '<http://acme/value/79451c61ee7a21407115df912637c022> .',
					'<http://acme/value/79451c61ee7a21407115df912637c022> '
						. '<http://www.w3.org/1999/02/22-rdf-syntax-ns#type> '
						. '<http://acme/onto/GlobecoordinateValue> .',
					'<http://acme/value/79451c61ee7a21407115df912637c022> '
						. '<http://acme/onto/geoLatitude> '
						. '"12.25"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					'<http://acme/value/79451c61ee7a21407115df912637c022> '
						. '<http://acme/onto/geoLongitude> '
						. '"-45.5"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					'<http://acme/value/79451c61ee7a21407115df912637c022> '
						. '<http://www.w3.org/1999/02/22-rdf-syntax-ns#type> '
						. '<http://acme/onto/GeoAutoPrecision> .',
					'<http://acme/value/79451c61ee7a21407115df912637c022> '
						. '<http://acme/onto/geoPrecision> '
						. '"0.00027777777777778"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					'<http://acme/value/79451c61ee7a21407115df912637c022> '
						. '<http://acme/onto/geoGlobe> '
						. '<http://www.wikidata.org/entity/Q2> .',
				)
		);

		return $data;
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
			$vocab->getEntityLName( $snak->getPropertyid() ),
			'DUMMY',
			$snak
		);

		$this->helper->assertNTriplesEquals( $expected, $snakWriter->drain() );
	}

}

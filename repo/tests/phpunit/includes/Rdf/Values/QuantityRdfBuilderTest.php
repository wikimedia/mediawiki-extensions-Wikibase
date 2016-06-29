<?php

namespace Wikibase\Test\Rdf;

use DataValues\QuantityValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\UnitConverter;
use Wikibase\Lib\UnitStorage;
use Wikibase\Rdf\HashDedupeBag;
use Wikibase\Rdf\RdfVocabulary;
use Wikibase\Rdf\Values\ComplexValueRdfHelper;
use Wikibase\Rdf\Values\QuantityRdfBuilder;
use Wikibase\Repo\Tests\Rdf\NTriplesRdfTestHelper;
use Wikimedia\Purtle\NTriplesRdfWriter;
use Wikibase\DataModel\Snak\PropertyValueSnak;

/**
 * @covers Wikibase\Rdf\Values\QuantityRdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseRdf
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class QuantityRdfBuilderTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var NTriplesRdfTestHelper
	 */
	private $helper;

	protected function setUp() {
		parent::setUp();

		$this->helper = new NTriplesRdfTestHelper();
	}

	public function provideAddValue() {
		$value = QuantityValue::newFromNumber( '+23.5', '1', '+23.6', '+23.4' );
		$snak = new PropertyValueSnak( new PropertyId( 'P7' ), $value );

		$unitId = new ItemId( 'Q2' );
		$value = QuantityValue::newFromNumber( '+23.5', $unitId->getSerialization(), '+23.6', '+23.4' );
		$snak2 = new PropertyValueSnak( new PropertyId( 'P7' ), $value );

		return array(
			'simple' => array(
				$snak,
				false,
				array(
					'<http://www/Q1> <http://acme/statement/P7> "+23.5"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
				)
			),
			'complex' => array(
				$snak,
				true,
				array(
					'<http://www/Q1> '
						. '<http://acme/statement/P7> '
						. '"+23.5"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					'<http://www/Q1> '
						. '<http://acme/statement/value/P7> '
						. '<http://acme/value/1ac4bb05a87bfd5fde2740bbb6941533> .',
					'<http://acme/value/1ac4bb05a87bfd5fde2740bbb6941533> '
						. '<http://www.w3.org/1999/02/22-rdf-syntax-ns#type> '
						. '<http://acme/onto/QuantityValue> .',
					'<http://acme/value/1ac4bb05a87bfd5fde2740bbb6941533> '
						. '<http://acme/onto/quantityAmount> '
						. '"+23.5"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					'<http://acme/value/1ac4bb05a87bfd5fde2740bbb6941533> '
						. '<http://acme/onto/quantityUpperBound> '
						. '"+23.6"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					'<http://acme/value/1ac4bb05a87bfd5fde2740bbb6941533> '
						. '<http://acme/onto/quantityLowerBound> '
						. '"+23.4"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					'<http://acme/value/1ac4bb05a87bfd5fde2740bbb6941533> '
						. '<http://acme/onto/quantityUnit> '
						. '<http://www.wikidata.org/entity/Q199> .',
				)
			),
			'units' => array(
				$snak2,
				true,
				array(
					'<http://www/Q1> '
					. '<http://acme/statement/P7> '
					. '"+23.5"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					'<http://www/Q1> '
					. '<http://acme/statement/value/P7> '
					. '<http://acme/value/85551225e0c03ec0caa13d267a78c189> .',
					'<http://acme/value/85551225e0c03ec0caa13d267a78c189> '
					. '<http://www.w3.org/1999/02/22-rdf-syntax-ns#type> '
					. '<http://acme/onto/QuantityValue> .',
					'<http://acme/value/85551225e0c03ec0caa13d267a78c189> '
					. '<http://acme/onto/quantityAmount> '
					. '"+23.5"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					'<http://acme/value/85551225e0c03ec0caa13d267a78c189> '
					. '<http://acme/onto/quantityUpperBound> '
					. '"+23.6"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					'<http://acme/value/85551225e0c03ec0caa13d267a78c189> '
					. '<http://acme/onto/quantityLowerBound> '
					. '"+23.4"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					'<http://acme/value/85551225e0c03ec0caa13d267a78c189> '
					. '<http://acme/onto/quantityUnit> '
					. '<Q2> .',
					'<http://acme/value/85551225e0c03ec0caa13d267a78c189> '
					. '<http://acme/onto/quantityNormalized> '
					. '<http://acme/value/85551225e0c03ec0caa13d267a78c189> .',
				)
			),
			'units_convert' => array(
				$snak2,
				true,
				array(
					'<http://www/Q1> '
					. '<http://acme/statement/P7> '
					. '"+23.5"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					'<http://www/Q1> '
					. '<http://acme/statement/value/P7> '
					. '<http://acme/value/85551225e0c03ec0caa13d267a78c189> .',
					'<http://acme/value/85551225e0c03ec0caa13d267a78c189> '
					. '<http://www.w3.org/1999/02/22-rdf-syntax-ns#type> '
					. '<http://acme/onto/QuantityValue> .',
					'<http://acme/value/85551225e0c03ec0caa13d267a78c189> '
					. '<http://acme/onto/quantityAmount> '
					. '"+23.5"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					'<http://acme/value/85551225e0c03ec0caa13d267a78c189> '
					. '<http://acme/onto/quantityUpperBound> '
					. '"+23.6"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					'<http://acme/value/85551225e0c03ec0caa13d267a78c189> '
					. '<http://acme/onto/quantityLowerBound> '
					. '"+23.4"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					'<http://acme/value/85551225e0c03ec0caa13d267a78c189> '
					. '<http://acme/onto/quantityUnit> '
					. '<Q2> .',
					'<http://acme/value/85551225e0c03ec0caa13d267a78c189> '
					. '<http://acme/onto/quantityNormalized> '
					. '<http://acme/value/e80660d9a958a139230804dacf35a6ea> .',
					'<http://acme/value/e80660d9a958a139230804dacf35a6ea> '
					. '<http://www.w3.org/1999/02/22-rdf-syntax-ns#type> '
					. '<http://acme/onto/QuantityValue> .',
					'<http://acme/value/e80660d9a958a139230804dacf35a6ea> '
					. '<http://acme/onto/quantityAmount> '
					. '"+2930"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					'<http://acme/value/e80660d9a958a139230804dacf35a6ea> '
					. '<http://acme/onto/quantityUpperBound> '
					. '"+2940"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					'<http://acme/value/e80660d9a958a139230804dacf35a6ea> '
					. '<http://acme/onto/quantityLowerBound> '
					. '"+2920"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					'<http://acme/value/e80660d9a958a139230804dacf35a6ea> '
					. '<http://acme/onto/quantityUnit> <http://acme/Q1> .',
					'<http://acme/value/e80660d9a958a139230804dacf35a6ea> '
					. '<http://acme/onto/quantityNormalized> '
					. '<http://acme/value/e80660d9a958a139230804dacf35a6ea> .',

				),
				array( 'factor' => '124.7', 'unit' => 'Q1' )
			),
		);
	}

	private function getConverter( $result ) {
		$mockStorage = $this->getMock( UnitStorage::class );
		$mockStorage->method( 'getConversion' )->willReturn( $result );
		return new UnitConverter( $mockStorage, 'http://acme/' );
	}


	/**
	 * @dataProvider provideAddValue
	 */
	public function testAddValue( PropertyValueSnak $snak, $complex, array $expected, array $units = null) {
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

		$builder = new QuantityRdfBuilder( $helper, $this->getConverter($units) );

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

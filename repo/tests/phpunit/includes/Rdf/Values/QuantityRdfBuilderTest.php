<?php

namespace Wikibase\Repo\Tests\Rdf\Values;

use DataValues\QuantityValue;
use DataValues\UnboundedQuantityValue;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\SubEntityTypesMapper;
use Wikibase\Lib\Units\UnitConverter;
use Wikibase\Lib\Units\UnitStorage;
use Wikibase\Repo\Rdf\HashDedupeBag;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikibase\Repo\Rdf\Values\ComplexValueRdfHelper;
use Wikibase\Repo\Rdf\Values\QuantityRdfBuilder;
use Wikibase\Repo\Tests\Rdf\NTriplesRdfTestHelper;
use Wikimedia\Purtle\NTriplesRdfWriter;
use Wikimedia\Purtle\RdfWriter;

/**
 * @covers \Wikibase\Repo\Rdf\Values\QuantityRdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRdf
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class QuantityRdfBuilderTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var NTriplesRdfTestHelper
	 */
	private $helper;

	protected function setUp(): void {
		parent::setUp();

		$this->helper = new NTriplesRdfTestHelper();
	}

	public function provideAddValue() {
		$unboundedValue = UnboundedQuantityValue::newFromNumber( '+23.5', '1' );
		$unboundedSnak = new PropertyValueSnak( new NumericPropertyId( 'P7' ), $unboundedValue );
		$value = QuantityValue::newFromNumber( '+23.5', '1', '+23.6', '+23.4' );
		$snak = new PropertyValueSnak( new NumericPropertyId( 'P7' ), $value );

		$unitId = new ItemId( 'Q2' );
		$value = QuantityValue::newFromNumber( '+23.5', 'http://acme/' . $unitId->getSerialization(), '+23.6', '+23.4' );
		$snak2 = new PropertyValueSnak( new NumericPropertyId( 'P7' ), $value );

		return [
			'simple unbounded' => [
				$unboundedSnak,
				false,
				[
					'<http://www/Q1>'
					. ' <http://acme/statement/P7> '
					. '"+23.5"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
				],
			],
			'simple' => [
				$snak,
				false,
				[
					'<http://www/Q1> '
					. '<http://acme/statement/P7> '
					. '"+23.5"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
				],
			],
			'complex unbounded' => [
				$unboundedSnak,
				true,
				[
					'<http://www/Q1> '
					. '<http://acme/statement/P7> '
					. '"+23.5"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					'<http://www/Q1> '
					. '<http://acme/statement/value/P7> '
					. '<http://acme/value/d0488ea37befd2940d39a1dbf47eebc0> .',
					'<http://acme/value/d0488ea37befd2940d39a1dbf47eebc0> '
					. '<http://www.w3.org/1999/02/22-rdf-syntax-ns#type> '
					. '<http://acme/onto/QuantityValue> .',
					'<http://acme/value/d0488ea37befd2940d39a1dbf47eebc0> '
					. '<http://acme/onto/quantityAmount> '
					. '"+23.5"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					'<http://acme/value/d0488ea37befd2940d39a1dbf47eebc0> '
					. '<http://acme/onto/quantityUnit> '
					. '<http://www.wikidata.org/entity/Q199> .',
				],
			],
			'complex' => [
				$snak,
				true,
				[
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
				],
			],
			'units' => [
				$snak2,
				true,
				[
					'<http://www/Q1> '
					. '<http://acme/statement/P7> '
					. '"+23.5"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					'<http://www/Q1> '
					. '<http://acme/statement/value/P7> '
					. '<http://acme/value/d56fea2e7acc4c42069d87f695cab5b9> .',
					'<http://acme/value/d56fea2e7acc4c42069d87f695cab5b9> '
					. '<http://www.w3.org/1999/02/22-rdf-syntax-ns#type> '
					. '<http://acme/onto/QuantityValue> .',
					'<http://acme/value/d56fea2e7acc4c42069d87f695cab5b9> '
					. '<http://acme/onto/quantityAmount> '
					. '"+23.5"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					'<http://acme/value/d56fea2e7acc4c42069d87f695cab5b9> '
					. '<http://acme/onto/quantityUpperBound> '
					. '"+23.6"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					'<http://acme/value/d56fea2e7acc4c42069d87f695cab5b9> '
					. '<http://acme/onto/quantityLowerBound> '
					. '"+23.4"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					'<http://acme/value/d56fea2e7acc4c42069d87f695cab5b9> '
					. '<http://acme/onto/quantityUnit> '
					. '<http://acme/Q2> .',
				],
			],
			'units_primary' => [
				$snak2,
				true,
				[
					'<http://www/Q1> '
					. '<http://acme/statement/P7> '
					. '"+23.5"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					'<http://www/Q1> '
					. '<http://acme/statement/value/P7> '
					. '<http://acme/value/d56fea2e7acc4c42069d87f695cab5b9> .',
					'<http://www/Q1> '
					. '<http://acme/statement/value-norm/P7> '
					. '<http://acme/value/d56fea2e7acc4c42069d87f695cab5b9> .',
					'<http://acme/value/d56fea2e7acc4c42069d87f695cab5b9> '
					. '<http://www.w3.org/1999/02/22-rdf-syntax-ns#type> '
					. '<http://acme/onto/QuantityValue> .',
					'<http://acme/value/d56fea2e7acc4c42069d87f695cab5b9> '
					. '<http://acme/onto/quantityAmount> '
					. '"+23.5"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					'<http://acme/value/d56fea2e7acc4c42069d87f695cab5b9> '
					. '<http://acme/onto/quantityUpperBound> '
					. '"+23.6"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					'<http://acme/value/d56fea2e7acc4c42069d87f695cab5b9> '
					. '<http://acme/onto/quantityLowerBound> '
					. '"+23.4"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					'<http://acme/value/d56fea2e7acc4c42069d87f695cab5b9> '
					. '<http://acme/onto/quantityUnit> '
					. '<http://acme/Q2> .',
					'<http://acme/value/d56fea2e7acc4c42069d87f695cab5b9> '
					. '<http://acme/onto/quantityNormalized> '
					. '<http://acme/value/d56fea2e7acc4c42069d87f695cab5b9> .',
				],
				[ 'factor' => '1', 'unit' => 'Q2' ],
			],
			'units_convert' => [
				$snak2,
				true,
				[
					'<http://www/Q1> '
					. '<http://acme/statement/P7> '
					. '"+23.5"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					'<http://www/Q1> '
					. '<http://acme/statement/value/P7> '
					. '<http://acme/value/d56fea2e7acc4c42069d87f695cab5b9> .',
					'<http://www/Q1> '
					. '<http://acme/statement/value-norm/P7> '
					. '<http://acme/value/e80660d9a958a139230804dacf35a6ea> .',
					'<http://acme/value/d56fea2e7acc4c42069d87f695cab5b9> '
					. '<http://www.w3.org/1999/02/22-rdf-syntax-ns#type> '
					. '<http://acme/onto/QuantityValue> .',
					'<http://acme/value/d56fea2e7acc4c42069d87f695cab5b9> '
					. '<http://acme/onto/quantityAmount> '
					. '"+23.5"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					'<http://acme/value/d56fea2e7acc4c42069d87f695cab5b9> '
					. '<http://acme/onto/quantityUpperBound> '
					. '"+23.6"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					'<http://acme/value/d56fea2e7acc4c42069d87f695cab5b9> '
					. '<http://acme/onto/quantityLowerBound> '
					. '"+23.4"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					'<http://acme/value/d56fea2e7acc4c42069d87f695cab5b9> '
					. '<http://acme/onto/quantityUnit> '
					. '<http://acme/Q2> .',
					'<http://acme/value/d56fea2e7acc4c42069d87f695cab5b9> '
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

				],
				[ 'factor' => '124.7', 'unit' => 'Q1' ],
			],
		];
	}

	private function getConverter( $result ) {
		$mockStorage = $this->createMock( UnitStorage::class );
		$mockStorage->method( 'getConversion' )->willReturn( $result );
		return new UnitConverter( $mockStorage, 'http://acme/' );
	}

	private function newSnakWriter() {
		$snakWriter = new NTriplesRdfWriter();
		$snakWriter->prefix( 'www', "http://www/" );
		$snakWriter->prefix( 'acme', "http://acme/" );
		$snakWriter->prefix( RdfVocabulary::NSP_CLAIM_VALUE, "http://acme/statement/value/" );
		$snakWriter->prefix( RdfVocabulary::NSP_CLAIM_VALUE_NORM, "http://acme/statement/value-norm/" );
		$snakWriter->prefix( RdfVocabulary::NSP_CLAIM_STATEMENT, "http://acme/statement/" );
		$snakWriter->prefix( RdfVocabulary::NS_VALUE, "http://acme/value/" );
		$snakWriter->prefix( RdfVocabulary::NS_ONTOLOGY, "http://acme/onto/" );

		return $snakWriter;
	}

	private function newQuantityRdfBuilder(
		RdfWriter $valueWriter,
		RdfVocabulary $vocab,
		$complex,
		$units
	) {
		if ( $complex ) {
			$helper = new ComplexValueRdfHelper( $vocab, $valueWriter, new HashDedupeBag() );
		} else {
			$helper = null;
		}

		$builder = new QuantityRdfBuilder( $helper, $this->getConverter( $units ) );
		return $builder;
	}

	/**
	 * @dataProvider provideAddValue
	 */
	public function testAddValue(
		PropertyValueSnak $snak,
		$complex,
		array $expected,
		array $units = null
	) {
		$vocab = new RdfVocabulary(
			[ '' => 'http://acme.com/item/' ],
			[ '' => 'http://acme.com/data/' ],
			new EntitySourceDefinitions( [], new SubEntityTypesMapper( [] ) ),
			[ '' => '' ],
			[ '' => '' ]
		);

		$snakWriter = $this->newSnakWriter();
		$builder = $this->newQuantityRdfBuilder( $snakWriter->sub(), $vocab, $complex, $units );

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

		$result = $snakWriter->drain();
		$this->helper->assertNTriplesEquals( $expected, $result );
	}

	public function testWriteQuantityValue() {
		$unitId = new ItemId( 'Q2' );
		$value = QuantityValue::newFromNumber( '+23.5', 'http://acme/' . $unitId->getSerialization(), '+23.6', '+23.4' );

		$vocab = new RdfVocabulary(
			[ '' => 'http://acme.com/item/' ],
			[ '' => 'http://acme.com/data/' ],
			new EntitySourceDefinitions( [], new SubEntityTypesMapper( [] ) ),
			[ '' => '' ],
			[ '' => '' ]
		);
		$snakWriter = $this->newSnakWriter();
		$valueWriter = $snakWriter->sub();
		$builder = $this->newQuantityRdfBuilder( $valueWriter, $vocab, true, null );

		$expected = [
			'<http://acme/value/d56fea2e7acc4c42069d87f695cab5b9> '
			. '<http://www.w3.org/1999/02/22-rdf-syntax-ns#type> '
			. '<http://acme/onto/QuantityValue> .',
			'<http://acme/value/d56fea2e7acc4c42069d87f695cab5b9> '
			. '<http://acme/onto/quantityAmount> '
			. '"+23.5"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
			'<http://acme/value/d56fea2e7acc4c42069d87f695cab5b9> '
			. '<http://acme/onto/quantityUpperBound> '
			. '"+23.6"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
			'<http://acme/value/d56fea2e7acc4c42069d87f695cab5b9> '
			. '<http://acme/onto/quantityLowerBound> '
			. '"+23.4"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
			'<http://acme/value/d56fea2e7acc4c42069d87f695cab5b9> '
			. '<http://acme/onto/quantityUnit> '
			. '<http://acme/Q2> .',
		];

		/** @var QuantityValue $value */
		$valueLName = $value->getHash();
		$valueWriter->about( RdfVocabulary::NS_VALUE, $valueLName )
			->a( RdfVocabulary::NS_ONTOLOGY, $vocab->getValueTypeName( $value ) );

		$builder->writeQuantityValue( $value );

		$result = $snakWriter->drain();
		$this->helper->assertNTriplesEquals( $expected, $result );
	}

	/**
	 * @see http://phabricator.wikimedia.org/T150877
	 */
	public function testWriteTwice() {
		$unboundedValue = UnboundedQuantityValue::newFromNumber( '-79.1', 'Q2' );
		$unboundedSnak = new PropertyValueSnak( new NumericPropertyId( 'P7' ), $unboundedValue );
		$unboundedSnak2 = new PropertyValueSnak( new NumericPropertyId( 'P9' ), $unboundedValue );

		$vocab = new RdfVocabulary(
			[ '' => 'http://acme.com/item/' ],
			[ '' => 'http://acme.com/data/' ],
			new EntitySourceDefinitions( [], new SubEntityTypesMapper( [] ) ),
			[ '' => '' ],
			[ '' => '' ]
		);
		$snakWriter = $this->newSnakWriter();
		$builder = $this->newQuantityRdfBuilder( $snakWriter->sub(), $vocab, true,
			[ 'factor' => 1, 'unit' => 'Q2' ] );

		$snakWriter->start();
		$snakWriter->about( 'www', 'Q1' );

		$builder->addValue(
			$snakWriter,
			RdfVocabulary::NSP_CLAIM_STATEMENT,
			$vocab->getEntityLName( $unboundedSnak->getPropertyId() ),
			'DUMMY',
			RdfVocabulary::NS_VALUE,
			$unboundedSnak
		);
		// And once more
		$builder->addValue(
			$snakWriter,
			RdfVocabulary::NSP_CLAIM_STATEMENT,
			$vocab->getEntityLName( $unboundedSnak2->getPropertyId() ),
			'DUMMY',
			RdfVocabulary::NS_VALUE,
			$unboundedSnak2
		);

		$expected = [
			'<http://www/Q1> ' . '<http://acme/statement/P7> ' .
			'"-79.1"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
			'<http://www/Q1> ' . '<http://acme/statement/value/P7> ' .
			'<http://acme/value/526c2826a6dfd29d460ea348b5d124a6> .',
			'<http://www/Q1> ' . '<http://acme/statement/value-norm/P7> ' .
			'<http://acme/value/526c2826a6dfd29d460ea348b5d124a6> .',
			'<http://www/Q1> ' . '<http://acme/statement/P9> ' .
			'"-79.1"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
			'<http://www/Q1> ' . '<http://acme/statement/value/P9> ' .
			'<http://acme/value/526c2826a6dfd29d460ea348b5d124a6> .',
			'<http://www/Q1> ' . '<http://acme/statement/value-norm/P9> ' .
			'<http://acme/value/526c2826a6dfd29d460ea348b5d124a6> .',
			'<http://acme/value/526c2826a6dfd29d460ea348b5d124a6> ' .
			'<http://www.w3.org/1999/02/22-rdf-syntax-ns#type> ' .
			'<http://acme/onto/QuantityValue> .',
			'<http://acme/value/526c2826a6dfd29d460ea348b5d124a6> ' .
			'<http://acme/onto/quantityAmount> ' .
			'"-79.1"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
			'<http://acme/value/526c2826a6dfd29d460ea348b5d124a6> ' .
			'<http://acme/onto/quantityUnit> ' . '<Q2> .',
			'<http://acme/value/526c2826a6dfd29d460ea348b5d124a6> ' .
			'<http://acme/onto/quantityNormalized> ' .
			'<http://acme/value/526c2826a6dfd29d460ea348b5d124a6> .',
		];

		$result = $snakWriter->drain();
		$this->helper->assertNTriplesEquals( $expected, $result );
	}

}

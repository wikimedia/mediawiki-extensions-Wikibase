<?php

namespace Wikibase\Repo\Tests\Search\Elastic\Fields;

use CirrusSearch;
use DataValues\BooleanValue;
use DataValues\StringValue;
use DataValues\UnboundedQuantityValue;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Repo\Search\Elastic\Fields\StatementsField;
use Wikibase\Repo\Tests\ChangeOp\StatementListProviderDummy;
use Wikibase\Repo\Tests\Rdf\RdfBuilderTestData;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\Search\Elastic\Fields\StatementsField
 *
 * @group WikibaseElastic
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Stas Malyshev
 */
class StatementsFieldTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	/**
	 * List of properties we handle.
	 * @var string[]
	 */
	private $properties = [ 'P1', 'P2', 'P4', 'P7', 'P8' ];

	public function statementsProvider() {
		$testData = new RdfBuilderTestData(
			__DIR__ . '/../../../../data/rdf/entities', ''
		);

		return [
			'empty' => [
				$testData->getEntity( 'Q1' ),
				[]
			],
			'Q4' => [
				$testData->getEntity( 'Q4' ),
				[ 'P2=Q42', 'P2=Q666', 'P7=simplestring',
				  'P9=http://url.acme.test\badurl?chars=\привет< >"'
				]
			],
			'Q6' => [
				$testData->getEntity( 'Q6' ),
				[
					'P7=string',
					'P7=string[P2=Q42]',
					'P7=string[P2=Q666]',
					'P7=string[P3=Universe.svg]',
					'P7=string[P6=20]',
					'P7=string[P7=simplestring]',
					'P7=string[P9=http://url.acme.test/]',
					"P7=string[P9= http://url.acme2.test/\n]",
					'P7=string[foreign:P11=simplestring]',
					'P7=string[foreign:P12=foreign:Q1234]',
				]
			],
			'Q7' => [
				$testData->getEntity( 'Q7' ),
				[ 'P7=string', 'P7=string2' ]
			],
			'Q8' => [
				$testData->getEntity( 'Q8' ),
				[]
			],
		];
	}

	/**
	 * @param string[] $map
	 * @return PropertyDataTypeLookup
	 */
	private function getPropertyTypeLookup( array $map ) {
		$lookup = $this->getMockBuilder( PropertyDataTypeLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$lookup->method( 'getDataTypeIdForProperty' )
			->willReturnCallback( function ( PropertyId $id ) use ( $map ) {
				if ( isset( $map[$id->getSerialization()] ) ) {
					return $map[$id->getSerialization()];
				}
				return 'string';
			} );

		return $lookup;
	}

	/**
	 * @dataProvider statementsProvider
	 */
	public function testStatements( EntityDocument $entity, array $expected ) {
		if ( !class_exists( CirrusSearch::class ) ) {
			$this->markTestSkipped( 'CirrusSearch needed.' );
		}

		$repo = WikibaseRepo::getDefaultInstance();
		$lookup = $this->getPropertyTypeLookup( [
			'P9' => 'sometype',
			'P11' => 'sometype',
		] );

		$field = new StatementsField( $lookup, $this->properties, [ 'sometype' ], [ 'P11' ],
				$repo->getDataTypeDefinitions()->getSearchIndexDataFormatterCallbacks() );
		$this->assertEquals( $expected, $field->getFieldData( $entity ) );
	}

	public function testFormatters() {
		$formatters = [
			'VT:string' => function ( StringValue $s ) {
				return 'STRING:' . $s->getValue();
			},
			'VT:quantity' => function ( UnboundedQuantityValue $v ) {
				return 'VALUE:' . $v->getAmount();
			},
		];
		$lookup = $this->getPropertyTypeLookup( [
			'P9' => 'sometype',
		] );
		$field = new StatementsField( $lookup, [ 'P123' ], [], [], $formatters );

		$statementList = new StatementList();
		$statementList->addNewStatement( new PropertyValueSnak( 123, new StringValue( 'testString' ) ) );
		$statementList->addNewStatement( new PropertyValueSnak( 123, UnboundedQuantityValue::newFromNumber( 456 ) ) );
		$statementList->addNewStatement( new PropertySomeValueSnak( 123 ) );
		$statementList->addNewStatement( new PropertyValueSnak( 123, new StringValue( 'testString2' ) ) );
		$statementList->addNewStatement( new PropertyNoValueSnak( 123 ) );
		$statementList->addNewStatement( new PropertyValueSnak( 123, new BooleanValue( false ) ) );

		$entity = $this->getMockBuilder( StatementListProviderDummy::class )
			->disableOriginalConstructor()
			->getMock();
		$entity->expects( $this->once() )
			->method( 'getStatements' )
			->willReturn( $statementList );

		$expected = [
			'P123=STRING:testString',
			'P123=VALUE:+456',
			'P123=STRING:testString2'
		];

		$data = $field->getFieldData( $entity );
		$this->assertEquals( $expected, $data );
	}

}

<?php

namespace Wikibase\Repo\Tests\Search\Elastic\Fields;

use CirrusSearch;
use DataValues\BooleanValue;
use DataValues\StringValue;
use DataValues\UnboundedQuantityValue;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\EntityDocument;
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
 * @license GPL-2.0+
 * @author Stas Malyshev
 */
class StatementsFieldTest extends PHPUnit_Framework_TestCase {

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
				[ 'P2=Q42', 'P2=Q666', 'P7=simplestring' ]
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
	 * @dataProvider statementsProvider
	 */
	public function testStatements( EntityDocument $entity, array $expected ) {
		if ( !class_exists( CirrusSearch::class ) ) {
			$this->markTestSkipped( 'CirrusSearch needed.' );
		}

		$repo = WikibaseRepo::getDefaultInstance();
		$field = new StatementsField( $this->properties, $repo->getDataTypeDefinitions()->getSearchIndexDataFormatterCallbacks() );
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
		$field = new StatementsField( [ 'P123' ], $formatters );

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

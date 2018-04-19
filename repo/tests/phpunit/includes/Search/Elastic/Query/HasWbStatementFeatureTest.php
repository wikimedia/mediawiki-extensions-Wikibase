<?php

namespace Wikibase\Repo\Tests\Search\Elastic\Query;

use CirrusSearch\Query\BaseSimpleKeywordFeatureTest;
use CirrusSearch\Search\SearchContext;
use Wikibase\Repo\Search\Elastic\Query\HasWbStatementFeature;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\LoadBalancer;

/**
 * @covers \Wikibase\Repo\Search\Elastic\Query\HasWbStatementFeature
 *
 * @group WikibaseElastic
 * @group Wikibase
 */
class HasWbStatementFeatureTest extends BaseSimpleKeywordFeatureTest {

	public function applyProvider() {
		return [
			'single statement entity' => [
				[ 'bool' => [
					'should' => [
						[ 'match' => [
							'statement_keywords' => [
								'query' => 'P999=Q888',
							],
						] ]
					]
				] ],
				'haswbstatement:P999=Q888'
			],
			'single statement string' => [
				[ 'bool' => [
					'should' => [
						[ 'match' => [
							'statement_keywords' => [
								'query' => 'P999=12345',
							],
						] ]
					]
				] ],
				'haswbstatement:P999=12345'
			],
			'single statement federated' => [
				[ 'bool' => [
					'should' => [
						[ 'match' => [
							'statement_keywords' => [
								'query' => 'Federated:P999=Federated:Q888',
							],
						] ]
					]
				] ],
				'haswbstatement:Federated:P999=Federated:Q888'
			],
			'multiple statements' => [
				[ 'bool' => [
					'should' => [
						[ 'match' => [
							'statement_keywords' => [
								'query' => 'P999=Q888',
							],
						] ],
						[ 'match' => [
							'statement_keywords' => [
								'query' => 'P777=someString',
							],
						] ]
					]
				] ],
				'haswbstatement:P999=Q888|P777=someString'
			],
			'throws away invalid values' => [
				null,
				'haswbstatement:one=two',
			],
		];
	}

	/**
	 * @dataProvider applyProvider
	 */
	public function testApply( array $expected = null, $term ) {
		$this->mockDB();

		$context = $this->mockContextExpectingAddFilter( $expected );
		$context->expects( $this->exactly(
				$expected === null ? 1 : 0
			) )
			->method( 'setResultsPossible' )
			->with( false );

		$feature = new HasWbStatementFeature();
		$feature->apply( $context, $term );
	}

	/**
	 * Injects a database that knows about a fake page with id of 2
	 * for use in test cases.
	 */
	private function mockDB() {
		$db = $this->getMock( IDatabase::class );
		$db->expects( $this->any() )
			->method( 'select' )
			->with( 'page' )
			->will( $this->returnCallback( function ( $table, $select, $where ) {
				if ( isset( $where['page_id'] ) && $where['page_id'] === [ '2' ] ) {
					return [ (object)[
						'page_namespace' => NS_FILE,
						'page_title' => 'Some_file.jpg',
						'page_id' => 2,
					] ];
				} else {
					return [];
				}
			} ) );
		$lb = $this->getMockBuilder( LoadBalancer::class )
			->disableOriginalConstructor()
			->getMock();
		$lb->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $db ) );
		$this->setService( 'DBLoadBalancer', $lb );
	}

	public function testInvalidStatementWarning() {
		$this->assertWarnings(
			new HasWbStatementFeature(),
			[ [ 'cirrussearch-haswbstatement-feature-no-valid-statements', 'haswbstatement' ] ],
			'haswbstatement:xyz'
		);
	}

	/**
	 * @dataProvider parseProvider
	 */
	public function testParseValue( $value, $expected, $warningExpected ) {
		$warningCollector = $this->getMockBuilder( SearchContext::class )
			->disableOriginalConstructor()
			->getMock();
		$warningCollector->expects( $warningExpected ? $this->once() : $this->never() )
			->method( 'addWarning' );

		$feature = new HasWbStatementFeature();
		$parsedValue = $feature->parseValue(
			'',
			$value,
			'',
			'|',
			'',
			$warningCollector
		);
		$this->assertEquals( [ 'statements' => $expected ], $parsedValue );
	}

	public function parseProvider() {
		return [
			'empty value' => [
				'value' => '',
				'expected' => [],
				'warningExpected' => true,
			],
			'bad value' => [
				'value' => 'xyz=12345',
				'expected' => [],
				'warningExpected' => true,
			],
			'single value Q-id' => [
				'value' => 'P999=Q888',
				'expected' => [ 'P999=Q888' ],
				'warningExpected' => false,
			],
			'single value other id' => [
				'value' => 'P999=AB123',
				'expected' => [ 'P999=AB123' ],
				'warningExpected' => false,
			],
			'single value federated' => [
				'value' => 'Wikidata:P999=Wikidata:Q888',
				'expected' => [ 'Wikidata:P999=Wikidata:Q888' ],
				'warningExpected' => false,
			],
			'multiple values' => [
				'value' => 'Wikidata:P999=Wikidata:Q888|Wikidata:P777=12345',
				'expected' => [
					'Wikidata:P999=Wikidata:Q888',
					'Wikidata:P777=12345'
				],
				'warningExpected' => false,
			],
		];
	}

}

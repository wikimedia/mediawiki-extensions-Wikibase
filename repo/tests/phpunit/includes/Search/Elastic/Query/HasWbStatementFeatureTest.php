<?php

namespace Wikibase\Repo\Tests\Search\Elastic\Query;

use CirrusSearch\Query\BaseSimpleKeywordFeatureTest;
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

	public function parseProvider() {
		return [
			'single statement entity' => [
				[ 'bool' => [
					'must' => [
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
					'must' => [
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
					'must' => [
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
					'must' => [
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
	 * @dataProvider parseProvider
	 */
	public function testParse( array $expected = null, $term ) {
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
}

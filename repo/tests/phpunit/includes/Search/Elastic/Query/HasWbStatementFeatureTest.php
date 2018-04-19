<?php

namespace Wikibase\Repo\Tests\Search\Elastic\Query;

use CirrusSearch\Query\BaseSimpleKeywordFeatureTest;
use CirrusSearch\Search\SearchContext;
use Wikibase\Repo\Search\Elastic\Query\HasWbStatementFeature;

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
				'expected' => [ 'bool' => [
					'should' => [
						[ 'match' => [
							'statement_keywords' => [
								'query' => 'P999=Q888',
							],
						] ]
					]
				] ],
				'search string' => 'haswbstatement:P999=Q888',
				'whitelist' => [ 'P999' ],
			],
			'single statement string' => [
				'expected' => [ 'bool' => [
					'should' => [
						[ 'match' => [
							'statement_keywords' => [
								'query' => 'P999=12345',
							],
						] ]
					]
				] ],
				'search string' => 'haswbstatement:P999=12345',
				'whitelist' => [ 'P999' ],
			],
			'single statement federated' => [
				'expected' => [ 'bool' => [
					'should' => [
						[ 'match' => [
							'statement_keywords' => [
								'query' => 'Federated:P999=Federated:Q888',
							],
						] ]
					]
				] ],
				'search string' => 'haswbstatement:Federated:P999=Federated:Q888',
				'whitelist' => [ 'Federated:P999' ],
			],
			'multiple statements' => [
				'expected' => [ 'bool' => [
					'should' => [
						[ 'match' => [
							'statement_keywords' => [
								'query' => 'Federated:P999=Q888',
							],
						] ],
						[ 'match' => [
							'statement_keywords' => [
								'query' => 'P777=someString',
							],
						] ]
					]
				] ],
				'search string' => 'haswbstatement:Federated:P999=Q888|P777=someString',
				'whitelist' => [ 'Federated:P999', 'P777' ],
			],
			'some data invalid' => [
				'expected' => [ 'bool' => [
					'should' => [
						[ 'match' => [
							'statement_keywords' => [
								'query' => 'P999=Q888',
							],
						] ],
					]
				] ],
				'search string' => 'haswbstatement:INVALID|P999=Q888',
				'whitelist' => [ 'P999' ],
			],
			'all data invalid' => [
				'expected' => null,
				'search string' => 'haswbstatement:INVALID',
				'whitelist' => [ 'P999' ],
			],
		];
	}

	/**
	 * @dataProvider applyProvider
	 */
	public function testApply( array $expected = null, $term, $whitelist ) {
		$context = $this->mockContextExpectingAddFilter( $expected );
		$context->expects( $this->exactly(
				$expected === null ? 1 : 0
			) )
			->method( 'setResultsPossible' )
			->with( false );

		$feature = new HasWbStatementFeature( $whitelist );
		$feature->apply( $context, $term );
	}

	public function applyNoDataProvider() {
		return [
			'empty data' => [
				null,
				'haswbstatement:',
			],
			'no data' => [
				null,
				'',
			],
		];
	}

	/**
	 * @dataProvider applyNoDataProvider
	 */
	public function testApplyNoData( array $expected = null, $term ) {
		$context = $this->mockContextExpectingAddFilter( $expected );

		$feature = new HasWbStatementFeature( [ 'P999' ] );
		$feature->apply( $context, $term );
	}

	public function testInvalidStatementWarning() {
		$this->assertWarnings(
			new HasWbStatementFeature([ 'P999' ] ),
			[ [ 'cirrussearch-haswbstatement-feature-no-valid-statements', 'haswbstatement' ] ],
			'haswbstatement:INVALID'
		);
	}

	/**
	 * @dataProvider parseProvider
	 */
	public function testParseValue( $whitelist, $value, $expected, $warningExpected ) {
		$warningCollector = $this->getMockBuilder( SearchContext::class )
			->disableOriginalConstructor()
			->getMock();
		$warningCollector->expects( $warningExpected ? $this->once() : $this->never() )
			->method( 'addWarning' );

		$feature = new HasWbStatementFeature( $whitelist );
		$parsedValue = $feature->parseValue(
			'',
			$value,
			'',
			'',
			'',
			$warningCollector
		);
		$this->assertEquals( [ 'statements' => $expected ], $parsedValue );
	}

	public function parseProvider() {
		return [
			'empty value' => [
				'whitelist' => [ 'P888', 'P999' ],
				'value' => '',
				'expected' => [],
				'warningExpected' => true,
			],
			'invalid value' => [
				'whitelist' => [ 'P888', 'P999' ],
				'value' => 'xyz=12345',
				'expected' => [],
				'warningExpected' => true,
			],
			'single value Q-id' => [
				'whitelist' => [ 'P888', 'P999' ],
				'value' => 'P999=Q888',
				'expected' => [ 'P999=Q888' ],
				'warningExpected' => false,
			],
			'single value other id' => [
				'whitelist' => [ 'P888', 'P999' ],
				'value' => 'P999=AB123',
				'expected' => [ 'P999=AB123' ],
				'warningExpected' => false,
			],
			'single value federated' => [
				'whitelist' => [ 'Wikidata:P999' ],
				'value' => 'Wikidata:P999=Wikidata:Q888',
				'expected' => [ 'Wikidata:P999=Wikidata:Q888' ],
				'warningExpected' => false,
			],
			'multiple values' => [
				'whitelist' => [ 'Wikidata:P999', 'Wikidata:P777' ],
				'value' => 'Wikidata:P999=Wikidata:Q888|Wikidata:P777=12345',
				'expected' => [
					'Wikidata:P999=Wikidata:Q888',
					'Wikidata:P777=12345',
				],
				'warningExpected' => false,
			],
			'multiple values, not all valid' => [
				'whitelist' => [ 'Wikidata:P999' ],
				'value' => 'Wikidata:P999=Wikidata:Q888|Wikidata:P777=12345',
				'expected' => [
					'Wikidata:P999=Wikidata:Q888',
				],
				'warningExpected' => false,
			],
		];
	}

}

<?php

namespace Wikibase\Repo\Tests\Search\Elastic\Query;

use CirrusSearch\InterwikiResolver;
use CirrusSearch\Query\KeywordFeature;
use CirrusSearch\Search\Escaper;
use CirrusSearch\Search\SearchContext;
use CirrusSearch\SearchConfig;
use MediaWiki\MediaWikiServices;
use Wikibase\Repo\Search\Elastic\Query\HasWbStatementFeature;

/**
 * @covers \Wikibase\Repo\Search\Elastic\Query\HasWbStatementFeature
 *
 * @group WikibaseElastic
 * @group Wikibase
 */
class HasWbStatementFeatureTest extends \MediaWikiTestCase {

	public function setUp() {
		parent::setUp();

		if ( !class_exists( \CirrusSearch::class ) ) {
			$this->markTestSkipped( 'CirrusSearch needed.' );
		} else {
			MediaWikiServices::getInstance()
				->resetServiceForTesting( InterwikiResolver::SERVICE );
		}
	}

	/**
	 * @return SearchContext
	 */
	protected function mockContext() {
		$context = $this->getMockBuilder( SearchContext::class )
			->disableOriginalConstructor()
			->getMock();
		$context->expects( $this->any() )->method( 'getConfig' )->willReturn( new SearchConfig() );
		$context->expects( $this->any() )->method( 'escaper' )->willReturn(
			new Escaper( 'en', true )
		);

		return $context;
	}

	protected function mockContextExpectingAddFilter( array $expectedQuery = null ) {
		$context = $this->mockContext();
		if ( $expectedQuery === null ) {
			$context->expects( $this->never() )
				->method( 'addFilter' );
		} else {
			$context->expects( $this->once() )
				->method( 'addFilter' )
				->with( $this->callback( function ( $query ) use ( $expectedQuery ) {
					$this->assertEquals( $expectedQuery, $query->toArray() );
					return true;
				} ) );
		}

		return $context;
	}

	protected function assertWarnings( KeywordFeature $feature, $expected, $term ) {
		$warnings = [];
		$context = $this->mockContext();
		$context->expects( $this->any() )
			->method( 'addWarning' )
			->will( $this->returnCallback( function () use ( &$warnings ) {
				$warnings[] = array_filter( func_get_args() );
			} ) );
		$feature->apply( $context, $term );
		$this->assertEquals( $expected, $warnings );
	}

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
				'foreignRepoNames' => [],
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
				'foreignRepoNames' => [],
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
				'foreignRepoNames' => [ 'Federated' ],
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
				'foreignRepoNames' => [ 'Federated' ],
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
				'foreignRepoNames' => [],
			],
			'invalid foreign repo name rejected' => [
				'expected' => [ 'bool' => [
					'should' => [
						[ 'match' => [
							'statement_keywords' => [
								'query' => 'Federated:P999=Q888',
							],
						] ],
					]
				] ],
				'search string' => 'haswbstatement:INVALID_FOREIGN_REPO:P999=P777|' .
					'Federated:P999=Q888',
				'foreignRepoNames' => [ 'Federated' ],
			],
			'all data invalid' => [
				'expected' => null,
				'search string' => 'haswbstatement:INVALID',
				'foreignRepoNames' => [],
			],
		];
	}

	/**
	 * @dataProvider applyProvider
	 */
	public function testApply( array $expected = null, $term, $foreignRepoNames ) {
		$context = $this->mockContextExpectingAddFilter( $expected );
		$context->expects( $this->exactly(
				$expected === null ? 1 : 0
			) )
			->method( 'setResultsPossible' )
			->with( false );

		$feature = new HasWbStatementFeature( $foreignRepoNames );
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
			new HasWbStatementFeature( [ 'P999' ] ),
			[ [ 'cirrussearch-haswbstatement-feature-no-valid-statements', 'haswbstatement' ] ],
			'haswbstatement:INVALID'
		);
	}

	/**
	 * @dataProvider parseProvider
	 */
	public function testParseValue( $foreignRepoNames, $value, $expected, $warningExpected ) {
		$warningCollector = $this->getMockBuilder( SearchContext::class )
			->disableOriginalConstructor()
			->getMock();
		$warningCollector->expects( $warningExpected ? $this->once() : $this->never() )
			->method( 'addWarning' );

		$feature = new HasWbStatementFeature( $foreignRepoNames );
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
				'foreignRepoNames' => [],
				'value' => '',
				'expected' => [],
				'warningExpected' => true,
			],
			'invalid value' => [
				'foreignRepoNames' => [],
				'value' => 'xyz=12345',
				'expected' => [],
				'warningExpected' => true,
			],
			'invalid federated value' => [
				'foreignRepoNames' => [ 'Wikidata' ],
				'value' => 'Wikisource:P123=12345',
				'expected' => [],
				'warningExpected' => true,
			],
			'single value Q-id' => [
				'foreignRepoNames' => [],
				'value' => 'P999=Q888',
				'expected' => [ 'P999=Q888' ],
				'warningExpected' => false,
			],
			'single value other id' => [
				'foreignRepoNames' => [],
				'value' => 'P999=AB123',
				'expected' => [ 'P999=AB123' ],
				'warningExpected' => false,
			],
			'single value federated' => [
				'foreignRepoNames' => [ 'Wikidata' ],
				'value' => 'Wikidata:P999=Wikidata:Q888',
				'expected' => [ 'Wikidata:P999=Wikidata:Q888' ],
				'warningExpected' => false,
			],
			'multiple values' => [
				'foreignRepoNames' => [ 'Wikidata', 'Wikisource' ],
				'value' => 'Wikidata:P999=Wikidata:Q888|Wikisource:P777=12345',
				'expected' => [
					'Wikidata:P999=Wikidata:Q888',
					'Wikisource:P777=12345',
				],
				'warningExpected' => false,
			],
			'multiple values, not all valid' => [
				'foreignRepoNames' => [ 'Wikidata' ],
				'value' => 'Wikidata:P999=Wikidata:Q888|Wikisource:P777=12345',
				'expected' => [
					'Wikidata:P999=Wikidata:Q888',
				],
				'warningExpected' => false,
			],
		];
	}

}

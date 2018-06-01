<?php

namespace Wikibase\Repo\Tests\Search\Elastic\Query;

use CirrusSearch\CrossSearchStrategy;
use CirrusSearch\Query\KeywordFeatureAssertions;
use Wikibase\Repo\Search\Elastic\Query\WbStatementQuantityFeature;

/**
 * @covers \Wikibase\Repo\Search\Elastic\Query\WbStatementQuantityFeature
 *
 * @group WikibaseElastic
 * @group Wikibase
 */
class WbStatementQuantityFeatureTest extends \MediaWikiTestCase {

	public function setUp() {
		parent::setUp();

		if ( !class_exists( \CirrusSearch::class ) ) {
			$this->markTestSkipped( 'CirrusSearch needed.' );
		}
	}

	public function applyProvider() {
		return [
			/*'single statement with equals' => [
				'expected' => [
					'term_freq' => [
							'term' => 'P999=Q888',
							'field' => 'statement_quantity',
							'eq' => 777,
					],
				],
				'search string' => 'wbstatementquantity:P999=Q888=777',
				'foreignRepoNames' => [],
			],
			'single statement with >' => [
				'expected' => [
					'term_freq' => [
						'term' => 'P999=Q888',
						'field' => 'statement_quantity',
						'gt' => 777,
					],
				],
				'search string' => 'wbstatementquantity:P999=Q888>777',
				'foreignRepoNames' => [],
			],
			'single statement with >=' => [
				'expected' => [
					'term_freq' => [
						'term' => 'P999=Q888',
						'field' => 'statement_quantity',
						'gte' => 777,
					],
				],
				'search string' => 'wbstatementquantity:P999=Q888>=777',
				'foreignRepoNames' => [],
			],
			'single statement with <' => [
				'expected' => [
					'term_freq' => [
						'term' => 'P111=Q222',
						'field' => 'statement_quantity',
						'lt' => 333,
					],
				],
				'search string' => 'wbstatementquantity:P111=Q222<333',
				'foreignRepoNames' => [],
			],
			'single statement with <=' => [
				'expected' => [
					'term_freq' => [
						'term' => 'P111=Q222',
						'field' => 'statement_quantity',
						'lte' => 333,
					],
				],
				'search string' => 'wbstatementquantity:P111=Q222<=333',
				'foreignRepoNames' => [],
			],
			'single statement federated' => [
				'expected' => [
					'term_freq' => [
						'term' => 'Federated:P111=Federated:Q222',
						'field' => 'statement_quantity',
						'lte' => 333,
					],
				],
				'search string' => 'wbstatementquantity:Federated:P111=Federated:Q222<=333',
				'foreignRepoNames' => [ 'Federated' ],
			],*/
			'multiple statements' => [
				'expected' => [
					'bool' => [
						'should' => [
							[ 'term_freq' => [
									'term' => 'P111=Q222',
									'field' => 'statement_quantity',
									'lte' => 333,
							] ],
							[ 'term_freq' => [
								'term' => 'P999=Q888',
								'field' => 'statement_quantity',
								'gt' => 1,
							] ],
						]
					]
				],
				'search string' => 'wbstatementquantity:P111=Q222<=333|P999=P888>1',
				'foreignRepoNames' => [],
			],
			/*'some data invalid' => [
				'expected' => [
					'term_freq' => [
						'term' => 'P999=Q888',
						'field' => 'statement_quantity',
						'gt' => 1,
					],
				],
				'search string' => 'wbstatementquantity:INVALID|P999=Q888>1',
				'foreignRepoNames' => [],
			],
			'invalid foreign repo name rejected' => [
				'expected' => [
					'term_freq' => [
						'term' => 'Federated:P999=Q888',
						'field' => 'statement_quantity',
						'eq' => 9,
					],
				],
				'search string' => 'wbstatementquantity:INVALID_FOREIGN_REPO:P999=P777<10|' .
					'Federated:P999=Q888=9',
				'foreignRepoNames' => [ 'Federated' ],
			],
			'all data invalid' => [
				'expected' => null,
				'search string' => 'wbstatementquantity:INVALID',
				'foreignRepoNames' => [],
			],*/
		];
	}

	/**
	 * @dataProvider applyProvider
	 */
	public function testApply( array $expected = null, $term, $foreignRepoNames ) {
		$feature = new WbStatementQuantityFeature( $foreignRepoNames );
		$kwAssertions = $this->getKWAssertions();
		$expectedWarnings =
			$expected !== null
			? [ [ 'cirrussearch-wbstatementquantity-feature-no-valid-statements', 'haswbstatement' ] ]
			: [];
		$kwAssertions->assertFilter( $feature, $term, $expected, $expectedWarnings );
		$kwAssertions->assertCrossSearchStrategy( $feature, $term, CrossSearchStrategy::hostWikiOnlyStrategy() );
		if ( $expected === null ) {
			$kwAssertions->assertNoResultsPossible( $feature, $term );
		}
	}

	public function applyNoDataProvider() {
		return [
			'empty data' => [
				'wbstatementquantity:',
			],
			'no data' => [
				'',
			],
		];
	}

	/**
	 * @dataProvider applyNoDataProvider
	 */
	public function testNotConsumed( $term ) {
		$feature = new WbStatementQuantityFeature( [ 'P999' ] );
		$this->getKWAssertions()->assertNotConsumed( $feature, $term );
	}

	public function testInvalidStatementWarning() {
		$feature = new WbStatementQuantityFeature( [ 'P999' ] );
		$expectedWarnings = [ [ 'cirrussearch-wbstatementquantity-feature-no-valid-statements', 'haswbstatement' ] ];
		$kwAssertions = $this->getKWAssertions();
		$kwAssertions->assertParsedValue(
			$feature,
			'wbstatementquantity:INVALID',
			[ 'statements' => [] ],
			$expectedWarnings
		);
		$kwAssertions->assertExpandedData( $feature, 'wbstatementquantity:INVALID', [], [] );
		$kwAssertions->assertFilter( $feature, 'wbstatementquantity:INVALID', null, $expectedWarnings );
		$kwAssertions->assertNoResultsPossible( $feature, 'wbstatementquantity:INVALID' );
	}

	/**
	 * @dataProvider parseProvider
	 */
	public function testParseValue( $foreignRepoNames, $value, $expected, $warningExpected ) {
		$feature = new WbStatementQuantityFeature( $foreignRepoNames );
		$expectedWarnings = $warningExpected ? [ [ 'cirrussearch-haswbstatement-feature-no-valid-statements', 'haswbstatement' ] ] : [];
		$kwAssertions = $this->getKWAssertions();
		$kwAssertions->assertParsedValue( $feature, "haswbstatement:\"$value\"", [ 'statements' => $expected ], $expectedWarnings );
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

	/**
	 * @return KeywordFeatureAssertions
	 */
	private function getKWAssertions() {
		return new KeywordFeatureAssertions( $this );
	}

}

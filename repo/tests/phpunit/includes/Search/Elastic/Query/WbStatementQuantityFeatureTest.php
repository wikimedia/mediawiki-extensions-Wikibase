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
			'single statement with equals' => [
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
			],
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
				'search string' => 'wbstatementquantity:P111=Q222<=333|P999=Q888>1',
				'foreignRepoNames' => [],
			],
			'some data invalid' => [
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
		];
	}

	/**
	 * @dataProvider applyProvider
	 */
	public function testApply( $expected, $term, $foreignRepoNames ) {
		$feature = new WbStatementQuantityFeature( $foreignRepoNames );
		$kwAssertions = $this->getKWAssertions();
		$kwAssertions->assertFilter( $feature, $term, $expected, [] );
		$kwAssertions->assertCrossSearchStrategy( $feature, $term, CrossSearchStrategy::hostWikiOnlyStrategy() );
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
		$feature = new WbStatementQuantityFeature( [ 'SOME_FOREIGN_REPO' ] );
		$this->getKWAssertions()->assertNotConsumed( $feature, $term );
	}

	public function testInvalidStatementWarning() {
		$feature = new WbStatementQuantityFeature( [ 'P999' ] );
		$expectedWarnings = [ [ 'cirrussearch-wbstatementquantity-feature-no-valid-statements', 'wbstatementquantity' ] ];
		$kwAssertions = $this->getKWAssertions();
		$kwAssertions->assertParsedValue(
			$feature,
			'wbstatementquantity:INVALID',
			[ 'statements' => [], 'operators' => [], 'numbers' => [] ],
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
		$expectedWarnings = $warningExpected ? [ [ 'cirrussearch-wbstatementquantity-feature-no-valid-statements', 'wbstatementquantity' ] ] : [];
		$kwAssertions = $this->getKWAssertions();
		$kwAssertions->assertParsedValue( $feature, "wbstatementquantity:\"$value\"", $expected, $expectedWarnings );
	}

	public function parseProvider() {
		return [
			'empty value' => [
				'foreignRepoNames' => [],
				'value' => '',
				'expected' => [
					'statements' => [],
					'operators' => [],
					'numbers' => [],
				],
				'warningExpected' => true,
			],
			'invalid property id' => [
				'foreignRepoNames' => [],
				'value' => 'xyz=test>1',
				'expected' => [
					'statements' => [],
					'operators' => [],
					'numbers' => [],
				],
				'warningExpected' => true,
			],
			'invalid operator' => [
				'foreignRepoNames' => [],
				'value' => 'P999=test|1',
				'expected' => [
					'statements' => [],
					'operators' => [],
					'numbers' => [],
				],
				'warningExpected' => true,
			],
			'invalid value' => [
				'foreignRepoNames' => [],
				'value' => 'P999=Q888>A',
				'expected' => [
					'statements' => [],
					'operators' => [],
					'numbers' => [],
				],
				'warningExpected' => true,
			],
			'invalid federated value' => [
				'foreignRepoNames' => [ 'Wikidata' ],
				'value' => 'Wikisource:P123=ABC>1',
				'expected' => [
					'statements' => [],
					'operators' => [],
					'numbers' => [],
				],
				'warningExpected' => true,
			],
			'single value equals' => [
				'foreignRepoNames' => [],
				'value' => 'P999=Q888=1',
				'expected' => [
					'statements' => [ 'P999=Q888' ],
					'operators' => [ '=' ],
					'numbers' => [ '1' ],
				],
				'warningExpected' => false,
			],
			'single value greater than' => [
				'foreignRepoNames' => [],
				'value' => 'P999=Q888>1',
				'expected' => [
					'statements' => [ 'P999=Q888' ],
					'operators' => [ '>' ],
					'numbers' => [ '1' ],
				],
				'warningExpected' => false,
			],
			'single value greater than or equals' => [
				'foreignRepoNames' => [],
				'value' => 'P999=Q888>=9',
				'expected' => [
					'statements' => [ 'P999=Q888' ],
					'operators' => [ '>=' ],
					'numbers' => [ '9' ],
				],
				'warningExpected' => false,
			],
			'single value less than' => [
				'foreignRepoNames' => [],
				'value' => 'P333=ABCD<9',
				'expected' => [
					'statements' => [ 'P333=ABCD' ],
					'operators' => [ '<' ],
					'numbers' => [ '9' ],
				],
				'warningExpected' => false,
			],
			'single value less than or equals' => [
				'foreignRepoNames' => [],
				'value' => 'P111=ABCD<=9',
				'expected' => [
					'statements' => [ 'P111=ABCD' ],
					'operators' => [ '<=' ],
					'numbers' => [ '9' ],
				],
				'warningExpected' => false,
			],
			'single value federated' => [
				'foreignRepoNames' => [ 'Wikidata' ],
				'value' => 'Wikidata:P999=Wikidata:Q888<=9',
				'expected' => [
					'statements' => [ 'Wikidata:P999=Wikidata:Q888' ],
					'operators' => [ '<=' ],
					'numbers' => [ '9' ]
				],
				'warningExpected' => false,
			],
			'multiple values' => [
				'foreignRepoNames' => [ 'Wikidata', 'Wikisource' ],
				'value' => 'Wikidata:P999=ABCD<9|Wikisource:P777=Wikisource:Q111>1',
				'expected' => [
					'statements' => [ 'Wikidata:P999=ABCD', 'Wikisource:P777=Wikisource:Q111' ],
					'operators' => [ '<', '>' ],
					'numbers' => [ '9', '1' ]
				],
				'warningExpected' => false,
			],
			'multiple values, not all valid' => [
				'foreignRepoNames' => [ 'Wikidata' ],
				'value' => 'Wikidata:P999=ABCD=5|Wikisource:P777=WXYZ>12345',
				'expected' => [
					'statements' => [ 'Wikidata:P999=ABCD' ],
					'operators' => [ '=' ],
					'numbers' => [ '5' ]
				],
				'warningExpected' => false,
			],
			'multiple values with pipe character in foreign repo name' => [
				'foreignRepoNames' => [ 'Wiki|data', 'Wiki|source' ],
				'value' => 'Wiki|data:P999=ABCD<9|Wiki|source:P777=Wiki|source:Q111>1',
				'expected' => [
					'statements' => [ 'Wiki|data:P999=ABCD', 'Wiki|source:P777=Wiki|source:Q111' ],
					'operators' => [ '<', '>' ],
					'numbers' => [ '9', '1' ]
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

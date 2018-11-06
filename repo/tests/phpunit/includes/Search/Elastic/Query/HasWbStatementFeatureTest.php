<?php

namespace Wikibase\Repo\Tests\Search\Elastic\Query;

use CirrusSearch\CrossSearchStrategy;
use CirrusSearch\Query\KeywordFeatureAssertions;
use Elastica\Query\Match;
use Elastica\Query\Prefix;
use Wikibase\Repo\Search\Elastic\Fields\StatementsField;
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
		}
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
			'property only' => [
				'expected' => [ 'bool' => [
					'should' => [
						[ 'match' => [
							'statement_keywords.property' => [
								'query' => 'P999',
							],
						] ]
					]
				] ],
				'search string' => 'haswbstatement:P999',
				'foreignRepoNames' => [],
			],
			'property and value' => [
				'expected' => [ 'bool' => [
					'should' => [
						[ 'match' => [
							'statement_keywords.property' => [
								'query' => 'P999',
							],
						] ],
						[ 'match' => [
							'statement_keywords' => [
								'query' => 'P777=someString',
							],
						] ]
					]
				] ],
				'search string' => 'haswbstatement:P999|P777=someString',
				'foreignRepoNames' => [],
			],
			'prefix' => [
				'expected' => [ 'bool' => [
					'should' => [
						[ 'prefix' => [
							'statement_keywords' => [
								'value' => 'P999=Q888[P111=',
								'rewrite' => 'top_terms_1024',
							],
						] ]
					]
				] ],
				'search string' => 'haswbstatement:P999=Q888[P111=*',
				'foreignRepoNames' => [],
			],
		];
	}

	/**
	 * @dataProvider applyProvider
	 */
	public function testApply( array $expected = null, $term, $foreignRepoNames ) {
		$feature = new HasWbStatementFeature( $foreignRepoNames );
		$kwAssertions = $this->getKWAssertions();
		$expectedWarnings = $expected === null ? [ [ 'wikibase-haswbstatement-feature-no-valid-statements', 'haswbstatement' ] ] : [];
		$kwAssertions->assertFilter( $feature, $term, $expected, $expectedWarnings );
		$kwAssertions->assertCrossSearchStrategy( $feature, $term, CrossSearchStrategy::hostWikiOnlyStrategy() );
		if ( $expected === null ) {
			$kwAssertions->assertNoResultsPossible( $feature, $term );
		}
	}

	public function applyNoDataProvider() {
		return [
			'empty data' => [
				'haswbstatement:',
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
		$feature = new HasWbStatementFeature( [ 'P999' ] );
		$this->getKWAssertions()->assertNotConsumed( $feature, $term );
	}

	public function testInvalidStatementWarning() {
		$feature = new HasWbStatementFeature( [ 'P999' ] );
		$expectedWarnings = [ [ 'wikibase-haswbstatement-feature-no-valid-statements', 'haswbstatement' ] ];
		$kwAssertions = $this->getKWAssertions();
		$kwAssertions->assertParsedValue( $feature, 'haswbstatement:INVALID', [], $expectedWarnings );
		$kwAssertions->assertExpandedData( $feature, 'haswbstatement:INVALID', [], [] );
		$kwAssertions->assertFilter( $feature, 'haswbstatement:INVALID', null, $expectedWarnings );
		$kwAssertions->assertNoResultsPossible( $feature, 'haswbstatement:INVALID' );
	}

	/**
	 * @dataProvider parseProvider
	 */
	public function testParseValue( $foreignRepoNames, $value, $expected, $warningExpected ) {
		$feature = new HasWbStatementFeature( $foreignRepoNames );
		$expectedWarnings = $warningExpected ? [ [ 'wikibase-haswbstatement-feature-no-valid-statements', 'haswbstatement' ] ] : [];
		$kwAssertions = $this->getKWAssertions();
		$kwAssertions->assertParsedValue( $feature, "haswbstatement:\"$value\"", $expected, $expectedWarnings );
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
				'expected' => [
					[
						'class' => Match::class,
						'field' => StatementsField::NAME,
						'string' => 'P999=Q888'
					]
				],
				'warningExpected' => false,
			],
			'single value other id' => [
				'foreignRepoNames' => [],
				'value' => 'P999=AB123',
				'expected' => [
					[
						'class' => Match::class,
						'field' => StatementsField::NAME,
						'string' => 'P999=AB123'
					]
				],
				'warningExpected' => false,
			],
			'single value federated' => [
				'foreignRepoNames' => [ 'Wikidata' ],
				'value' => 'Wikidata:P999=Wikidata:Q888',
				'expected' => [
					[
						'class' => Match::class,
						'field' => StatementsField::NAME,
						'string' => 'Wikidata:P999=Wikidata:Q888'
					]
				],
				'warningExpected' => false,
			],
			'multiple values' => [
				'foreignRepoNames' => [ 'Wikidata', 'Wikisource' ],
				'value' => 'Wikidata:P999=Wikidata:Q888|Wikisource:P777=12345',
				'expected' => [
					[
						'class' => Match::class,
						'field' => StatementsField::NAME,
						'string' => 'Wikidata:P999=Wikidata:Q888'
					],
					[
						'class' => Match::class,
						'field' => StatementsField::NAME,
						'string' => 'Wikisource:P777=12345'
					],
				],
				'warningExpected' => false,
			],
			'multiple values, not all valid' => [
				'foreignRepoNames' => [ 'Wikidata' ],
				'value' => 'Wikidata:P999=Wikidata:Q888|Wikisource:P777=12345',
				'expected' => [
					[
						'class' => Match::class,
						'field' => StatementsField::NAME,
						'string' => 'Wikidata:P999=Wikidata:Q888'
					],
				],
				'warningExpected' => false,
			],
			'property-only' => [
				'foreignRepoNames' => [],
				'value' => 'P999',
				'expected' => [
					[
						'class' => Match::class,
						'field' => StatementsField::NAME . '.property',
						'string' => 'P999'
					],
				],
				'warningExpected' => false,
			],
			'federated property-only' => [
				'foreignRepoNames' => [ 'Wikidata' ],
				'value' => 'Wikidata:P999',
				'expected' => [
					[
						'class' => Match::class,
						'field' => StatementsField::NAME . '.property',
						'string' => 'Wikidata:P999'
					],
				],
				'warningExpected' => false,
			],
			'invalid property-only' => [
				'foreignRepoNames' => [],
				'value' => 'P123,abc',
				'expected' => [],
				'warningExpected' => true,
			],
			'invalid and valid property-only' => [
				'foreignRepoNames' => [],
				'value' => 'P123,abc|P345',
				'expected' => [
					[
						'class' => Match::class,
						'field' => StatementsField::NAME . '.property',
						'string' => 'P345'
					],
				],
				'warningExpected' => false,
			],
			'prefix search' => [
				'foreignRepoNames' => [],
				'value' => 'P999=P888[P111*',
				'expected' => [
					[
						'class' => Prefix::class,
						'field' => StatementsField::NAME,
						'string' => 'P999=P888[P111'
					],
				],
				'warningExpected' => false,
			],
			'normal, property-only and prefix search simultaneously' => [
				'foreignRepoNames' => [],
				'value' => 'P111=Q222|P333|P444=Q555[P666*',
				'expected' => [
					[
						'class' => Match::class,
						'field' => StatementsField::NAME,
						'string' => 'P111=Q222'
					],
					[
						'class' => Match::class,
						'field' => StatementsField::NAME . '.property',
						'string' => 'P333'
					],
					[
						'class' => Prefix::class,
						'field' => StatementsField::NAME,
						'string' => 'P444=Q555[P666'
					],
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

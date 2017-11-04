<?php

namespace Wikibase\Repo\Search\Elastic\Tests;

use CirrusSearch;
use CirrusSearch\Search\SearchContext;
use Elastica\Result;
use Elastica\ResultSet;
use MediaWikiTestCase;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\LanguageFallbackChain;
use Wikibase\Repo\Search\Elastic\ElasticTermResult;

/**
 * @covers \Wikibase\Repo\Search\Elastic\ElasticTermResult
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Stas Malyshev
 */
class ElasticTermResultTest extends MediaWikiTestCase {

	public function setUp() {
		parent::setUp();

		if ( !class_exists( CirrusSearch::class ) ) {
			$this->markTestSkipped( 'CirrusSearch needed.' );
		}
	}

	public function termResultsProvider() {
		return [
			'simple' => [
				[ 'en' ],
				[ 'en' ],
				[
					'_source' => [
						'title' => 'Q1',
						'labels' => [ 'en' => [ 'Test 1', 'Test 1 alias' ] ],
						'descriptions' => [ 'en' => 'Describe it' ],
					],
					'highlight' => [ 'labels.en.prefix' => [ 'Test 1' ] ]
				],
				[
					'id' => 'Q1',
					'label' => [ 'en', 'Test 1' ],
					'description' => [ 'en', 'Describe it' ],
					'matched' => [ 'en', 'Test 1' ],
					'matchedType' => 'label'
				]
			],
			'alias' => [
				[ 'en' ],
				[ 'en' ],
				[
					'_source' => [
						'title' => 'Q2',
						'labels' => [ 'en' => [ 'Test 1', 'Alias', 'Another' ] ],
						'descriptions' => [ 'en' => 'Describe it' ],
					],
					'highlight' => [ 'labels.en.prefix' => [ 'Another' ] ]
				],
				[
					'id' => 'Q2',
					'label' => [ 'en', 'Test 1' ],
					'description' => [ 'en', 'Describe it' ],
					'matched' => [ 'en', 'Another' ],
					'matchedType' => 'alias'
				]
			],
			'byid' => [
				[ 'en' ],
				[ 'en' ],
				[
					'_source' => [
						'title' => 'Q10',
						'labels' => [ 'en' => [ 'Test 1', 'Alias', 'Another' ] ],
						'descriptions' => [ 'en' => 'Describe it' ],
					],
					'highlight' => [ 'title' => [ 'Q10' ] ]
				],
				[
					'id' => 'Q10',
					'label' => [ 'en', 'Test 1' ],
					'description' => [ 'en', 'Describe it' ],
					'matched' => [ 'qid', 'Q10' ],
					'matchedType' => 'entityId'
				]
			],
			'label with fallback' => [
				[ 'en', 'ru' ],
				[ 'en', 'ru' ],
				[
					'_source' => [
						'title' => 'Q3',
						'labels' => [ 'en' => [ 'Test 1', 'Test 1 alias' ],
									  'ru' => [ 'Тест 1', 'Тили тили тест' ]
									],
						'descriptions' => [ 'en' => 'Describe it' ],
					],
					'highlight' => [ 'labels.ru.prefix' => [ 'Тест 1' ] ]
				],
				[
					'id' => 'Q3',
					'label' => [ 'en', 'Test 1' ],
					'description' => [ 'en', 'Describe it' ],
					'matched' => [ 'ru', 'Тест 1' ],
					'matchedType' => 'label'
				]
			],
			'fallback label' => [
				[ 'en', 'ru' ],
				[ 'en', 'ru' ],
				[
					'_source' => [
						'title' => 'Q3',
						'labels' => [ 'ru' => [ 'Тест 1', 'Тили тили тест' ] ],
						'descriptions' => [ 'en' => 'Describe it' ],
					],
					'highlight' => [ 'labels.en.prefix' => [ 'Test 1' ] ]
				],
				[
					'id' => 'Q3',
					'label' => [ 'ru', 'Тест 1' ],
					'description' => [ 'en', 'Describe it' ],
					'matched' => [ 'en', 'Test 1' ],
					'matchedType' => 'label'
				],
			],
			'fallback description' => [
				[ 'en', 'ru' ],
				[ 'en', 'ru' ],
				[
					'_source' => [
						'title' => 'Q3',
						'labels' => [ 'ru' => [ 'Тест 1', 'Тили тили тест' ] ],
						'descriptions' => [ 'ru' => 'Описание' ],
					],
					'highlight' => [ 'labels.en.prefix' => [ 'Test 1' ] ]
				],
				[
					'id' => 'Q3',
					'label' => [ 'ru', 'Тест 1' ],
					'description' => [ 'ru', 'Описание' ],
					'matched' => [ 'en', 'Test 1' ],
					'matchedType' => 'label'
				],
			],
			'fallback alias' => [
				[ 'de-ch', 'de', 'en' ],
				[ 'de-ch', 'de', 'en' ],
				[
					'_source' => [
						'title' => 'Q6',
						'labels' => [
							'de' => [ 'Der Test 1', 'Test 2' ],
							'de-ch' => [ '', 'Test 2' ]
						],
						'descriptions' => [ 'en' => 'Describe it' ],
					],
					'highlight' => [ 'labels.de-ch.prefix' => [ 'Test 2' ] ]
				],
				[
					'id' => 'Q6',
					'label' => [ 'de', 'Der Test 1' ],
					'description' => [ 'en', 'Describe it' ],
					'matched' => [ 'de-ch', 'Test 2' ],
					'matchedType' => 'alias'
				],
			],
			'fallback alias, ignore language' => [
				[ 'de-ch', 'de', 'en' ],
				[ 'de-ch', 'en' ],
				[
					'_source' => [
						'title' => 'Q8',
						'labels' => [
							'de' => [ 'Der Test 1', 'Test 4' ],
							'en' => [ 'Test 1', 'Test 3' ],
							'de-ch' => [ '', 'Test 2' ]
						],
						'descriptions' => [ 'en' => 'Describe it' ],
					],
					'highlight' => [ 'labels.de-ch.prefix' => [ 'Test 2' ] ]
				],
				[
					'id' => 'Q8',
					'label' => [ 'en', 'Test 1' ],
					'description' => [ 'en', 'Describe it' ],
					'matched' => [ 'de-ch', 'Test 2' ],
					'matchedType' => 'alias'
				],
			],
			'fallback alias, no label' => [
				[ 'de-ch', 'de', 'en' ],
				[ 'de-ch', 'de', 'en' ],
				[
					'_source' => [
						'title' => 'Q34',
						'labels' => [
							'de' => [ '', 'Test 2' ],
							'de-ch' => [ '', 'Test 2' ]
						],
					],
					'highlight' => [ 'labels.de-ch.prefix' => [ 'Test 2' ] ]
				],
				[
					'id' => 'Q34',
					'label' => [ 'de-ch', 'Test 2' ],
					'matched' => [ 'de-ch', 'Test 2' ],
					'matchedType' => 'alias'
				],
			],
			'other language, label' => [
				[ 'en' ],
				[ 'en' ],
				[
					'_source' => [
						'title' => 'Q56',
						'labels' => [
							'en' => [ 'Name', 'And alias' ],
						],
					],
					'highlight' => [ 'labels.de.prefix' => [ 'Something else' ] ]
				],
				[
					'id' => 'Q56',
					'label' => [ 'en', 'Name' ],
					'matched' => [ 'de', 'Something else' ],
					'matchedType' => 'label'
				],
			],
			'simple, new highlighter' => [
				[ 'en' ],
				[ 'en' ],
				[
					'_source' => [
						'title' => 'Q71',
						'labels' => [ 'en' => [ 'Test 1', 'Test 1 alias' ] ],
						'descriptions' => [ 'en' => 'Describe it' ],
					],
					'highlight' => [ 'labels.en.prefix' => [ '0:0-5:5|Test 1' ] ]
				],
				[
					'id' => 'Q71',
					'label' => [ 'en', 'Test 1' ],
					'description' => [ 'en', 'Describe it' ],
					'matched' => [ 'en', 'Test 1' ],
					'matchedType' => 'label'
				]
			],
			'alias, new highlighter' => [
				[ 'en' ],
				[ 'en' ],
				[
					'_source' => [
						'title' => 'Q82',
						'labels' => [ 'en' => [ 'Test 1', 'Alias', 'Another' ] ],
						'descriptions' => [ 'en' => 'Describe it' ],
					],
					'highlight' => [ 'labels.en.prefix' => [ '10:10-15:15|Another' ] ]
				],
				[
					'id' => 'Q82',
					'label' => [ 'en', 'Test 1' ],
					'description' => [ 'en', 'Describe it' ],
					'matched' => [ 'en', 'Another' ],
					'matchedType' => 'alias'
				]
			],
			'other language, new hl' => [
				[ 'en' ],
				[ 'en' ],
				[
					'_source' => [
						'title' => 'Q96',
						'labels' => [
							'en' => [ 'Name', 'And alias' ],
						],
					],
					'highlight' => [ 'labels.de.prefix' => [ '0:0-7:7|Something else' ] ]
				],
				[
					'id' => 'Q96',
					'label' => [ 'en', 'Name' ],
					'matched' => [ 'de', 'Something else' ],
					'matchedType' => 'label'
				],
			],
			'other language, alias, new hl' => [
				[ 'en' ],
				[ 'en' ],
				[
					'_source' => [
						'title' => 'Q106',
						'labels' => [
							'en' => [ 'Name', 'And alias' ],
						],
					],
					'highlight' => [ 'labels.de.prefix' => [ '1:1-8:8|Something else' ] ]
				],
				[
					'id' => 'Q106',
					'label' => [ 'en', 'Name' ],
					'matched' => [ 'de', 'Something else' ],
					'matchedType' => 'alias'
				],
			],
			'alias, new highlighter, extended' => [
				[ 'en' ],
				[ 'en' ],
				[
					'_source' => [
						'title' => 'Q117',
						'labels' => [ 'en' => [ 'Test 1', 'Alias', 'Another' ] ],
						'descriptions' => [ 'en' => 'Describe it' ],
					],
					'highlight' => [ 'labels.en.prefix' => [ '10:10-15,20-30,40-45:15|Another' ] ]
				],
				[
					'id' => 'Q117',
					'label' => [ 'en', 'Test 1' ],
					'description' => [ 'en', 'Describe it' ],
					'matched' => [ 'en', 'Another' ],
					'matchedType' => 'alias'
				]
			],

		];
	}

	private function getMockFallbackChain( array $languages ) {
		$mock = $this->getMockBuilder( LanguageFallbackChain::class )
				->disableOriginalConstructor()
				->getMock();
		$mock->expects( $this->any() )
			->method( 'getFetchLanguageCodes' )
			->will( $this->returnValue( $languages ) );
		$mock->expects( $this->atLeastOnce() )
			->method( 'extractPreferredValueOrAny' )
			->will( $this->returnCallback( function ( $sourceData ) use ( $languages ) {
				foreach ( $languages as $language ) {
					if ( isset( $sourceData[$language] ) ) {
						return [ 'language' => $language, 'value' => $sourceData[$language] ];
					}
				}
				return null;
			} ) );
		return $mock;
	}

	/**
	 * @dataProvider termResultsProvider
	 */
	public function testTransformResult( array $languages, array $displayLanguages, array $resultData, array $expected ) {
		$res = new ElasticTermResult(
			new BasicEntityIdParser(),
			$languages,
			$this->getMockFallbackChain( $displayLanguages )
		);

		$context = $this->getMockBuilder( SearchContext::class )->disableOriginalConstructor()->getMock();

		$result = new Result( $resultData );
		$resultSet = $this->getMockBuilder( ResultSet::class )
			->disableOriginalConstructor()->getMock();
		$resultSet->expects( $this->once() )->method( 'getResults' )->willReturn( [ $result ] );

		$converted = $res->transformElasticsearchResult( $context, $resultSet );
		$this->assertCount( 1, $converted );
		$this->assertArrayHasKey( $expected['id'], $converted );
		$converted = $converted[$expected['id']];

		$this->assertEquals( $expected['id'], $converted->getEntityId()->getSerialization(), 'ID is wrong' );

		$this->assertEquals( $expected['label'][0],
			$converted->getDisplayLabel()->getLanguageCode(), 'Label language is wrong' );
		$this->assertEquals( $expected['label'][1], $converted->getDisplayLabel()->getText(), 'Label text is wrong' );

		$this->assertEquals( $expected['matched'][0],
			$converted->getMatchedTerm()->getLanguageCode(), 'Matched language is wrong' );
		$this->assertEquals( $expected['matched'][1], $converted->getMatchedTerm()->getText(), 'Matched text is wrong' );

		$this->assertEquals( $expected['matchedType'], $converted->getMatchedTermType(), 'Match type is wrong' );

		if ( !empty( $expected['description'] ) ) {
			$this->assertEquals( $expected['description'][0],
				$converted->getDisplayDescription()->getLanguageCode(),
				'Description language is wrong' );
			$this->assertEquals( $expected['description'][1],
				$converted->getDisplayDescription()->getText(), 'Description text is wrong' );
		} else {
			$this->assertNull( $converted->getDisplayDescription() );
		}
	}

}

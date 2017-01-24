<?php

namespace Wikibase\Repo\Search\Elastic\Tests;

use CirrusSearch\Search\SearchContext;
use Elastica\Result;
use Elastica\ResultSet;
use MediaWikiTestCase;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\LanguageFallbackChain;
use Wikibase\Repo\Search\Elastic\ElasticTermResult;

/**
 * @covers ElasticTermResult
 * @group Wikibase
 */
class ElasticTermResultTest extends MediaWikiTestCase {

	public function termResultsProvider() {
		return [
			'simple' => [
				[ 'en' ],
				[ 'en' ],
				[
					'_source' => [
						'title' => 'Q1',
						'labels' => [ 'en' => [ 'Test 1', 'Test 1 alias' ] ]
					],
					'highlight' => [ 'labels.en.prefix' => [ 'Test 1' ] ]
				],
				[
					'id' => 'Q1',
					'label' => [ 'en', 'Test 1' ],
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
						'labels' => [ 'en' => [ 'Test 1', 'Alias', 'Another' ] ]
					],
					'highlight' => [ 'labels.en.prefix' => [ 'Another' ] ]
				],
				[
					'id' => 'Q2',
					'label' => [ 'en', 'Test 1' ],
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
						'labels' => [ 'en' => [ 'Test 1', 'Alias', 'Another' ] ]
					],
					'highlight' => [ 'title' => [ 'Q10' ] ]
				],
				[
					'id' => 'Q10',
					'label' => [ 'en', 'Test 1' ],
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
					],
					'highlight' => [ 'labels.ru.prefix' => [ 'Тест 1' ] ]
				],
				[
					'id' => 'Q3',
					'label' => [ 'en', 'Test 1' ],
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
					],
					'highlight' => [ 'labels.en.prefix' => [ 'Test 1' ] ]
				],
				[
					'id' => 'Q3',
					'label' => [ 'ru', 'Тест 1' ],
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
					],
					'highlight' => [ 'labels.de-ch.prefix' => [ 'Test 2' ] ]
				],
				[
					'id' => 'Q6',
					'label' => [ 'de', 'Der Test 1' ],
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
					],
					'highlight' => [ 'labels.de-ch.prefix' => [ 'Test 2' ] ]
				],
				[
					'id' => 'Q8',
					'label' => [ 'en', 'Test 1' ],
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

		];
	}

	/**
	 * Get a lookup that always returns a pt label and description suffixed by the entity ID
	 *
	 * @return LabelDescriptionLookup
	 */
	private function getMockLabelDescriptionLookup() {
		$mock = $this->getMockBuilder( LabelDescriptionLookup::class )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $this->any() )
			->method( 'getDescription' )
			->will( $this->returnValue( new Term( 'en', 'DESCRIPTION' ) ) );
		return $mock;
	}

	private function getMockFallbackChain( $languages ) {
		$mock = $this->getMockBuilder( LanguageFallbackChain::class )
				->disableOriginalConstructor()
				->getMock();
		$mock->expects( $this->any() )
			->method( 'getFetchLanguageCodes' )
			->will( $this->returnValue( $languages ) );
		$mock->expects( $this->once() )
			->method( 'extractPreferredValueOrAny' )
			->will( $this->returnCallback( function ( $sourceData ) use ( $languages ) {
				foreach ( $languages as $language ) {
					if ( isset( $sourceData[$language] ) ) {
						return [ 'language' => $language, 'value' => $sourceData[$language] ];
					}
				}
				return null;
			} ) );
	}

	/**
	 * @dataProvider termResultsProvider
	 * @param $languages
	 * @param $displayLanguages
	 * @param $resultData
	 * @param $expected
	 */
	public function testTransformResult( $languages, $displayLanguages, $resultData, $expected ) {
		$res = new ElasticTermResult(
			new BasicEntityIdParser(),
			$this->getMockLabelDescriptionLookup(),
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

		// TODO: this will be fixed when descriptions are indexed too
		$this->assertEquals( 'DESCRIPTION', $converted->getDisplayDescription()->getText() );
		$this->assertEquals( 'en', $converted->getDisplayDescription()->getLanguageCode() );
	}

}

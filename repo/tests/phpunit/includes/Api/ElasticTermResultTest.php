<?php
namespace Wikibase\Repo\Tests\Api;

use CirrusSearch\Search\SearchContext;
use Elastica\Result;
use Elastica\ResultSet;
use MediaWikiTestCase;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\Repo\Api\ElasticTermResult;

/**
 * @covers ElasticTermResult
 * @group Wikibase
 */
class ElasticTermResultTest extends MediaWikiTestCase {

	public function termResultsProvider() {
		return [
			'simple' => [
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
					'matchedType' => 'item'
				]
			],
			'alias' => [
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
			]



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

	/**
	 * @dataProvider termResultsProvider
	 * @param $languages
	 * @param $resultData
	 * @param $expected
	 */
	public function testTransformResult( $languages, $resultData, $expected ) {
		$repo = \Wikibase\Repo\WikibaseRepo::getDefaultInstance();

		$res = new ElasticTermResult(
			$repo->getEntityIdParser(),
			$this->getMockLabelDescriptionLookup(),
			$languages
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

		$this->assertEquals( $expected['id'], $converted->getEntityId()->getSerialization() );

		$this->assertEquals( $expected['label'][0],
			$converted->getDisplayLabel()->getLanguageCode() );
		$this->assertEquals( $expected['label'][1], $converted->getDisplayLabel()->getText() );

		$this->assertEquals( $expected['matched'][0],
			$converted->getMatchedTerm()->getLanguageCode() );
		$this->assertEquals( $expected['matched'][1], $converted->getMatchedTerm()->getText() );

		$this->assertEquals( $expected['matchedType'], $converted->getMatchedTermType() );

		// TODO: this will be fixed when descriptions are indexed too
		$this->assertEquals( 'DESCRIPTION', $converted->getDisplayDescription()->getText() );
		$this->assertEquals( 'en', $converted->getDisplayDescription()->getLanguageCode() );
	}
}
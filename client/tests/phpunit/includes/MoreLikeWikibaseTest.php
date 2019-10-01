<?php

namespace Wikibase\Client\Tests;

use CirrusSearch;
use CirrusSearch\HashSearchConfig;
use CirrusSearch\Search\SearchContext;
use MediaWiki\MediaWikiServices;
use MediaWikiTestCase;
use Title;
use Wikibase\Client\MoreLikeWikibase;

/**
 * Test for morelikewithwikibase feature.
 *
 * Some copypaste from MoreLikeFeatureTest but with different data.
 * @covers \Wikibase\Client\MoreLikeWikibase
 * @group Wikibase
 * @group WikibaseClient
 */
class MoreLikeWikibaseTest extends MediaWikiTestCase {

	public static function setUpBeforeClass() {
		if ( !class_exists( CirrusSearch::class ) ||
			!class_exists( \Elastica\Query\BoolQuery::class ) ) {
			self::markTestSkipped( "CirrusSearch needs to be enabled to run this test" );
		}
	}

	public function applyProvider() {
		if ( !class_exists( CirrusSearch::class ) ||
			!class_exists( \Elastica\Query\BoolQuery::class ) ) {
			return [];
		}
		return [
			'single page morelike w/wikibase' => [
				'morelikewithwikibase:Some page',
				( new \Elastica\Query\BoolQuery() )
					->addFilter( new \Elastica\Query\Exists( 'wikibase_item' ) )
					->addMust( ( new \Elastica\Query\MoreLikeThis() )
						->setParams( [
							'min_doc_freq' => 2,
							'max_doc_freq' => null,
							'max_query_terms' => 25,
							'min_term_freq' => 2,
							'min_word_length' => 0,
							'max_word_length' => 0,
							'minimum_should_match' => '30%',
						] )
						->setFields( [ 'text' ] )
						->setLike( [
							[ '_id' => '12345' ],
						] )
					),
				true,
			]
		];
	}

	/**
	 * @dataProvider applyProvider
	 */
	public function testApply( $term, $expectedQuery, $mltUsed ) {
		// Inject fake pages for MoreLikeFeature::collectTitles() to find
		$linkCache = MediaWikiServices::getInstance()->getLinkCache();
		$linkCache->addGoodLinkObj( 12345, Title::newFromText( 'Some page' ) );
		$linkCache->addGoodLinkObj( 23456, Title::newFromText( 'Other page' ) );

		// @todo Use a HashConfig with explicit values?
		$config = new HashSearchConfig( [ 'CirrusSearchMoreLikeThisTTL' => 600 ], [ 'inherit' ] );

		$context = new SearchContext( $config );

		// Finally run the test
		$feature = new MoreLikeWikibase( $config );

		$result = $feature->apply( $context, $term );

		$this->assertEquals( $mltUsed, $context->isSyntaxUsed( 'more_like' ) );
		if ( $mltUsed ) {
			$this->assertGreaterThan( 0, $context->getCacheTtl() );
		} else {
			$this->assertSame( 0, $context->getCacheTtl() );
		}
		if ( $expectedQuery === null ) {
			$this->assertFalse( $context->areResultsPossible() );
		} else {
			$this->assertEquals( $expectedQuery, $context->getQuery() );
			if ( $expectedQuery instanceof \Elastica\Query\MatchAll ) {
				$this->assertEquals( $term, $result, 'Term must be unchanged' );
			} else {
				$this->assertSame( '', $result, 'Term must be empty string' );
			}
		}
	}

}

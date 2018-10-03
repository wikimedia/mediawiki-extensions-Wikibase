<?php
namespace Wikibase\Client\Tests;

use CirrusSearch\Query\MoreLikeFeatureTest;

/**
 * Test for morelikewithwikibase feature.
 *
 * Reuses MoreLikeFeatureTest but with different data.
 * @covers \Wikibase\Client\MoreLikeWikibase
 */
class MoreLikeWikibaseTest extends MoreLikeFeatureTest {
	public function applyProvider() {
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

}
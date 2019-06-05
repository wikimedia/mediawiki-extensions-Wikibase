<?php

namespace Wikibase\Client;

use CirrusSearch\Query\MoreLikeFeature;
use CirrusSearch\Search\SearchContext;

/**
 * Wikibase extension for MoreLike feature
 */
class MoreLikeWikibase extends MoreLikeFeature {
	const MORE_LIKE_THIS_JUST_WIKIBASE = 'morelikewithwikibase';

	protected function getKeywords() {
		return [ self::MORE_LIKE_THIS_JUST_WIKIBASE ];
	}

	protected function doApply( SearchContext $context, $key, $value, $quotedValue, $negated ) {
		parent::doApply( $context, $key, $value, $quotedValue, $negated );
		$wbFilter = new \Elastica\Query\Exists( 'wikibase_item' );
		return [ $wbFilter, false ];
	}

}

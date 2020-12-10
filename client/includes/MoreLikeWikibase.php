<?php

namespace Wikibase\Client;

use CirrusSearch\Query\MoreLikeFeature;
use CirrusSearch\Search\SearchContext;
use Elastica\Query\Exists;

/**
 * Wikibase extension for MoreLike feature
 * @license GPL-2.0-or-later
 */
class MoreLikeWikibase extends MoreLikeFeature {
	private const MORE_LIKE_THIS_JUST_WIKIBASE = 'morelikewithwikibase';

	protected function getKeywords() {
		return [ self::MORE_LIKE_THIS_JUST_WIKIBASE ];
	}

	protected function doApply( SearchContext $context, $key, $value, $quotedValue, $negated ) {
		parent::doApply( $context, $key, $value, $quotedValue, $negated );
		$wbFilter = new Exists( 'wikibase_item' );
		return [ $wbFilter, false ];
	}

}

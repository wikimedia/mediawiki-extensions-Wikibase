<?php

namespace Wikibase\Repo\Search\Elastic\Query;

use CirrusSearch\Query\QueryHelper;
use CirrusSearch\Query\SimpleKeywordFeature;
use CirrusSearch\Search\SearchContext;

/**
 * @todo Move to WikibaseCirrusSearch extension
 * @see https://phabricator.wikimedia.org/T190022
 */
class HasWbStatementFeature extends SimpleKeywordFeature {
	/**
	 * @return string[]
	 */
	protected function getKeywords() {
		return [ 'haswbstatement' ];
	}

	private function isStatementValid( $statement ) {
		return preg_match( '/^([a-z][a-z0-9\-]*:)?P[1-9]\d{0,9}=/i', $statement );
	}

	/**
	 * @param SearchContext $context
	 * @param string $key The keyword
	 * @param string $value The value attached to the keyword with quotes stripped
	 * @param string $quotedValue The original value in the search string, including quotes if used
	 * @param bool $negated Is the search negated? Not used to generate the returned AbstractQuery,
	 *  that will be negated as necessary. Used for any other building/context necessary.
	 * @return array Two element array, first an AbstractQuery or null to apply to the
	 *  query. Second a boolean indicating if the quotedValue should be kept in the search
	 *  string.
	 */
	protected function doApply( SearchContext $context, $key, $value, $quotedValue, $negated ) {
		$statements = explode( '|', $value );
		$filter = $this->matchStatements( $statements );
		if ( $filter === null ) {
			$context->setResultsPossible( false );
			$context->addWarning(
				'cirrussearch-haswbstatement-feature-no-valid-statements',
				$key
			);
		}

		return [ $filter, false ];
	}

	/**
	 * Builds an or between many categories that the page could be in.
	 *
	 * @param string[] $statements statements to match
	 * @return \Elastica\Query\BoolQuery|null A null return value means all values are filtered
	 *  and an empty result set should be returned.
	 */
	private function matchStatements( array $statements ) {
		$validValuesFound = false;
		$filter = new \Elastica\Query\BoolQuery();
		foreach ( $statements as $statement ) {
			if ( $this->isStatementValid( $statement ) ) {
				$filter->addMust( QueryHelper::matchPage( 'statement_keywords', $statement ) );
				$validValuesFound = true;
			}
		}

		if ( $validValuesFound ) {
			return $filter;
		}
		return null;
	}
}

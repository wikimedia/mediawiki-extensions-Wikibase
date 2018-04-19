<?php

namespace Wikibase\Repo\Search\Elastic\Query;

use CirrusSearch\Query\SimpleKeywordFeature;
use CirrusSearch\Search\SearchContext;
use CirrusSearch\WarningCollector;
use Elastica\Query\BoolQuery;
use Elastica\Query\Match;

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
		$parsedValue = $this->parseValue(
			$key,
			$value,
			$quotedValue,
			'|',
			'',
			$context
		);
		if ( count( $parsedValue[ 'statements' ] ) == 0 ) {
			$context->setResultsPossible( false );
			return [ null, false ];
		}

		return [ $this->matchStatements( $parsedValue[ 'statements' ] ), false ];
	}

	/**
	 * Builds an OR between many statements about the wikibase item
	 *
	 * @param string[] $statements statements to match
	 * @return \Elastica\Query\BoolQuery
	 */
	private function matchStatements( array $statements ) {
		$query = new BoolQuery();
		foreach ( $statements as $statement ) {
			$query->addShould( new Match( 'statement_keywords', [ 'query' => $statement ] ) );
		}
		return $query;
	}

	/**
	 * @param string $key
	 * @param string $value
	 * @param string $quotedValue
	 * @param string $valueDelimiter
	 * @param string $suffix
	 * @param WarningCollector $warningCollector
	 * @return array|false|null
	 */
	public function parseValue(
		$key,
		$value,
		$quotedValue,
		$valueDelimiter,
		$suffix,
		WarningCollector $warningCollector
	) {
		$validStatements = [];
		$statementStrings = explode( $valueDelimiter, $value );
		foreach ( $statementStrings as $statementString ) {
			if ( $this->isStatementStringValid( $statementString ) ) {
				$validStatements[] = $statementString;
			}
		}
		if ( count( $validStatements ) == 0 ) {
			$warningCollector->addWarning(
				'cirrussearch-haswbstatement-feature-no-valid-statements',
				$key
			);
		}
		return [ 'statements' => $validStatements ];
	}

	private function isStatementStringValid( $statementString ) {
		return preg_match( '/^([a-z][a-z0-9\-]*:)?P[1-9]\d{0,9}=/i', $statementString );
	}

}

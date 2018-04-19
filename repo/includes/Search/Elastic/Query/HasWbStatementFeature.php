<?php

namespace Wikibase\Repo\Search\Elastic\Query;

use CirrusSearch\Query\SimpleKeywordFeature;
use CirrusSearch\Search\SearchContext;
use CirrusSearch\WarningCollector;
use Elastica\Query\BoolQuery;
use Elastica\Query\Match;

/**
 * Handles the search keyword 'haswbstatement:'
 *
 * Allows the user to search for pages/items that have wikibase statements associated with them.
 *
 * If a file page has the statement 'wikidata:P180=wikidata:Q527' (meaning 'depicts sky') associated
 * with it then it can be found by including 'haswbstatement:wikidata:P180=wikidata:Q527' in the
 * search query.
 *
 * If a file page has the statement 'wikidata:P2014=79802' (meaning 'MoMA artwork id 79802')
 * associated with it then it can be found by including 'haswbstatement:wikidata:P2014=79802' in the
 * search query.
 *
 * Statements can be combined using logical OR by separating them with a | character in a single
 * haswbstatement query e.g. 'haswbstatement:P999=Q888|P999=Q777'
 *
 * Statements can be combined using logical AND by using two separate haswbstatement queries e.g.
 * 'haswbstatement:P999=Q888 haswbstatement:P999=Q777'
 *
 * @uses CirrusSearch
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
			'',
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
		$statementStrings = explode( '|', $value );
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

	/**
	 * Check that a statement string is valid. Valid strings must contain a P-id followed by an
	 * equals symbol, and possibly suffixed by a foreign repo name.
	 *
	 * Example valid strings:
	 * P999=Q888
	 * Wikidata:P180=Wikidata:Q537
	 * wikidata:P2014=79802
	 *
	 * Example invalid strings:
	 * P1F=Q888
	 * Wikidata:PA=Wikidata:Q537
	 * 1234567
	 *
	 * @param $statementString
	 * @return bool
	 */
	private function isStatementStringValid( $statementString ) {
		return (bool)preg_match( '/^([a-z][a-z0-9\-]*:)?P[1-9]\d{0,9}=/i', $statementString );
	}

}

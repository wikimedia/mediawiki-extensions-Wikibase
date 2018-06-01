<?php

namespace Wikibase\Repo\Search\Elastic\Query;

use CirrusSearch\Extra\Query\TermFreq;
use CirrusSearch\Parser\AST\KeywordFeatureNode;
use CirrusSearch\Query\Builder\QueryBuildingContext;
use CirrusSearch\Query\FilterQueryFeature;
use CirrusSearch\Query\SimpleKeywordFeature;
use CirrusSearch\Search\Filters;
use CirrusSearch\Search\SearchContext;
use CirrusSearch\WarningCollector;
use Elastica\Query\AbstractQuery;
use Wikibase\Repo\Search\Elastic\Fields\StatementQuantityField;
use Wikibase\Repo\Search\Elastic\Fields\StatementsField;

/**
 * Handles the search keyword 'wbstatementquantity:'
 *
 * Allows the user to search for pages/items that have wikibase statements associated with them, and
 * specify quantities of those statements
 *
 * If a file page has the statement 'P180=Q5' with the qualifier 'P1114=5' (meaning 'depicts human,
 * quantity 5' in wikidata) associated then it can be found using any of the following search
 * queries:
 *
 * 	- wbstatementquantity:P180=Q5<6
 * 	- wbstatementquantity:P180=Q5<=5
 * 	- wbstatementquantity:P180=Q5>=5
 * 	- wbstatementquantity:P180=Q5>4
 * 	- wbstatementquantity:P180=Q5=5
 *
 * Statements can be combined using logical OR by separating them using a pipe
 * e.g. wbstatementquantity:P999=Q888>5|P999=Q888<8
 *
 * Statements can be combined using logical AND by using two separate wbstatementquantity queries
 * e.g. wbstatementquantity:P999=Q888>5 wbstatementquantity:P999=Q888<8 (a range search)
 * e.g. wbstatementquantity:P999=Q888>5 wbstatementquantity:P999=Q777<8
 *
 * Note that NOT ALL STATEMENTS ARE INDEXED. Searching for a statement about a property that has
 * not been indexed will give an empty result set.
 *
 * @uses CirrusSearch
 * @todo Move to WikibaseCirrusSearch extension
 * @see https://phabricator.wikimedia.org/T190022
 */
class WbStatementQuantityFeature extends SimpleKeywordFeature implements FilterQueryFeature {

	/**
	 * @var string[] Names of foreign repos from $wgWBRepoSettings
	 */
	private $foreignRepoNames;

	/**
	 * @param string[] $foreignRepoNames Array of names of foreign repos from $wgWBRepoSettings
	 */
	public function __construct( array $foreignRepoNames ) {
		$this->foreignRepoNames = $foreignRepoNames;
	}

	/**
	 * @return string[]
	 */
	protected function getKeywords() {
		return [ 'wbstatementquantity' ];
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
		$params = $this->parseValue(
			$key,
			$value,
			$quotedValue,
			'',
			'',
			$context
		);
		if ( count( $params['statements'] ) == 0 ) {
			$context->setResultsPossible( false );
			return [ null, false ];
		}

		return [ $this->createFilters( $params ), false ];
	}

	private function createFilters( array $params ) {
		$filters = [];
		foreach ( $params[ 'statements' ] as $index => $statement ) {
			$filters[] = new TermFreq(
				StatementQuantityField::NAME,
				$statement,
				$params['operators'][$index],
				$params['numbers'][$index]
			);
		}
		return Filters::booleanOr( $filters );
	}

	/**
	 * @param string $key
	 * @param string $value
	 * @param string $quotedValue
	 * @param string $valueDelimiter
	 * @param string $suffix
	 * @param WarningCollector $warningCollector
	 * @return array
	 */
	public function parseValue(
		$key,
		$value,
		$quotedValue,
		$valueDelimiter,
		$suffix,
		WarningCollector $warningCollector
	) {
		$statements = $operators = $numbers = [];
		$queryStrings = $this->getQueryStrings( $value );
		$pattern = $this->getPattern();
		foreach ( $queryStrings as $queryString ) {
			$queryParts = [];
			if ( preg_match( $pattern, $queryString, $queryParts ) ) {
				$statements[] = $queryParts['statement'];
				$operators[] = $queryParts['operator'];
				$numbers[] = (int)$queryParts['number'];
			}
		}

		if ( count( $statements ) == 0 ) {
			$warningCollector->addWarning(
				'cirrussearch-wbstatementquantity-feature-no-valid-statements',
				$key
			);
		}
		return [ 'statements' => $statements, 'operators' => $operators, 'numbers' => $numbers ];
	}

	private function getQueryStrings( $value ) {
		if ( count( $this->foreignRepoNames ) == 0 ) {
			return explode( '|', $value );
		}
		//Handle the case where foreign repo names contain the '|' character
		$hashedForeignRepoNames = [];
		foreach ( $this->foreignRepoNames as $foreignRepoName ) {
			$hash = md5( $foreignRepoName );
			$hashedForeignRepoNames[ $foreignRepoName ] = $hash;
		}
		$value = str_replace( $this->foreignRepoNames, $hashedForeignRepoNames, $value );
		$queryStrings = explode( '|', $value );
		foreach ( $queryStrings as $index => $queryString ) {
			$queryStrings[$index] = str_replace(
				$hashedForeignRepoNames,
				$this->foreignRepoNames,
				$queryString
			);
		}
		return $queryStrings;
	}

	/**
	 * Construct a regex pattern with which to parse the query string into a statement, an operator,
	 * and a number.
	 *
	 * @return string
	 */
	private function getPattern() {
		$statementPattern = '(?<statement>';
		if ( count( $this->foreignRepoNames ) > 0 ) {
			$statementPattern .= '((' .
				implode( '|', array_map(
					'preg_quote',
					$this->foreignRepoNames
				) )
				.'):)?';
		}
		$statementPattern .= 'P[1-9]\d{0,9}' .
			preg_quote( StatementsField::STATEMENT_SEPARATOR ) .
			'.+)';
		$operatorPattern = '(?<operator>>=?|<=?|=)';
		$numberPattern = '(?<number>\d+)';

		return '/^' . $statementPattern . $operatorPattern . $numberPattern . '$/U';
	}

	/**
	 * @param KeywordFeatureNode $node
	 * @param QueryBuildingContext $context
	 * @return AbstractQuery|null
	 */
	public function getFilterQuery( KeywordFeatureNode $node, QueryBuildingContext $context ) {
		$params = $node->getParsedValue();
		if ( count( $params['statements'] ) == 0 ) {
			return null;
		}
		return $this->createFilters( $params );
	}

}

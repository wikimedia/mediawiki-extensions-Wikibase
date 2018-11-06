<?php

namespace Wikibase\Repo\Search\Elastic\Query;

use CirrusSearch\Parser\AST\KeywordFeatureNode;
use CirrusSearch\Query\Builder\QueryBuildingContext;
use CirrusSearch\Query\FilterQueryFeature;
use CirrusSearch\Query\SimpleKeywordFeature;
use CirrusSearch\Search\SearchContext;
use CirrusSearch\WarningCollector;
use Elastica\Query\AbstractQuery;
use Elastica\Query\BoolQuery;
use Elastica\Query\Match;
use Elastica\Query\Prefix;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Repo\Search\Elastic\Fields\StatementsField;

/**
 * Handles the search keyword 'haswbstatement:'
 *
 * Allows the user to search for pages/items that have wikibase properties or statements associated
 * with them.
 *
 * If a file page has ANY statement about property 'wikidata:P180' ('depicts') then it can be found
 * by including 'haswbstatement:wikidata:P180' in the search query.
 *
 * If a file page has the statement 'wikidata:P180=wikidata:Q527' (meaning 'depicts sky') associated
 * with it then it can be found by including 'haswbstatement:wikidata:P180=wikidata:Q527' in the
 * search query.
 *
 * If a file page has the statement 'wikidata:P2014=79802' (meaning 'MoMA artwork id 79802')
 * associated with it then it can be found by including 'haswbstatement:wikidata:P2014=79802' in the
 * search query.
 *
 * A '*' at the end of a 'haswbstatement' string triggers a prefix search. If different file pages
 * have the statements:
 *	- wikidata:P180=wikidata:Q146[wikidata:P462=wikidata:Q23445] ('depicts cat, color black')
 * 	- wikidata:P180=wikidata:Q146[wikidata:P462=wikidata:Q23444] ('depicts cat, color white')
 * ... then both those pages will be found if 'wikidata:P180=wikidata:Q146[wikidata:P462=*' is
 * included in the search query.
 *
 *
 * Statements can be combined using logical OR by separating them with a | character in a single
 * haswbstatement query e.g. 'haswbstatement:P999=Q888|P999=Q777'
 *
 * Statements can be combined using logical AND by using two separate haswbstatement queries e.g.
 * 'haswbstatement:P999=Q888 haswbstatement:P999=Q777'
 *
 *
 * Note that NOT ALL STATEMENTS ARE INDEXED. Searching for a statement about a property that has
 * not been indexed will give an empty result set.
 *
 * @uses CirrusSearch
 * @todo Move to WikibaseCirrusSearch extension
 * @see https://phabricator.wikimedia.org/T190022
 */
class HasWbStatementFeature extends SimpleKeywordFeature implements FilterQueryFeature {

	/**
	 * @var array Names of foreign repos from $wgWBRepoSettings
	 */
	private $foreignRepoNames;

	/**
	 * HasWbStatementFeature constructor.
	 * @param array $foreignRepoNames Array of names of foreign repos from $wgWBRepoSettings
	 */
	public function __construct( array $foreignRepoNames ) {
		$this->foreignRepoNames = $foreignRepoNames;
	}

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
		$queries = $this->parseValue(
			$key,
			$value,
			$quotedValue,
			'',
			'',
			$context
		);
		if ( count( $queries ) == 0 ) {
			$context->setResultsPossible( false );
			return [ null, false ];
		}

		return [ $this->combineQueries( $queries ), false ];
	}

	/**
	 * Builds an OR between many statements about the wikibase item
	 *
	 * @param string[] $queries queries to combine
	 * @return \Elastica\Query\BoolQuery
	 */
	private function combineQueries( array $queries ) {
		$return = new BoolQuery();
		foreach ( $queries as $query ) {
			if ( $query['class'] == Prefix::class ) {
				$return->addShould( new Prefix( [
					$query['field'] =>
						[
							'value' => $query['string'],
							'rewrite' => 'top_terms_1024'
						]
				] ) );
			}
			if ( $query['class'] == Match::class ) {
				$return->addShould( new Match(
					$query['field'],
					[ 'query' => $query['string'] ]
				) );
			}
		}
		return $return;
	}

	/**
	 * @param string $key
	 * @param string $value
	 * @param string $quotedValue
	 * @param string $valueDelimiter
	 * @param string $suffix
	 * @param WarningCollector $warningCollector
	 * @return array [
	 * 		[
	 * 			'class' => \Elastica\Query class name to be used to construct the query,
	 * 			'field' => document field to run the query against,
	 * 			'string' => string to search for
	 * 		],
	 * 		...
	 * 	]
	 */
	public function parseValue(
		$key,
		$value,
		$quotedValue,
		$valueDelimiter,
		$suffix,
		WarningCollector $warningCollector
	) {
		$queries = [];
		$statementStrings = explode( '|', $value );
		foreach ( $statementStrings as $statementString ) {
			if ( !$this->isStatementStringValid( $statementString ) ) {
				continue;
			}
			if ( $this->statementContainsPropertyOnly( $statementString ) ) {
				$queries[] = [
					'class' => Match::class,
					'field' => StatementsField::NAME . '.property',
					'string' => $statementString,
				];
				continue;
			}
			if ( $this->statementEndsWithWildcard( $statementString ) ) {
				$queries[] = [
					'class' => Prefix::class,
					'field' => StatementsField::NAME,
					'string' => substr( $statementString, 0, strlen( $statementString ) - 1 ),
				];
				continue;
			}
			$queries[] = [
				'class' => Match::class,
				'field' => StatementsField::NAME,
				'string' => $statementString,
			];
		}
		if ( count( $queries ) == 0 ) {
			$warningCollector->addWarning(
				'wikibase-haswbstatement-feature-no-valid-statements',
				$key
			);
		}
		return $queries;
	}

	/**
	 * Check that a statement string is valid. A valid string is a P-id
	 * optionally suffixed with a foreign repo name followed by a colon
	 *
	 * The following strings are valid:
	 * Wikidata:P180=Wikidata:Q537 (assuming 'Wikidata' is a valid foreign repo name)
	 * P2014=79802
	 * P999
	 *
	 * The following strings are invalid:
	 * NON_EXISTENT_FOREIGN_REPO_NAME:P123=Wikidata:Q537
	 * PA=Q888
	 * PF=1234567
	 *
	 * @param string $statementString
	 * @return bool
	 */
	private function isStatementStringValid( $statementString ) {
		//Strip delimiters, anchors and pattern modifiers from PropertyId::PATTERN
		$propertyIdPattern = preg_replace(
			'/([^\sa-zA-Z0-9\\\])(\^|\\\A)?(.*?)(\$|\\\z|\\\Z)?\\1[a-zA-Z]*/',
			'$3',
			PropertyId::PATTERN
		);
		$validStatementStringPattern = '/^' .
			'((' . implode( '|', $this->foreignRepoNames ) .'):)?' .
			$propertyIdPattern .
			'(' . StatementsField::STATEMENT_SEPARATOR . '|$)' .
			'/i';

		return (bool)preg_match(
			$validStatementStringPattern,
			$statementString
		);
	}

	private function statementContainsPropertyOnly( $statementString ) {
		if ( strpos( $statementString, '=' ) === false ) {
			return true;
		}
		return false;
	}

	private function statementEndsWithWildcard( $statementString ) {
		if ( substr( $statementString, -1 ) == '*' ) {
			return true;
		}
		return false;
	}

	/**
	 * @param KeywordFeatureNode $node
	 * @param QueryBuildingContext $context
	 * @return AbstractQuery|null
	 */
	public function getFilterQuery( KeywordFeatureNode $node, QueryBuildingContext $context ) {
		$statements = $node->getParsedValue();
		if ( $statements === [] ) {
			return null;
		}
		return $this->combineQueries( $statements );
	}

}

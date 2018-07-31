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
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Repo\Search\Elastic\Fields\StatementsField;

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
			if ( strchr( $statement, '=' ) === false ) {
				$query->addShould( new Match( StatementsField::NAME . '.property',
					[ 'query' => $statement ] ) );
			} else {
				$query->addShould( new Match( StatementsField::NAME, [ 'query' => $statement ] ) );
			}
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
				'wikibase-haswbstatement-feature-no-valid-statements',
				$key
			);
		}
		return [ 'statements' => $validStatements ];
	}

	/**
	 * Check that a statement string is valid. A valid string is a P-id followed by an equals and
	 * optionally suffixed with a foreign repo name followed by a colon
	 *
	 * The following strings are valid:
	 * Wikidata:P180=Wikidata:Q537 (assuming 'Wikidata' is a valid foreign repo name)
	 * P2014=79802
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

	/**
	 * @param KeywordFeatureNode $node
	 * @param QueryBuildingContext $context
	 * @return AbstractQuery|null
	 */
	public function getFilterQuery( KeywordFeatureNode $node, QueryBuildingContext $context ) {
		$statements = $node->getParsedValue()['statements'];
		if ( $statements === [] ) {
			return null;
		}
		return $this->matchStatements( $statements );
	}

}

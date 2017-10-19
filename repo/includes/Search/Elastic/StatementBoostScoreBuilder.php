<?php

namespace Wikibase\Repo\Search\Elastic;

use CirrusSearch\Search\FunctionScoreBuilder;
use CirrusSearch\Search\SearchContext;
use CirrusSearch\Search\TermBoostScoreBuilder;
use Elastica\Query\FunctionScore;

/**
 * Boost function implementation for statement values.
 *
 * @license GPL-2.0+
 * @author Stas Malyshev
 */
class StatementBoostScoreBuilder extends FunctionScoreBuilder {

	/**
	 * @var TermBoostScoreBuilder
	 */
	private $termBuilder;

	/**
	 * @param SearchContext $context
	 * @param float $weight
	 * @param array $statementBoost
	 */
	public function __construct( SearchContext $context, $weight, array $statementBoost ) {
		parent::__construct( $context, $weight );

		$this->termBuilder = new TermBoostScoreBuilder(
			$context,
			$weight,
			[ 'statement_keywords' => $statementBoost ]
		);
	}

	/**
	 * Append functions to the function score $container
	 *
	 * @param FunctionScore $container
	 */
	public function append( FunctionScore $container ) {
		$this->termBuilder->append( $container );
	}

}

<?php
namespace Wikibase\Repo\Search\Elastic;

use CirrusSearch\Search\FunctionScoreBuilder;
use CirrusSearch\Search\SearchContext;
use CirrusSearch\Search\TermBoostScoreBuilder;
use Elastica\Query\FunctionScore;
use Wikibase\Repo\WikibaseRepo;

/**
 * Boost function implementation for statement values.
 * @package Wikibase\Repo\Search\Elastic
 */
class StatementBoostScoreBuilder extends FunctionScoreBuilder {
	/**
	 * @var TermBoostScoreBuilder
	 */
	private $termBuilder;

	/**
	 * @param SearchContext $context
	 * @param float $weight
	 * @param WikibaseRepo $repo
	 */
	public function __construct( SearchContext $context, $weight, WikibaseRepo $repo ) {
		parent::__construct( $context, $weight );
		$settings = $repo->getSettings()->getSetting( 'entitySearch' );
		$this->termBuilder = new TermBoostScoreBuilder( $context, $weight,
				[ 'statement_keywords' => $settings['statementBoost'] ] );
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

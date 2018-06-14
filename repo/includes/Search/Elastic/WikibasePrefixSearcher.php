<?php

namespace Wikibase\Repo\Search\Elastic;

use CirrusSearch\CirrusDebugOptions;
use CirrusSearch\Connection;
use CirrusSearch\Searcher;
use Elastica\Query;
use Elastica\Query\AbstractQuery;
use MediaWiki\MediaWikiServices;
use Status;

/**
 * Searcher class for performing Wikibase prefix search.
 * @see \CirrusSearch\Searcher
 *
 * @license GPL-2.0-or-later
 * @author Stas Malyshev
 */
class WikibasePrefixSearcher extends Searcher {
	/**
	 * @var AbstractQuery
	 */
	private $query;

	/**
	 * @param int $offset Search offset.
	 * @param int $limit Search limit.
	 * @param CirrusDebugOptions $options
	 */
	public function __construct( $offset, $limit, CirrusDebugOptions $options = null ) {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'CirrusSearch' );
		$connection = new Connection( $config );
		parent::__construct( $connection, $offset, $limit, $config, null, null, null, $options );
	}

	/**
	 * Build search query object.
	 * @return \Elastica\Search
	 */
	protected function buildSearch() {
		$this->searchContext->addSyntaxUsed( 'wikibase_prefix', PHP_INT_MAX );

		$indexType = $this->connection->pickIndexTypeForNamespaces( $this->getSearchContext()->getNamespaces() );
		$pageType = $this->connection->getPageType( $this->indexBaseName, $indexType );

		$queryOptions = [
			\Elastica\Search::OPTION_TIMEOUT => $this->config->getElement( 'CirrusSearchSearchShardTimeout',
				'default' ),
		];
		$searchQuery = new Query();
		$searchQuery->setQuery( $this->query );
		$resultsType = $this->searchContext->getResultsType();
		$searchQuery->setSource( $resultsType->getSourceFiltering() );
		$searchQuery->setStoredFields( $resultsType->getStoredFields() );

		$highlight = $this->searchContext->getHighlight( $resultsType );
		if ( $highlight ) {
			$searchQuery->setHighlight( $highlight );
		}
		if ( $this->offset ) {
			$searchQuery->setFrom( $this->offset );
		}
		if ( $this->limit ) {
			$searchQuery->setSize( $this->limit );
		}
		$searchQuery->setParam( 'rescore', $this->searchContext->getRescore() );
		// Mark wikibase prefix searches for statistics
		$searchQuery->addParam( 'stats', 'wikibase-prefix' );
		$this->applyDebugOptionsToQuery( $searchQuery );
		return $pageType->createSearch( $searchQuery, $queryOptions );
	}

	/**
	 * Set rescore profile for search.
	 * @param array|string $profile
	 */
	public function setRescoreProfile( $profile ) {
		$this->getSearchContext()->setRescoreProfile( $profile );
	}

	/**
	 * Perform prefix search for Wikibase entities.
	 * @param AbstractQuery $query Search query.
	 * @return Status
	 */
	public function performSearch( AbstractQuery $query ) {
		$this->query = $query;
		$status = $this->searchOne();

		// TODO: this probably needs to go to Searcher API.
		foreach ( $this->searchContext->getWarnings() as $warning ) {
			// $warning is a parameter array
			call_user_func_array( [ $status, 'warning' ], $warning );
		}

		return $status;
	}

	/**
	 * Add warning message about something in search.
	 * @param string $message i18n message key
	 */
	public function addWarning( $message /*, parameters... */ ) {
		call_user_func_array( [ $this->searchContext, 'addWarning' ], func_get_args() );
	}

}

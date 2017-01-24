<?php

namespace Wikibase\Repo\Search\Elastic;

use CirrusSearch\Connection;
use CirrusSearch\Search\RescoreBuilder;
use CirrusSearch\Searcher;
use Elastica\Query;
use Elastica\Query\AbstractQuery;
use MediaWiki\MediaWikiServices;
use Status;

/**
 * Searcher class for performing Wikibase prefix search.
 * @see \CirrusSearch\Searcher
 */
class WikibasePrefixSearcher extends Searcher {
	/**
	 * @var AbstractQuery
	 */
	private $query;

	/**
	 * @param int $offset Search offset.
	 * @param int $limit Search limit.
	 */
	public function __construct( $offset, $limit ) {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'CirrusSearch' );
		$connection = new Connection( $config );
		parent::__construct( $connection, $offset, $limit, $config );
	}

	/**
	 * Build search query object.
	 * @return \Elastica\Search
	 */
	protected function buildSearch() {
		$this->getSearchContext()->addSyntaxUsed( 'wikibase_prefix', PHP_INT_MAX );

		$indexType = $this->connection->pickIndexTypeForNamespaces( $this->getSearchContext()->getNamespaces() );
		$pageType = $this->connection->getPageType( $this->indexBaseName, $indexType );

		$queryOptions = [
			\Elastica\Search::OPTION_TIMEOUT => $this->config->getElement( 'CirrusSearchSearchShardTimeout',
				'default' ),
		];
		$searchQuery = new Query();
		$searchQuery->setQuery( $this->query );

		$searchQuery->setSource( $this->resultsType->getSourceFiltering() );
		$searchQuery->setStoredFields( $this->resultsType->getStoredFields() );

		$highlight = $this->searchContext->getHighlight( $this->resultsType );
		if ( $highlight ) {
			$searchQuery->setHighlight( $highlight );
		}
		$searchQuery->setParam( 'rescore', $this->searchContext->getRescore() );

		return $pageType->createSearch( $searchQuery, $queryOptions );
	}

	/**
	 * Set rescore profile for search.
	 * @param array|string $profile
	 */
	public function setRescoreProfile( $profile ) {
		$rescore = new RescoreBuilder( $this->getSearchContext(), $profile );
		$this->getSearchContext()->mergeRescore( $rescore->build() );
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
			call_user_func_array( [ $status, 'warning' ], $warning );
		}

		// FIXME: this is a hack, we need to return Status upstream instead
		foreach ( $status->getErrors() as $error ) {
			wfLogWarning( json_encode( $error ) );
		}

		if ( $status->isOK() ) {
			return $status->getValue();
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

<?php

namespace Wikibase\Repo\Search\Elastic;

use CirrusSearch\Connection;
use CirrusSearch\Searcher;
use Elastica\Query;
use Elastica\Query\AbstractQuery;
use MediaWiki\MediaWikiServices;

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
	 * @param AbstractQuery $query Search query.
	 * @param int $offset Search offset.
	 * @param int $limit Search limit.
	 */
	public function __construct( AbstractQuery $query, $offset, $limit ) {
		$config =
			MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'CirrusSearch' );
		$connection = new Connection( $config );
		$this->query = $query;
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

		$searchQuery->setParam( '_source', $this->resultsType->getSourceFiltering() );
		$searchQuery->setParam( 'fields', $this->resultsType->getFields() );

		$highlight = $this->searchContext->getHighlight( $this->resultsType );
		if ( $highlight ) {
			$searchQuery->setHighlight( $highlight );
		}
		$searchQuery->setParam( 'rescore', $this->searchContext->getRescore() );

		return $pageType->createSearch( $searchQuery, $queryOptions );
	}

	/**
	 * Perform prefix search for Wikibase entities.
	 * @return array
	 */
	public function performSearch() {
		$result = $this->searchOne();

		if ( $result->isOK() ) {
			return $result->getValue();
		}
		// TODO: can we do any error reporting upstream here?
		return [];
	}

}

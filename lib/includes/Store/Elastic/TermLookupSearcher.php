<?php
namespace Wikibase\Lib\Store;

use CirrusSearch\Connection;
use CirrusSearch\ElasticsearchIntermediary;
use CirrusSearch\RequestLog;
use CirrusSearch\SearchRequestLog;
use Psr\Log\InvalidArgumentException;
use Title;

/**
 * Implementation of ElasticSearch connector for looking up entities by title.
 */
class TermLookupSearcher extends ElasticsearchIntermediary {

	const MAX_TITLES_PER_QUERY = 512;
	const MAX_RESULT_SIZE = 10000;

	/**
	 * @var string
	 */
	private $indexBaseName;
	/**
	 * @var int
	 */
	private $clientTimeout;

	/**
	 * TermLookupSearcher constructor.
	 * @param Connection $conn
	 * @param string $indexBaseName Index base for fetching
	 * @param float $slowSeconds how many seconds a request through this
	 *  intermediary needs to take before it counts as slow.  0 means none count
	 *  as slow.
	 * @param int $clientTimeout Client-side timeout
	 */
	public function __construct( Connection $conn, $indexBaseName, $slowSeconds = 0.0, $clientTimeout = 0 ) {
		parent::__construct( $conn, null, $slowSeconds );
		$this->indexBaseName = $indexBaseName;
		$this->clientTimeout = $clientTimeout;
	}

	/**
	 * @param string $description A psr-3 compliant string describing the request
	 * @param string $queryType The type of search being performed such as
	 * fulltext, get, etc.
	 * @param string[] $extra A map of additional request-specific data
	 * @return RequestLog
	 */
	protected function newLog( $description, $queryType, array $extra = [] ) {
		return new SearchRequestLog(
			$this->connection->getClient(),
			$description,
			$queryType,
			$extra
		);
	}

	/**
	 * @param Title[] $titles List of titles to look for
	 * @param string[] $sourceFields Fields to fetch as source
	 * @return \Status Status containing \Elastica\Result[] of documents
	 */
	public function getByTitle( array $titles, $sourceFields ) {
		if ( count( $titles ) > self::MAX_RESULT_SIZE ) {
			return $this->failure( new InvalidArgumentException( "Too many titles, max is " .
				self::MAX_TITLES_PER_QUERY ) );
		}
		$namespaces = array_unique( array_map( function ( Title $t ) {
			return $t->getNamespace();
		}, $titles ) );
		$titleTexts = array_map( function ( Title $t ) {
			return $t->getText();
		}, $titles );
		$indexType = $this->connection->pickIndexTypeForNamespaces( $namespaces );

		// The worst case would be to have all ids duplicated in all available indices.
		// We set the limit accordingly
		$size = count( $this->connection->getAllIndexSuffixesForNamespaces( $namespaces ) );
		$size *= count( $titles );
		if ( $size > self::MAX_RESULT_SIZE ) {
			return $this->failure( new InvalidArgumentException( "Result too big: $size > 10000" ) );
		}

		try {
			$this->startNewLog( 'get of {indexType}.{docIds}', 'get', [
				'indexType' => $indexType,
				'docIds' => $titleTexts,
			] );
			$this->connection->setTimeout( $this->clientTimeout );
			$pageType = $this->connection->getPageType( $this->indexBaseName, $indexType );

			$mainQuery = new \Elastica\Query\BoolQuery();
			$mainQuery->addMust( new \Elastica\Query\Terms( 'title.keyword', $titleTexts ) );
			$mainQuery->addFilter( new \Elastica\Query\Terms( 'namespace', $namespaces ) );

			$query = new \Elastica\Query( $mainQuery );
			$query->setParam( '_source', $sourceFields );
			$query->addParam( 'stats', 'get' );
			$query->setFrom( 0 );
			$query->setSize( $size );
			$resultSet = $pageType->search( $query, [ 'search_type' => 'query_then_fetch' ] );
			return $this->success( $resultSet->getResults() );
		} catch ( \Elastica\Exception\NotFoundException $e ) {
			// NotFoundException just means the field didn't exist.
			// It is up to the caller to decide if that is an error.
			return $this->success( [] );
		} catch ( \Elastica\Exception\ExceptionInterface $e ) {
			return $this->failure( $e );
		}
	}

}

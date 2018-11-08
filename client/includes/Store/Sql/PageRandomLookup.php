<?php

namespace Wikibase\Client\Store\Sql;

use Wikimedia\Rdbms\ILoadBalancer;
use Psr\Log\LoggerInterface;

/**
 * Lookup of page.page_random based on page ID.
 *
 * @license GPL-2.0-or-later
 */
class PageRandomLookup {

	/**
	 * @var ILoadBalancer
	 */
	private $loadBalancer;

	/**
	 * @var LoggerInterface|null
	 */
	private $logger;

	/**
	 * @param ILoadBalancer $loadBalancer
	 * @param LoggerInterface|null $pageRandomLookupLogger
	*/
	public function __construct( ILoadBalancer $loadBalancer, LoggerInterface $logger = null ) {
		$this->loadBalancer = $loadBalancer;
		$this->logger = $logger;
	}

	/**
	 * @param int $pageId
	 *
	 * @return float|null
	 */
	public function getPageRandom( $pageId ) {
		$db = $this->loadBalancer->getConnection( DB_REPLICA );

		$pageRandom = $db->selectField( 'page', 'page_random', [ 'page_id' => $pageId ] );

		$this->loadBalancer->reuseConnection( $db );

		if ( $pageRandom === false || $pageRandom < 0 || $pageRandom > 1 ) {
			if ( $this->logger ) {
				$this->logger->warning( 'Invalid probability for page_random, {pageRandom}, on page ID {pageId}.', [
					'pageId' => $pageId,
					'pageRandom' => $pageRandom,
				] );
			}
			return null;
		}

		return (float)$pageRandom;
	}

}
